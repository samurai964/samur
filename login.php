<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'bootstrap.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password == $user['password']) {

        $_SESSION['user_id'] = $user['id'];

        header("Location: index.php");
        exit;

    } else {
        $error = "❌ بيانات تسجيل الدخول غير صحيحة";
    }
}
?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل الدخول</title>
</head>

<body style="font-family:Arial; background:#f5f5f5;">

<div style="width:300px;margin:100px auto;background:#fff;padding:20px;border-radius:10px;">

<h2>تسجيل الدخول</h2>

<?php if ($error): ?>
    <div style="color:red;"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <input type="text" name="username" placeholder="اسم المستخدم" style="width:100%;margin-bottom:10px;">
    <input type="password" name="password" placeholder="كلمة المرور" style="width:100%;margin-bottom:10px;">
    <button type="submit" style="width:100%;">دخول</button>
</form>

</div>

</body>
</html>