<?php
require_once 'bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ يجب تسجيل الدخول");
}

ob_start();
?>

<h1>📊 لوحة التحكم</h1>

<ul>
    <li><a href="profile.php">الملف الشخصي</a></li>
    <li><a href="#">الإعدادات</a></li>
</ul>

<?php
$content = ob_get_clean();
$layout->render($content);
