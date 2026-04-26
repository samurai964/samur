<?php
require_once __DIR__ . 
'/../../core/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!is_admin()) {
    $response['message'] = 'غير مصرح لك بالوصول.';
    echo json_encode($response);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        $posts = get_all_posts();
        $response['success'] = true;
        $response['data'] = $posts;
        break;
    case 'POST':
        // Add post logic
        $response['message'] = 'إضافة مشاركة غير مدعومة حالياً عبر API.';
        break;
    case 'PUT':
        // Update post logic
        $response['message'] = 'تحديث مشاركة غير مدعومة حالياً عبر API.';
        break;
    case 'DELETE':
        // Delete post logic
        $response['message'] = 'حذف مشاركة غير مدعومة حالياً عبر API.';
        break;
    default:
        $response['message'] = 'طريقة الطلب غير مدعومة.';
        break;
}

echo json_encode($response);
?>

