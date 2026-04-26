<?php
require_once '../bootstrap.php';

// إضافة Sidebar
if (isset($_POST['add_sidebar'])) {
    $name = $_POST['name'];
    $position = $_POST['position'];

    $stmt = $pdo->prepare("INSERT INTO sidebars (name, position) VALUES (?, ?)");
    $stmt->execute([$name, $position]);
}

// حذف Sidebar
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM sidebars WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}

// إضافة عنصر داخل Sidebar
if (isset($_POST['add_item'])) {
    $sidebar_id = $_POST['sidebar_id'];
    $content = $_POST['content'];

    $stmt = $pdo->prepare("
        INSERT INTO sidebar_items (sidebar_id, type, content)
        VALUES (?, 'html', ?)
    ");
    $stmt->execute([$sidebar_id, $content]);
}

// جلب البيانات
$sidebars = $pdo->query("SELECT * FROM sidebars")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة القوائم الجانبية</title>

<style>
body { font-family: Arial; padding:20px; }
.box { border:1px solid #ccc; padding:10px; margin-bottom:10px; }
</style>

</head>
<body>

<h2>إضافة Sidebar</h2>

<form method="POST">
    <input type="text" name="name" placeholder="اسم القائمة" required>

<select name="position">
    <option value="left">يسار</option>
    <option value="right">يمين</option>
</select>

<button name="add_sidebar">إضافة</button>

</form>

<hr>

<h2>القوائم الحالية</h2>

<?php foreach ($sidebars as $sb): ?>

<div class="box">
    <strong><?= $sb['name'] ?> (<?= $sb['position'] ?>)</strong>

<a href="?delete=<?= $sb['id'] ?>" style="color:red;">حذف</a>

<form method="POST">
    <input type="hidden" name="sidebar_id" value="<?= $sb['id'] ?>">
    <input type="text" name="content" placeholder="محتوى HTML">
    <button name="add_item">إضافة عنصر</button>
</form>

</div>

<?php endforeach; ?>

</body>
</html>
