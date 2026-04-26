<?php

class Admin {

    public static function check() {

        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit;
        }

        global $pdo;

        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        $role = $stmt->fetchColumn();

        if ($role !== 'admin') {
            die("❌ غير مصرح");
        }
    }
}
?>
