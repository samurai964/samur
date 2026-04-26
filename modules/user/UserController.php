<?php

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/UserModel.php';

class UserController extends BaseController {

    private $auth;
    private $model;

    public function __construct($db = null, $prefix = '') {
        parent::__construct($db);

        $this->model = new UserModel($this->db);

        // إذا كان Auth موجود
        if (class_exists('Auth')) {
            $this->auth = new Auth($this->db, $prefix);
        }
    }

    public function login() {
        if ($this->auth && $this->auth->check()) {
            header('Location: /');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $result = $this->auth->login($username, $password);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
                header('Location: /');
                exit();
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
        }

        $this->view('frontend/auth/login');
    }

    public function logout() {
        if ($this->auth) {
            $this->auth->logout();
        }

        header('Location: /');
        exit();
    }

    public function profile() {
        if (!$this->auth || !$this->auth->check()) {
            header('Location: /login');
            exit();
        }

        $user = $this->auth->user();
        $this->view('frontend/user/profile', ['user' => $user]);
    }

public function listUsers() {

if (!$this->auth || !$this->auth->isAdmin()) {
    $_SESSION['error_message'] = 'ليس لديك صلاحية للوصول إلى هذه الصفحة.';
    header('Location: /');
    exit();
}

$users = $this->model->getAllUsers();

$this->view('admin/users/list', ['users' => $users]);

}

}
