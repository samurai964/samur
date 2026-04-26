<?php

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Auth.php';

class ServicesController extends Controller {
    private $auth;
    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix) {
        parent::__construct();
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->auth = new Auth($pdo, $prefix);
    }

    // عرض جميع الخدمات
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
        $maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;

        try {
            $whereClause = "WHERE s.status = 'active'";
            $params = [];
            
            if ($category) {
                $whereClause .= " AND s.category_id = ?";
                $params[] = $category;
            }
            
            if ($search) {
                $whereClause .= " AND (s.title LIKE ? OR s.description LIKE ? OR s.tags LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($minPrice !== null) {
                $whereClause .= " AND s.price >= ?";
                $params[] = $minPrice;
            }
            
            if ($maxPrice !== null) {
                $whereClause .= " AND s.price <= ?";
                $params[] = $maxPrice;
            }

            $sql = "SELECT s.*, u.username as seller_name, u.first_name, u.last_name, 
                           c.name as category_name, 
                           AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                    FROM `{$this->prefix}services` s 
                    LEFT JOIN `{$this->prefix}users` u ON s.seller_id = u.id 
                    LEFT JOIN `{$this->prefix}service_categories` c ON s.category_id = c.id 
                    LEFT JOIN `{$this->prefix}service_reviews` r ON s.id = r.service_id
                    $whereClause 
                    GROUP BY s.id
                    ORDER BY s.featured DESC, s.created_at DESC 
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // حساب العدد الإجمالي
            $countSql = "SELECT COUNT(DISTINCT s.id) FROM `{$this->prefix}services` s $whereClause";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalServices = $countStmt->fetchColumn();
            $totalPages = ceil($totalServices / $limit);

            // جلب فئات الخدمات
            $categoriesStmt = $this->pdo->query("SELECT * FROM `{$this->prefix}service_categories` ORDER BY `name`");
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/services/index', [
                'services' => $services,
                'categories' => $categories,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'filters' => [
                    'category' => $category,
                    'search' => $search,
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice
                ]
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب الخدمات: ' . $e->getMessage();
            $this->view('frontend/services/index', ['services' => [], 'categories' => []]);
        }
    }

    // عرض تفاصيل خدمة
    public function detail() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            header('Location: /services');
            exit();
        }

        try {
            // جلب الخدمة
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.username as seller_name, u.first_name, u.last_name, 
                       u.avatar, u.created_at as seller_since, u.country, u.city,
                       c.name as category_name,
                       AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM `{$this->prefix}services` s 
                LEFT JOIN `{$this->prefix}users` u ON s.seller_id = u.id 
                LEFT JOIN `{$this->prefix}service_categories` c ON s.category_id = c.id 
                LEFT JOIN `{$this->prefix}service_reviews` r ON s.id = r.service_id
                WHERE s.id = ? AND s.status = 'active'
                GROUP BY s.id
            ");
            $stmt->execute([$id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$service) {
                header('Location: /services');
                exit();
            }

            // تحديث عدد المشاهدات
            $updateStmt = $this->pdo->prepare("UPDATE `{$this->prefix}services` SET `views` = `views` + 1 WHERE `id` = ?");
            $updateStmt->execute([$id]);

            // جلب باقات الخدمة
            $packagesStmt = $this->pdo->prepare("
                SELECT * FROM `{$this->prefix}service_packages` 
                WHERE `service_id` = ? 
                ORDER BY `price` ASC
            ");
            $packagesStmt->execute([$id]);
            $packages = $packagesStmt->fetchAll(PDO::FETCH_ASSOC);

            // جلب المراجعات
            $reviewsStmt = $this->pdo->prepare("
                SELECT r.*, u.username as reviewer_name, u.first_name, u.last_name, u.avatar
                FROM `{$this->prefix}service_reviews` r 
                LEFT JOIN `{$this->prefix}users` u ON r.buyer_id = u.id 
                WHERE r.service_id = ? 
                ORDER BY r.created_at DESC 
                LIMIT 10
            ");
            $reviewsStmt->execute([$id]);
            $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

            // جلب خدمات مشابهة
            $similarStmt = $this->pdo->prepare("
                SELECT s.*, u.username as seller_name, AVG(r.rating) as avg_rating
                FROM `{$this->prefix}services` s 
                LEFT JOIN `{$this->prefix}users` u ON s.seller_id = u.id 
                LEFT JOIN `{$this->prefix}service_reviews` r ON s.id = r.service_id
                WHERE s.category_id = ? AND s.id != ? AND s.status = 'active'
                GROUP BY s.id
                ORDER BY RAND() 
                LIMIT 4
            ");
            $similarStmt->execute([$service['category_id'], $id]);
            $similarServices = $similarStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/services/detail', [
                'service' => $service,
                'packages' => $packages,
                'reviews' => $reviews,
                'similarServices' => $similarServices
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب الخدمة: ' . $e->getMessage();
            header('Location: /services');
            exit();
        }
    }

    // طلب خدمة
    public function order() {
        if (!$this->auth->check()) {
            $_SESSION['error_message'] = 'يجب تسجيل الدخول لطلب الخدمة.';
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
            $packageId = isset($_POST['package_id']) ? (int)$_POST['package_id'] : null;
            $requirements = isset($_POST['requirements']) ? trim($_POST['requirements']) : '';
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

            if (!$serviceId || !$requirements) {
                $_SESSION['error_message'] = 'جميع الحقول مطلوبة.';
                header("Location: /service/$serviceId");
                exit();
            }

            try {
                $user = $this->auth->user();
                
                // جلب معلومات الخدمة والباقة
                if ($packageId) {
                    $stmt = $this->pdo->prepare("
                        SELECT sp.*, s.seller_id, s.title as service_title
                        FROM `{$this->prefix}service_packages` sp
                        JOIN `{$this->prefix}services` s ON sp.service_id = s.id
                        WHERE sp.id = ? AND s.id = ?
                    ");
                    $stmt->execute([$packageId, $serviceId]);
                    $package = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$package) {
                        $_SESSION['error_message'] = 'الباقة المحددة غير موجودة.';
                        header("Location: /service/$serviceId");
                        exit();
                    }
                    
                    $totalPrice = $package['price'] * $quantity;
                    $deliveryTime = $package['delivery_time'];
                } else {
                    $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}services` WHERE `id` = ?");
                    $stmt->execute([$serviceId]);
                    $service = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$service) {
                        $_SESSION['error_message'] = 'الخدمة غير موجودة.';
                        header("Location: /services");
                        exit();
                    }
                    
                    $totalPrice = $service['price'] * $quantity;
                    $deliveryTime = $service['delivery_time'];
                }

                // إنشاء الطلب
                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}service_orders` 
                    (`service_id`, `package_id`, `buyer_id`, `seller_id`, `requirements`, 
                     `quantity`, `total_price`, `delivery_time`, `status`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $serviceId,
                    $packageId,
                    $user['id'],
                    $package['seller_id'] ?? $service['seller_id'],
                    $requirements,
                    $quantity,
                    $totalPrice,
                    $deliveryTime
                ]);

                $orderId = $this->pdo->lastInsertId();
                $_SESSION['success_message'] = 'تم إرسال طلبك بنجاح. سيتم التواصل معك قريباً.';
                header("/order/$orderId");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في إرسال الطلب: ' . $e->getMessage();
                header("Location: /service/$serviceId");
                exit();
            }
        }
    }

    // عرض طلب
    public function viewOrder() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            header('Location: /services');
            exit();
        }

        try {
            $user = $this->auth->user();
            
            $stmt = $this->pdo->prepare("
                SELECT o.*, s.title as service_title, s.image as service_image,
                       sp.name as package_name,
                       buyer.username as buyer_name, buyer.email as buyer_email,
                       seller.username as seller_name, seller.email as seller_email
                FROM `{$this->prefix}service_orders` o
                JOIN `{$this->prefix}services` s ON o.service_id = s.id
                LEFT JOIN `{$this->prefix}service_packages` sp ON o.package_id = sp.id
                JOIN `{$this->prefix}users` buyer ON o.buyer_id = buyer.id
                JOIN `{$this->prefix}users` seller ON o.seller_id = seller.id
                WHERE o.id = ? AND (o.buyer_id = ? OR o.seller_id = ?)
            ");
            $stmt->execute([$id, $user['id'], $user['id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $_SESSION['error_message'] = 'الطلب غير موجود أو ليس لديك صلاحية لعرضه.';
                header('Location: /services');
                exit();
            }

            // جلب رسائل الطلب
            $messagesStmt = $this->pdo->prepare("
                SELECT m.*, u.username as sender_name
                FROM `{$this->prefix}order_messages` m
                JOIN `{$this->prefix}users` u ON m.sender_id = u.id
                WHERE m.order_id = ?
                ORDER BY m.created_at ASC
            ");
            $messagesStmt->execute([$id]);
            $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/services/order', [
                'order' => $order,
                'messages' => $messages,
                'userRole' => ($order['buyer_id'] == $user['id']) ? 'buyer' : 'seller'
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب الطلب: ' . $e->getMessage();
            header('Location: /services');
            exit();
        }
    }

    // إرسال رسالة في الطلب
    public function sendMessage() {
        if (!$this->auth->check()) {
            echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';

            if (!$orderId || !$message) {
                echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
                exit();
            }

            try {
                $user = $this->auth->user();
                
                // التحقق من صلاحية المستخدم للطلب
                $checkStmt = $this->pdo->prepare("
                    SELECT id FROM `{$this->prefix}service_orders` 
                    WHERE `id` = ? AND (`buyer_id` = ? OR `seller_id` = ?)
                ");
                $checkStmt->execute([$orderId, $user['id'], $user['id']]);
                
                if (!$checkStmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية']);
                    exit();
                }

                // إرسال الرسالة
                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}order_messages` 
                    (`order_id`, `sender_id`, `message`) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$orderId, $user['id'], $message]);

                echo json_encode(['success' => true, 'message' => 'تم إرسال الرسالة']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'خطأ في إرسال الرسالة']);
            }
        }
        exit();
    }

    // تحديث حالة الطلب
    public function updateOrderStatus() {
        if (!$this->auth->check()) {
            echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
            $status = isset($_POST['status']) ? trim($_POST['status']) : '';

            if (!$orderId || !$status) {
                echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
                exit();
            }

            try {
                $user = $this->auth->user();
                
                // التحقق من الصلاحية حسب الحالة المطلوبة
                if ($status === 'accepted' || $status === 'rejected' || $status === 'in_progress' || $status === 'delivered') {
                    // البائع فقط يمكنه تحديث هذه الحالات
                    $checkStmt = $this->pdo->prepare("
                        SELECT id FROM `{$this->prefix}service_orders` 
                        WHERE `id` = ? AND `seller_id` = ?
                    ");
                    $checkStmt->execute([$orderId, $user['id']]);
                } elseif ($status === 'completed' || $status === 'cancelled') {
                    // المشتري يمكنه إكمال أو إلغاء الطلب
                    $checkStmt = $this->pdo->prepare("
                        SELECT id FROM `{$this->prefix}service_orders` 
                        WHERE `id` = ? AND `buyer_id` = ?
                    ");
                    $checkStmt->execute([$orderId, $user['id']]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'حالة غير صالحة']);
                    exit();
                }
                
                if (!$checkStmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية']);
                    exit();
                }

                // تحديث الحالة
                $stmt = $this->pdo->prepare("
                    UPDATE `{$this->prefix}service_orders` 
                    SET `status` = ?, `updated_at` = NOW() 
                    WHERE `id` = ?
                ");
                $stmt->execute([$status, $orderId]);

                echo json_encode(['success' => true, 'message' => 'تم تحديث حالة الطلب']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'خطأ في تحديث الحالة']);
            }
        }
        exit();
    }

    // إضافة مراجعة
    public function addReview() {
        if (!$this->auth->check()) {
            $_SESSION['error_message'] = 'يجب تسجيل الدخول لإضافة مراجعة.';
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
            $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
            $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
            $review = isset($_POST['review']) ? trim($_POST['review']) : '';

            if (!$serviceId || !$orderId || !$rating || !$review) {
                $_SESSION['error_message'] = 'جميع الحقول مطلوبة.';
                header("Location: /service/$serviceId");
                exit();
            }

            if ($rating < 1 || $rating > 5) {
                $_SESSION['error_message'] = 'التقييم يجب أن يكون بين 1 و 5.';
                header("Location: /service/$serviceId");
                exit();
            }

            try {
                $user = $this->auth->user();
                
                // التحقق من أن المستخدم اشترى الخدمة وأكملها
                $checkStmt = $this->pdo->prepare("
                    SELECT id FROM `{$this->prefix}service_orders` 
                    WHERE `id` = ? AND `service_id` = ? AND `buyer_id` = ? AND `status` = 'completed'
                ");
                $checkStmt->execute([$orderId, $serviceId, $user['id']]);
                
                if (!$checkStmt->fetch()) {
                    $_SESSION['error_message'] = 'يجب إكمال الطلب أولاً لإضافة مراجعة.';
                    header("Location: /service/$serviceId");
                    exit();
                }

                // التحقق من عدم وجود مراجعة سابقة
                $existingStmt = $this->pdo->prepare("
                    SELECT id FROM `{$this->prefix}service_reviews` 
                    WHERE `service_id` = ? AND `buyer_id` = ? AND `order_id` = ?
                ");
                $existingStmt->execute([$serviceId, $user['id'], $orderId]);
                
                if ($existingStmt->fetch()) {
                    $_SESSION['error_message'] = 'لقد قمت بإضافة مراجعة لهذا الطلب مسبقاً.';
                    header("Location: /service/$serviceId");
                    exit();
                }

                // إضافة المراجعة
                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}service_reviews` 
                    (`service_id`, `buyer_id`, `order_id`, `rating`, `review`) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$serviceId, $user['id'], $orderId, $rating, $review]);

                $_SESSION['success_message'] = 'تم إضافة المراجعة بنجاح.';
                header("Location: /service/$serviceId");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'خطأ في إضافة المراجعة: ' . $e->getMessage();
                header("Location: /service/$serviceId");
                exit();
            }
        }
    }

    // لوحة تحكم البائع
    public function sellerDashboard() {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $user = $this->auth->user();
        
        try {
            // إحصائيات البائع
            $stats = [];
            
            // عدد الخدمات
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}services` WHERE `seller_id` = ?");
            $stmt->execute([$user['id']]);
            $stats['total_services'] = $stmt->fetchColumn();
            
            // عدد الطلبات
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}service_orders` WHERE `seller_id` = ?");
            $stmt->execute([$user['id']]);
            $stats['total_orders'] = $stmt->fetchColumn();
            
            // الطلبات المعلقة
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}service_orders` WHERE `seller_id` = ? AND `status` = 'pending'");
            $stmt->execute([$user['id']]);
            $stats['pending_orders'] = $stmt->fetchColumn();
            
            // إجمالي الأرباح
            $stmt = $this->pdo->prepare("SELECT SUM(`total_price`) FROM `{$this->prefix}service_orders` WHERE `seller_id` = ? AND `status` = 'completed'");
            $stmt->execute([$user['id']]);
            $stats['total_earnings'] = $stmt->fetchColumn() ?: 0;

            // الطلبات الأخيرة
            $ordersStmt = $this->pdo->prepare("
                SELECT o.*, s.title as service_title, u.username as buyer_name
                FROM `{$this->prefix}service_orders` o
                JOIN `{$this->prefix}services` s ON o.service_id = s.id
                JOIN `{$this->prefix}users` u ON o.buyer_id = u.id
                WHERE o.seller_id = ?
                ORDER BY o.created_at DESC
                LIMIT 10
            ");
            $ordersStmt->execute([$user['id']]);
            $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->view('frontend/services/seller_dashboard', [
                'stats' => $stats,
                'recentOrders' => $recentOrders
            ]);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'خطأ في جلب البيانات: ' . $e->getMessage();
            $this->view('frontend/services/seller_dashboard', ['stats' => [], 'recentOrders' => []]);
        }
    }
}

?>

