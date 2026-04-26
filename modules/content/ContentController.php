<?php

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Auth.php';

class ContentController extends Controller {
    private $auth;
    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix) {
        parent::__construct();
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->auth = new Auth($pdo, $prefix);
    }

    // عرض جميع الأقسام
    public function categories() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM `{$this->prefix}categories` WHERE `parent_id` IS NULL ORDER BY `sort_order`, `name`");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // جلب الأقسام الفرعية لكل قسم رئيسي
            foreach ($categories as &$category) {
                $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}categories` WHERE `parent_id` = ? ORDER BY `sort_order`, `name`");
                $stmt->execute([$category['id']]);
                $category['subcategories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $this->view('frontend/content/categories', ['categories' => $categories]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب الأقسام: ' . $e->getMessage();
            $this->view('frontend/content/categories', ['categories' => []]);
        }
    }

    // عرض جميع المواضيع
    public function topics() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        try {
            // بناء الاستعلام
            $whereClause = "WHERE t.status = 'published'";
            $params = [];
            
            if ($categoryId) {
                $whereClause .= " AND t.category_id = ?";
                $params[] = $categoryId;
            }
            
            if ($search) {
                $whereClause .= " AND (t.title LIKE ? OR t.content LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            // جلب المواضيع مع معلومات المؤلف والقسم
            $sql = "SELECT t.*, u.username as author_name, c.name as category_name 
                    FROM `{$this->prefix}topics` t 
                    LEFT JOIN `{$this->prefix}users` u ON t.author_id = u.id 
                    LEFT JOIN `{$this->prefix}categories` c ON t.category_id = c.id 
                    $whereClause 
                    ORDER BY t.created_at DESC 
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // حساب العدد الإجمالي للصفحات
            $countSql = "SELECT COUNT(*) FROM `{$this->prefix}topics` t $whereClause";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalTopics = $countStmt->fetchColumn();
            $totalPages = ceil($totalTopics / $limit);

            // جلب الأقسام للفلترة
            $categoriesStmt = $this->pdo->query("SELECT * FROM `{$this->prefix}categories` ORDER BY `name`");
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/content/topics', [
                'topics' => $topics,
                'categories' => $categories,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'currentCategory' => $categoryId,
                'currentSearch' => $search
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب المواضيع: ' . $e->getMessage();
            $this->view('frontend/content/topics', ['topics' => [], 'categories' => []]);
        }
    }

    // عرض موضوع واحد
    public function topicDetail() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            header('Location: /topics');
            exit();
        }

        try {
            // جلب الموضوع مع معلومات المؤلف والقسم
            $stmt = $this->pdo->prepare("
                SELECT t.*, u.username as author_name, u.first_name, u.last_name, 
                       c.name as category_name, c.id as category_id
                FROM `{$this->prefix}topics` t 
                LEFT JOIN `{$this->prefix}users` u ON t.author_id = u.id 
                LEFT JOIN `{$this->prefix}categories` c ON t.category_id = c.id 
                WHERE t.id = ? AND t.status = 'published'
            ");
            $stmt->execute([$id]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$topic) {
                header('Location: /topics');
                exit();
            }

            // تحديث عدد المشاهدات
            $updateStmt = $this->pdo->prepare("UPDATE `{$this->prefix}topics` SET `views` = `views` + 1 WHERE `id` = ?");
            $updateStmt->execute([$id]);

            // جلب التعليقات
            $commentsStmt = $this->pdo->prepare("
                SELECT c.*, u.username as commenter_name, u.first_name, u.last_name
                FROM `{$this->prefix}comments` c 
                LEFT JOIN `{$this->prefix}users` u ON c.user_id = u.id 
                WHERE c.topic_id = ? AND c.status = 'approved' AND c.parent_id IS NULL
                ORDER BY c.created_at ASC
            ");
            $commentsStmt->execute([$id]);
            $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

            // جلب الردود لكل تعليق
            foreach ($comments as &$comment) {
                $repliesStmt = $this->pdo->prepare("
                    SELECT c.*, u.username as commenter_name, u.first_name, u.last_name
                    FROM `{$this->prefix}comments` c 
                    LEFT JOIN `{$this->prefix}users` u ON c.user_id = u.id 
                    WHERE c.parent_id = ? AND c.status = 'approved'
                    ORDER BY c.created_at ASC
                ");
                $repliesStmt->execute([$comment['id']]);
                $comment['replies'] = $repliesStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $this->view('frontend/content/topic_detail', [
                'topic' => $topic,
                'comments' => $comments
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب الموضوع: ' . $e->getMessage();
            header('Location: /topics');
            exit();
        }
    }

    // إضافة تعليق
    public function addComment() {
        if (!$this->auth->check()) {
            $_SESSION['error_message'] = 'يجب تسجيل الدخول لإضافة تعليق.';
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $topicId = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0;
            $content = isset($_POST['content']) ? trim($_POST['content']) : '';
            $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

            if (!$topicId || !$content) {
                $_SESSION['error_message'] = 'جميع الحقول مطلوبة.';
                header("Location: /topic/$topicId");
                exit();
            }

            try {
                $user = $this->auth->user();
                $status = 'approved'; // يمكن تغييرها إلى 'pending' إذا كنت تريد مراجعة التعليقات

                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}comments` 
                    (`topic_id`, `user_id`, `content`, `parent_id`, `status`) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$topicId, $user['id'], $content, $parentId, $status]);

                $_SESSION['success_message'] = 'تم إضافة التعليق بنجاح.';
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في إضافة التعليق: ' . $e->getMessage();
            }

            header("Location: /topic/$topicId");
            exit();
        }
    }

    // إعجاب بموضوع
    public function likeTopic() {
        if (!$this->auth->check()) {
            echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
            exit();
        }

        $topicId = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0;
        if (!$topicId) {
            echo json_encode(['success' => false, 'message' => 'معرف الموضوع مطلوب']);
            exit();
        }

        try {
            $user = $this->auth->user();
            
            // فحص إذا كان المستخدم قد أعجب بالموضوع مسبقاً
            $checkStmt = $this->pdo->prepare("SELECT id FROM `{$this->prefix}topic_likes` WHERE `topic_id` = ? AND `user_id` = ?");
            $checkStmt->execute([$topicId, $user['id']]);
            
            if ($checkStmt->fetch()) {
                // إلغاء الإعجاب
                $deleteStmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}topic_likes` WHERE `topic_id` = ? AND `user_id` = ?");
                $deleteStmt->execute([$topicId, $user['id']]);
                
                $updateStmt = $this->pdo->prepare("UPDATE `{$this->prefix}topics` SET `likes` = `likes` - 1 WHERE `id` = ?");
                $updateStmt->execute([$topicId]);
                
                echo json_encode(['success' => true, 'action' => 'unliked']);
            } else {
                // إضافة إعجاب
                $insertStmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}topic_likes` (`topic_id`, `user_id`) VALUES (?, ?)");
                $insertStmt->execute([$topicId, $user['id']]);
                
                $updateStmt = $this->pdo->prepare("UPDATE `{$this->prefix}topics` SET `likes` = `likes` + 1 WHERE `id` = ?");
                $updateStmt->execute([$topicId]);
                
                echo json_encode(['success' => true, 'action' => 'liked']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في العملية']);
        }
        exit();
    }

    // البحث المتقدم
    public function search() {
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $author = isset($_GET['author']) ? trim($_GET['author']) : '';
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

        $results = [];
        
        if ($query || $category || $author || $dateFrom || $dateTo) {
            try {
                $whereClause = "WHERE t.status = 'published'";
                $params = [];
                
                if ($query) {
                    $whereClause .= " AND (t.title LIKE ? OR t.content LIKE ? OR t.tags LIKE ?)";
                    $params[] = "%$query%";
                    $params[] = "%$query%";
                    $params[] = "%$query%";
                }
                
                if ($category) {
                    $whereClause .= " AND t.category_id = ?";
                    $params[] = $category;
                }
                
                if ($author) {
                    $whereClause .= " AND u.username LIKE ?";
                    $params[] = "%$author%";
                }
                
                if ($dateFrom) {
                    $whereClause .= " AND DATE(t.created_at) >= ?";
                    $params[] = $dateFrom;
                }
                
                if ($dateTo) {
                    $whereClause .= " AND DATE(t.created_at) <= ?";
                    $params[] = $dateTo;
                }

                $sql = "SELECT t.*, u.username as author_name, c.name as category_name 
                        FROM `{$this->prefix}topics` t 
                        LEFT JOIN `{$this->prefix}users` u ON t.author_id = u.id 
                        LEFT JOIN `{$this->prefix}categories` c ON t.category_id = c.id 
                        $whereClause 
                        ORDER BY t.created_at DESC 
                        LIMIT 50";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في البحث: ' . $e->getMessage();
            }
        }

        // جلب الأقسام للفلترة
        try {
            $categoriesStmt = $this->pdo->query("SELECT * FROM `{$this->prefix}categories` ORDER BY `name`");
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $categories = [];
        }

        $this->view('frontend/content/search', [
            'results' => $results,
            'categories' => $categories,
            'query' => $query,
            'selectedCategory' => $category,
            'author' => $author,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
    }
}

?>

