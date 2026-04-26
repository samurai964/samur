<?php
/**
 * Final Max CMS - API Endpoint
 * واجهة برمجة التطبيقات الأساسية
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// معالجة طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../core/functions.php';
require_once '../core/Auth.php';
require_once '../core/Security.php';

class APIController {
    private $db;
    private $method;
    private $endpoint;
    private $params;
    
    public function __construct() {
        $this->db = get_db_connection();
        $this->method = $_SERVER['REQUEST_METHOD'];
        
        // تحليل المسار
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/api/', '', $path);
        $parts = explode('/', trim($path, '/'));
        
        $this->endpoint = $parts[0] ?? '';
        $this->params = array_slice($parts, 1);
        
        // معالجة الطلب
        $this->handleRequest();
    }
    
    /**
     * معالجة الطلب
     */
    private function handleRequest() {
        try {
            // التحقق من Rate Limiting
            $ip = get_client_ip();
            if (is_rate_limited('api', $ip, 100, 3600)) { // 100 طلب في الساعة
                $this->sendError('تم تجاوز حد الطلبات المسموحة', 429);
                return;
            }
            
            record_rate_limit('api', $ip);
            
            switch ($this->endpoint) {
                case 'auth':
                    $this->handleAuth();
                    break;
                    
                case 'topics':
                    $this->handleTopics();
                    break;
                    
                case 'services':
                    $this->handleServices();
                    break;
                    
                case 'courses':
                    $this->handleCourses();
                    break;
                    
                case 'search':
                    $this->handleSearch();
                    break;
                    
                case 'stats':
                    $this->handleStats();
                    break;
                    
                case 'notifications':
                    $this->handleNotifications();
                    break;
                    
                default:
                    $this->sendError('نقطة نهاية غير موجودة', 404);
            }
            
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            $this->sendError('خطأ في الخادم', 500);
        }
    }
    
    /**
     * معالجة طلبات المصادقة
     */
    private function handleAuth() {
        switch ($this->method) {
            case 'POST':
                if (isset($this->params[0]) && $this->params[0] === 'login') {
                    $this->apiLogin();
                } elseif (isset($this->params[0]) && $this->params[0] === 'register') {
                    $this->apiRegister();
                } else {
                    $this->sendError('طلب غير صحيح', 400);
                }
                break;
                
            case 'DELETE':
                if (isset($this->params[0]) && $this->params[0] === 'logout') {
                    $this->apiLogout();
                } else {
                    $this->sendError('طلب غير صحيح', 400);
                }
                break;
                
            default:
                $this->sendError('طريقة غير مدعومة', 405);
        }
    }
    
    /**
     * تسجيل دخول عبر API
     */
    private function apiLogin() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $this->sendError('اسم المستخدم وكلمة المرور مطلوبان', 400);
            return;
        }
        
        $stmt = $this->db->prepare("
            SELECT id, username, email, password, role, status 
            FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            $this->sendError('بيانات الدخول غير صحيحة', 401);
            return;
        }
        
        // إنشاء توكن API
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 86400); // 24 ساعة
        
        $stmt = $this->db->prepare("
            INSERT INTO user_tokens (user_id, token, type, expires_at) 
            VALUES (?, ?, 'api', ?)
        ");
        $stmt->execute([$user['id'], hash('sha256', $token), $expires]);
        
        $this->sendSuccess([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'expires_at' => $expires
        ]);
    }
    
    /**
     * معالجة طلبات المواضيع
     */
    private function handleTopics() {
        switch ($this->method) {
            case 'GET':
                if (isset($this->params[0])) {
                    $this->getTopicById($this->params[0]);
                } else {
                    $this->getTopics();
                }
                break;
                
            case 'POST':
                $this->requireAuth();
                $this->createTopic();
                break;
                
            case 'PUT':
                $this->requireAuth();
                if (isset($this->params[0])) {
                    $this->updateTopic($this->params[0]);
                } else {
                    $this->sendError('معرف الموضوع مطلوب', 400);
                }
                break;
                
            case 'DELETE':
                $this->requireAuth();
                if (isset($this->params[0])) {
                    $this->deleteTopic($this->params[0]);
                } else {
                    $this->sendError('معرف الموضوع مطلوب', 400);
                }
                break;
                
            default:
                $this->sendError('طريقة غير مدعومة', 405);
        }
    }
    
    /**
     * الحصول على قائمة المواضيع
     */
    private function getTopics() {
        $page = (int)($_GET['page'] ?? 1);
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        $offset = ($page - 1) * $limit;
        
        $category_id = $_GET['category_id'] ?? null;
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'latest';
        
        $where_conditions = ["t.status = 'published'"];
        $params = [];
        
        if ($category_id) {
            $where_conditions[] = "t.category_id = ?";
            $params[] = $category_id;
        }
        
        if ($search) {
            $where_conditions[] = "(t.title LIKE ? OR t.content LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $order_clause = match($sort) {
            'popular' => 'ORDER BY t.views DESC',
            'comments' => 'ORDER BY t.comments_count DESC',
            'oldest' => 'ORDER BY t.created_at ASC',
            default => 'ORDER BY t.created_at DESC'
        };
        
        // الحصول على المواضيع
        $stmt = $this->db->prepare("
            SELECT t.id, t.title, t.slug, t.excerpt, t.views, t.comments_count,
                   t.created_at, t.updated_at, u.username, u.avatar,
                   c.name as category_name, c.slug as category_slug
            FROM topics t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE {$where_clause}
            {$order_clause}
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // الحصول على العدد الإجمالي
        $count_params = array_slice($params, 0, -2);
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM topics t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE {$where_clause}
        ");
        $stmt->execute($count_params);
        $total = $stmt->fetchColumn();
        
        $this->sendSuccess([
            'topics' => $topics,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * معالجة طلبات البحث
     */
    private function handleSearch() {
        if ($this->method !== 'GET') {
            $this->sendError('طريقة غير مدعومة', 405);
            return;
        }
        
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'all';
        $limit = min((int)($_GET['limit'] ?? 10), 50);
        
        if (strlen($query) < 2) {
            $this->sendError('استعلام البحث قصير جداً', 400);
            return;
        }
        
        $results = [];
        
        switch ($type) {
            case 'topics':
                $results = $this->searchTopics($query, $limit);
                break;
                
            case 'services':
                $results = $this->searchServices($query, $limit);
                break;
                
            case 'courses':
                $results = $this->searchCourses($query, $limit);
                break;
                
            case 'users':
                $results = $this->searchUsers($query, $limit);
                break;
                
            default:
                $results = [
                    'topics' => $this->searchTopics($query, $limit),
                    'services' => $this->searchServices($query, $limit),
                    'courses' => $this->searchCourses($query, $limit),
                    'users' => $this->searchUsers($query, $limit)
                ];
        }
        
        $this->sendSuccess(['results' => $results]);
    }
    
    /**
     * البحث في المواضيع
     */
    private function searchTopics($query, $limit) {
        $stmt = $this->db->prepare("
            SELECT t.id, t.title, t.slug, t.excerpt, t.created_at,
                   u.username, c.name as category_name
            FROM topics t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.status = 'published' 
            AND (t.title LIKE ? OR t.content LIKE ?)
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        
        $search_term = "%{$query}%";
        $stmt->execute([$search_term, $search_term, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * معالجة طلبات الإحصائيات
     */
    private function handleStats() {
        if ($this->method !== 'GET') {
            $this->sendError('طريقة غير مدعومة', 405);
            return;
        }
        
        $stats = [
            'users' => $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
            'topics' => $this->db->query("SELECT COUNT(*) FROM topics WHERE status = 'published'")->fetchColumn(),
            'services' => $this->db->query("SELECT COUNT(*) FROM services WHERE status = 'active'")->fetchColumn(),
            'courses' => $this->db->query("SELECT COUNT(*) FROM courses WHERE status = 'published'")->fetchColumn(),
            'comments' => $this->db->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn()
        ];
        
        $this->sendSuccess($stats);
    }
    
    /**
     * التحقق من المصادقة
     */
    private function requireAuth() {
        $token = $this->getBearerToken();
        
        if (!$token) {
            $this->sendError('توكن المصادقة مطلوب', 401);
            return false;
        }
        
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.role 
            FROM user_tokens t
            JOIN users u ON t.user_id = u.id
            WHERE t.token = ? AND t.type = 'api' 
            AND t.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([hash('sha256', $token)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $this->sendError('توكن غير صحيح أو منتهي الصلاحية', 401);
            return false;
        }
        
        // تعيين معلومات المستخدم
        $_SESSION['api_user'] = $user;
        return true;
    }
    
    /**
     * الحصول على توكن Bearer
     */
    private function getBearerToken() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * إرسال استجابة نجاح
     */
    private function sendSuccess($data = null, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * إرسال استجابة خطأ
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// تشغيل API
new APIController();

