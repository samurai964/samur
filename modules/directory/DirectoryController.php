<?php
/**
 * Final Max CMS - Directory Controller
 * متحكم دليل المواقع
 */

require_once '../core/Controller.php';
require_once '../core/Auth.php';
require_once '../core/Security.php';
require_once 'DirectoryModel.php';

class DirectoryController extends Controller {
    private $directoryModel;
    
    public function __construct() {
        parent::__construct();
        $this->directoryModel = new DirectoryModel();
    }
    
    /**
     * عرض الصفحة الرئيسية لدليل المواقع
     */
    public function index() {
        $page = (int)($_GET['page'] ?? 1);
        $category_id = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'latest';
        $limit = 20;
        
        try {
            // الحصول على المواقع
            $websites = $this->directoryModel->getWebsites([
                'page' => $page,
                'limit' => $limit,
                'category_id' => $category_id,
                'search' => $search,
                'sort' => $sort,
                'status' => 'approved'
            ]);
            
            // الحصول على الفئات
            $categories = $this->directoryModel->getCategories();
            
            // الحصول على المواقع المميزة
            $featured_websites = $this->directoryModel->getFeaturedWebsites(6);
            
            // الحصول على إحصائيات
            $stats = $this->directoryModel->getStats();
            
            $page_title = 'دليل المواقع';
            include '../templates/frontend/directory/index.php';
            
        } catch (Exception $e) {
            error_log("خطأ في دليل المواقع: " . $e->getMessage());
            $this->setError('حدث خطأ في تحميل دليل المواقع');
            include '../templates/frontend/directory/index.php';
        }
    }
    
    /**
     * عرض تفاصيل موقع
     */
    public function view($slug) {
        if (empty($slug)) {
            redirect('/directory');
            return;
        }
        
        try {
            $website = $this->directoryModel->getWebsiteBySlug($slug);
            
            if (!$website || $website['status'] !== 'approved') {
                $this->setError('الموقع غير موجود أو غير متاح');
                redirect('/directory');
                return;
            }
            
            // زيادة عدد المشاهدات
            $this->directoryModel->incrementViews($website['id']);
            
            // الحصول على المواقع ذات الصلة
            $related_websites = $this->directoryModel->getRelatedWebsites($website['id'], $website['category_id'], 6);
            
            // الحصول على التقييمات
            $reviews = $this->directoryModel->getWebsiteReviews($website['id'], 1, 10);
            
            // التحقق من تقييم المستخدم الحالي
            $user_review = null;
            if (is_logged_in()) {
                $user_review = $this->directoryModel->getUserReview($website['id'], $_SESSION['user_id']);
            }
            
            $page_title = $website['title'] . ' - دليل المواقع';
            include '../templates/frontend/directory/view.php';
            
        } catch (Exception $e) {
            error_log("خطأ في عرض الموقع: " . $e->getMessage());
            $this->setError('حدث خطأ في تحميل الموقع');
            redirect('/directory');
        }
    }
    
    /**
     * إضافة موقع جديد
     */
    public function add() {
        if (!is_logged_in()) {
            $this->setError('يجب تسجيل الدخول لإضافة موقع');
            redirect('/login?redirect=' . urlencode('/directory/add'));
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAddWebsite();
        } else {
            $this->showAddForm();
        }
    }
    
    /**
     * معالجة إضافة موقع جديد
     */
    private function handleAddWebsite() {
        // التحقق من CSRF
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->setError('خطأ في التحقق من الأمان');
            $this->showAddForm();
            return;
        }
        
        // التحقق من Rate Limiting
        $user_id = $_SESSION['user_id'];
        if (is_rate_limited('add_website', $user_id, 5, 3600)) { // 5 مواقع في الساعة
            $this->setError('تم تجاوز عدد المواقع المسموح إضافتها في الساعة الواحدة');
            $this->showAddForm();
            return;
        }
        
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'url' => trim($_POST['url'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'tags' => trim($_POST['tags'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'language' => $_POST['language'] ?? 'ar',
            'is_free' => isset($_POST['is_free']) ? 1 : 0
        ];
        
        // التحقق من المدخلات
        $errors = $this->validateWebsiteData($data);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setError($error);
            }
            $this->showAddForm();
            return;
        }
        
        try {
            // التحقق من عدم وجود الموقع مسبقاً
            if ($this->directoryModel->websiteExists($data['url'])) {
                $this->setError('هذا الموقع موجود بالفعل في الدليل');
                $this->showAddForm();
                return;
            }
            
            // إنشاء slug
            $data['slug'] = $this->directoryModel->generateSlug($data['title']);
            $data['user_id'] = $user_id;
            $data['status'] = get_setting('directory_auto_approve', false) ? 'approved' : 'pending';
            
            // معالجة الصورة إذا تم رفعها
            if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
                $screenshot = $this->handleScreenshotUpload($_FILES['screenshot']);
                if ($screenshot) {
                    $data['screenshot'] = $screenshot;
                }
            }
            
            $website_id = $this->directoryModel->addWebsite($data);
            
            if ($website_id) {
                record_rate_limit('add_website', $user_id);
                
                // إضافة نقاط للمستخدم
                $points = get_setting('directory_add_points', 10);
                if ($points > 0) {
                    add_user_points($user_id, $points, 'directory_add', 'إضافة موقع في الدليل');
                }
                
                // تسجيل النشاط
                log_activity($user_id, 'directory_add', 'إضافة موقع جديد في الدليل', [
                    'website_id' => $website_id,
                    'title' => $data['title'],
                    'url' => $data['url']
                ]);
                
                if ($data['status'] === 'approved') {
                    $this->setSuccess('تم إضافة موقعك بنجاح وهو متاح الآن في الدليل');
                } else {
                    $this->setSuccess('تم إرسال موقعك للمراجعة. سيتم نشره بعد الموافقة عليه');
                }
                
                redirect('/directory');
            } else {
                $this->setError('حدث خطأ في إضافة الموقع');
                $this->showAddForm();
            }
            
        } catch (Exception $e) {
            error_log("خطأ في إضافة موقع: " . $e->getMessage());
            $this->setError('حدث خطأ في النظام. حاول مرة أخرى.');
            $this->showAddForm();
        }
    }
    
    /**
     * التحقق من صحة بيانات الموقع
     */
    private function validateWebsiteData($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'عنوان الموقع مطلوب';
        } elseif (strlen($data['title']) < 3) {
            $errors[] = 'عنوان الموقع يجب أن يكون 3 أحرف على الأقل';
        }
        
        if (empty($data['url'])) {
            $errors[] = 'رابط الموقع مطلوب';
        } elseif (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'رابط الموقع غير صحيح';
        }
        
        if (empty($data['description'])) {
            $errors[] = 'وصف الموقع مطلوب';
        } elseif (strlen($data['description']) < 20) {
            $errors[] = 'وصف الموقع يجب أن يكون 20 حرف على الأقل';
        }
        
        if (empty($data['category_id'])) {
            $errors[] = 'فئة الموقع مطلوبة';
        }
        
        if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد الإلكتروني غير صحيح';
        }
        
        return $errors;
    }
    
    /**
     * معالجة رفع صورة الموقع
     */
    private function handleScreenshotUpload($file) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $this->setError('نوع الصورة غير مدعوم. استخدم JPG, PNG, GIF أو WebP');
            return false;
        }
        
        if ($file['size'] > $max_size) {
            $this->setError('حجم الصورة كبير جداً. الحد الأقصى 5MB');
            return false;
        }
        
        $upload_dir = '../assets/uploads/directory/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('website_') . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return 'directory/' . $filename;
        }
        
        return false;
    }
    
    /**
     * عرض نموذج إضافة موقع
     */
    private function showAddForm() {
        $categories = $this->directoryModel->getCategories();
        $page_title = 'إضافة موقع جديد';
        include '../templates/frontend/directory/add.php';
    }
    
    /**
     * إضافة تقييم للموقع
     */
    public function review($website_id) {
        if (!is_logged_in()) {
            $this->sendJsonResponse(['success' => false, 'message' => 'يجب تسجيل الدخول']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'طريقة غير مدعومة']);
            return;
        }
        
        // التحقق من CSRF
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->sendJsonResponse(['success' => false, 'message' => 'خطأ في التحقق من الأمان']);
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        
        if ($rating < 1 || $rating > 5) {
            $this->sendJsonResponse(['success' => false, 'message' => 'التقييم يجب أن يكون بين 1 و 5']);
            return;
        }
        
        try {
            // التحقق من وجود الموقع
            $website = $this->directoryModel->getWebsiteById($website_id);
            if (!$website || $website['status'] !== 'approved') {
                $this->sendJsonResponse(['success' => false, 'message' => 'الموقع غير موجود']);
                return;
            }
            
            // التحقق من عدم وجود تقييم سابق
            $existing_review = $this->directoryModel->getUserReview($website_id, $user_id);
            if ($existing_review) {
                $this->sendJsonResponse(['success' => false, 'message' => 'لقد قمت بتقييم هذا الموقع مسبقاً']);
                return;
            }
            
            // إضافة التقييم
            $review_data = [
                'website_id' => $website_id,
                'user_id' => $user_id,
                'rating' => $rating,
                'comment' => $comment,
                'status' => get_setting('directory_auto_approve_reviews', true) ? 'approved' : 'pending'
            ];
            
            $review_id = $this->directoryModel->addReview($review_data);
            
            if ($review_id) {
                // تحديث متوسط التقييم
                $this->directoryModel->updateWebsiteRating($website_id);
                
                // إضافة نقاط للمستخدم
                $points = get_setting('directory_review_points', 5);
                if ($points > 0) {
                    add_user_points($user_id, $points, 'directory_review', 'تقييم موقع في الدليل');
                }
                
                // تسجيل النشاط
                log_activity($user_id, 'directory_review', 'تقييم موقع في الدليل', [
                    'website_id' => $website_id,
                    'rating' => $rating
                ]);
                
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => 'تم إضافة تقييمك بنجاح'
                ]);
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => 'حدث خطأ في إضافة التقييم']);
            }
            
        } catch (Exception $e) {
            error_log("خطأ في إضافة تقييم: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    }
    
    /**
     * البحث في المواقع (AJAX)
     */
    public function search() {
        $query = $_GET['q'] ?? '';
        $category_id = $_GET['category'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 10), 20);
        
        if (strlen($query) < 2) {
            $this->sendJsonResponse(['success' => false, 'message' => 'استعلام البحث قصير جداً']);
            return;
        }
        
        try {
            $results = $this->directoryModel->searchWebsites($query, $category_id, $limit);
            
            $this->sendJsonResponse([
                'success' => true,
                'results' => $results
            ]);
            
        } catch (Exception $e) {
            error_log("خطأ في البحث: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'حدث خطأ في البحث']);
        }
    }
    
    /**
     * عرض المواقع حسب الفئة
     */
    public function category($slug) {
        if (empty($slug)) {
            redirect('/directory');
            return;
        }
        
        try {
            $category = $this->directoryModel->getCategoryBySlug($slug);
            
            if (!$category) {
                $this->setError('الفئة غير موجودة');
                redirect('/directory');
                return;
            }
            
            $page = (int)($_GET['page'] ?? 1);
            $sort = $_GET['sort'] ?? 'latest';
            $limit = 20;
            
            // الحصول على المواقع في هذه الفئة
            $websites = $this->directoryModel->getWebsites([
                'page' => $page,
                'limit' => $limit,
                'category_id' => $category['id'],
                'sort' => $sort,
                'status' => 'approved'
            ]);
            
            // الحصول على جميع الفئات للقائمة الجانبية
            $categories = $this->directoryModel->getCategories();
            
            $page_title = $category['name'] . ' - دليل المواقع';
            include '../templates/frontend/directory/category.php';
            
        } catch (Exception $e) {
            error_log("خطأ في عرض الفئة: " . $e->getMessage());
            $this->setError('حدث خطأ في تحميل الفئة');
            redirect('/directory');
        }
    }
    
    /**
     * إرسال استجابة JSON
     */
    private function sendJsonResponse($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

