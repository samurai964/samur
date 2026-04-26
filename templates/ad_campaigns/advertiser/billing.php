<?php
/**
 * صفحة الفواتير والمدفوعات للمعلنين
 * تعرض جميع الفواتير وتسمح بإدارة المدفوعات
 */

// تضمين الهيدر
include_once __DIR__ . '/../../frontend/partials/header.php';

// التحقق من تسجيل الدخول
if (!Auth::isLoggedIn()) {
    redirect('/auth/login');
}

require_once __DIR__ . '/../../../modules/ad_campaigns/PaymentManager.php';

// إنشاء مدير المدفوعات
$payment_manager = new PaymentManager($database->getConnection());

// الحصول على معرف المعلن
$advertiser_id = $_SESSION['user_id'];

// معالجة الإجراءات
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'pay_invoice':
            $invoice_id = $_POST['invoice_id'] ?? 0;
            $gateway = $_POST['gateway'] ?? '';
            $payment_data = $_POST['payment_data'] ?? [];
            
            $result = $payment_manager->processPayment($invoice_id, $gateway, $payment_data);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'create_payment_link':
            $invoice_id = $_POST['invoice_id'] ?? 0;
            $gateway = $_POST['gateway'] ?? '';
            
            $result = $payment_manager->createPaymentLink($invoice_id, $gateway);
            if ($result['success']) {
                header('Location: ' . $result['payment_url']);
                exit;
            } else {
                $error = $result['message'];
            }
            break;
    }
}

// جلب الفواتير
$invoices_sql = "SELECT i.*, 
                        CASE 
                            WHEN i.status = 'pending' AND i.due_date < CURDATE() THEN 'overdue'
                            ELSE i.status 
                        END as display_status
                 FROM fmc_invoices i 
                 WHERE i.advertiser_id = ? 
                 ORDER BY i.created_at DESC";
$invoices_stmt = $database->getConnection()->prepare($invoices_sql);
$invoices_stmt->execute([$advertiser_id]);
$invoices = $invoices_stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب الرصيد الحالي
$balance_sql = "SELECT COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END), 0) as balance
                FROM fmc_advertiser_balance 
                WHERE advertiser_id = ?";
$balance_stmt = $database->getConnection()->prepare($balance_sql);
$balance_stmt->execute([$advertiser_id]);
$current_balance = $balance_stmt->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0;

// جلب المعاملات الأخيرة
$transactions_sql = "SELECT pt.*, i.invoice_number
                     FROM fmc_payment_transactions pt
                     LEFT JOIN fmc_invoices i ON pt.invoice_id = i.id
                     WHERE pt.advertiser_id = ?
                     ORDER BY pt.created_at DESC
                     LIMIT 10";
$transactions_stmt = $database->getConnection()->prepare($transactions_sql);
$transactions_stmt->execute([$advertiser_id]);
$transactions = $transactions_stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب بوابات الدفع المدعومة
$supported_gateways = $payment_manager->getSupportedGateways();

// إحصائيات الفواتير
$invoice_stats = [
    'total' => count($invoices),
    'pending' => count(array_filter($invoices, fn($i) => $i['status'] === 'pending')),
    'paid' => count(array_filter($invoices, fn($i) => $i['status'] === 'paid')),
    'overdue' => count(array_filter($invoices, fn($i) => $i['display_status'] === 'overdue')),
    'total_amount' => array_sum(array_column($invoices, 'total_amount')),
    'paid_amount' => array_sum(array_column(array_filter($invoices, fn($i) => $i['status'] === 'paid'), 'total_amount')),
    'pending_amount' => array_sum(array_column(array_filter($invoices, fn($i) => $i['status'] === 'pending'), 'total_amount'))
];
?>

<div class="billing-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <h1 class="page-title">
                    <i class="fas fa-file-invoice-dollar"></i>
                    الفواتير والمدفوعات
                </h1>
                <p class="page-subtitle">إدارة فواتيرك ومدفوعاتك بسهولة</p>
            </div>
            
            <div class="header-actions">
                <button class="btn btn-primary" onclick="showAddFundsModal()">
                    <i class="fas fa-plus"></i>
                    إضافة رصيد
                </button>
                <button class="btn btn-outline-primary" onclick="downloadInvoices()">
                    <i class="fas fa-download"></i>
                    تحميل الفواتير
                </button>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Billing Overview -->
    <div class="billing-overview">
        <div class="overview-cards">
            <div class="overview-card balance">
                <div class="card-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="card-content">
                    <h3 class="card-value">$<?= number_format($current_balance, 2) ?></h3>
                    <p class="card-label">الرصيد الحالي</p>
                    <div class="card-action">
                        <button class="btn btn-sm btn-primary" onclick="showAddFundsModal()">
                            إضافة رصيد
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="overview-card invoices">
                <div class="card-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="card-content">
                    <h3 class="card-value"><?= $invoice_stats['total'] ?></h3>
                    <p class="card-label">إجمالي الفواتير</p>
                    <div class="card-breakdown">
                        <span class="breakdown-item paid"><?= $invoice_stats['paid'] ?> مدفوعة</span>
                        <span class="breakdown-item pending"><?= $invoice_stats['pending'] ?> معلقة</span>
                        <?php if ($invoice_stats['overdue'] > 0): ?>
                            <span class="breakdown-item overdue"><?= $invoice_stats['overdue'] ?> متأخرة</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="overview-card amount">
                <div class="card-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="card-content">
                    <h3 class="card-value">$<?= number_format($invoice_stats['total_amount'], 2) ?></h3>
                    <p class="card-label">إجمالي المبلغ</p>
                    <div class="card-breakdown">
                        <span class="breakdown-item paid">$<?= number_format($invoice_stats['paid_amount'], 2) ?> مدفوع</span>
                        <span class="breakdown-item pending">$<?= number_format($invoice_stats['pending_amount'], 2) ?> معلق</span>
                    </div>
                </div>
            </div>
            
            <div class="overview-card transactions">
                <div class="card-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="card-content">
                    <h3 class="card-value"><?= count($transactions) ?></h3>
                    <p class="card-label">المعاملات الأخيرة</p>
                    <div class="card-action">
                        <a href="#transactions-section" class="btn btn-sm btn-outline-primary">
                            عرض الكل
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Section -->
    <div class="invoices-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-file-invoice"></i>
                الفواتير
            </h2>
            <div class="section-filters">
                <select id="status-filter" class="form-select">
                    <option value="">جميع الحالات</option>
                    <option value="pending">معلقة</option>
                    <option value="paid">مدفوعة</option>
                    <option value="overdue">متأخرة</option>
                    <option value="cancelled">ملغاة</option>
                </select>
                <input type="date" id="date-filter" class="form-control" placeholder="التاريخ">
            </div>
        </div>
        
        <div class="invoices-table">
            <?php if (empty($invoices)): ?>
                <div class="no-data-message">
                    <div class="no-data-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h3>لا توجد فواتير</h3>
                    <p>لم يتم إنشاء أي فواتير بعد.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>رقم الفاتورة</th>
                            <th>التاريخ</th>
                            <th>الوصف</th>
                            <th>المبلغ</th>
                            <th>الضريبة</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr class="invoice-row" data-status="<?= $invoice['display_status'] ?>">
                                <td>
                                    <div class="invoice-number">
                                        <strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="invoice-date">
                                        <?= date('d/m/Y', strtotime($invoice['created_at'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="invoice-description">
                                        <?= htmlspecialchars($invoice['description']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="invoice-amount">
                                        $<?= number_format($invoice['amount'], 2) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="invoice-tax">
                                        $<?= number_format($invoice['tax_amount'], 2) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="invoice-total">
                                        <strong>$<?= number_format($invoice['total_amount'], 2) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $invoice['display_status'] ?>">
                                        <?php
                                        $status_labels = [
                                            'pending' => 'معلقة',
                                            'paid' => 'مدفوعة',
                                            'overdue' => 'متأخرة',
                                            'cancelled' => 'ملغاة',
                                            'refunded' => 'مسترد'
                                        ];
                                        echo $status_labels[$invoice['display_status']] ?? $invoice['display_status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="due-date <?= $invoice['display_status'] === 'overdue' ? 'overdue' : '' ?>">
                                        <?= date('d/m/Y', strtotime($invoice['due_date'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewInvoice(<?= $invoice['id'] ?>)" 
                                                title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <button class="btn btn-sm btn-outline-secondary" 
                                                onclick="downloadInvoice(<?= $invoice['id'] ?>)" 
                                                title="تحميل">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        
                                        <?php if ($invoice['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="showPaymentModal(<?= $invoice['id'] ?>)" 
                                                    title="دفع">
                                                <i class="fas fa-credit-card"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Transactions Section -->
    <div id="transactions-section" class="transactions-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-exchange-alt"></i>
                المعاملات الأخيرة
            </h2>
            <a href="/ad_campaigns/advertiser/transactions" class="section-link">
                عرض جميع المعاملات
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
        
        <div class="transactions-list">
            <?php if (empty($transactions)): ?>
                <div class="no-data-message">
                    <div class="no-data-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3>لا توجد معاملات</h3>
                    <p>لم يتم تسجيل أي معاملات بعد.</p>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <div class="transaction-item">
                        <div class="transaction-icon">
                            <i class="fas fa-<?= $transaction['status'] === 'completed' ? 'check-circle' : ($transaction['status'] === 'failed' ? 'times-circle' : 'clock') ?>"></i>
                        </div>
                        <div class="transaction-details">
                            <div class="transaction-title">
                                <?php if ($transaction['invoice_number']): ?>
                                    دفع فاتورة <?= htmlspecialchars($transaction['invoice_number']) ?>
                                <?php else: ?>
                                    <?= ucfirst($transaction['transaction_type']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="transaction-meta">
                                <span class="transaction-gateway"><?= htmlspecialchars($transaction['gateway']) ?></span>
                                <span class="transaction-date"><?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?></span>
                            </div>
                        </div>
                        <div class="transaction-amount">
                            <span class="amount-value">$<?= number_format($transaction['amount'], 2) ?></span>
                            <span class="status-badge status-<?= $transaction['status'] ?>">
                                <?php
                                $status_labels = [
                                    'pending' => 'معلق',
                                    'completed' => 'مكتمل',
                                    'failed' => 'فاشل',
                                    'cancelled' => 'ملغى'
                                ];
                                echo $status_labels[$transaction['status']] ?? $transaction['status'];
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-credit-card"></i>
                دفع الفاتورة
            </h3>
            <button class="modal-close" onclick="closePaymentModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div id="payment-form-container">
                <!-- سيتم تحميل نموذج الدفع هنا -->
            </div>
        </div>
    </div>
</div>

<!-- Add Funds Modal -->
<div id="addFundsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-plus"></i>
                إضافة رصيد
            </h3>
            <button class="modal-close" onclick="closeAddFundsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <form id="add-funds-form" method="POST">
                <input type="hidden" name="action" value="add_funds">
                
                <div class="form-group">
                    <label for="amount">المبلغ</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" id="amount" name="amount" class="form-control" 
                               min="10" step="0.01" required>
                    </div>
                    <small class="form-text">الحد الأدنى: $10.00</small>
                </div>
                
                <div class="form-group">
                    <label>طريقة الدفع</label>
                    <div class="payment-methods">
                        <?php foreach ($supported_gateways as $gateway => $name): ?>
                            <div class="payment-method">
                                <input type="radio" id="gateway_<?= $gateway ?>" 
                                       name="gateway" value="<?= $gateway ?>" required>
                                <label for="gateway_<?= $gateway ?>" class="payment-method-label">
                                    <div class="payment-method-icon">
                                        <i class="fab fa-<?= $gateway === 'bank_transfer' ? 'university' : $gateway ?>"></i>
                                    </div>
                                    <span><?= htmlspecialchars($name) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddFundsModal()">
                        إلغاء
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i>
                        متابعة الدفع
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Invoice Details Modal -->
<div id="invoiceModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-file-invoice"></i>
                تفاصيل الفاتورة
            </h3>
            <button class="modal-close" onclick="closeInvoiceModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div id="invoice-details-container">
                <!-- سيتم تحميل تفاصيل الفاتورة هنا -->
            </div>
        </div>
    </div>
</div>

<style>
/* Billing Page Styles */
.billing-page {
    padding: 2rem;
    background: #f8fafc;
    min-height: 100vh;
}

.page-header {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title i {
    color: #667eea;
}

.page-subtitle {
    color: #6b7280;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

/* Overview Cards */
.billing-overview {
    margin-bottom: 2rem;
}

.overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.overview-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: transform 0.3s ease;
}

.overview-card:hover {
    transform: translateY(-2px);
}

.card-icon {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.overview-card.balance .card-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.overview-card.invoices .card-icon {
    background: linear-gradient(135deg, #f093fb, #f5576c);
}

.overview-card.amount .card-icon {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
}

.overview-card.transactions .card-icon {
    background: linear-gradient(135deg, #fa709a, #fee140);
}

.card-content {
    flex: 1;
}

.card-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.card-label {
    color: #6b7280;
    margin-bottom: 1rem;
    font-size: 1rem;
}

.card-breakdown {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.breakdown-item {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.breakdown-item.paid {
    background: #dcfce7;
    color: #166534;
}

.breakdown-item.pending {
    background: #fef3c7;
    color: #92400e;
}

.breakdown-item.overdue {
    background: #fee2e2;
    color: #991b1b;
}

.card-action {
    margin-top: 1rem;
}

/* Section Styles */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-filters {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.section-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-link:hover {
    text-decoration: underline;
}

/* Invoices Table */
.invoices-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.invoices-table {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: right;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}

.invoice-number strong {
    color: #667eea;
}

.invoice-date,
.invoice-description {
    color: #6b7280;
}

.invoice-total strong {
    color: #1f2937;
}

.due-date.overdue {
    color: #dc2626;
    font-weight: 600;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-paid {
    background: #dcfce7;
    color: #166534;
}

.status-overdue {
    background: #fee2e2;
    color: #991b1b;
}

.status-cancelled {
    background: #f3f4f6;
    color: #6b7280;
}

.status-completed {
    background: #dcfce7;
    color: #166534;
}

.status-failed {
    background: #fee2e2;
    color: #991b1b;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

/* Transactions Section */
.transactions-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.transactions-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.transaction-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 10px;
    transition: background-color 0.3s ease;
}

.transaction-item:hover {
    background: #f1f5f9;
}

.transaction-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.transaction-details {
    flex: 1;
}

.transaction-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.transaction-meta {
    display: flex;
    gap: 1rem;
    color: #6b7280;
    font-size: 0.9rem;
}

.transaction-amount {
    text-align: right;
}

.amount-value {
    display: block;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.modal-content.large {
    max-width: 800px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem;
    border-bottom: 2px solid #f3f4f6;
}

.modal-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: background-color 0.3s ease;
}

.modal-close:hover {
    background: #f3f4f6;
}

.modal-body {
    padding: 2rem;
}

/* Form Styles */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form-control,
.form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus,
.form-select:focus {
    outline: none;
    border-color: #667eea;
}

.input-group {
    display: flex;
}

.input-group-text {
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-right: none;
    padding: 0.75rem 1rem;
    border-radius: 8px 0 0 8px;
    font-weight: 600;
    color: #6b7280;
}

.input-group .form-control {
    border-left: none;
    border-radius: 0 8px 8px 0;
}

.form-text {
    color: #6b7280;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

/* Payment Methods */
.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.payment-method {
    position: relative;
}

.payment-method input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.payment-method-label {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-method input[type="radio"]:checked + .payment-method-label {
    border-color: #667eea;
    background: #f0f4ff;
}

.payment-method-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #667eea;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
}

/* No Data Message */
.no-data-message {
    text-align: center;
    padding: 4rem 2rem;
}

.no-data-icon {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.no-data-message h3 {
    color: #374151;
    margin-bottom: 1rem;
}

.no-data-message p {
    color: #6b7280;
}

/* Alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .overview-cards {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
    
    .payment-methods {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .billing-page {
        padding: 1rem;
    }
    
    .page-header {
        padding: 1.5rem;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .header-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .overview-cards {
        grid-template-columns: 1fr;
    }
    
    .overview-card {
        padding: 1.5rem;
    }
    
    .card-value {
        font-size: 2rem;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .section-filters {
        width: 100%;
        justify-content: space-between;
    }
    
    .invoices-table {
        font-size: 0.8rem;
    }
    
    .table th,
    .table td {
        padding: 0.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .transaction-item {
        padding: 1rem;
    }
    
    .transaction-meta {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .modal-content {
        width: 95%;
        margin: 1rem;
    }
    
    .modal-header,
    .modal-body {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Billing Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize filters
    initializeFilters();
    
    // Handle form submissions
    handleFormSubmissions();
});

function initializeFilters() {
    const statusFilter = document.getElementById('status-filter');
    const dateFilter = document.getElementById('date-filter');
    
    if (statusFilter) {
        statusFilter.addEventListener('change', filterInvoices);
    }
    
    if (dateFilter) {
        dateFilter.addEventListener('change', filterInvoices);
    }
}

function filterInvoices() {
    const statusFilter = document.getElementById('status-filter').value;
    const dateFilter = document.getElementById('date-filter').value;
    const invoiceRows = document.querySelectorAll('.invoice-row');
    
    invoiceRows.forEach(row => {
        let show = true;
        
        // Filter by status
        if (statusFilter && row.dataset.status !== statusFilter) {
            show = false;
        }
        
        // Filter by date (simplified - you might want more complex date filtering)
        if (dateFilter) {
            const invoiceDate = row.querySelector('.invoice-date').textContent.trim();
            const filterDate = new Date(dateFilter).toLocaleDateString('en-GB');
            if (invoiceDate !== filterDate) {
                show = false;
            }
        }
        
        row.style.display = show ? '' : 'none';
    });
}

function handleFormSubmissions() {
    const addFundsForm = document.getElementById('add-funds-form');
    if (addFundsForm) {
        addFundsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const amount = formData.get('amount');
            const gateway = formData.get('gateway');
            
            if (!amount || !gateway) {
                alert('يرجى ملء جميع الحقول المطلوبة');
                return;
            }
            
            // Create invoice for the amount
            createInvoiceAndPay(amount, gateway);
        });
    }
}

function showPaymentModal(invoiceId) {
    const modal = document.getElementById('paymentModal');
    const container = document.getElementById('payment-form-container');
    
    // Load payment form
    container.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            جاري تحميل نموذج الدفع...
        </div>
    `;
    
    modal.classList.add('show');
    
    // Simulate loading payment form
    setTimeout(() => {
        loadPaymentForm(invoiceId);
    }, 1000);
}

function loadPaymentForm(invoiceId) {
    const container = document.getElementById('payment-form-container');
    
    container.innerHTML = `
        <form id="payment-form" method="POST">
            <input type="hidden" name="action" value="pay_invoice">
            <input type="hidden" name="invoice_id" value="${invoiceId}">
            
            <div class="form-group">
                <label>طريقة الدفع</label>
                <div class="payment-methods">
                    <?php foreach ($supported_gateways as $gateway => $name): ?>
                        <div class="payment-method">
                            <input type="radio" id="payment_gateway_<?= $gateway ?>" 
                                   name="gateway" value="<?= $gateway ?>" required>
                            <label for="payment_gateway_<?= $gateway ?>" class="payment-method-label">
                                <div class="payment-method-icon">
                                    <i class="fab fa-<?= $gateway === 'bank_transfer' ? 'university' : $gateway ?>"></i>
                                </div>
                                <span><?= htmlspecialchars($name) ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">
                    إلغاء
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-credit-card"></i>
                    دفع الآن
                </button>
            </div>
        </form>
    `;
    
    // Handle payment form submission
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const gateway = formData.get('gateway');
        
        if (!gateway) {
            alert('يرجى اختيار طريقة الدفع');
            return;
        }
        
        // Create payment link and redirect
        createPaymentLink(invoiceId, gateway);
    });
}

function createPaymentLink(invoiceId, gateway) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="create_payment_link">
        <input type="hidden" name="invoice_id" value="${invoiceId}">
        <input type="hidden" name="gateway" value="${gateway}">
    `;
    
    document.body.appendChild(form);
    form.submit();
}

function createInvoiceAndPay(amount, gateway) {
    // This would typically make an AJAX call to create an invoice
    // For now, we'll simulate it
    
    const invoiceData = {
        amount: amount,
        description: 'إضافة رصيد للحساب',
        gateway: gateway
    };
    
    // Simulate invoice creation
    setTimeout(() => {
        alert('تم إنشاء الفاتورة بنجاح. سيتم توجيهك لصفحة الدفع.');
        closeAddFundsModal();
        // Redirect to payment page
        window.location.href = `/payment/${gateway}?amount=${amount}`;
    }, 1000);
}

function showAddFundsModal() {
    const modal = document.getElementById('addFundsModal');
    modal.classList.add('show');
}

function closeAddFundsModal() {
    const modal = document.getElementById('addFundsModal');
    modal.classList.remove('show');
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.remove('show');
}

function viewInvoice(invoiceId) {
    const modal = document.getElementById('invoiceModal');
    const container = document.getElementById('invoice-details-container');
    
    container.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            جاري تحميل تفاصيل الفاتورة...
        </div>
    `;
    
    modal.classList.add('show');
    
    // Load invoice details (would typically be an AJAX call)
    setTimeout(() => {
        loadInvoiceDetails(invoiceId);
    }, 1000);
}

function loadInvoiceDetails(invoiceId) {
    const container = document.getElementById('invoice-details-container');
    
    // This would typically load real invoice data
    container.innerHTML = `
        <div class="invoice-details">
            <div class="invoice-header">
                <h4>فاتورة رقم: INV-${invoiceId}</h4>
                <div class="invoice-status">
                    <span class="status-badge status-pending">معلقة</span>
                </div>
            </div>
            
            <div class="invoice-info">
                <div class="info-row">
                    <span class="info-label">تاريخ الإنشاء:</span>
                    <span class="info-value">${new Date().toLocaleDateString('ar-SA')}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">تاريخ الاستحقاق:</span>
                    <span class="info-value">${new Date(Date.now() + 30*24*60*60*1000).toLocaleDateString('ar-SA')}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">الوصف:</span>
                    <span class="info-value">خدمات إعلانية</span>
                </div>
            </div>
            
            <div class="invoice-items">
                <h5>عناصر الفاتورة</h5>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>الوصف</th>
                            <th>الكمية</th>
                            <th>السعر</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>خدمات إعلانية</td>
                            <td>1</td>
                            <td>$100.00</td>
                            <td>$100.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="invoice-total">
                <div class="total-row">
                    <span>المجموع الفرعي:</span>
                    <span>$100.00</span>
                </div>
                <div class="total-row">
                    <span>الضريبة (15%):</span>
                    <span>$15.00</span>
                </div>
                <div class="total-row final">
                    <span>الإجمالي:</span>
                    <span>$115.00</span>
                </div>
            </div>
            
            <div class="invoice-actions">
                <button class="btn btn-outline-primary" onclick="downloadInvoice(${invoiceId})">
                    <i class="fas fa-download"></i>
                    تحميل PDF
                </button>
                <button class="btn btn-primary" onclick="closeInvoiceModal(); showPaymentModal(${invoiceId})">
                    <i class="fas fa-credit-card"></i>
                    دفع الآن
                </button>
            </div>
        </div>
    `;
}

function closeInvoiceModal() {
    const modal = document.getElementById('invoiceModal');
    modal.classList.remove('show');
}

function downloadInvoice(invoiceId) {
    // Simulate download
    const link = document.createElement('a');
    link.href = `/invoices/${invoiceId}/download`;
    link.download = `invoice-${invoiceId}.pdf`;
    link.click();
}

function downloadInvoices() {
    // Simulate bulk download
    const link = document.createElement('a');
    link.href = '/invoices/download-all';
    link.download = 'invoices.zip';
    link.click();
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

<?php
// تضمين الفوتر
include_once __DIR__ . '/../../frontend/partials/footer.php';
?>

