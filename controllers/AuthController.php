<?php

require_once __DIR__ . '/../core/Layout.php';

class AuthController {

    public function login() {

        $layout = new Layout();

        ob_start();
        ?>

    <h2>تسجيل الدخول</h2>

    <form method="POST" action="/login">
        <input type="text" name="username" placeholder="اسم المستخدم"><br><br>
        <input type="password" name="password" placeholder="كلمة المرور"><br><br>
        <button type="submit">دخول</button>
    </form>

    <?php
    $content = ob_get_clean();
    $layout->render($content);
}

public function doLogin() {

    // 🔥 بيانات مؤقتة (نربطها لاحقاً بقاعدة البيانات)
    if ($_POST['username'] === 'admin' && $_POST['password'] === '1234') {

        $_SESSION['admin'] = true;

        header("Location: /admin");
        exit;
    }

    echo "بيانات خاطئة";
}

public function logout() {

    session_destroy();

    header("Location: /");
    exit;
}

}
?>
