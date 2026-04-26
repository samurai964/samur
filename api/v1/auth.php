<?php
require_once '../../core/JWT.php';

function checkAuth($pdo) {

    $headers = getallheaders();

    // ===== JWT =====
    if (isset($headers['Authorization'])) {

        $token = str_replace("Bearer ", "", $headers['Authorization']);
        $data = JWT::decode($token);

        if ($data && isset($data['user_id'])) {
            return $data['user_id'];
        }
    }

    // ===== API KEY =====
    if (isset($headers['X-API-KEY'])) {

        $key = $headers['X-API-KEY'];

        $stmt = $pdo->prepare("SELECT user_id FROM api_keys WHERE api_key = ?");
        $stmt->execute([$key]);

        $userId = $stmt->fetchColumn();

        if ($userId) return $userId;
    }

    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
?>
