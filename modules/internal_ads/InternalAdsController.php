<?php

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/core/Security.php';
require_once ROOT_PATH . '/modules/internal_ads/InternalAdsModel.php';

class InternalAdsController extends Controller {
    private $auth;
    private $pdo;
    private $prefix;
    private $internalAdsModel;

    public function __construct($pdo, $prefix) {
        parent::__construct();
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->auth = new Auth($pdo, $prefix);
        $this->internalAdsModel = new InternalAdsModel($pdo, $prefix);
    }

    // عرض لوحة تحكم الإعلانات الداخلية (للمدير)
    public function adminDashboard() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $ads = $this->internalAdsModel->getAllInternalAds($limit, $offset);
            $totalCount = $this->internalAdsModel->countAllInternalAds();
            $totalPages = ceil($totalCount / $limit);

            $stats = $this->internalAdsModel->getInternalAdsStats();

            $this->view('admin/internal_ads/dashboard', [
                'ads' => $ads,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'stats' => $stats,
                'csrf_token' => Security::generateCSRFToken()
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب البيانات: ' . $e->getMessage();
            $this->view('admin/internal_ads/dashboard', [
                'ads' => [],
                'currentPage' => 1,
                'totalPages' => 1,
                'stats' => [],
                'csrf_token' => Security::generateCSRFToken()
            ]);
        }
    }

    // إضافة إعلان داخلي جديد
    public function addInternalAd() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error_message'] = 'خطأ في رمز CSRF';
                header('Location: /admin/internal-ads');
                exit();
            }

            $title = Security::sanitizeInput($_POST['title'] ?? '');
            $content = Security::sanitizeInput($_POST['content'] ?? '');
            $imageUrl = Security::sanitizeInput($_POST['image_url'] ?? '');
            $linkUrl = Security::sanitizeInput($_POST['link_url'] ?? '');
            $position = Security::sanitizeInput($_POST['position'] ?? '');
            $pages = $_POST['pages'] ?? [];
            $startDate = Security::sanitizeInput($_POST['start_date'] ?? '');
            $endDate = Security::sanitizeInput($_POST['end_date'] ?? '');
            $priority = (int)Security::sanitizeInput($_POST['priority'] ?? 1);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($title) || empty($content) || empty($position)) {
                $_SESSION['error_message'] = 'العنوان والمحتوى والموضع مطلوبة.';
                header('Location: /admin/internal-ads/add');
                exit();
            }

            try {
                $result = $this->internalAdsModel->addInternalAd(
                    $title, $content, $imageUrl, $linkUrl, $position, 
                    $pages, $startDate, $endDate, $priority, $isActive
                );

                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                    header('Location: /admin/internal-ads');
                } else {
                    $_SESSION['error_message'] = $result['message'];
                    header('Location: /admin/internal-ads/add');
                }
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في إضافة الإعلان: ' . $e->getMessage();
                header('Location: /admin/internal-ads/add');
                exit();
            }
        }

        $this->view('admin/internal_ads/add', [
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    // تعديل إعلان داخلي
    public function editInternalAd() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        $adId = (int)($_GET['id'] ?? 0);
        if (!$adId) {
            $_SESSION['error_message'] = 'معرف الإعلان مطلوب.';
            header('Location: /admin/internal-ads');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error_message'] = 'خطأ في رمز CSRF';
                header('Location: /admin/internal-ads');
                exit();
            }

            $title = Security::sanitizeInput($_POST['title'] ?? '');
            $content = Security::sanitizeInput($_POST['content'] ?? '');
            $imageUrl = Security::sanitizeInput($_POST['image_url'] ?? '');
            $linkUrl = Security::sanitizeInput($_POST['link_url'] ?? '');
            $position = Security::sanitizeInput($_POST['position'] ?? '');
            $pages = $_POST['pages'] ?? [];
            $startDate = Security::sanitizeInput($_POST['start_date'] ?? '');
            $endDate = Security::sanitizeInput($_POST['end_date'] ?? '');
            $priority = (int)Security::sanitizeInput($_POST['priority'] ?? 1);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($title) || empty($content) || empty($position)) {
                $_SESSION['error_message'] = 'العنوان والمحتوى والموضع مطلوبة.';
                header('Location: /admin/internal-ads/edit?id=' . $adId);
                exit();
            }

            try {
                $result = $this->internalAdsModel->updateInternalAd(
                    $adId, $title, $content, $imageUrl, $linkUrl, $position, 
                    $pages, $startDate, $endDate, $priority, $isActive
                );

                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                    header('Location: /admin/internal-ads');
                } else {
                    $_SESSION['error_message'] = $result['message'];
                    header('Location: /admin/internal-ads/edit?id=' . $adId);
                }
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في تعديل الإعلان: ' . $e->getMessage();
                header('Location: /admin/internal-ads/edit?id=' . $adId);
                exit();
            }
        }

        try {
            $ad = $this->internalAdsModel->getInternalAdById($adId);
            if (!$ad) {
                $_SESSION['error_message'] = 'الإعلان غير موجود.';
                header('Location: /admin/internal-ads');
                exit();
            }

            $this->view('admin/internal_ads/edit', [
                'ad' => $ad,
                'csrf_token' => Security::generateCSRFToken()
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب بيانات الإعلان: ' . $e->getMessage();
            header('Location: /admin/internal-ads');
            exit();
        }
    }

    // حذف إعلان داخلي
    public function deleteInternalAd() {
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

        $adId = (int)Security::sanitizeInput($_POST['ad_id'] ?? 0);

        if (!$adId) {
            echo json_encode(['success' => false, 'message' => 'معرف الإعلان مطلوب.']);
            exit();
        }

        try {
            $result = $this->internalAdsModel->deleteInternalAd($adId);
            echo json_encode($result);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في حذف الإعلان: ' . $e->getMessage()]);
        }
        exit();
    }

    // تغيير حالة الإعلان (تفعيل/إلغاء تفعيل)
    public function toggleInternalAdStatus() {
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

        $adId = (int)Security::sanitizeInput($_POST['ad_id'] ?? 0);

        if (!$adId) {
            echo json_encode(['success' => false, 'message' => 'معرف الإعلان مطلوب.']);
            exit();
        }

        try {
            $result = $this->internalAdsModel->toggleInternalAdStatus($adId);
            echo json_encode($result);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في تغيير حالة الإعلان: ' . $e->getMessage()]);
        }
        exit();
    }

    // جلب الإعلانات للعرض في الصفحات
    public function getAdsForPage($page, $position) {
        try {
            return $this->internalAdsModel->getActiveAdsForPage($page, $position);
        } catch (PDOException $e) {
            return [];
        }
    }

    // تسجيل مشاهدة إعلان
    public function recordAdView() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
            exit();
        }

        $adId = (int)Security::sanitizeInput($_POST['ad_id'] ?? 0);

        if (!$adId) {
            echo json_encode(['success' => false, 'message' => 'معرف الإعلان مطلوب.']);
            exit();
        }

        try {
            $result = $this->internalAdsModel->recordAdView($adId);
            echo json_encode($result);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في تسجيل المشاهدة']);
        }
        exit();
    }

    // تسجيل نقرة إعلان
    public function recordAdClick() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
            exit();
        }

        $adId = (int)Security::sanitizeInput($_POST['ad_id'] ?? 0);

        if (!$adId) {
            echo json_encode(['success' => false, 'message' => 'معرف الإعلان مطلوب.']);
            exit();
        }

        try {
            $result = $this->internalAdsModel->recordAdClick($adId);
            echo json_encode($result);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في تسجيل النقرة']);
        }
        exit();
    }
}

?>

