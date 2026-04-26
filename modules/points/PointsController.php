<?php

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Auth.php';

class PointsController extends Controller {
    private $auth;
    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix) {
        parent::__construct();
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->auth = new Auth($pdo, $prefix);
    }

    // عرض صفحة النقاط للمستخدم
    public function myPoints() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $user = $this->auth->user();
        
        try {
            // جلب إجمالي النقاط
            $stmt = $this->pdo->prepare("SELECT `points` FROM `{$this->prefix}users` WHERE `id` = ?");
            $stmt->execute([$user['id']]);
            $totalPoints = $stmt->fetchColumn() ?: 0;

            // جلب تاريخ النقاط
            $pointsHistoryStmt = $this->pdo->prepare("
                SELECT * FROM `{$this->prefix}points_history` 
                WHERE `user_id` = ? 
                ORDER BY `created_at` DESC 
                LIMIT 50
            ");
            $pointsHistoryStmt->execute([$user['id']]);
            $pointsHistory = $pointsHistoryStmt->fetchAll(PDO::FETCH_ASSOC);

            // جلب إحصائيات النقاط
            $statsStmt = $this->pdo->prepare("
                SELECT 
                    `action_type`,
                    SUM(`points`) as total_points,
                    COUNT(*) as count
                FROM `{$this->prefix}points_history` 
                WHERE `user_id` = ? 
                GROUP BY `action_type`
            ");
            $statsStmt->execute([$user['id']]);
            $pointsStats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);

            // جلب إعدادات النقاط
            $pointsSettings = $this->getPointsSettings();

            $this->view('frontend/points/my_points', [
                'totalPoints' => $totalPoints,
                'pointsHistory' => $pointsHistory,
                'pointsStats' => $pointsStats,
                'pointsSettings' => $pointsSettings
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب بيانات النقاط: ' . $e->getMessage();
            $this->view('frontend/points/my_points', [
                'totalPoints' => 0, 
                'pointsHistory' => [], 
                'pointsStats' => [],
                'pointsSettings' => []
            ]);
        }
    }

    // لوحة النقاط (أفضل الأعضاء)
    public function leaderboard() {
        try {
            // أفضل الأعضاء بالنقاط
            $topPointsStmt = $this->pdo->query("
                SELECT u.id, u.username, u.avatar, u.points, u.created_at
                FROM `{$this->prefix}users` u 
                WHERE u.status = 'active' AND u.role != 'admin'
                ORDER BY u.points DESC 
                LIMIT 50
            ");
            $topPoints = $topPointsStmt->fetchAll(PDO::FETCH_ASSOC);

            // أفضل الأعضاء بالمشاركات
            $topPostsStmt = $this->pdo->query("
                SELECT u.id, u.username, u.avatar, COUNT(t.id) as posts_count
                FROM `{$this->prefix}users` u 
                LEFT JOIN `{$this->prefix}topics` t ON u.id = t.author_id
                WHERE u.status = 'active' AND u.role != 'admin'
                GROUP BY u.id 
                ORDER BY posts_count DESC 
                LIMIT 50
            ");
            $topPosts = $topPostsStmt->fetchAll(PDO::FETCH_ASSOC);

            // أفضل الأعضاء بالتعليقات
            $topCommentsStmt = $this->pdo->query("
                SELECT u.id, u.username, u.avatar, COUNT(c.id) as comments_count
                FROM `{$this->prefix}users` u 
                LEFT JOIN `{$this->prefix}comments` c ON u.id = c.user_id
                WHERE u.status = 'active' AND u.role != 'admin'
                GROUP BY u.id 
                ORDER BY comments_count DESC 
                LIMIT 50
            ");
            $topComments = $topCommentsStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/points/leaderboard', [
                'topPoints' => $topPoints,
                'topPosts' => $topPosts,
                'topComments' => $topComments
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب لوحة النقاط: ' . $e->getMessage();
            $this->view('frontend/points/leaderboard', [
                'topPoints' => [],
                'topPosts' => [],
                'topComments' => []
            ]);
        }
    }

    // إضافة نقاط للمستخدم
    public function addPoints($userId, $points, $actionType, $description, $referenceId = null, $referenceType = null) {
        try {
            // إضافة النقاط للمستخدم
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}users` 
                SET `points` = `points` + ?, `updated_at` = NOW() 
                WHERE `id` = ?
            ");
            $stmt->execute([$points, $userId]);

            // إضافة سجل في تاريخ النقاط
            $historyStmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}points_history` 
                (`user_id`, `points`, `action_type`, `description`, `reference_id`, `reference_type`) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $historyStmt->execute([$userId, $points, $actionType, $description, $referenceId, $referenceType]);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إضافة النقاط: ' . $e->getMessage()];
        }
    }

    // معالجة النقاط للإجراءات المختلفة
    public function processPointsAction($actionType, $userId, $referenceId = null, $referenceType = null) {
        $pointsSettings = $this->getPointsSettings();
        
        $points = 0;
        $description = '';

        switch ($actionType) {
            case 'create_topic':
                $points = $pointsSettings['points_per_topic'] ?? 10;
                $description = 'إنشاء موضوع جديد';
                break;
            
            case 'create_comment':
                $points = $pointsSettings['points_per_comment'] ?? 5;
                $description = 'إضافة تعليق';
                break;
            
            case 'receive_like':
                $points = $pointsSettings['points_per_like'] ?? 2;
                $description = 'الحصول على إعجاب';
                break;
            
            case 'topic_view':
                $views = $pointsSettings['views_per_point'] ?? 100;
                if ($referenceId && $referenceId % $views == 0) {
                    $points = $pointsSettings['points_per_view_milestone'] ?? 1;
                    $description = "الوصول إلى {$referenceId} مشاهدة";
                }
                break;
            
            case 'online_time':
                $hours = $pointsSettings['hours_per_point'] ?? 1;
                $points = $pointsSettings['points_per_hour'] ?? 1;
                $description = "قضاء {$hours} ساعة في الموقع";
                break;
            
            case 'deposit_money':
                $amount = $referenceId ?? 0;
                $pointsPerDollar = $pointsSettings['points_per_dollar_deposited'] ?? 1;
                $points = $amount * $pointsPerDollar;
                $description = "شحن رصيد بقيمة {$amount}";
                break;
            
            case 'buy_service':
                $points = $pointsSettings['points_per_service_purchase'] ?? 20;
                $description = 'شراء خدمة من الموقع';
                break;
            
            case 'invite_friend':
                $points = $pointsSettings['points_per_referral'] ?? 50;
                $description = 'دعوة صديق للموقع';
                break;
            
            case 'comment_milestone':
                $commentCount = $referenceId ?? 0;
                $milestone = $pointsSettings['comments_milestone'] ?? 50;
                if ($commentCount % $milestone == 0) {
                    $points = $pointsSettings['points_per_comment_milestone'] ?? 25;
                    $description = "الوصول إلى {$commentCount} تعليق";
                }
                break;
            
            case 'topic_milestone':
                $topicCount = $referenceId ?? 0;
                $milestone = $pointsSettings['topics_milestone'] ?? 20;
                if ($topicCount % $milestone == 0) {
                    $points = $pointsSettings['points_per_topic_milestone'] ?? 50;
                    $description = "الوصول إلى {$topicCount} موضوع";
                }
                break;
        }

        if ($points > 0) {
            return $this->addPoints($userId, $points, $actionType, $description, $referenceId, $referenceType);
        }

        return ['success' => true, 'points' => 0];
    }

    // إعدادات النقاط (للمدير)
    public function pointsSettings() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settings = [
                'points_per_topic' => (int)($_POST['points_per_topic'] ?? 10),
                'points_per_comment' => (int)($_POST['points_per_comment'] ?? 5),
                'points_per_like' => (int)($_POST['points_per_like'] ?? 2),
                'points_per_hour' => (int)($_POST['points_per_hour'] ?? 1),
                'points_per_service_purchase' => (int)($_POST['points_per_service_purchase'] ?? 20),
                'points_per_referral' => (int)($_POST['points_per_referral'] ?? 50),
                'views_per_point' => (int)($_POST['views_per_point'] ?? 100),
                'points_per_view_milestone' => (int)($_POST['points_per_view_milestone'] ?? 1),
                'points_per_dollar_deposited' => (float)($_POST['points_per_dollar_deposited'] ?? 1),
                'comments_milestone' => (int)($_POST['comments_milestone'] ?? 50),
                'points_per_comment_milestone' => (int)($_POST['points_per_comment_milestone'] ?? 25),
                'topics_milestone' => (int)($_POST['topics_milestone'] ?? 20),
                'points_per_topic_milestone' => (int)($_POST['points_per_topic_milestone'] ?? 50),
                'hours_per_point' => (int)($_POST['hours_per_point'] ?? 1),
                'enable_points_system' => isset($_POST['enable_points_system']) ? 1 : 0
            ];

            try {
                foreach ($settings as $key => $value) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO `{$this->prefix}settings` (`key`, `value`) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
                    ");
                    $stmt->execute([$key, $value]);
                }

                $_SESSION['success_message'] = 'تم حفظ إعدادات النقاط بنجاح.';
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في حفظ الإعدادات: ' . $e->getMessage();
            }

            header('Location: /admin/points/settings');
            exit();
        }

        $currentSettings = $this->getPointsSettings();
        $this->view('admin/points/settings', ['settings' => $currentSettings]);
    }

    // جلب إعدادات النقاط
    private function getPointsSettings() {
        try {
            $stmt = $this->pdo->query("SELECT `key`, `value` FROM `{$this->prefix}settings` WHERE `key` LIKE 'points_%' OR `key` LIKE '%_per_%' OR `key` = 'enable_points_system'");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // القيم الافتراضية
            $defaults = [
                'points_per_topic' => 10,
                'points_per_comment' => 5,
                'points_per_like' => 2,
                'points_per_hour' => 1,
                'points_per_service_purchase' => 20,
                'points_per_referral' => 50,
                'views_per_point' => 100,
                'points_per_view_milestone' => 1,
                'points_per_dollar_deposited' => 1,
                'comments_milestone' => 50,
                'points_per_comment_milestone' => 25,
                'topics_milestone' => 20,
                'points_per_topic_milestone' => 50,
                'hours_per_point' => 1,
                'enable_points_system' => 1
            ];

            return array_merge($defaults, $settings);
        } catch (PDOException $e) {
            return [
                'points_per_topic' => 10,
                'points_per_comment' => 5,
                'points_per_like' => 2,
                'points_per_hour' => 1,
                'points_per_service_purchase' => 20,
                'points_per_referral' => 50,
                'views_per_point' => 100,
                'points_per_view_milestone' => 1,
                'points_per_dollar_deposited' => 1,
                'comments_milestone' => 50,
                'points_per_comment_milestone' => 25,
                'topics_milestone' => 20,
                'points_per_topic_milestone' => 50,
                'hours_per_point' => 1,
                'enable_points_system' => 1
            ];
        }
    }

    // تتبع الوقت المتصل للمستخدم
    public function trackOnlineTime() {
        if (!$this->auth->check()) {
            echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
            exit();
        }

        $user = $this->auth->user();
        $pointsSettings = $this->getPointsSettings();
        
        if (!$pointsSettings['enable_points_system']) {
            echo json_encode(['success' => false, 'message' => 'نظام النقاط معطل']);
            exit();
        }

        try {
            // تحديث آخر نشاط
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}users` 
                SET `last_activity` = NOW() 
                WHERE `id` = ?
            ");
            $stmt->execute([$user['id']]);

            // التحقق من الوقت المتصل اليوم
            $stmt = $this->pdo->prepare("
                SELECT `online_time_today`, `last_online_update` 
                FROM `{$this->prefix}user_stats` 
                WHERE `user_id` = ?
            ");
            $stmt->execute([$user['id']]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            $currentTime = time();
            $onlineTimeToday = $stats['online_time_today'] ?? 0;
            $lastUpdate = $stats['last_online_update'] ? strtotime($stats['last_online_update']) : $currentTime;

            // إذا كان اليوم جديد، إعادة تعيين الوقت
            if (date('Y-m-d', $lastUpdate) !== date('Y-m-d', $currentTime)) {
                $onlineTimeToday = 0;
            }

            // إضافة دقيقة واحدة
            $onlineTimeToday += 1;

            // تحديث الإحصائيات
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}user_stats` 
                (`user_id`, `online_time_today`, `last_online_update`) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                `online_time_today` = ?, `last_online_update` = NOW()
            ");
            $stmt->execute([$user['id'], $onlineTimeToday, $onlineTimeToday]);

            // إضافة نقاط كل ساعة
            $hoursPerPoint = $pointsSettings['hours_per_point'] ?? 1;
            $minutesPerPoint = $hoursPerPoint * 60;
            
            if ($onlineTimeToday % $minutesPerPoint == 0) {
                $this->processPointsAction('online_time', $user['id'], $hoursPerPoint);
            }

            echo json_encode(['success' => true, 'online_time' => $onlineTimeToday]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ في تتبع الوقت']);
        }
        exit();
    }

    // إحصائيات النقاط (للمدير)
    public function pointsStats() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            header('Location: /');
            exit();
        }

        try {
            $stats = [];
            
            // إجمالي النقاط الممنوحة
            $stmt = $this->pdo->query("SELECT SUM(`points`) FROM `{$this->prefix}points_history` WHERE `points` > 0");
            $stats['total_points_awarded'] = $stmt->fetchColumn() ?: 0;

            // إجمالي النقاط المخصومة
            $stmt = $this->pdo->query("SELECT SUM(ABS(`points`)) FROM `{$this->prefix}points_history` WHERE `points` < 0");
            $stats['total_points_deducted'] = $stmt->fetchColumn() ?: 0;

            // عدد المستخدمين النشطين في النقاط
            $stmt = $this->pdo->query("SELECT COUNT(DISTINCT `user_id`) FROM `{$this->prefix}points_history`");
            $stats['active_users'] = $stmt->fetchColumn();

            // أكثر الإجراءات منحاً للنقاط
            $stmt = $this->pdo->query("
                SELECT `action_type`, SUM(`points`) as total_points, COUNT(*) as count
                FROM `{$this->prefix}points_history` 
                WHERE `points` > 0
                GROUP BY `action_type` 
                ORDER BY total_points DESC
            ");
            $stats['top_actions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // إحصائيات شهرية
            $stmt = $this->pdo->query("
                SELECT 
                    DATE_FORMAT(`created_at`, '%Y-%m') as month,
                    SUM(`points`) as total_points,
                    COUNT(*) as transaction_count
                FROM `{$this->prefix}points_history` 
                WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(`created_at`, '%Y-%m')
                ORDER BY month DESC
            ");
            $stats['monthly_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('admin/points/stats', ['stats' => $stats]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب الإحصائيات: ' . $e->getMessage();
            $this->view('admin/points/stats', ['stats' => []]);
        }
    }

    // منح أو خصم نقاط يدوياً (للمدير)
    public function manualPoints() {
        if (!$this->auth->check() || $this->auth->user()['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'غير مصرح']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $points = (int)($_POST['points'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');

            if (!$userId || !$points || !$reason) {
                echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
                exit();
            }

            $result = $this->addPoints($userId, $points, 'manual_adjustment', $reason);
            echo json_encode($result);
        }
        exit();
    }
}

?>

