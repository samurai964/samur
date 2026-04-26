<?php

require_once __DIR__ . '/Auth.php';

class Middleware {

    public static function admin($pdo, $prefix) {

        $auth = new Auth($pdo, $prefix);

        if (!$auth->check()) {
            header("Location: /login");
            exit;
        }

        if (!$auth->isAdmin()) {
            echo "❌ غير مصرح لك بالدخول";
            exit;
        }
    }

    public static function guest($pdo, $prefix) {

        $auth = new Auth($pdo, $prefix);

        if ($auth->check()) {
            header("Location: /");
            exit;
        }
    }
}
?>
