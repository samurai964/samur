<?php
require_once __DIR__ . '/bootstrap.php';

ob_start();
?>

<h1>الخدمات</h1>
<p>تم استعادة التصميم المستقر</p>

<?php
$content = ob_get_clean();
$layout->render($content);
?>
