<?php
/**
 * مدير المدفوعات للحملات الإعلانية
 * يدير المدفوعات، الفواتير، والتسوية المالية مع المعلنين
 */

class PaymentManager {
    private $db;
    private $supported_gateways;
    private $default_currency = 'USD';
    
    public function __construct($database) {
        $this->db = $database;
        $this->supported_gateways = [
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'razorpay' => 'Razorpay',
            'paymob' => 'PayMob',
            'tap' => 'Tap Payments',
            'mada' => 'مدى',
            'bank_transfer' => 'تحويل بنكي'
        ];
    }
    
    /**
     * إنشاء فاتورة جديدة
     * @param int $advertiser_id معرف المعلن
     * @param float $amount المبلغ
     * @param string $description وصف الفاتورة
     * @param array $items عناصر الفاتورة
     * @return array نتيجة إنشاء الفاتورة
     */
    public function createInvoice($advertiser_id, $amount, $description, $items = []) {
        try {
            // إنشاء رقم فاتورة فريد
            $invoice_number = $this->generateInvoiceNumber();
            
            // حساب الضرائب
            $tax_rate = $this->getTaxRate($advertiser_id);
            $tax_amount = $amount * ($tax_rate / 100);
            $total_amount = $amount + $tax_amount;
            
            // إدراج الفاتورة في قاعدة البيانات
            $sql = "INSERT INTO fmc_invoices 
                    (invoice_number, advertiser_id, amount, tax_amount, total_amount, 
                     description, status, created_at, due_date) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $invoice_number,
                $advertiser_id,
                $amount,
                $tax_amount,
                $total_amount,
                $description
            ]);
            
            $invoice_id = $this->db->lastInsertId();
            
            // إضافة عناصر الفاتورة
            if (!empty($items)) {
                $this->addInvoiceItems($invoice_id, $items);
            }
            
            // إرسال إشعار للمعلن
            $this->sendInvoiceNotification($invoice_id);
            
            return [
                'success' => true,
                'invoice_id' => $invoice_id,
                'invoice_number' => $invoice_number,
                'total_amount' => $total_amount,
                'message' => 'تم إنشاء الفاتورة بنجاح'
            ];
            
        } catch (Exception $e) {
            error_log("خطأ في إنشاء الفاتورة: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الفاتورة'
            ];
        }
    }
    
    /**
     * معالجة الدفع
     * @param int $invoice_id معرف الفاتورة
     * @param string $gateway بوابة الدفع
     * @param array $payment_data بيانات الدفع
     * @return array نتيجة معالجة الدفع
     */
    public function processPayment($invoice_id, $gateway, $payment_data) {
        try {
            // جلب بيانات الفاتورة
            $invoice = $this->getInvoice($invoice_id);
            if (!$invoice) {
                return [
                    'success' => false,
                    'message' => 'الفاتورة غير موجودة'
                ];
            }
            
            if ($invoice['status'] !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'الفاتورة مدفوعة بالفعل أو ملغاة'
                ];
            }
            
            // معالجة الدفع حسب البوابة
            $payment_result = $this->processGatewayPayment($gateway, $invoice, $payment_data);
            
            if ($payment_result['success']) {
                // تحديث حالة الفاتورة
                $this->updateInvoiceStatus($invoice_id, 'paid', $payment_result['transaction_id']);
                
                // إضافة رصيد للمعلن
                $this->addAdvertiserBalance($invoice['advertiser_id'], $invoice['amount']);
                
                // تسجيل المعاملة
                $this->recordTransaction($invoice_id, $gateway, $payment_result);
                
                // إرسال إشعار الدفع
                $this->sendPaymentConfirmation($invoice_id);
                
                return [
                    'success' => true,
                    'transaction_id' => $payment_result['transaction_id'],
                    'message' => 'تم الدفع بنجاح'
                ];
            } else {
                // تسجيل فشل الدفع
                $this->recordFailedPayment($invoice_id, $gateway, $payment_result['error']);
                
                return [
                    'success' => false,
                    'message' => $payment_result['error'] ?? 'فشل في معالجة الدفع'
                ];
            }
            
        } catch (Exception $e) {
            error_log("خطأ في معالجة الدفع: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الدفع'
            ];
        }
    }
    
    /**
     * إنشاء رابط دفع
     * @param int $invoice_id معرف الفاتورة
     * @param string $gateway بوابة الدفع
     * @return array رابط الدفع
     */
    public function createPaymentLink($invoice_id, $gateway) {
        try {
            $invoice = $this->getInvoice($invoice_id);
            if (!$invoice) {
                return [
                    'success' => false,
                    'message' => 'الفاتورة غير موجودة'
                ];
            }
            
            // إنشاء رابط دفع حسب البوابة
            switch ($gateway) {
                case 'stripe':
                    return $this->createStripePaymentLink($invoice);
                case 'paypal':
                    return $this->createPayPalPaymentLink($invoice);
                case 'razorpay':
                    return $this->createRazorpayPaymentLink($invoice);
                case 'paymob':
                    return $this->createPayMobPaymentLink($invoice);
                case 'tap':
                    return $this->createTapPaymentLink($invoice);
                default:
                    return [
                        'success' => false,
                        'message' => 'بوابة الدفع غير مدعومة'
                    ];
            }
            
        } catch (Exception $e) {
            error_log("خطأ في إنشاء رابط الدفع: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء رابط الدفع'
            ];
        }
    }
    
    /**
     * إدارة رصيد المعلن
     * @param int $advertiser_id معرف المعلن
     * @param float $amount المبلغ (موجب للإضافة، سالب للخصم)
     * @param string $description وصف العملية
     * @param string $type نوع العملية
     * @return bool نجح أم لا
     */
    public function manageAdvertiserBalance($advertiser_id, $amount, $description, $type = 'payment') {
        try {
            // الحصول على الرصيد الحالي
            $current_balance = $this->getAdvertiserBalance($advertiser_id);
            
            // التحقق من كفاية الرصيد في حالة الخصم
            if ($amount < 0 && abs($amount) > $current_balance) {
                return false;
            }
            
            // تحديث الرصيد
            $new_balance = $current_balance + $amount;
            
            $sql = "INSERT INTO fmc_advertiser_balance 
                    (advertiser_id, amount, balance_after, description, type, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    current_balance = ?, updated_at = NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $advertiser_id,
                $amount,
                $new_balance,
                $description,
                $type,
                $new_balance
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("خطأ في إدارة رصيد المعلن: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * إنشاء تقرير مالي
     * @param array $params معاملات التقرير
     * @return array التقرير المالي
     */
    public function generateFinancialReport($params = []) {
        try {
            $start_date = $params['start_date'] ?? date('Y-m-01');
            $end_date = $params['end_date'] ?? date('Y-m-t');
            $advertiser_id = $params['advertiser_id'] ?? null;
            
            $report = [
                'period' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ],
                'summary' => $this->getFinancialSummary($start_date, $end_date, $advertiser_id),
                'invoices' => $this->getInvoicesReport($start_date, $end_date, $advertiser_id),
                'payments' => $this->getPaymentsReport($start_date, $end_date, $advertiser_id),
                'balance_changes' => $this->getBalanceChangesReport($start_date, $end_date, $advertiser_id),
                'revenue_breakdown' => $this->getRevenueBreakdown($start_date, $end_date, $advertiser_id)
            ];
            
            return $report;
            
        } catch (Exception $e) {
            error_log("خطأ في إنشاء التقرير المالي: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * معالجة الدفع عبر بوابة محددة
     */
    private function processGatewayPayment($gateway, $invoice, $payment_data) {
        switch ($gateway) {
            case 'stripe':
                return $this->processStripePayment($invoice, $payment_data);
            case 'paypal':
                return $this->processPayPalPayment($invoice, $payment_data);
            case 'razorpay':
                return $this->processRazorpayPayment($invoice, $payment_data);
            case 'paymob':
                return $this->processPayMobPayment($invoice, $payment_data);
            case 'tap':
                return $this->processTapPayment($invoice, $payment_data);
            case 'bank_transfer':
                return $this->processBankTransfer($invoice, $payment_data);
            default:
                return [
                    'success' => false,
                    'error' => 'بوابة دفع غير مدعومة'
                ];
        }
    }
    
    /**
     * معالجة دفع Stripe
     */
    private function processStripePayment($invoice, $payment_data) {
        // تطبيق مبسط - يجب استخدام Stripe SDK الحقيقي
        try {
            // محاكاة معالجة Stripe
            $transaction_id = 'stripe_' . uniqid();
            
            // هنا يجب إضافة كود Stripe الحقيقي
            /*
            \Stripe\Stripe::setApiKey($this->getStripeSecretKey());
            
            $charge = \Stripe\Charge::create([
                'amount' => $invoice['total_amount'] * 100, // بالسنت
                'currency' => $this->default_currency,
                'source' => $payment_data['token'],
                'description' => 'Invoice #' . $invoice['invoice_number'],
                'metadata' => [
                    'invoice_id' => $invoice['id'],
                    'advertiser_id' => $invoice['advertiser_id']
                ]
            ]);
            
            return [
                'success' => true,
                'transaction_id' => $charge->id,
                'gateway_response' => $charge
            ];
            */
            
            // محاكاة للاختبار
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'gateway_response' => ['status' => 'succeeded']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * معالجة دفع PayPal
     */
    private function processPayPalPayment($invoice, $payment_data) {
        try {
            $transaction_id = 'paypal_' . uniqid();
            
            // هنا يجب إضافة كود PayPal الحقيقي
            // محاكاة للاختبار
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'gateway_response' => ['status' => 'COMPLETED']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * معالجة دفع Razorpay
     */
    private function processRazorpayPayment($invoice, $payment_data) {
        try {
            $transaction_id = 'razorpay_' . uniqid();
            
            // محاكاة للاختبار
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'gateway_response' => ['status' => 'captured']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * معالجة دفع PayMob
     */
    private function processPayMobPayment($invoice, $payment_data) {
        try {
            $transaction_id = 'paymob_' . uniqid();
            
            // محاكاة للاختبار
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'gateway_response' => ['success' => true]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * معالجة دفع Tap
     */
    private function processTapPayment($invoice, $payment_data) {
        try {
            $transaction_id = 'tap_' . uniqid();
            
            // محاكاة للاختبار
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'gateway_response' => ['status' => 'CAPTURED']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * معالجة التحويل البنكي
     */
    private function processBankTransfer($invoice, $payment_data) {
        try {
            $transaction_id = 'bank_' . uniqid();
            
            // التحويل البنكي يحتاج موافقة يدوية
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'gateway_response' => ['status' => 'pending_verification'],
                'requires_verification' => true
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * إنشاء رابط دفع Stripe
     */
    private function createStripePaymentLink($invoice) {
        $payment_url = "/payment/stripe?invoice_id=" . $invoice['id'];
        
        return [
            'success' => true,
            'payment_url' => $payment_url,
            'gateway' => 'stripe'
        ];
    }
    
    /**
     * إنشاء رابط دفع PayPal
     */
    private function createPayPalPaymentLink($invoice) {
        $payment_url = "/payment/paypal?invoice_id=" . $invoice['id'];
        
        return [
            'success' => true,
            'payment_url' => $payment_url,
            'gateway' => 'paypal'
        ];
    }
    
    /**
     * إنشاء رابط دفع Razorpay
     */
    private function createRazorpayPaymentLink($invoice) {
        $payment_url = "/payment/razorpay?invoice_id=" . $invoice['id'];
        
        return [
            'success' => true,
            'payment_url' => $payment_url,
            'gateway' => 'razorpay'
        ];
    }
    
    /**
     * إنشاء رابط دفع PayMob
     */
    private function createPayMobPaymentLink($invoice) {
        $payment_url = "/payment/paymob?invoice_id=" . $invoice['id'];
        
        return [
            'success' => true,
            'payment_url' => $payment_url,
            'gateway' => 'paymob'
        ];
    }
    
    /**
     * إنشاء رابط دفع Tap
     */
    private function createTapPaymentLink($invoice) {
        $payment_url = "/payment/tap?invoice_id=" . $invoice['id'];
        
        return [
            'success' => true,
            'payment_url' => $payment_url,
            'gateway' => 'tap'
        ];
    }
    
    /**
     * إنشاء رقم فاتورة فريد
     */
    private function generateInvoiceNumber() {
        $prefix = 'INV';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . '-' . $date . '-' . $random;
    }
    
    /**
     * الحصول على معدل الضريبة
     */
    private function getTaxRate($advertiser_id) {
        // يمكن تخصيص معدل الضريبة حسب المعلن أو البلد
        return 15.0; // 15% ضريبة افتراضية
    }
    
    /**
     * إضافة عناصر الفاتورة
     */
    private function addInvoiceItems($invoice_id, $items) {
        foreach ($items as $item) {
            $sql = "INSERT INTO fmc_invoice_items 
                    (invoice_id, description, quantity, unit_price, total_price) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $invoice_id,
                $item['description'],
                $item['quantity'],
                $item['unit_price'],
                $item['quantity'] * $item['unit_price']
            ]);
        }
    }
    
    /**
     * الحصول على الفاتورة
     */
    private function getInvoice($invoice_id) {
        $sql = "SELECT * FROM fmc_invoices WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$invoice_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * تحديث حالة الفاتورة
     */
    private function updateInvoiceStatus($invoice_id, $status, $transaction_id = null) {
        $sql = "UPDATE fmc_invoices SET status = ?, paid_at = ?, transaction_id = ? WHERE id = ?";
        $paid_at = $status === 'paid' ? date('Y-m-d H:i:s') : null;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $paid_at, $transaction_id, $invoice_id]);
    }
    
    /**
     * إضافة رصيد للمعلن
     */
    private function addAdvertiserBalance($advertiser_id, $amount) {
        return $this->manageAdvertiserBalance($advertiser_id, $amount, 'إضافة رصيد من دفع فاتورة', 'payment');
    }
    
    /**
     * الحصول على رصيد المعلن
     */
    private function getAdvertiserBalance($advertiser_id) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as balance FROM fmc_advertiser_balance WHERE advertiser_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$advertiser_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['balance'] ?? 0;
    }
    
    /**
     * تسجيل المعاملة
     */
    private function recordTransaction($invoice_id, $gateway, $payment_result) {
        $sql = "INSERT INTO fmc_payment_transactions 
                (invoice_id, gateway, transaction_id, amount, status, gateway_response, created_at) 
                VALUES (?, ?, ?, ?, 'completed', ?, NOW())";
        
        $invoice = $this->getInvoice($invoice_id);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $invoice_id,
            $gateway,
            $payment_result['transaction_id'],
            $invoice['total_amount'],
            json_encode($payment_result['gateway_response'])
        ]);
    }
    
    /**
     * تسجيل فشل الدفع
     */
    private function recordFailedPayment($invoice_id, $gateway, $error) {
        $sql = "INSERT INTO fmc_payment_transactions 
                (invoice_id, gateway, amount, status, error_message, created_at) 
                VALUES (?, ?, ?, 'failed', ?, NOW())";
        
        $invoice = $this->getInvoice($invoice_id);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $invoice_id,
            $gateway,
            $invoice['total_amount'],
            $error
        ]);
    }
    
    /**
     * إرسال إشعار الفاتورة
     */
    private function sendInvoiceNotification($invoice_id) {
        // يمكن إضافة إرسال إيميل أو إشعار
        // هذا مثال مبسط
        error_log("إشعار فاتورة جديدة: $invoice_id");
    }
    
    /**
     * إرسال تأكيد الدفع
     */
    private function sendPaymentConfirmation($invoice_id) {
        // يمكن إضافة إرسال إيميل أو إشعار
        error_log("تأكيد دفع الفاتورة: $invoice_id");
    }
    
    /**
     * الحصول على الملخص المالي
     */
    private function getFinancialSummary($start_date, $end_date, $advertiser_id = null) {
        $where_clause = "WHERE DATE(created_at) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
        
        if ($advertiser_id) {
            $where_clause .= " AND advertiser_id = ?";
            $params[] = $advertiser_id;
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_invoices,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_invoices,
                    SUM(total_amount) as total_amount,
                    SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
                    SUM(tax_amount) as total_tax
                FROM fmc_invoices 
                {$where_clause}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على تقرير الفواتير
     */
    private function getInvoicesReport($start_date, $end_date, $advertiser_id = null) {
        $where_clause = "WHERE DATE(i.created_at) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
        
        if ($advertiser_id) {
            $where_clause .= " AND i.advertiser_id = ?";
            $params[] = $advertiser_id;
        }
        
        $sql = "SELECT 
                    i.*,
                    u.username as advertiser_name,
                    u.email as advertiser_email
                FROM fmc_invoices i
                JOIN fmc_users u ON i.advertiser_id = u.id
                {$where_clause}
                ORDER BY i.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على تقرير المدفوعات
     */
    private function getPaymentsReport($start_date, $end_date, $advertiser_id = null) {
        $where_clause = "WHERE DATE(pt.created_at) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
        
        if ($advertiser_id) {
            $where_clause .= " AND i.advertiser_id = ?";
            $params[] = $advertiser_id;
        }
        
        $sql = "SELECT 
                    pt.*,
                    i.invoice_number,
                    u.username as advertiser_name
                FROM fmc_payment_transactions pt
                JOIN fmc_invoices i ON pt.invoice_id = i.id
                JOIN fmc_users u ON i.advertiser_id = u.id
                {$where_clause}
                ORDER BY pt.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على تقرير تغييرات الرصيد
     */
    private function getBalanceChangesReport($start_date, $end_date, $advertiser_id = null) {
        $where_clause = "WHERE DATE(ab.created_at) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
        
        if ($advertiser_id) {
            $where_clause .= " AND ab.advertiser_id = ?";
            $params[] = $advertiser_id;
        }
        
        $sql = "SELECT 
                    ab.*,
                    u.username as advertiser_name
                FROM fmc_advertiser_balance ab
                JOIN fmc_users u ON ab.advertiser_id = u.id
                {$where_clause}
                ORDER BY ab.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على تفصيل الإيرادات
     */
    private function getRevenueBreakdown($start_date, $end_date, $advertiser_id = null) {
        $where_clause = "WHERE DATE(pt.created_at) BETWEEN ? AND ? AND pt.status = 'completed'";
        $params = [$start_date, $end_date];
        
        if ($advertiser_id) {
            $where_clause .= " AND i.advertiser_id = ?";
            $params[] = $advertiser_id;
        }
        
        $sql = "SELECT 
                    pt.gateway,
                    COUNT(*) as transaction_count,
                    SUM(pt.amount) as total_amount,
                    AVG(pt.amount) as avg_amount
                FROM fmc_payment_transactions pt
                JOIN fmc_invoices i ON pt.invoice_id = i.id
                {$where_clause}
                GROUP BY pt.gateway
                ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على بوابات الدفع المدعومة
     */
    public function getSupportedGateways() {
        return $this->supported_gateways;
    }
    
    /**
     * التحقق من صحة بيانات الدفع
     */
    public function validatePaymentData($gateway, $payment_data) {
        switch ($gateway) {
            case 'stripe':
                return isset($payment_data['token']) && !empty($payment_data['token']);
            case 'paypal':
                return isset($payment_data['payment_id']) && !empty($payment_data['payment_id']);
            case 'bank_transfer':
                return isset($payment_data['transfer_reference']) && !empty($payment_data['transfer_reference']);
            default:
                return true;
        }
    }
    
    /**
     * حساب الرسوم
     */
    public function calculateFees($amount, $gateway) {
        $fee_rates = [
            'stripe' => 0.029, // 2.9%
            'paypal' => 0.034, // 3.4%
            'razorpay' => 0.02, // 2%
            'paymob' => 0.025, // 2.5%
            'tap' => 0.025, // 2.5%
            'bank_transfer' => 0.01 // 1%
        ];
        
        $rate = $fee_rates[$gateway] ?? 0;
        return $amount * $rate;
    }
}
?>

