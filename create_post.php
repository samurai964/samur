<?php
require_once 'bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $title, $content]);

    $message = "✅ تم نشر الموضوع";
}

ob_start();
?>

<h1>✍️ إنشاء موضوع</h1>

<?php if ($message): ?>

<div style="color:green;"><?= $message ?></div>

<?php endif; ?>

<form method="POST">

<input type="text" name="title" placeholder="عنوان الموضوع" style="width:100%;margin-bottom:10px;">

<textarea name="content" placeholder="محتوى الموضوع" style="width:100%;height:150px;margin-bottom:10px;"></textarea>

<button type="submit">🚀 نشر</button>

</form>

<?php
$content = ob_get_clean();
?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إنشاء موضوع</title>
<link rel="stylesheet" href="assets/css/layout.css">
</head>

<body>
<?php $layout->render($content); ?>
</body>
</html>
