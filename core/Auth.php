<?php

class Auth {

    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix = '') {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    /* =========================
       CHECK LOGIN
    ========================= */
    public function check() {
        return isset($_SESSION['user_id']);
    }

    /* =========================
       GET USER
    ========================= */
    public function user() {

        if (!$this->check()) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->prefix}users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================
       IS ADMIN (🔥 تم التعديل)
    ========================= */
    public function isAdmin() {

        $user = $this->user();

        if (!$user) {
            return false;
        }

        // 🔥 دعم أكثر من نوع قاعدة بيانات
        if (isset($user['role'])) {
            return $user['role'] === 'admin';
        }

        if (isset($user['user_role'])) {
            return $user['user_role'] === 'admin';
        }

        if (isset($user['type'])) {
            return $user['type'] === 'admin';
        }

        if (isset($user['is_admin'])) {
            return $user['is_admin'] == 1;
        }

        return false;
    }

    /* =========================
       LOGIN
    ========================= */
    public function login($username, $password) {

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->prefix}users WHERE username = ?");
        $stmt->execute([$username]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        // 🔥 مقارنة كلمة المرور (يمكنك لاحقًا استخدام password_verify)
        if ($user['password'] != $password) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];

        return true;
    }

    /* =========================
       LOGOUT
    ========================= */
    public function logout() {
        session_destroy();
    }
}
?>
