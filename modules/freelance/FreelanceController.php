<?php

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Auth.php';

class FreelanceController extends Controller {
    private $auth;
    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix) {
        parent::__construct();
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->auth = new Auth($pdo, $prefix);
    }

    // عرض جميع المشاريع
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $budget_min = isset($_GET['budget_min']) ? (float)$_GET['budget_min'] : null;
        $budget_max = isset($_GET['budget_max']) ? (float)$_GET['budget_max'] : null;
        $skills = isset($_GET['skills']) ? trim($_GET['skills']) : '';

        try {
            $whereClause = "WHERE p.status = 'open'";
            $params = [];
            
            if ($category) {
                $whereClause .= " AND p.category_id = ?";
                $params[] = $category;
            }
            
            if ($search) {
                $whereClause .= " AND (p.title LIKE ? OR p.description LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($budget_min !== null) {
                $whereClause .= " AND p.budget >= ?";
                $params[] = $budget_min;
            }
            
            if ($budget_max !== null) {
                $whereClause .= " AND p.budget <= ?";
                $params[] = $budget_max;
            }
            
            if ($skills) {
                $whereClause .= " AND p.required_skills LIKE ?";
                $params[] = "%$skills%";
            }

            $sql = "SELECT p.*, u.username as client_name, u.country as client_country,
                           c.name as category_name, 
                           COUNT(pr.id) as proposals_count
                    FROM `{$this->prefix}freelance_projects` p 
                    LEFT JOIN `{$this->prefix}users` u ON p.client_id = u.id 
                    LEFT JOIN `{$this->prefix}freelance_categories` c ON p.category_id = c.id 
                    LEFT JOIN `{$this->prefix}project_proposals` pr ON p.id = pr.project_id
                    $whereClause 
                    GROUP BY p.id
                    ORDER BY p.featured DESC, p.created_at DESC 
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // حساب العدد الإجمالي
            $countSql = "SELECT COUNT(DISTINCT p.id) FROM `{$this->prefix}freelance_projects` p $whereClause";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalProjects = $countStmt->fetchColumn();
            $totalPages = ceil($totalProjects / $limit);

            // جلب فئات المشاريع
            $categoriesStmt = $this->pdo->query("SELECT * FROM `{$this->prefix}freelance_categories` ORDER BY `name`");
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/freelance/index', [
                'projects' => $projects,
                'categories' => $categories,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'filters' => [
                    'category' => $category,
                    'search' => $search,
                    'budget_min' => $budget_min,
                    'budget_max' => $budget_max,
                    'skills' => $skills
                ]
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب المشاريع: ' . $e->getMessage();
            $this->view('frontend/freelance/index', ['projects' => [], 'categories' => []]);
        }
    }

    // عرض تفاصيل مشروع
    public function detail() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            header('Location: /freelance');
            exit();
        }

        try {
            // جلب المشروع
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.username as client_name, u.first_name, u.last_name, 
                       u.avatar, u.created_at as client_since, u.country, u.city,
                       c.name as category_name,
                       COUNT(pr.id) as proposals_count
                FROM `{$this->prefix}freelance_projects` p 
                LEFT JOIN `{$this->prefix}users` u ON p.client_id = u.id 
                LEFT JOIN `{$this->prefix}freelance_categories` c ON p.category_id = c.id 
                LEFT JOIN `{$this->prefix}project_proposals` pr ON p.id = pr.project_id
                WHERE p.id = ?
                GROUP BY p.id
            ");
            $stmt->execute([$id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$project) {
                header('Location: /freelance');
                exit();
            }

            // تحديث عدد المشاهدات
            $updateStmt = $this->pdo->prepare("UPDATE `{$this->prefix}freelance_projects` SET `views` = `views` + 1 WHERE `id` = ?");
            $updateStmt->execute([$id]);

            // جلب العروض (للعميل فقط أو إذا كان المستخدم مدير)
            $proposals = [];
            if ($this->auth->check()) {
                $user = $this->auth->user();
                if ($user['id'] == $project['client_id'] || $user['role'] === 'admin') {
                    $proposalsStmt = $this->pdo->prepare("
                        SELECT pr.*, u.username as freelancer_name, u.first_name, u.last_name, 
                               u.avatar, u.country, u.city,
                               AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                        FROM `{$this->prefix}project_proposals` pr 
                        LEFT JOIN `{$this->prefix}users` u ON pr.freelancer_id = u.id 
                        LEFT JOIN `{$this->prefix}freelancer_reviews` r ON u.id = r.freelancer_id
                        WHERE pr.project_id = ? 
                        GROUP BY pr.id
                        ORDER BY pr.created_at DESC
                    ");
                    $proposalsStmt->execute([$id]);
                    $proposals = $proposalsStmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            // جلب مشاريع مشابهة
            $similarStmt = $this->pdo->prepare("
                SELECT p.*, u.username as client_name, COUNT(pr.id) as proposals_count
                FROM `{$this->prefix}freelance_projects` p 
                LEFT JOIN `{$this->prefix}users` u ON p.client_id = u.id 
                LEFT JOIN `{$this->prefix}project_proposals` pr ON p.id = pr.project_id
                WHERE p.category_id = ? AND p.id != ? AND p.status = 'open'
                GROUP BY p.id
                ORDER BY RAND() 
                LIMIT 4
            ");
            $similarStmt->execute([$project['category_id'], $id]);
            $similarProjects = $similarStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/freelance/detail', [
                'project' => $project,
                'proposals' => $proposals,
                'similarProjects' => $similarProjects
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب المشروع: ' . $e->getMessage();
            header('Location: /freelance');
            exit();
        }
    }

    // تقديم عرض على مشروع
    public function submitProposal() {
        if (!$this->auth->check()) {
            $_SESSION['error_message'] = 'يجب تسجيل الدخول لتقديم عرض.';
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
            $coverLetter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : '';
            $proposedBudget = isset($_POST['proposed_budget']) ? (float)$_POST['proposed_budget'] : 0;
            $deliveryTime = isset($_POST['delivery_time']) ? (int)$_POST['delivery_time'] : 0;

            if (!$projectId || !$coverLetter || !$proposedBudget || !$deliveryTime) {
                $_SESSION['error_message'] = 'جميع الحقول مطلوبة.';
                header("Location: /project/$projectId");
                exit();
            }

            try {
                $user = $this->auth->user();
                
                // التحقق من أن المشروع مفتوح
                $projectStmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}freelance_projects` WHERE `id` = ? AND `status` = 'open'");
                $projectStmt->execute([$projectId]);
                $project = $projectStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$project) {
                    $_SESSION['error_message'] = 'المشروع غير متاح للعروض.';
                    header("Location: /project/$projectId");
                    exit();
                }

                // التحقق من عدم تقديم عرض سابق
                $existingStmt = $this->pdo->prepare("
                    SELECT id FROM `{$this->prefix}project_proposals` 
                    WHERE `project_id` = ? AND `freelancer_id` = ?
                ");
                $existingStmt->execute([$projectId, $user['id']]);
                
                if ($existingStmt->fetch()) {
                    $_SESSION['error_message'] = 'لقد قمت بتقديم عرض على هذا المشروع مسبقاً.';
                    header("Location: /project/$projectId");
                    exit();
                }

                // إضافة العرض
                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}project_proposals` 
                    (`project_id`, `freelancer_id`, `cover_letter`, `proposed_budget`, `delivery_time`, `status`) 
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$projectId, $user['id'], $coverLetter, $proposedBudget, $deliveryTime]);

                $_SESSION['success_message'] = 'تم تقديم عرضك بنجاح.';
                header("Location: /project/$projectId");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في تقديم العرض: ' . $e->getMessage();
                header("Location: /project/$projectId");
                exit();
            }
        }
    }

    // قبول عرض
    public function acceptProposal() {
        if (!$this->auth->check()) {
            echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proposalId = isset($_POST['proposal_id']) ? (int)$_POST['proposal_id'] : 0;

            if (!$proposalId) {
                echo json_encode(['success' => false, 'message' => 'معرف العرض مطلوب']);
                exit();
            }

            try {
                $user = $this->auth->user();
                
                // جلب العرض والمشروع
                $stmt = $this->pdo->prepare("
                    SELECT pr.*, p.client_id, p.title as project_title
                    FROM `{$this->prefix}project_proposals` pr
                    JOIN `{$this->prefix}freelance_projects` p ON pr.project_id = p.id
                    WHERE pr.id = ? AND p.client_id = ?
                ");
                $stmt->execute([$proposalId, $user['id']]);
                $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$proposal) {
                    echo json_encode(['success' => false, 'message' => 'العرض غير موجود أو ليس لديك صلاحية']);
                    exit();
                }

                // قبول العرض
                $acceptStmt = $this->pdo->prepare("UPDATE `{$this->prefix}project_proposals` SET `status` = 'accepted' WHERE `id` = ?");
                $acceptStmt->execute([$proposalId]);

                // رفض باقي العروض
                $rejectStmt = $this->pdo->prepare("UPDATE `{$this->prefix}project_proposals` SET `status` = 'rejected' WHERE `project_id` = ? AND `id` != ?");
                $rejectStmt->execute([$proposal['project_id'], $proposalId]);

                // تحديث حالة المشروع
                $projectStmt = $this->pdo->prepare("UPDATE `{$this->prefix}freelance_projects` SET `status` = 'in_progress', `assigned_freelancer_id` = ? WHERE `id` = ?");
                $projectStmt->execute([$proposal['freelancer_id'], $proposal['project_id']]);

                echo json_encode(['success' => true, 'message' => 'تم قبول العرض بنجاح']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'خطأ في قبول العرض']);
            }
        }
        exit();
    }

    // إنشاء مشروع جديد
    public function createProject() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
            $budget = isset($_POST['budget']) ? (float)$_POST['budget'] : 0;
            $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
            $requiredSkills = isset($_POST['required_skills']) ? trim($_POST['required_skills']) : '';
            $attachments = isset($_POST['attachments']) ? trim($_POST['attachments']) : '';

            if (!$title || !$description || !$categoryId || !$budget || !$duration) {
                $_SESSION['error_message'] = 'جميع الحقول الأساسية مطلوبة.';
                $this->view('frontend/freelance/create_project', []);
                return;
            }

            try {
                $user = $this->auth->user();
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}freelance_projects` 
                    (`title`, `description`, `category_id`, `client_id`, `budget`, `duration`, 
                     `required_skills`, `attachments`, `status`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')
                ");
                $stmt->execute([
                    $title, $description, $categoryId, $user['id'], 
                    $budget, $duration, $requiredSkills, $attachments
                ]);

                $projectId = $this->pdo->lastInsertId();
                $_SESSION['success_message'] = 'تم إنشاء المشروع بنجاح.';
                header("Location: /project/$projectId");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في إنشاء المشروع: ' . $e->getMessage();
            }
        }

        // جلب الفئات
        try {
            $categoriesStmt = $this->pdo->query("SELECT * FROM `{$this->prefix}freelance_categories` ORDER BY `name`");
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $categories = [];
        }

        $this->view('frontend/freelance/create_project', ['categories' => $categories]);
    }

    // لوحة تحكم العميل
    public function clientDashboard() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $user = $this->auth->user();
        
        try {
            // إحصائيات العميل
            $stats = [];
            
            // عدد المشاريع
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}freelance_projects` WHERE `client_id` = ?");
            $stmt->execute([$user['id']]);
            $stats['total_projects'] = $stmt->fetchColumn();
            
            // المشاريع النشطة
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}freelance_projects` WHERE `client_id` = ? AND `status` IN ('open', 'in_progress')");
            $stmt->execute([$user['id']]);
            $stats['active_projects'] = $stmt->fetchColumn();
            
            // المشاريع المكتملة
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}freelance_projects` WHERE `client_id` = ? AND `status` = 'completed'");
            $stmt->execute([$user['id']]);
            $stats['completed_projects'] = $stmt->fetchColumn();
            
            // إجمالي الإنفاق
            $stmt = $this->pdo->prepare("SELECT SUM(`budget`) FROM `{$this->prefix}freelance_projects` WHERE `client_id` = ? AND `status` = 'completed'");
            $stmt->execute([$user['id']]);
            $stats['total_spent'] = $stmt->fetchColumn() ?: 0;

            // المشاريع الأخيرة
            $projectsStmt = $this->pdo->prepare("
                SELECT p.*, COUNT(pr.id) as proposals_count
                FROM `{$this->prefix}freelance_projects` p
                LEFT JOIN `{$this->prefix}project_proposals` pr ON p.id = pr.project_id
                WHERE p.client_id = ?
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            $projectsStmt->execute([$user['id']]);
            $recentProjects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/freelance/client_dashboard', [
                'stats' => $stats,
                'recentProjects' => $recentProjects
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب البيانات: ' . $e->getMessage();
            $this->view('frontend/freelance/client_dashboard', ['stats' => [], 'recentProjects' => []]);
        }
    }

    // لوحة تحكم المستقل
    public function freelancerDashboard() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $user = $this->auth->user();
        
        try {
            // إحصائيات المستقل
            $stats = [];
            
            // عدد العروض المقدمة
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}project_proposals` WHERE `freelancer_id` = ?");
            $stmt->execute([$user['id']]);
            $stats['total_proposals'] = $stmt->fetchColumn();
            
            // العروض المقبولة
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}project_proposals` WHERE `freelancer_id` = ? AND `status` = 'accepted'");
            $stmt->execute([$user['id']]);
            $stats['accepted_proposals'] = $stmt->fetchColumn();
            
            // المشاريع المكتملة
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM `{$this->prefix}freelance_projects` p
                JOIN `{$this->prefix}project_proposals` pr ON p.id = pr.project_id
                WHERE pr.freelancer_id = ? AND pr.status = 'accepted' AND p.status = 'completed'
            ");
            $stmt->execute([$user['id']]);
            $stats['completed_projects'] = $stmt->fetchColumn();
            
            // إجمالي الأرباح
            $stmt = $this->pdo->prepare("
                SELECT SUM(pr.proposed_budget) FROM `{$this->prefix}project_proposals` pr
                JOIN `{$this->prefix}freelance_projects` p ON pr.project_id = p.id
                WHERE pr.freelancer_id = ? AND pr.status = 'accepted' AND p.status = 'completed'
            ");
            $stmt->execute([$user['id']]);
            $stats['total_earnings'] = $stmt->fetchColumn() ?: 0;

            // العروض الأخيرة
            $proposalsStmt = $this->pdo->prepare("
                SELECT pr.*, p.title as project_title, p.budget as project_budget, u.username as client_name
                FROM `{$this->prefix}project_proposals` pr
                JOIN `{$this->prefix}freelance_projects` p ON pr.project_id = p.id
                JOIN `{$this->prefix}users` u ON p.client_id = u.id
                WHERE pr.freelancer_id = ?
                ORDER BY pr.created_at DESC
                LIMIT 10
            ");
            $proposalsStmt->execute([$user['id']]);
            $recentProposals = $proposalsStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/freelance/freelancer_dashboard', [
                'stats' => $stats,
                'recentProposals' => $recentProposals
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب البيانات: ' . $e->getMessage();
            $this->view('frontend/freelance/freelancer_dashboard', ['stats' => [], 'recentProposals' => []]);
        }
    }
}

?>

