<?php
require_once '../bootstrap.php';

header('Content-Type: application/json');

$service = new Service($pdo);

// 📁 مكان حفظ الكاش
$cacheFile = __DIR__ . '/../cache/services.cache';

// ⏱️ مدة الكاش (30 ثانية)
$cacheTime = 30;

// إذا الكاش موجود ولسه صالح
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    echo file_get_contents($cacheFile);
    exit;
}

// ❗ إذا لا يوجد كاش → نجيب من قاعدة البيانات
$data = $service->getAll();

$json = json_encode($data);

// نحفظ في الكاش
file_put_contents($cacheFile, $json);

// نعرض النتيجة
echo $json;
?>
