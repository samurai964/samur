<?php
require_once 'bootstrap.php';

ob_start();
?>

<h1>اتصل بنا</h1>

<form method="post">
    <input type="text" placeholder="الاسم" style="width:100%;margin-bottom:10px;">
    <input type="email" placeholder="البريد الإلكتروني" style="width:100%;margin-bottom:10px;">
    <textarea placeholder="رسالتك" style="width:100%;height:100px;margin-bottom:10px;"></textarea>
    <button>إرسال</button>
</form>

<?php
$content = ob_get_clean();

$layout->render($content);
