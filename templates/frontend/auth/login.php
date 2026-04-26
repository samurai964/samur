<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - Final Max CMS</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/main.css">
</head>
<body>
    <?php include ROOT_PATH . 
        '/templates/frontend/partials/header.php'; ?>

    <main class="container auth-container">
        <h2>تسجيل الدخول</h2>
        <?php if (isset($_SESSION["error_message"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error_message"]; unset($_SESSION["error_message"]); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION["success_message"])): ?>
            <div class="alert alert-success"><?php echo $_SESSION["success_message"]; unset($_SESSION["success_message"]); ?></div>
        <?php endif; ?>
        <form action="/login" method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo $data["csrf_token"]; ?>">
            <div class="form-group">
                <label for="username">اسم المستخدم أو البريد الإلكتروني:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">تسجيل الدخول</button>
        </form>
        <p class="auth-link">ليس لديك حساب؟ <a href="/register">سجل الآن</a></p>
        <p class="auth-link"><a href="/forgot-password">نسيت كلمة المرور؟</a></p>
    </main>

    <?php include ROOT_PATH . 
        '/templates/frontend/partials/footer.php'; ?>
    <script src="<?php echo ASSETS_URL; ?>/js/script.js"></script>
</body>
</html>

