<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسيت كلمة المرور - Final Max CMS</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/main.css">
</head>
<body>
    <?php include ROOT_PATH . 
        '/templates/frontend/partials/header.php'; ?>

    <main class="container auth-container">
        <h2>نسيت كلمة المرور</h2>
        <?php if (isset($_SESSION["error_message"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error_message"]; unset($_SESSION["error_message"]); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION["success_message"])): ?>
            <div class="alert alert-success"><?php echo $_SESSION["success_message"]; unset($_SESSION["success_message"]); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION["message"])): ?>
            <div class="alert alert-info"><?php echo $_SESSION["message"]; unset($_SESSION["message"]); ?></div>
        <?php endif; ?>
        <form action="/forgot-password" method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo $data["csrf_token"]; ?>">
            <div class="form-group">
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary">إرسال رابط إعادة التعيين</button>
        </form>
        <p class="auth-link"><a href="/login">العودة لتسجيل الدخول</a></p>
    </main>

    <?php include ROOT_PATH . 
        '/templates/frontend/partials/footer.php'; ?>
    <script src="<?php echo ASSETS_URL; ?>/js/script.js"></script>
</body>
</html>

