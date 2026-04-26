<?php

require_once ROOT_PATH . '/core/Model.php';

class PaymentModel extends Model {
    
    // إنشاء معاملة دفع
    public function createTransaction($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}payment_transactions` 
                (`user_id`, `type`, `amount`, `payment_method`, `card_id`, `status`, 
                 `transaction_id`, `gateway_response`, `description`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['user_id'],
                $data['type'],
                $data['amount'],
                $data['payment_method'] ?? null,
                $data['card_id'] ?? null,
                $data['status'] ?? 'pending',
                $data['transaction_id'] ?? null,
                $data['gateway_response'] ?? null,
                $data['description'] ?? null
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إنشاء المعاملة: ' . $e->getMessage()];
        }
    }

    // تحديث حالة المعاملة
    public function updateTransactionStatus($id, $status, $gatewayResponse = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}payment_transactions` 
                SET `status` = ?, `gateway_response` = ?, `updated_at` = NOW() 
                WHERE `id` = ?
            ");
            $stmt->execute([$status, $gatewayResponse, $id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تحديث المعاملة: ' . $e->getMessage()];
        }
    }

    // إضافة بطاقة دفع
    public function addPaymentCard($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}payment_cards` 
                (`user_id`, `card_number_encrypted`, `card_type`, `last_four_digits`, 
                 `expiry_month`, `expiry_year`, `cardholder_name`, `is_default`, `status`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $data['user_id'],
                $data['card_number_encrypted'],
                $data['card_type'],
                $data['last_four_digits'],
                $data['expiry_month'],
                $data['expiry_year'],
                $data['cardholder_name'],
                $data['is_default'] ?? 0
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إضافة البطاقة: ' . $e->getMessage()];
        }
    }

    // حذف بطاقة دفع
    public function deletePaymentCard($id, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}payment_cards` 
                SET `status` = 'deleted', `updated_at` = NOW() 
                WHERE `id` = ? AND `user_id` = ?
            ");
            $stmt->execute([$id, $userId]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في حذف البطاقة: ' . $e->getMessage()];
        }
    }

    // إنشاء طلب سحب
    public function createWithdrawalRequest($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}withdrawal_requests` 
                (`user_id`, `amount`, `method`, `account_details`, `status`, `notes`) 
                VALUES (?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([
                $data['user_id'],
                $data['amount'],
                $data['method'],
                $data['account_details'],
                $data['notes'] ?? null
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إنشاء طلب السحب: ' . $e->getMessage()];
        }
    }

    // تحديث حالة طلب السحب
    public function updateWithdrawalStatus($id, $status, $adminNotes = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}withdrawal_requests` 
                SET `status` = ?, `admin_notes` = ?, `processed_at` = NOW() 
                WHERE `id` = ?
            ");
            $stmt->execute([$status, $adminNotes, $id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تحديث طلب السحب: ' . $e->getMessage()];
        }
    }

    // إضافة معاملة للمحفظة
    public function addWalletTransaction($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}wallet_transactions` 
                (`user_id`, `type`, `amount`, `description`, `reference_id`, `reference_type`) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['user_id'],
                $data['type'],
                $data['amount'],
                $data['description'],
                $data['reference_id'] ?? null,
                $data['reference_type'] ?? null
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إضافة معاملة المحفظة: ' . $e->getMessage()];
        }
    }

    // تحديث رصيد المستخدم
    public function updateUserBalance($userId, $amount) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}users` 
                SET `balance` = `balance` + ?, `updated_at` = NOW() 
                WHERE `id` = ?
            ");
            $stmt->execute([$amount, $userId]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تحديث الرصيد: ' . $e->getMessage()];
        }
    }

    // جلب رصيد المستخدم
    public function getUserBalance($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT `balance` FROM `{$this->prefix}users` WHERE `id` = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // جلب معاملات المحفظة
    public function getWalletTransactions($userId, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM `{$this->prefix}wallet_transactions` 
                WHERE `user_id` = ? 
                ORDER BY `created_at` DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // جلب بطاقات الدفع للمستخدم
    public function getUserPaymentCards($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM `{$this->prefix}payment_cards` 
                WHERE `user_id` = ? AND `status` = 'active' 
                ORDER BY `is_default` DESC, `created_at` DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // جلب معاملات الدفع
    public function getPaymentTransactions($userId = null, $status = null, $limit = 20, $offset = 0) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($userId) {
                $whereClause .= " AND `user_id` = ?";
                $params[] = $userId;
            }
            
            if ($status) {
                $whereClause .= " AND `status` = ?";
                $params[] = $status;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT pt.*, u.username 
                FROM `{$this->prefix}payment_transactions` pt
                LEFT JOIN `{$this->prefix}users` u ON pt.user_id = u.id
                $whereClause 
                ORDER BY pt.created_at DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // جلب طلبات السحب
    public function getWithdrawalRequests($userId = null, $status = null, $limit = 20, $offset = 0) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($userId) {
                $whereClause .= " AND wr.user_id = ?";
                $params[] = $userId;
            }
            
            if ($status) {
                $whereClause .= " AND wr.status = ?";
                $params[] = $status;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT wr.*, u.username 
                FROM `{$this->prefix}withdrawal_requests` wr
                LEFT JOIN `{$this->prefix}users` u ON wr.user_id = u.id
                $whereClause 
                ORDER BY wr.created_at DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // إحصائيات المدفوعات
    public function getPaymentStats() {
        try {
            $stats = [];
            
            // إجمالي الإيداعات
            $stmt = $this->pdo->query("
                SELECT SUM(`amount`) FROM `{$this->prefix}payment_transactions` 
                WHERE `status` = 'completed' AND `type` = 'deposit'
            ");
            $stats['total_deposits'] = $stmt->fetchColumn() ?: 0;
            
            // إجمالي السحوبات
            $stmt = $this->pdo->query("
                SELECT SUM(`amount`) FROM `{$this->prefix}withdrawal_requests` 
                WHERE `status` = 'completed'
            ");
            $stats['total_withdrawals'] = $stmt->fetchColumn() ?: 0;
            
            // الربح الصافي
            $stats['net_profit'] = $stats['total_deposits'] - $stats['total_withdrawals'];
            
            // عدد المعاملات
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}payment_transactions`");
            $stats['total_transactions'] = $stmt->fetchColumn();
            
            // عدد المستخدمين النشطين في المدفوعات
            $stmt = $this->pdo->query("
                SELECT COUNT(DISTINCT `user_id`) FROM `{$this->prefix}payment_transactions` 
                WHERE `status` = 'completed'
            ");
            $stats['active_users'] = $stmt->fetchColumn();
            
            // متوسط قيمة المعاملة
            if ($stats['total_transactions'] > 0) {
                $stats['avg_transaction'] = $stats['total_deposits'] / $stats['total_transactions'];
            } else {
                $stats['avg_transaction'] = 0;
            }
            
            // إحصائيات شهرية
            $stmt = $this->pdo->query("
                SELECT 
                    DATE_FORMAT(`created_at`, '%Y-%m') as month,
                    SUM(`amount`) as total_amount,
                    COUNT(*) as transaction_count
                FROM `{$this->prefix}payment_transactions` 
                WHERE `status` = 'completed' AND `type` = 'deposit'
                    AND `created_at` >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(`created_at`, '%Y-%m')
                ORDER BY month DESC
            ");
            $stats['monthly_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (PDOException $e) {
            return [];
        }
    }

    // جلب معاملة بالمعرف
    public function getTransactionById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pt.*, u.username 
                FROM `{$this->prefix}payment_transactions` pt
                LEFT JOIN `{$this->prefix}users` u ON pt.user_id = u.id
                WHERE pt.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    // جلب طلب سحب بالمعرف
    public function getWithdrawalById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT wr.*, u.username, u.email 
                FROM `{$this->prefix}withdrawal_requests` wr
                LEFT JOIN `{$this->prefix}users` u ON wr.user_id = u.id
                WHERE wr.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    // تحديث البطاقة الافتراضية
    public function setDefaultCard($cardId, $userId) {
        try {
            // إلغاء الافتراضية للبطاقات الأخرى
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}payment_cards` 
                SET `is_default` = 0 
                WHERE `user_id` = ?
            ");
            $stmt->execute([$userId]);
            
            // تعيين البطاقة الجديدة كافتراضية
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}payment_cards` 
                SET `is_default` = 1 
                WHERE `id` = ? AND `user_id` = ?
            ");
            $stmt->execute([$cardId, $userId]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تحديث البطاقة الافتراضية: ' . $e->getMessage()];
        }
    }

    // التحقق من كفاية الرصيد
    public function checkSufficientBalance($userId, $amount) {
        $currentBalance = $this->getUserBalance($userId);
        return $currentBalance >= $amount;
    }

    // معالجة عملية دفع (خصم من الرصيد)
    public function processPayment($userId, $amount, $description, $referenceId = null, $referenceType = null) {
        try {
            // التحقق من كفاية الرصيد
            if (!$this->checkSufficientBalance($userId, $amount)) {
                return ['success' => false, 'message' => 'الرصيد غير كافي'];
            }
            
            // خصم المبلغ
            $this->updateUserBalance($userId, -$amount);
            
            // إضافة معاملة
            $this->addWalletTransaction([
                'user_id' => $userId,
                'type' => 'payment',
                'amount' => -$amount,
                'description' => $description,
                'reference_id' => $referenceId,
                'reference_type' => $referenceType
            ]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'خطأ في معالجة الدفع: ' . $e->getMessage()];
        }
    }

    // معالجة استلام دفعة (إضافة للرصيد)
    public function receivePayment($userId, $amount, $description, $referenceId = null, $referenceType = null) {
        try {
            // إضافة المبلغ
            $this->updateUserBalance($userId, $amount);
            
            // إضافة معاملة
            $this->addWalletTransaction([
                'user_id' => $userId,
                'type' => 'received',
                'amount' => $amount,
                'description' => $description,
                'reference_id' => $referenceId,
                'reference_type' => $referenceType
            ]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'خطأ في استلام الدفع: ' . $e->getMessage()];
        }
    }
}

?>

