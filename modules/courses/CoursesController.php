<?php

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Auth.php';

class CoursesController extends Controller {
    private $auth;
    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix) {
        parent::__construct();
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->auth = new Auth($pdo, $prefix);
    }

    // عرض جميع الدورات
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $level = isset($_GET['level']) ? trim($_GET['level']) : '';
        $price_type = isset($_GET['price_type']) ? trim($_GET['price_type']) : '';

        try {
            $whereClause = "WHERE c.status = 'published'";
            $params = [];
            
            if ($category) {
                $whereClause .= " AND c.category_id = ?";
                $params[] = $category;
            }
            
            if ($search) {
                $whereClause .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.tags LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($level) {
                $whereClause .= " AND c.level = ?";
                $params[] = $level;
            }
            
            if ($price_type === 'free') {
                $whereClause .= " AND c.price = 0";
            } elseif ($price_type === 'paid') {
                $whereClause .= " AND c.price > 0";
            }

            $sql = "SELECT c.*, u.username as instructor_name, u.first_name, u.last_name,
                           cat.name as category_name,
                           COUNT(DISTINCT e.id) as enrollments_count,
                           COUNT(DISTINCT l.id) as lessons_count,
                           AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count
                    FROM `{$this->prefix}courses` c 
                    LEFT JOIN `{$this->prefix}users` u ON c.instructor_id = u.id 
                    LEFT JOIN `{$this->prefix}course_categories` cat ON c.category_id = cat.id 
                    LEFT JOIN `{$this->prefix}course_enrollments` e ON c.id = e.course_id
                    LEFT JOIN `{$this->prefix}course_lessons` l ON c.id = l.course_id
                    LEFT JOIN `{$this->prefix}course_reviews` r ON c.id = r.course_id
                    $whereClause 
                    GROUP BY c.id
                    ORDER BY c.featured DESC, c.created_at DESC 
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // حساب العدد الإجمالي
            $countSql = "SELECT COUNT(DISTINCT c.id) FROM `{$this->prefix}courses` c $whereClause";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalCourses = $countStmt->fetchColumn();
            $totalPages = ceil($totalCourses / $limit);

            // جلب فئات الدورات
            $categoriesStmt = $this->pdo->query("SELECT * FROM `{$this->prefix}course_categories` ORDER BY `name`");
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/courses/index', [
                'courses' => $courses,
                'categories' => $categories,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'filters' => [
                    'category' => $category,
                    'search' => $search,
                    'level' => $level,
                    'price_type' => $price_type
                ]
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب الدورات: ' . $e->getMessage();
            $this->view('frontend/courses/index', ['courses' => [], 'categories' => []]);
        }
    }

    // عرض تفاصيل دورة
    public function detail() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            header('Location: /courses');
            exit();
        }

        try {
            // جلب الدورة
            $stmt = $this->pdo->prepare("
                SELECT c.*, u.username as instructor_name, u.first_name, u.last_name, 
                       u.avatar, u.bio, u.created_at as instructor_since,
                       cat.name as category_name,
                       COUNT(DISTINCT e.id) as enrollments_count,
                       COUNT(DISTINCT l.id) as lessons_count,
                       AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count
                FROM `{$this->prefix}courses` c 
                LEFT JOIN `{$this->prefix}users` u ON c.instructor_id = u.id 
                LEFT JOIN `{$this->prefix}course_categories` cat ON c.category_id = cat.id 
                LEFT JOIN `{$this->prefix}course_enrollments` e ON c.id = e.course_id
                LEFT JOIN `{$this->prefix}course_lessons` l ON c.id = l.course_id
                LEFT JOIN `{$this->prefix}course_reviews` r ON c.id = r.course_id
                WHERE c.id = ? AND c.status = 'published'
                GROUP BY c.id
            ");
            $stmt->execute([$id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                header('Location: /courses');
                exit();
            }

            // تحديث عدد المشاهدات
            $updateStmt = $this->pdo->prepare("UPDATE `{$this->prefix}courses` SET `views` = `views` + 1 WHERE `id` = ?");
            $updateStmt->execute([$id]);

            // جلب الدروس
            $lessonsStmt = $this->pdo->prepare("
                SELECT * FROM `{$this->prefix}course_lessons` 
                WHERE `course_id` = ? 
                ORDER BY `sort_order`, `created_at` ASC
            ");
            $lessonsStmt->execute([$id]);
            $lessons = $lessonsStmt->fetchAll(PDO::FETCH_ASSOC);

            // التحقق من التسجيل
            $isEnrolled = false;
            $enrollment = null;
            if ($this->auth->check()) {
                $user = $this->auth->user();
                $enrollmentStmt = $this->pdo->prepare("
                    SELECT * FROM `{$this->prefix}course_enrollments` 
                    WHERE `course_id` = ? AND `student_id` = ?
                ");
                $enrollmentStmt->execute([$id, $user['id']]);
                $enrollment = $enrollmentStmt->fetch(PDO::FETCH_ASSOC);
                $isEnrolled = (bool)$enrollment;
            }

            // جلب المراجعات
            $reviewsStmt = $this->pdo->prepare("
                SELECT r.*, u.username as reviewer_name, u.first_name, u.last_name, u.avatar
                FROM `{$this->prefix}course_reviews` r 
                LEFT JOIN `{$this->prefix}users` u ON r.student_id = u.id 
                WHERE r.course_id = ? 
                ORDER BY r.created_at DESC 
                LIMIT 10
            ");
            $reviewsStmt->execute([$id]);
            $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

            // جلب دورات مشابهة
            $similarStmt = $this->pdo->prepare("
                SELECT c.*, u.username as instructor_name, AVG(r.rating) as avg_rating,
                       COUNT(DISTINCT e.id) as enrollments_count
                FROM `{$this->prefix}courses` c 
                LEFT JOIN `{$this->prefix}users` u ON c.instructor_id = u.id 
                LEFT JOIN `{$this->prefix}course_reviews` r ON c.id = r.course_id
                LEFT JOIN `{$this->prefix}course_enrollments` e ON c.id = e.course_id
                WHERE c.category_id = ? AND c.id != ? AND c.status = 'published'
                GROUP BY c.id
                ORDER BY RAND() 
                LIMIT 4
            ");
            $similarStmt->execute([$course['category_id'], $id]);
            $similarCourses = $similarStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/courses/detail', [
                'course' => $course,
                'lessons' => $lessons,
                'isEnrolled' => $isEnrolled,
                'enrollment' => $enrollment,
                'reviews' => $reviews,
                'similarCourses' => $similarCourses
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب الدورة: ' . $e->getMessage();
            header('Location: /courses');
            exit();
        }
    }

    // التسجيل في دورة
    public function enroll() {
        if (!$this->auth->check()) {
            $_SESSION['error_message'] = 'يجب تسجيل الدخول للتسجيل في الدورة.';
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

            if (!$courseId) {
                $_SESSION['error_message'] = 'معرف الدورة مطلوب.';
                header('Location: /courses');
                exit();
            }

            try {
                $user = $this->auth->user();
                
                // جلب معلومات الدورة
                $courseStmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}courses` WHERE `id` = ? AND `status` = 'published'");
                $courseStmt->execute([$courseId]);
                $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$course) {
                    $_SESSION['error_message'] = 'الدورة غير متاحة.';
                    header("Location: /course/$courseId");
                    exit();
                }

                // التحقق من عدم التسجيل المسبق
                $existingStmt = $this->pdo->prepare("
                    SELECT id FROM `{$this->prefix}course_enrollments` 
                    WHERE `course_id` = ? AND `student_id` = ?
                ");
                $existingStmt->execute([$courseId, $user['id']]);
                
                if ($existingStmt->fetch()) {
                    $_SESSION['error_message'] = 'أنت مسجل في هذه الدورة مسبقاً.';
                    header("Location: /course/$courseId");
                    exit();
                }

                // التسجيل في الدورة
                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}course_enrollments` 
                    (`course_id`, `student_id`, `enrollment_date`, `status`) 
                    VALUES (?, ?, NOW(), 'active')
                ");
                $stmt->execute([$courseId, $user['id']]);

                $_SESSION['success_message'] = 'تم التسجيل في الدورة بنجاح.';
                header("Location: /course/$courseId");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في التسجيل: ' . $e->getMessage();
                header("Location: /course/$courseId");
                exit();
            }
        }
    }

    // عرض درس
    public function lesson() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
        
        if (!$courseId || !$lessonId) {
            header('Location: /courses');
            exit();
        }

        try {
            $user = $this->auth->user();
            
            // التحقق من التسجيل في الدورة
            $enrollmentStmt = $this->pdo->prepare("
                SELECT * FROM `{$this->prefix}course_enrollments` 
                WHERE `course_id` = ? AND `student_id` = ? AND `status` = 'active'
            ");
            $enrollmentStmt->execute([$courseId, $user['id']]);
            $enrollment = $enrollmentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$enrollment) {
                $_SESSION['error_message'] = 'يجب التسجيل في الدورة أولاً.';
                header("Location: /course/$courseId");
                exit();
            }

            // جلب الدرس
            $lessonStmt = $this->pdo->prepare("
                SELECT l.*, c.title as course_title, c.instructor_id
                FROM `{$this->prefix}course_lessons` l
                JOIN `{$this->prefix}courses` c ON l.course_id = c.id
                WHERE l.id = ? AND l.course_id = ?
            ");
            $lessonStmt->execute([$lessonId, $courseId]);
            $lesson = $lessonStmt->fetch(PDO::FETCH_ASSOC);

            if (!$lesson) {
                header("Location: /course/$courseId");
                exit();
            }

            // جلب جميع دروس الدورة للتنقل
            $allLessonsStmt = $this->pdo->prepare("
                SELECT id, title, sort_order FROM `{$this->prefix}course_lessons` 
                WHERE `course_id` = ? 
                ORDER BY `sort_order`, `created_at` ASC
            ");
            $allLessonsStmt->execute([$courseId]);
            $allLessons = $allLessonsStmt->fetchAll(PDO::FETCH_ASSOC);

            // تسجيل التقدم
            $progressStmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}lesson_progress` 
                (`enrollment_id`, `lesson_id`, `completed`, `last_accessed`) 
                VALUES (?, ?, 0, NOW())
                ON DUPLICATE KEY UPDATE `last_accessed` = NOW()
            ");
            $progressStmt->execute([$enrollment['id'], $lessonId]);

            $this->view('frontend/courses/lesson', [
                'lesson' => $lesson,
                'allLessons' => $allLessons,
                'courseId' => $courseId,
                'enrollment' => $enrollment
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب الدرس: ' . $e->getMessage();
            header("Location: /course/$courseId");
            exit();
        }
    }

    // تسجيل إكمال درس
    public function completeLesson() {
        if (!$this->auth->check()) {
            echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lessonId = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;
            $enrollmentId = isset($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : 0;

            if (!$lessonId || !$enrollmentId) {
                echo json_encode(['success' => false, 'message' => 'بيانات غير كاملة']);
                exit();
            }

            try {
                $user = $this->auth->user();
                
                // التحقق من صحة التسجيل
                $checkStmt = $this->pdo->prepare("
                    SELECT id FROM `{$this->prefix}course_enrollments` 
                    WHERE `id` = ? AND `student_id` = ?
                ");
                $checkStmt->execute([$enrollmentId, $user['id']]);
                
                if (!$checkStmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
                    exit();
                }

                // تحديث التقدم
                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}lesson_progress` 
                    (`enrollment_id`, `lesson_id`, `completed`, `completed_at`) 
                    VALUES (?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE `completed` = 1, `completed_at` = NOW()
                ");
                $stmt->execute([$enrollmentId, $lessonId]);

                echo json_encode(['success' => true, 'message' => 'تم تسجيل إكمال الدرس']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'خطأ في تسجيل التقدم']);
            }
        }
        exit();
    }

    // إضافة مراجعة للدورة
    public function addReview() {
        if (!$this->auth->check()) {
            $_SESSION['error_message'] = 'يجب تسجيل الدخول لإضافة مراجعة.';
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
            $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
            $review = isset($_POST['review']) ? trim($_POST['review']) : '';

            if (!$courseId || !$rating || !$review) {
                $_SESSION['error_message'] = 'جميع الحقول مطلوبة.';
                header("Location: /course/$courseId");
                exit();
            }

            if ($rating < 1 || $rating > 5) {
                $_SESSION['error_message'] = 'التقييم يجب أن يكون بين 1 و 5.';
                header("Location: /course/$courseId");
                exit();
            }

            try {
                $user = $this->auth->user();
                
                // التحقق من التسجيل في الدورة
                $enrollmentStmt = $this->pdo->prepare("
                    SELECT id FROM `{$this->prefix}course_enrollments` 
                    WHERE `course_id` = ? AND `student_id` = ?
                ");
                $enrollmentStmt->execute([$courseId, $user['id']]);
                
                if (!$enrollmentStmt->fetch()) {
                    $_SESSION['error_message'] = 'يجب التسجيل في الدورة أولاً لإضافة مراجعة.';
                    header("Location: /course/$courseId");
                    exit();
                }

                // التحقق من عدم وجود مراجعة سابقة
                $existingStmt = $this->pdo->prepare("
                    SELECT id FROM `{$this->prefix}course_reviews` 
                    WHERE `course_id` = ? AND `student_id` = ?
                ");
                $existingStmt->execute([$courseId, $user['id']]);
                
                if ($existingStmt->fetch()) {
                    $_SESSION['error_message'] = 'لقد قمت بإضافة مراجعة لهذه الدورة مسبقاً.';
                    header("Location: /course/$courseId");
                    exit();
                }

                // إضافة المراجعة
                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}course_reviews` 
                    (`course_id`, `student_id`, `rating`, `review`) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$courseId, $user['id'], $rating, $review]);

                $_SESSION['success_message'] = 'تم إضافة المراجعة بنجاح.';
                header("Location: /course/$courseId");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في إضافة المراجعة: ' . $e->getMessage();
                header("Location: /course/$courseId");
                exit();
            }
        }
    }

    // لوحة تحكم الطالب
    public function studentDashboard() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $user = $this->auth->user();
        
        try {
            // إحصائيات الطالب
            $stats = [];
            
            // عدد الدورات المسجل بها
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}course_enrollments` WHERE `student_id` = ?");
            $stmt->execute([$user['id']]);
            $stats['enrolled_courses'] = $stmt->fetchColumn();
            
            // الدورات المكتملة
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM `{$this->prefix}course_enrollments` e
                JOIN `{$this->prefix}courses` c ON e.course_id = c.id
                WHERE e.student_id = ? AND e.progress = 100
            ");
            $stmt->execute([$user['id']]);
            $stats['completed_courses'] = $stmt->fetchColumn();
            
            // الشهادات المحصل عليها
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}course_certificates` WHERE `student_id` = ?");
            $stmt->execute([$user['id']]);
            $stats['certificates'] = $stmt->fetchColumn();

            // الدورات الحالية
            $coursesStmt = $this->pdo->prepare("
                SELECT e.*, c.title, c.image, c.instructor_id, u.username as instructor_name,
                       COUNT(l.id) as total_lessons,
                       COUNT(lp.id) as completed_lessons,
                       ROUND((COUNT(lp.id) / COUNT(l.id)) * 100, 2) as progress_percent
                FROM `{$this->prefix}course_enrollments` e
                JOIN `{$this->prefix}courses` c ON e.course_id = c.id
                JOIN `{$this->prefix}users` u ON c.instructor_id = u.id
                LEFT JOIN `{$this->prefix}course_lessons` l ON c.id = l.course_id
                LEFT JOIN `{$this->prefix}lesson_progress` lp ON l.id = lp.lesson_id AND lp.enrollment_id = e.id AND lp.completed = 1
                WHERE e.student_id = ? AND e.status = 'active'
                GROUP BY e.id
                ORDER BY e.enrollment_date DESC
            ");
            $coursesStmt->execute([$user['id']]);
            $enrolledCourses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/courses/student_dashboard', [
                'stats' => $stats,
                'enrolledCourses' => $enrolledCourses
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب البيانات: ' . $e->getMessage();
            $this->view('frontend/courses/student_dashboard', ['stats' => [], 'enrolledCourses' => []]);
        }
    }

    // لوحة تحكم المدرب
    public function instructorDashboard() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $user = $this->auth->user();
        
        try {
            // إحصائيات المدرب
            $stats = [];
            
            // عدد الدورات
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}courses` WHERE `instructor_id` = ?");
            $stmt->execute([$user['id']]);
            $stats['total_courses'] = $stmt->fetchColumn();
            
            // عدد الطلاب
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT e.student_id) FROM `{$this->prefix}course_enrollments` e
                JOIN `{$this->prefix}courses` c ON e.course_id = c.id
                WHERE c.instructor_id = ?
            ");
            $stmt->execute([$user['id']]);
            $stats['total_students'] = $stmt->fetchColumn();
            
            // إجمالي الأرباح
            $stmt = $this->pdo->prepare("
                SELECT SUM(c.price) FROM `{$this->prefix}course_enrollments` e
                JOIN `{$this->prefix}courses` c ON e.course_id = c.id
                WHERE c.instructor_id = ? AND c.price > 0
            ");
            $stmt->execute([$user['id']]);
            $stats['total_earnings'] = $stmt->fetchColumn() ?: 0;

            // الدورات
            $coursesStmt = $this->pdo->prepare("
                SELECT c.*, COUNT(e.id) as enrollments_count, AVG(r.rating) as avg_rating
                FROM `{$this->prefix}courses` c
                LEFT JOIN `{$this->prefix}course_enrollments` e ON c.id = e.course_id
                LEFT JOIN `{$this->prefix}course_reviews` r ON c.id = r.course_id
                WHERE c.instructor_id = ?
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ");
            $coursesStmt->execute([$user['id']]);
            $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/courses/instructor_dashboard', [
                'stats' => $stats,
                'courses' => $courses
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب البيانات: ' . $e->getMessage();
            $this->view('frontend/courses/instructor_dashboard', ['stats' => [], 'courses' => []]);
        }
    }
}

?>

