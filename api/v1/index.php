<?php
require_once '../../bootstrap.php';
require_once 'auth.php';

header('Content-Type: application/json');

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// 🔐 المستخدم الحالي
$userId = checkAuth($pdo);

// Input
$endpoint = $_GET['endpoint'] ?? null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$limit = 10;
$offset = ($page - 1) * $limit;

// Router
switch ($endpoint) {

    case 'services':

        $total = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT id, title, price 
            FROM services 
            ORDER BY id DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        response([
            "user_id" => $userId,
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);

    break;

    case 'projects':

        $total = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT id, title, budget 
            FROM projects 
            ORDER BY id DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        response([
            "user_id" => $userId,
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);

    break;

    default:
        response(["error" => "Invalid endpoint"], 404);
}
?>
