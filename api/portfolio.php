<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/helpers.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'غير مصرح']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        case 'PUT':
            handlePutRequest($action);
            break;
        case 'DELETE':
            handleDeleteRequest($action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'طريقة غير مدعومة']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'خطأ في الخادم: ' . $e->getMessage()]);
}

function handleGetRequest($action) {
    global $pdo;
    
    switch ($action) {
        case 'list':
            getPortfolioList();
            break;
        case 'item':
            getPortfolioItem();
            break;
        case 'user':
            getUserPortfolio();
            break;
        case 'categories':
            getCategories();
            break;
        case 'stats':
            getPortfolioStats();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'إجراء غير صالح']);
    }
}

function handlePostRequest($action) {
    global $pdo;
    
    switch ($action) {
        case 'create':
            createPortfolioItem();
            break;
        case 'like':
            toggleLike();
            break;
        case 'view':
            recordView();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'إجراء غير صالح']);
    }
}

function handlePutRequest($action) {
    switch ($action) {
        case 'update':
            updatePortfolioItem();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'إجراء غير صالح']);
    }
}

function handleDeleteRequest($action) {
    switch ($action) {
        case 'delete':
            deletePortfolioItem();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'إجراء غير صالح']);
    }
}

function getPortfolioList() {
    global $pdo;
    
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(50, max(1, (int)($_GET['per_page'] ?? 12)));
    $offset = ($page - 1) * $per_page;
    
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $user_id = $_GET['user_id'] ?? '';
    $sort = $_GET['sort'] ?? 'newest';
    
    // بناء الاستعلام
    $where_conditions = ["pi.status = 'published'"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(pi.title LIKE ? OR pi.description LIKE ? OR pi.technologies LIKE ?)";
        $search_term = "%$search%";
        $params = array_merge($params, [$search_term, $search_term, $search_term]);
    }
    
    if (!empty($category)) {
        $where_conditions[] = "pi.category = ?";
        $params[] = $category;
    }
    
    if (!empty($user_id)) {
        $where_conditions[] = "pi.user_id = ?";
        $params[] = $user_id;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // ترتيب النتائج
    $order_clause = match($sort) {
        'oldest' => 'ORDER BY pi.created_at ASC',
        'popular' => 'ORDER BY likes_count DESC, pi.created_at DESC',
        'viewed' => 'ORDER BY views_count DESC, pi.created_at DESC',
        default => 'ORDER BY pi.created_at DESC'
    };
    
    // جلب البيانات
    $stmt = $pdo->prepare("
        SELECT pi.*, u.username, u.first_name, u.last_name, u.avatar,
               (SELECT image_path FROM portfolio_images WHERE portfolio_id = pi.id AND is_primary = 1 LIMIT 1) as primary_image,
               (SELECT COUNT(*) FROM portfolio_likes WHERE portfolio_id = pi.id) as likes_count,
               (SELECT COUNT(*) FROM portfolio_views WHERE portfolio_id = pi.id) as views_count,
               (SELECT COUNT(*) FROM portfolio_likes WHERE portfolio_id = pi.id AND user_id = ?) as user_liked
        FROM portfolio_items pi
        JOIN users u ON pi.user_id = u.id
        $where_clause
        $order_clause
        LIMIT $per_page OFFSET $offset
    ");
    
    $params_with_user = array_merge([$_SESSION['user_id']], $params);
    $stmt->execute($params_with_user);
    $items = $stmt->fetchAll();
    
    // عدد النتائج الإجمالي
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM portfolio_items pi 
        JOIN users u ON pi.user_id = u.id 
        $where_clause
    ");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $items,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page)
        ]
    ]);
}

function getPortfolioItem() {
    global $pdo;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'معرف غير صالح']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT pi.*, u.username, u.first_name, u.last_name, u.avatar, u.bio,
               (SELECT COUNT(*) FROM portfolio_likes WHERE portfolio_id = pi.id) as likes_count,
               (SELECT COUNT(*) FROM portfolio_views WHERE portfolio_id = pi.id) as views_count,
               (SELECT COUNT(*) FROM portfolio_likes WHERE portfolio_id = pi.id AND user_id = ?) as user_liked
        FROM portfolio_items pi
        JOIN users u ON pi.user_id = u.id
        WHERE pi.id = ? AND pi.status = 'published'
    ");
    
    $stmt->execute([$_SESSION['user_id'], $id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'العمل غير موجود']);
        return;
    }
    
    // جلب الصور
    $stmt = $pdo->prepare("
        SELECT * FROM portfolio_images 
        WHERE portfolio_id = ? 
        ORDER BY is_primary DESC, created_at ASC
    ");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
    
    $item['images'] = $images;
    
    echo json_encode([
        'success' => true,
        'data' => $item
    ]);
}

function getUserPortfolio() {
    global $pdo;
    
    $user_id = (int)($_GET['user_id'] ?? $_SESSION['user_id']);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(50, max(1, (int)($_GET['per_page'] ?? 12)));
    $offset = ($page - 1) * $per_page;
    
    $stmt = $pdo->prepare("
        SELECT pi.*, 
               (SELECT image_path FROM portfolio_images WHERE portfolio_id = pi.id AND is_primary = 1 LIMIT 1) as primary_image,
               (SELECT COUNT(*) FROM portfolio_likes WHERE portfolio_id = pi.id) as likes_count,
               (SELECT COUNT(*) FROM portfolio_views WHERE portfolio_id = pi.id) as views_count
        FROM portfolio_items pi
        WHERE pi.user_id = ? AND pi.status = 'published'
        ORDER BY pi.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
    
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
    
    // عدد النتائج الإجمالي
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM portfolio_items 
        WHERE user_id = ? AND status = 'published'
    ");
    $stmt->execute([$user_id]);
    $total = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $items,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page)
        ]
    ]);
}

function getCategories() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT category, COUNT(*) as count
        FROM portfolio_items 
        WHERE status = 'published' AND category IS NOT NULL AND category != ''
        GROUP BY category
        ORDER BY count DESC, category ASC
    ");
    
    $categories = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
}

function getPortfolioStats() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    
    $stats = [
        'total_items' => $pdo->prepare("SELECT COUNT(*) FROM portfolio_items WHERE user_id = ?")->execute([$user_id])->fetchColumn(),
        'published_items' => $pdo->prepare("SELECT COUNT(*) FROM portfolio_items WHERE user_id = ? AND status = 'published'")->execute([$user_id])->fetchColumn(),
        'total_likes' => $pdo->prepare("SELECT COUNT(*) FROM portfolio_likes pl JOIN portfolio_items pi ON pl.portfolio_id = pi.id WHERE pi.user_id = ?")->execute([$user_id])->fetchColumn(),
        'total_views' => $pdo->prepare("SELECT COUNT(*) FROM portfolio_views pv JOIN portfolio_items pi ON pv.portfolio_id = pi.id WHERE pi.user_id = ?")->execute([$user_id])->fetchColumn(),
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function createPortfolioItem() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $category = trim($input['category'] ?? '');
    $project_url = trim($input['project_url'] ?? '');
    $technologies = trim($input['technologies'] ?? '');
    
    if (empty($title) || empty($description)) {
        http_response_code(400);
        echo json_encode(['error' => 'العنوان والوصف مطلوبان']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO portfolio_items (user_id, title, description, category, project_url, technologies, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'published')
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $title,
            $description,
            $category,
            $project_url,
            $technologies
        ]);
        
        $portfolio_id = $pdo->lastInsertId();
        
        $pdo->commit();
        
        // تسجيل النشاط
        logActivity($_SESSION['user_id'], 'create_portfolio', "تم إنشاء عمل جديد: $title");
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء العمل بنجاح',
            'data' => ['id' => $portfolio_id]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'فشل في إنشاء العمل']);
    }
}

function toggleLike() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $portfolio_id = (int)($input['portfolio_id'] ?? 0);
    
    if ($portfolio_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'معرف غير صالح']);
        return;
    }
    
    // التحقق من وجود العمل
    $stmt = $pdo->prepare("SELECT id FROM portfolio_items WHERE id = ? AND status = 'published'");
    $stmt->execute([$portfolio_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'العمل غير موجود']);
        return;
    }
    
    // التحقق من وجود إعجاب سابق
    $stmt = $pdo->prepare("SELECT id FROM portfolio_likes WHERE portfolio_id = ? AND user_id = ?");
    $stmt->execute([$portfolio_id, $_SESSION['user_id']]);
    $existing_like = $stmt->fetch();
    
    try {
        if ($existing_like) {
            // إزالة الإعجاب
            $stmt = $pdo->prepare("DELETE FROM portfolio_likes WHERE id = ?");
            $stmt->execute([$existing_like['id']]);
            $action = 'unliked';
        } else {
            // إضافة إعجاب
            $stmt = $pdo->prepare("INSERT INTO portfolio_likes (portfolio_id, user_id) VALUES (?, ?)");
            $stmt->execute([$portfolio_id, $_SESSION['user_id']]);
            $action = 'liked';
        }
        
        // جلب العدد الجديد
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM portfolio_likes WHERE portfolio_id = ?");
        $stmt->execute([$portfolio_id]);
        $likes_count = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'likes_count' => $likes_count
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'فشل في تحديث الإعجاب']);
    }
}

function recordView() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $portfolio_id = (int)($input['portfolio_id'] ?? 0);
    
    if ($portfolio_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'معرف غير صالح']);
        return;
    }
    
    // التحقق من عدم وجود مشاهدة سابقة من نفس المستخدم في آخر ساعة
    $stmt = $pdo->prepare("
        SELECT id FROM portfolio_views 
        WHERE portfolio_id = ? AND user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$portfolio_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        try {
            $stmt = $pdo->prepare("INSERT INTO portfolio_views (portfolio_id, user_id) VALUES (?, ?)");
            $stmt->execute([$portfolio_id, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'تم تسجيل المشاهدة']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'فشل في تسجيل المشاهدة']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'المشاهدة مسجلة مسبقاً']);
    }
}

function updatePortfolioItem() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'معرف غير صالح']);
        return;
    }
    
    // التحقق من الملكية
    $stmt = $pdo->prepare("SELECT id FROM portfolio_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'غير مصرح لك بتعديل هذا العمل']);
        return;
    }
    
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $category = trim($input['category'] ?? '');
    $project_url = trim($input['project_url'] ?? '');
    $technologies = trim($input['technologies'] ?? '');
    
    if (empty($title) || empty($description)) {
        http_response_code(400);
        echo json_encode(['error' => 'العنوان والوصف مطلوبان']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE portfolio_items 
            SET title = ?, description = ?, category = ?, project_url = ?, technologies = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$title, $description, $category, $project_url, $technologies, $id]);
        
        // تسجيل النشاط
        logActivity($_SESSION['user_id'], 'update_portfolio', "تم تحديث العمل: $title");
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث العمل بنجاح'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'فشل في تحديث العمل']);
    }
}

function deletePortfolioItem() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'معرف غير صالح']);
        return;
    }
    
    // التحقق من الملكية
    $stmt = $pdo->prepare("SELECT title FROM portfolio_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $item = $stmt->fetch();
    
    if (!$item) {
        http_response_code(403);
        echo json_encode(['error' => 'غير مصرح لك بحذف هذا العمل']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // حذف الصور من الخادم
        $stmt = $pdo->prepare("SELECT image_path FROM portfolio_images WHERE portfolio_id = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll();
        
        foreach ($images as $image) {
            if (file_exists($image['image_path'])) {
                unlink($image['image_path']);
            }
        }
        
        // حذف البيانات من قاعدة البيانات
        $stmt = $pdo->prepare("DELETE FROM portfolio_images WHERE portfolio_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM portfolio_likes WHERE portfolio_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM portfolio_views WHERE portfolio_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM portfolio_items WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        
        // تسجيل النشاط
        logActivity($_SESSION['user_id'], 'delete_portfolio', "تم حذف العمل: {$item['title']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف العمل بنجاح'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'فشل في حذف العمل']);
    }
}
?>

