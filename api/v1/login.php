<?php
require_once '../../bootstrap.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$email = Security::sanitizeInput($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["error" => "Missing data"]);
    exit;
}

if ($user->login($email, $password)) {

    $payload = [
        "user_id" => $_SESSION['user_id'],
        "time" => time()
    ];

    $token = JWT::encode($payload);

    echo json_encode([
        "status" => "success",
        "token" => $token
    ]);

} else {
    echo json_encode(["error" => "Invalid credentials"]);
}
?>
