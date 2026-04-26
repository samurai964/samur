<?php

require_once ROOT_PATH . 
'/core/Controller.php';
require_once ROOT_PATH . 
'/core/Auth.php';
require_once ROOT_PATH . 
'/core/Security.php';
require_once ROOT_PATH . 
'/modules/payment/PaymentModel.php';

class PaymentController extends Controller {
    private $auth;
    private $pdo;
    private $prefix;
    private $paymentModel;

    public function __construct($pdo, $prefix) {
        parent::__construct();
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->auth = new Auth($pdo, $prefix);
        $this->paymentModel = new PaymentModel($pdo, $prefix);
    }

    // عرض صفحة المحفظة
    public function wallet() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $user = $this->auth->user();
        
        try {
            $balance = $this->paymentModel->getUserBalance($user['id']);
            $transactions = $this->paymentModel->getWalletTransactions($user['id']);
            $cards = $this->paymentModel->getPaymentCards($user['id']);

            $this->view('frontend/payment/wallet', [
                'balance' => $balance,
                'transactions' => $transactions,
                'cards' => $cards,
                'csrf_token' => Security::generateCSRFToken()
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب بيانات المحفظة: ' . $e->getMessage();
            $this->view('frontend/payment/wallet', ['balance' => 0, 'transactions' => [], 'cards' => [], 'csrf_token' => Security::generateCSRFToken()]);
        }
    }

    // شحن الرصيد
    public function deposit() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error_message'] = 'خطأ في رمز CSRF';
                header('Location: /wallet');
                exit();
            }

            $amount = (float)Security::sanitizeInput($_POST['amount'] ?? 0);
            $paymentMethod = Security::sanitizeInput($_POST['payment_method'] ?? '');
            $cardId = (int)Security::sanitizeInput($_POST['card_id'] ?? 0);

            if (!$amount || $amount < 1) {
                $_SESSION['error_message'] = 'يجب أن يكون المبلغ أكبر من 1.';
                header('Location: /wallet');
                exit();
            }

            if (empty($paymentMethod)) {
                $_SESSION['error_message'] = 'يجب اختيار طريقة الدفع.';
                header('Location: /wallet');
                exit();
            }

            try {
                $user = $this->auth->user();
                
                $result = $this->paymentModel->processDeposit($user['id'], $amount, $paymentMethod, $cardId);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }

                header('Location: /wallet');
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في شحن الرصيد: ' . $e->getMessage();
                header('Location: /wallet');
                exit();
            }
        }

        $this->view('frontend/payment/deposit', ['csrf_token' => Security::generateCSRFToken()]);
    }

    // سحب الرصيد
    public function withdraw() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error_message'] = 'خطأ في رمز CSRF';
                header('Location: /wallet');
                exit();
            }

            $amount = (float)Security::sanitizeInput($_POST['amount'] ?? 0);
            $withdrawMethod = Security::sanitizeInput($_POST['withdraw_method'] ?? '');
            $accountDetails = Security::sanitizeInput($_POST['account_details'] ?? '');

            if (!$amount || $amount < 10) {
                $_SESSION['error_message'] = 'الحد الأدنى للسحب هو 10.';
                header('Location: /wallet');
                exit();
            }

            if (empty($withdrawMethod) || empty($accountDetails)) {
                $_SESSION['error_message'] = 'جميع الحقول مطلوبة.';
                header('Location: /wallet');
                exit();
            }

            try {
                $user = $this->auth->user();
                
                $result = $this->paymentModel->processWithdrawal($user['id'], $amount, $withdrawMethod, $accountDetails);

                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
                header('Location: /wallet');
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في طلب السحب: ' . $e->getMessage();
                header('Location: /wallet');
                exit();
            }
        }

        $this->view('frontend/payment/withdraw', ['csrf_token' => Security::generateCSRFToken()]);
    }

    // إضافة بطاقة دفع
    public function addCard() {
        if (!$this->auth->check()) {
            echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'خطأ في رمز CSRF']);
                exit();
            }

            $cardNumber = Security::sanitizeInput($_POST['card_number'] ?? '');
            $expiryMonth = Security::sanitizeInput($_POST['expiry_month'] ?? '');
            $expiryYear = Security::sanitizeInput($_POST['expiry_year'] ?? '');
            $cvv = Security::sanitizeInput($_POST['cvv'] ?? '');
            $cardholderName = Security::sanitizeInput($_POST['cardholder_name'] ?? '');
            $isDefault = isset($_POST['is_default']) ? 1 : 0;

            if (empty($cardNumber) || empty($expiryMonth) || empty($expiryYear) || empty($cvv) || empty($cardholderName)) {
                echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
                exit();
            }

            $user = $this->auth->user();
            $result = $this->paymentModel->addPaymentCard($user['id'], $cardNumber, $expiryMonth, $expiryYear, $cvv, $cardholderName, $isDefault);

            echo json_encode($result);
        }
        exit();
    }

    // حذف بطاقة دفع
    public function deleteCard() {
        if (!$this->auth->check()) {
            echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'خطأ في رمز CSRF']);
                exit();
            }

            $cardId = (int)Security::sanitizeInput($_POST['card_id'] ?? 0);

            if (!$cardId) {
                echo json_encode(['success' => false, 'message' => 'معرف البطاقة مطلوب']);
                exit();
            }

            $user = $this->auth->user();
            $result = $this->paymentModel->deletePaymentCard($cardId, $user['id']);

            echo json_encode($result);
        }
        exit();
    }

    // تعيين بطاقة كافتراضية
    public function setDefaultCard() {
        if (!$this->auth->check()) {
            echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'خطأ في رمز CSRF']);
                exit();
            }

            $cardId = (int)Security::sanitizeInput($_POST['card_id'] ?? 0);

            if (!$cardId) {
                echo json_encode(['success' => false, 'message' => 'معرف البطاقة مطلوب']);
                exit();
            }

            $user = $this->auth->user();
            $result = $this->paymentModel->setDefaultPaymentCard($cardId, $user['id']);

            echo json_encode($result);
        }
        exit();
    }

    // عرض لوحة تحكم المدفوعات للمدير
    public function adminDashboard() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $transactions = $this->paymentModel->getAllPaymentTransactions($limit, $offset);
            $totalCount = $this->paymentModel->countAllPaymentTransactions();
            $totalPages = ceil($totalCount / $limit);

            $withdrawalRequests = $this->paymentModel->getPendingWithdrawalRequests();
            $stats = $this->paymentModel->getPaymentStats();

            $this->view('admin/payment/dashboard', [
                'transactions' => $transactions,
                'withdrawalRequests' => $withdrawalRequests,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'stats' => $stats,
                'csrf_token' => Security::generateCSRFToken()
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب البيانات: ' . $e->getMessage();
            $this->view('admin/payment/dashboard', [
                'transactions' => [],
                'withdrawalRequests' => [],
                'currentPage' => 1,
                'totalPages' => 1,
                'stats' => [],
                'csrf_token' => Security::generateCSRFToken()
            ]);
        }
    }

    // معالجة طلب سحب (للمدير)
    public function processWithdrawalRequest() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'غير مصرح']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
            exit();
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'خطأ في رمز CSRF']);
            exit();
        }

        $requestId = (int)Security::sanitizeInput($_POST['request_id'] ?? 0);
        $status = Security::sanitizeInput($_POST['status'] ?? ''); // 'approved' or 'rejected'

        if (!$requestId || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'معرف الطلب والحالة مطلوبان.']);
            exit();
        }

        try {
            $result = $this->paymentModel->updateWithdrawalRequestStatus($requestId, $status);
            echo json_encode($result);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في معالجة الطلب: ' . $e->getMessage()]);
        }
        exit();
    }

    // إعدادات الدفع (للمدير)
    public function paymentSettings() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error_message'] = 'خطأ في رمز CSRF';
                header('Location: /admin/payment/settings');
                exit();
            }

            $settings = [
                'min_deposit_amount' => (float)($_POST['min_deposit_amount'] ?? 1),
                'min_withdrawal_amount' => (float)($_POST['min_withdrawal_amount'] ?? 10),
                'paypal_enabled' => isset($_POST['paypal_enabled']) ? 1 : 0,
                'stripe_enabled' => isset($_POST['stripe_enabled']) ? 1 : 0,
                'bank_transfer_enabled' => isset($_POST['bank_transfer_enabled']) ? 1 : 0,
                'paypal_client_id' => Security::sanitizeInput($_POST['paypal_client_id'] ?? ''),
                'paypal_client_secret' => Security::sanitizeInput($_POST['paypal_client_secret'] ?? ''),
                'stripe_secret_key' => Security::sanitizeInput($_POST['stripe_secret_key'] ?? ''),
                'stripe_publishable_key' => Security::sanitizeInput($_POST['stripe_publishable_key'] ?? ''),
                'bank_transfer_details' => Security::sanitizeInput($_POST['bank_transfer_details'] ?? ''),
            ];

            try {
                $this->paymentModel->updatePaymentSettings($settings);
                $_SESSION['success_message'] = 'تم حفظ إعدادات الدفع بنجاح.';
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في حفظ الإعدادات: ' . $e->getMessage();
            }

            header('Location: /admin/payment/settings');
            exit();
        }

        $currentSettings = $this->paymentModel->getPaymentSettings();
        $this->view('admin/payment/settings', [
            'settings' => $currentSettings,
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }
}

?>

