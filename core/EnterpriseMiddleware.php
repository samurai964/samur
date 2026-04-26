<?php

class EnterpriseMiddleware {

    public static function adminOnly($auth) {
        if (!$auth || !$auth->isAdmin()) {
            die('❌ لا تملك صلاحية الدخول');
        }
    }

    public static function checkPermission($auth, $permission) {

        $user = $auth->user();

        if (!$user) {
            die('❌ غير مسجل الدخول');
        }

        // المدير له كل الصلاحيات
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }

        // مثال بسيط (يمكن توسعته)
        $permissions = [
            'user' => ['view']
        ];

        if (!in_array($permission, $permissions[$user['role']] ?? [])) {
            die('❌ لا تملك هذا الإذن');
        }
    }
}
?>
