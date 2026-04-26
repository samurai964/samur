<?php
/**
 * API نظام الحملات الإعلانية
 * يتعامل مع طلبات الإعلانات والنقرات والتتبع
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// التعامل مع طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/ad_campaigns/AdDisplayManager.php';
require_once __DIR__ . '/../modules/ad_campaigns/AdTargetingEngine.php';

try {
    // إنشاء اتصال قاعدة البيانات
    $database = new Database();
    $db = $database->getConnection();
    
    // إنشاء مدير العرض
    $display_manager = new AdDisplayManager($db);
    $targeting_engine = new AdTargetingEngine($db);
    
    // تحديد نوع الطلب
    $request_method = $_SERVER['REQUEST_METHOD'];
    $path_info = $_SERVER['PATH_INFO'] ?? '';
    $path_parts = explode('/', trim($path_info, '/'));
    $action = $path_parts[0] ?? '';
    
    switch ($action) {
        case 'get_ads':
            handleGetAds($targeting_engine);
            break;
            
        case 'click':
            handleAdClick($display_manager, $path_parts);
            break;
            
        case 'track_impression':
            handleTrackImpression($db);
            break;
            
        case 'track_click':
            handleTrackClick($db);
            break;
            
        default:
            sendError('Invalid action', 400);
    }
    
} catch (Exception $e) {
    error_log("خطأ في API الإعلانات: " . $e->getMessage());
    sendError('Internal server error', 500);
}

/**
 * التعامل مع طلب الحصول على الإعلانات
 */
function handleGetAds($targeting_engine) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendError('Method not allowed', 405);
        return;
    }
    
    $placement = $_GET['placement'] ?? 'general';
    $limit = min((int)($_GET['limit'] ?? 3), 10); // حد أقصى 10 إعلانات
    $format = $_GET['format'] ?? 'json';
    
    // التحقق من صحة موقع العرض
    $allowed_placements = [
        'homepage', 'sidebar', 'header', 'footer', 'inline',
        'topic_detail', 'category', 'search_results', 'profile',
        'classified_ads', 'directory', 'courses'
    ];
    
    if (!in_array($placement, $allowed_placements)) {
        sendError('Invalid placement', 400);
        return;
    }
    
    try {
        $ads = $targeting_engine->getTargetedAds($placement, $limit);
        
        if ($format === 'html') {
            // إرجاع HTML جاهز للعرض
            $display_manager = new AdDisplayManager($targeting_engine->db);
            $html = $display_manager->renderAds($ads, $placement, 'api');
            
            sendSuccess([
                'html' => $html,
                'count' => count($ads)
            ]);
        } else {
            // إرجاع بيانات JSON
            $formatted_ads = [];
            foreach ($ads as $ad) {
                $formatted_ads[] = formatAdForAPI($ad);
            }
            
            sendSuccess([
                'ads' => $formatted_ads,
                'count' => count($formatted_ads),
                'placement' => $placement
            ]);
        }
        
    } catch (Exception $e) {
        error_log("خطأ في جلب الإعلانات: " . $e->getMessage());
        sendError('Failed to fetch ads', 500);
    }
}

/**
 * التعامل مع نقرة الإعلان
 */
function handleAdClick($display_manager, $path_parts) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendError('Method not allowed', 405);
        return;
    }
    
    $ad_id = $path_parts[1] ?? null;
    
    if (!$ad_id || !is_numeric($ad_id)) {
        sendError('Invalid ad ID', 400);
        return;
    }
    
    try {
        $destination_url = $display_manager->handleAdClick($ad_id);
        
        if ($destination_url) {
            // إعادة توجيه إلى رابط الوجهة
            header("Location: $destination_url");
            exit();
        } else {
            sendError('Ad not found or inactive', 404);
        }
        
    } catch (Exception $e) {
        error_log("خطأ في معالجة نقرة الإعلان: " . $e->getMessage());
        sendError('Failed to process click', 500);
    }
}

/**
 * تتبع مشاهدة الإعلان
 */
function handleTrackImpression($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $ad_id = $input['ad_id'] ?? null;
    
    if (!$ad_id || !is_numeric($ad_id)) {
        sendError('Invalid ad ID', 400);
        return;
    }
    
    try {
        // تسجيل المشاهدة
        $user_id = $_SESSION['user_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $page_url = $input['page_url'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        
        $sql = "INSERT INTO fmc_ad_impressions 
                (ad_id, user_id, ip_address, user_agent, page_url, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$ad_id, $user_id, $ip_address, $user_agent, $page_url]);
        
        sendSuccess(['message' => 'Impression tracked']);
        
    } catch (Exception $e) {
        error_log("خطأ في تتبع المشاهدة: " . $e->getMessage());
        sendError('Failed to track impression', 500);
    }
}

/**
 * تتبع نقرة الإعلان (للإحصائيات فقط)
 */
function handleTrackClick($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $ad_id = $input['ad_id'] ?? null;
    
    if (!$ad_id || !is_numeric($ad_id)) {
        sendError('Invalid ad ID', 400);
        return;
    }
    
    try {
        // تسجيل النقرة للإحصائيات
        $user_id = $_SESSION['user_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $sql = "INSERT INTO fmc_ad_clicks 
                (ad_id, user_id, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$ad_id, $user_id, $ip_address, $user_agent]);
        
        sendSuccess(['message' => 'Click tracked']);
        
    } catch (Exception $e) {
        error_log("خطأ في تتبع النقرة: " . $e->getMessage());
        sendError('Failed to track click', 500);
    }
}

/**
 * تنسيق الإعلان للـ API
 */
function formatAdForAPI($ad) {
    $formatted = [
        'id' => $ad['id'],
        'type' => $ad['type'],
        'title' => $ad['title'],
        'click_url' => "/api/ad_campaigns/click/{$ad['id']}"
    ];
    
    switch ($ad['type']) {
        case 'text':
            $formatted['description'] = $ad['description'];
            $formatted['display_url'] = parse_url($ad['destination_url'], PHP_URL_HOST);
            break;
            
        case 'image':
            $formatted['description'] = $ad['description'] ?? '';
            $formatted['image_url'] = $ad['image_url'];
            $formatted['alt_text'] = $ad['title'];
            break;
            
        case 'html':
            $formatted['html_content'] = $ad['html_content'];
            break;
    }
    
    return $formatted;
}

/**
 * إرسال استجابة نجاح
 */
function sendSuccess($data) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * إرسال استجابة خطأ
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
?>

