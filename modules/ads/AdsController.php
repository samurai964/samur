<?php

require_once ROOT_PATH . 
'/core/Controller.php';
require_once ROOT_PATH . 
'/core/Auth.php';
require_once ROOT_PATH . 
'/core/Security.php';
require_once ROOT_PATH . 
'/modules/ads/AdsModel.php';

class AdsController extends Controller {
    private $auth;
    private $pdo;
    private $prefix;
    private $adsModel;

    public function __construct($pdo, $prefix) {
        parent::__construct();
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->auth = new Auth($pdo, $prefix);
        $this->adsModel = new AdsModel($pdo, $prefix);
    }

    // عرض لوحة تحكم الإعلانات للمدير
    public function adminDashboard() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $ads = $this->adsModel->getAds([], $limit, $offset);
            $totalCount = $this->adsModel->countAds();
            $totalPages = ceil($totalCount / $limit);

            $stats = $this->adsModel->getAdStats();

            $this->view('admin/ads/dashboard', [
                'ads' => $ads,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'stats' => $stats
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب البيانات: ' . $e->getMessage();
            $this->view('admin/ads/dashboard', [
                'ads' => [],
                'currentPage' => 1,
                'totalPages' => 1,
                'stats' => []
            ]);
        }
    }

    // عرض نموذج إنشاء إعلان جديد
    public function createAd() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }
        $this->view('admin/ads/create');
    }

    // معالجة إنشاء إعلان جديد
    public function storeAd() {
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

        $title = Security::sanitizeInput($_POST['title'] ?? '');
        $description = Security::sanitizeInput($_POST['description'] ?? '');
        $target_url = Security::sanitizeInput($_POST['target_url'] ?? '');
        $image_url = Security::sanitizeInput($_POST['image_url'] ?? '');
        $budget = (float)($_POST['budget'] ?? 0);
        $start_date = Security::sanitizeInput($_POST['start_date'] ?? '');
        $end_date = Security::sanitizeInput($_POST['end_date'] ?? '');
        $ad_type = Security::sanitizeInput($_POST['ad_type'] ?? 'banner');
        $status = Security::sanitizeInput($_POST['status'] ?? 'pending');

        if (empty($title) || empty($target_url) || empty($image_url) || $budget <= 0 || empty($start_date) || empty($end_date)) {
            echo json_encode(['success' => false, 'message' => 'جميع الحقول المطلوبة غير مكتملة.']);
            exit();
        }

        try {
            $adId = $this->adsModel->createAd([
                'title' => $title,
                'description' => $description,
                'target_url' => $target_url,
                'image_url' => $image_url,
                'budget' => $budget,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'ad_type' => $ad_type,
                'status' => $status
            ]);

            if ($adId) {
                echo json_encode(['success' => true, 'message' => 'تم إنشاء الإعلان بنجاح.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'فشل في إنشاء الإعلان.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        }
        exit();
    }

    // عرض نموذج تعديل إعلان
    public function editAd($adId) {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        try {
            $ad = $this->adsModel->getAdById((int)$adId);
            if (!$ad) {
                $_SESSION['error_message'] = 'الإعلان غير موجود.';
                header('Location: /admin/ads');
                exit();
            }
            $this->view('admin/ads/edit', ['ad' => $ad]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب بيانات الإعلان: ' . $e->getMessage();
            header('Location: /admin/ads');
            exit();
        }
    }

    // معالجة تعديل إعلان
    public function updateAd() {
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

        $adId = (int)($_POST['ad_id'] ?? 0);
        $title = Security::sanitizeInput($_POST['title'] ?? '');
        $description = Security::sanitizeInput($_POST['description'] ?? '');
        $target_url = Security::sanitizeInput($_POST['target_url'] ?? '');
        $image_url = Security::sanitizeInput($_POST['image_url'] ?? '');
        $budget = (float)($_POST['budget'] ?? 0);
        $start_date = Security::sanitizeInput($_POST['start_date'] ?? '');
        $end_date = Security::sanitizeInput($_POST['end_date'] ?? '');
        $ad_type = Security::sanitizeInput($_POST['ad_type'] ?? 'banner');
        $status = Security::sanitizeInput($_POST['status'] ?? 'pending');

        if (!$adId || empty($title) || empty($target_url) || empty($image_url) || $budget <= 0 || empty($start_date) || empty($end_date)) {
            echo json_encode(['success' => false, 'message' => 'جميع الحقول المطلوبة غير مكتملة.']);
            exit();
        }

        try {
            $updated = $this->adsModel->updateAd($adId, [
                'title' => $title,
                'description' => $description,
                'target_url' => $target_url,
                'image_url' => $image_url,
                'budget' => $budget,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'ad_type' => $ad_type,
                'status' => $status
            ]);

            if ($updated) {
                echo json_encode(['success' => true, 'message' => 'تم تحديث الإعلان بنجاح.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'فشل في تحديث الإعلان.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        }
        exit();
    }

    // معالجة حذف إعلان
    public function deleteAd() {
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

        $adId = (int)($_POST['ad_id'] ?? 0);

        if (!$adId) {
            echo json_encode(['success' => false, 'message' => 'معرف الإعلان مطلوب.']);
            exit();
        }

        try {
            $deleted = $this->adsModel->deleteAd($adId);
            if ($deleted) {
                echo json_encode(['success' => true, 'message' => 'تم حذف الإعلان بنجاح.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'فشل في حذف الإعلان.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        }
        exit();
    }

    // عرض الإعلانات في الواجهة الأمامية (للمستخدمين)
    public function getActiveAds() {
        try {
            $ads = $this->adsModel->getActiveAds();
            header('Content-Type: application/json');
            echo json_encode($ads);
            exit();
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit();
        }
    }

    // تسجيل نقرة على إعلان
    public function recordClick() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
            exit();
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'خطأ في رمز CSRF']);
            exit();
        }

        $adId = (int)($_POST['ad_id'] ?? 0);
        $userId = $this->auth->check() ? $this->auth->user()['id'] : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        if (!$adId) {
            echo json_encode(['success' => false, 'message' => 'معرف الإعلان مطلوب.']);
            exit();
        }

        try {
            $this->adsModel->recordClick($adId, $userId, $ipAddress);
            echo json_encode(['success' => true, 'message' => 'تم تسجيل النقرة.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في تسجيل النقرة: ' . $e->getMessage()]);
        }
        exit();
    }

    // تسجيل ظهور إعلان (Impression)
    public function recordImpression() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
            exit();
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'خطأ في رمز CSRF']);
            exit();
        }

        $adId = (int)($_POST['ad_id'] ?? 0);
        $userId = $this->auth->check() ? $this->auth->user()['id'] : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        if (!$adId) {
            echo json_encode(['success' => false, 'message' => 'معرف الإعلان مطلوب.']);
            exit();
        }

        try {
            $this->adsModel->recordImpression($adId, $userId, $ipAddress);
            echo json_encode(['success' => true, 'message' => 'تم تسجيل الظهور.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في تسجيل الظهور: ' . $e->getMessage()]);
        }
        exit();
    }
}

?>

