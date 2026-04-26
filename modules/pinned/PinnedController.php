<?php

require_once ROOT_PATH . 
'/core/Controller.php';
require_once ROOT_PATH . 
'/core/Auth.php';
require_once ROOT_PATH . 
'/core/Security.php';
require_once ROOT_PATH . 
'/modules/pinned/PinnedModel.php';

class PinnedController extends Controller {
    private $auth;
    private $pdo;
    private $prefix;
    private $pinnedModel;

    public function __construct($pdo, $prefix) {
        parent::__construct();
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->auth = new Auth($pdo, $prefix);
        $this->pinnedModel = new PinnedModel($pdo, $prefix);
    }

    // عرض صفحة تثبيت الموضوع
    public function pinTopic($topicId) {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $user = $this->auth->user();
        
        try {
            // التحقق من وجود الموضوع وملكيته
            $stmt = $this->pdo->prepare("
                SELECT t.*, c.name as category_name 
                FROM `{$this->prefix}topics` t
                LEFT JOIN `{$this->prefix}categories` c ON t.category_id = c.id
                WHERE t.id = ? AND t.author_id = ?
            ");
            $stmt->execute([$topicId, $user['id']]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$topic) {
                $_SESSION['error_message'] = 'الموضوع غير موجود أو ليس لديك صلاحية لتثبيته.';
                header('Location: /topics');
                exit();
            }

            // التحقق من حالة التثبيت الحالية
            $currentPin = $this->pinnedModel->getTopicPinStatus($topicId);

            // جلب أسعار التثبيت
            $pricingSettings = $this->pinnedModel->getPinPricing();

            $this->view('frontend/pinned/pin_topic', [
                'topic' => $topic,
                'currentPin' => $currentPin,
                'pricing' => $pricingSettings,
                'csrf_token' => Security::generateCSRFToken()
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب بيانات الموضوع: ' . $e->getMessage();
            header('Location: /topics');
            exit();
        }
    }

    // معالجة طلب التثبيت
    public function processPinning() {
        if (!$this->auth->check()) {
            echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
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

        $topicId = (int)($_POST['topic_id'] ?? 0);
        $duration = Security::sanitizeInput($_POST['duration'] ?? '');
        $paymentMethod = Security::sanitizeInput($_POST['payment_method'] ?? 'wallet');

        if (!$topicId || empty($duration)) {
            echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
            exit();
        }

        $user = $this->auth->user();
        
        try {
            // التحقق من ملكية الموضوع
            $stmt = $this->pdo->prepare("SELECT `author_id` FROM `{$this->prefix}topics` WHERE `id` = ?");
            $stmt->execute([$topicId]);
            $authorId = $stmt->fetchColumn();

            if ($authorId != $user['id'] && $user['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية لتثبيت هذا الموضوع']);
                exit();
            }

            // حساب السعر والمدة
            $pricingSettings = $this->pinnedModel->getPinPricing();
            $price = 0;
            $durationDays = 0;

            switch ($duration) {
                case '1_month':
                    $price = $pricingSettings['pin_price_1_month'];
                    $durationDays = 30;
                    break;
                case '6_months':
                    $price = $pricingSettings['pin_price_6_months'];
                    $durationDays = 180;
                    break;
                case '1_year':
                    $price = $pricingSettings['pin_price_1_year'];
                    $durationDays = 365;
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'مدة غير صحيحة']);
                    exit();
            }

            // التحقق من الرصيد إذا كان الدفع من المحفظة
            if ($paymentMethod === 'wallet') {
                $stmt = $this->pdo->prepare("SELECT `balance` FROM `{$this->prefix}users` WHERE `id` = ?");
                $stmt->execute([$user['id']]);
                $balance = $stmt->fetchColumn() ?: 0;

                if ($balance < $price) {
                    echo json_encode(['success' => false, 'message' => 'الرصيد غير كافي']);
                    exit();
                }

                // خصم المبلغ من الرصيد
                $stmt = $this->pdo->prepare("
                    UPDATE `{$this->prefix}users` 
                    SET `balance` = `balance` - ? 
                    WHERE `id` = ?
                ");
                $stmt->execute([$price, $user['id']]);

                // إضافة معاملة المحفظة
                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}wallet_transactions` 
                    (`user_id`, `type`, `amount`, `description`, `reference_id`, `reference_type`) 
                    VALUES (?, 'payment', ?, ?, ?, 'pinned_topic')
                ");
                $stmt->execute([$user['id'], -$price, "تثبيت موضوع لمدة {$duration}", $topicId]);
            }

            // إنهاء أي تثبيت سابق للموضوع
            $this->pinnedModel->expirePinnedTopicByTopicId($topicId);

            // إضافة التثبيت الجديد
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
            $this->pinnedModel->createPinnedTopic([
                'topic_id' => $topicId,
                'user_id' => $user['id'],
                'duration' => $duration,
                'price' => $price,
                'payment_method' => $paymentMethod,
                'expires_at' => $expiresAt,
                'status' => 'active'
            ]);

            // تحديث حالة الموضوع
            $this->pinnedModel->updateTopicPinStatus($topicId, 'active', $expiresAt);

            echo json_encode(['success' => true, 'message' => 'تم تثبيت الموضوع بنجاح']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في معالجة التثبيت: ' . $e->getMessage()]);
        }
        exit();
    }

    // عرض المواضيع المثبتة (الشريط المتحرك)
    public function getPinnedTopics() {
        try {
            $pinnedTopics = $this->pinnedModel->getPinnedTopics();
            
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode($pinnedTopics);
                exit();
            }
            
            return $pinnedTopics;
        } catch (PDOException $e) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([]);
                exit();
            }
            return [];
        }
    }

    // إعدادات أسعار التثبيت (للمدير)
    public function pinPricingSettings() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error_message'] = 'خطأ في رمز CSRF';
                header('Location: /admin/pinned/settings');
                exit();
            }

            $settings = [
                'pin_price_1_month' => (float)($_POST['pin_price_1_month'] ?? 10),
                'pin_price_6_months' => (float)($_POST['pin_price_6_months'] ?? 50),
                'pin_price_1_year' => (float)($_POST['pin_price_1_year'] ?? 100),
                'enable_topic_pinning' => isset($_POST['enable_topic_pinning']) ? 1 : 0,
                'max_pinned_topics' => (int)($_POST['max_pinned_topics'] ?? 20),
                'admin_free_pinning' => isset($_POST['admin_free_pinning']) ? 1 : 0,
                'show_pinned_ticker' => isset($_POST['show_pinned_ticker']) ? 1 : 0,
                'ticker_speed' => (int)($_POST['ticker_speed'] ?? 50),
                'pin_discount_6_months' => (float)($_POST['pin_discount_6_months'] ?? 10),
                'pin_discount_1_year' => (float)($_POST['pin_discount_1_year'] ?? 20)
            ];

            try {
                $this->pinnedModel->updatePinPricing($settings);
                $_SESSION['success_message'] = 'تم حفظ إعدادات التثبيت بنجاح.';
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في حفظ الإعدادات: ' . $e->getMessage();
            }

            header('Location: /admin/pinned/settings');
            exit();
        }

        $currentSettings = $this->pinnedModel->getPinPricing();
        $this->view('admin/pinned/settings', [
            'settings' => $currentSettings,
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    // إدارة المواضيع المثبتة (للمدير)
    public function managePinnedTopics() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;

            // جلب المواضيع المثبتة
            $pinnedTopics = $this->pinnedModel->getAllPinnedTopics($limit, $offset);

            // عدد المواضيع المثبتة
            $totalCount = $this->pinnedModel->countAllPinnedTopics();
            $totalPages = ceil($totalCount / $limit);

            // إحصائيات
            $stats = $this->pinnedModel->getPinningStats();

            $this->view('admin/pinned/manage', [
                'pinnedTopics' => $pinnedTopics,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'stats' => $stats,
                'csrf_token' => Security::generateCSRFToken()
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب البيانات: ' . $e->getMessage();
            $this->view('admin/pinned/manage', [
                'pinnedTopics' => [],
                'currentPage' => 1,
                'totalPages' => 1,
                'stats' => [],
                'csrf_token' => Security::generateCSRFToken()
            ]);
        }
    }

    // إنهاء تثبيت موضوع (للمدير)
    public function unpinTopic() {
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

        $pinId = (int)($_POST['pin_id'] ?? 0);

        if (!$pinId) {
            echo json_encode(['success' => false, 'message' => 'معرف التثبيت مطلوب']);
            exit();
        }

        try {
            // الحصول على معرف الموضوع
            $pinData = $this->pinnedModel->getPinById($pinId);

            if (!$pinData) {
                echo json_encode(['success' => false, 'message' => 'التثبيت غير موجود']);
                exit();
            }

            // إنهاء التثبيت
            $this->pinnedModel->expirePinnedTopic($pinId);

            // تحديث الموضوع
            $this->pinnedModel->updateTopicPinStatus($pinData['topic_id'], 'expired');

            echo json_encode(['success' => true, 'message' => 'تم إنهاء التثبيت بنجاح']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في إنهاء التثبيت: ' . $e->getMessage()]);
        }
        exit();
    }

    // تثبيت مجاني للمدير
    public function adminFreePinning() {
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

        $topicId = (int)($_POST['topic_id'] ?? 0);
        $duration = Security::sanitizeInput($_POST['duration'] ?? '');

        if (!$topicId || empty($duration)) {
            echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
            exit();
        }

        try {
            $user = $this->auth->user();
            $durationDays = 0;

            switch ($duration) {
                case '1_month':
                    $durationDays = 30;
                    break;
                case '6_months':
                    $durationDays = 180;
                    break;
                case '1_year':
                    $durationDays = 365;
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'مدة غير صحيحة']);
                    exit();
            }

            // إنهاء أي تثبيت سابق
            $this->pinnedModel->expirePinnedTopicByTopicId($topicId);

            // إضافة التثبيت المجاني
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
            $this->pinnedModel->createPinnedTopic([
                'topic_id' => $topicId,
                'user_id' => $user['id'],
                'duration' => $duration,
                'price' => 0,
                'payment_method' => 'admin_free',
                'expires_at' => $expiresAt,
                'status' => 'active'
            ]);

            // تحديث الموضوع
            $this->pinnedModel->updateTopicPinStatus($topicId, 'active', $expiresAt);

            echo json_encode(['success' => true, 'message' => 'تم تثبيت الموضوع مجاناً بنجاح']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في معالجة التثبيت المجاني: ' . $e->getMessage()]);
        }
        exit();
    }

    // جلب إعدادات أسعار التثبيت (دالة مساعدة)
    private function getPinPricing() {
        return $this->pinnedModel->getPinPricing();
    }

    // جلب إحصائيات التثبيت (دالة مساعدة)
    private function getPinningStats() {
        return $this->pinnedModel->getPinningStats();
    }

    // تنظيف المواضيع المنتهية الصلاحية (Cron Job)
    public function cleanupExpiredPins() {
        return $this->pinnedModel->cleanupExpiredPins();
    }
}

?>

