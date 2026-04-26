<?php
require_once '../bootstrap.php';

// إضافة إعلان
if (isset($_POST['add_ad'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $placement = $_POST['placement'];

    $stmt = $pdo->prepare("
        INSERT INTO ads (title, content, placement)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([$title, $content, $placement]);
}

// جلب الإعلانات
$ads = $pdo->query("SELECT * FROM ads ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة الإعلانات</title>

<style>
body { font-family: Arial; padding:20px; }
.box { border:1px solid #ccc; padding:10px; margin-bottom:10px; }
</style>

</head>
<body>

<h2>إضافة إعلان</h2>

<form method="POST">
    <input type="text" name="title" placeholder="عنوان الإعلان" required><br><br>

<textarea name="content" placeholder="كود الإعلان HTML" required></textarea><br><br>

<select name="placement">
    <option value="sidebar">Sidebar</option>
    <option value="content_top">أعلى المحتوى</option>
    <option value="content_bottom">أسفل المحتوى</option>
</select><br><br>

<button name="add_ad">إضافة</button>

</form>

<hr>

<h2>الإعلانات الحالية</h2>

<?php foreach ($ads as $ad): ?>

<div class="box">
    <b><?= $ad['title'] ?></b><br><br>

👁️ المشاهدات: <?= $ad['views'] ?><br>
💰 الأرباح: <?= $ad['earnings'] ?><br><br>

<div><?= $ad['content'] ?></div>

</div>

<?php endforeach; ?>

</body>
</html>