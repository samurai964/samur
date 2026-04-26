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
        $comments = get_all_comments();
        $response['success'] = true;
        $response['data'] = $comments;
        break;
    case 'POST':
        // Add comment logic
        $response['message'] = 'إضافة تعليق غير مدعومة حالياً عبر API.';
        break;
    case 'PUT':
        // Update comment logic
        $response['message'] = 'تحديث تعليق غير مدعومة حالياً عبر API.';
        break;
    case 'DELETE':
        // Delete comment logic
        $response['message'] = 'حذف تعليق غير مدعومة حالياً عبر API.';
        break;
    default:
        $response['message'] = 'طريقة الطلب غير مدعومة.';
        break;
}

echo json_encode($response);
?>

