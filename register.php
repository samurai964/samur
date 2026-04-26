<?php
require_once __DIR__ . 
'/includes/header.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);

    if (empty($username)) {
        $errors[] = 'اسم المستخدم مطلوب.';
    }
    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'صيغة البريد الإلكتروني غير صحيحة.';
    }
    if (empty($password)) {
        $errors[] = 'كلمة المرور مطلوبة.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'كلمة المرور وتأكيد كلمة المرور غير متطابقين.';
    }

    if (empty($errors)) {
        if (get_user_by_username($username)) {
            $errors[] = 'اسم المستخدم موجود بالفعل.';
        }
        if (get_user_by_email($email)) {
            $errors[] = 'البريد الإلكتروني موجود بالفعل.';
        }

        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if (create_user($username, $email, $hashed_password)) {
                set_message('success', 'تم التسجيل بنجاح! يمكنك الآن تسجيل الدخول.');
                redirect('login.php');
            } else {
                $errors[] = 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.';
            }
        }
    }
}

?>

<div class="auth-container">
    <h2>إنشاء حساب جديد</h2>
    <?php display_message(); ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="register.php" method="POST">
        <div class="form-group">
            <label for="username">اسم المستخدم:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">البريد الإلكتروني:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">كلمة المرور:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">تأكيد كلمة المرور:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">تسجيل</button>
    </form>
    <p>لديك حساب بالفعل؟ <a href="login.php">سجل الدخول</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


