<?php
require_once __DIR__ . '/../bootstrap.php';

ob_start();
?>

<div style="text-align:center;padding:50px;">
    <h1 style="color:red;">❌ غير مصرح لك بالدخول</h1>
    <p>هذه الصفحة مخصصة للإدارة فقط</p>
    <a href="/login.php">🔐 تسجيل الدخول</a>
</div>

<?php
$content = ob_get_clean();
$layout->render($content);
?>
