<?php
require_once 'bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ يجب تسجيل الدخول");
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

ob_start();
?>

<h1>👤 الملف الشخصي</h1>

<p>الاسم: <?= $user['username'] ?></p>
<p>الرصيد: <?= $user['balance'] ?></p>
<p>النقاط: <?= $user['points'] ?></p>

<?php
$content = ob_get_clean();
$layout->render($content);
