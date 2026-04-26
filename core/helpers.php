<?php

function dd($data) {
    echo '<pre>';
    print_r($data);
    die;
}

function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
function register_dashboard_widget($widget) {
require_once __DIR__ . '/DashboardWidgets.php';
DashboardWidgets::register($widget);
}

function verify_csrf_token($token) {
return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in() {
return isset($_SESSION['user_id']);
}

function has_role($role) {
if (!isset($_SESSION['user_id'])) return false;

global $pdo;

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);

return $stmt->fetchColumn() === $role;

}

function setting($key, $default = null) {
    return Settings::get($key, $default);
}

function save_setting($key, $value) {
    $pdo = db();

    // تحقق هل الإعداد موجود
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);

    if ($stmt->fetchColumn() > 0) {
        // تحديث
        $stmt = $pdo->prepare("UPDATE settings SET `value` = ? WHERE `key` = ?");
        $stmt->execute([$value, $key]);
    } else {
        // إدخال جديد
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }

    // تحديث الكاش داخل Settings
    Settings::load($pdo);
}
?>
