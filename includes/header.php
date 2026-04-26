<?php
// تضمين ملفات التهيئة الأساسية
require_once __DIR__ . '/../core/bootstrap.php';

use Core\Auth;
use Core\Session;
use Core\LanguageEngine;
use Models\User;

// تحديد اللغة باستخدام نظام اللغات الجديد
$lang = LanguageEngine::getCurrentLanguage();
$direction = LanguageEngine::getDirection();

// متغيرات الصفحة
$page_title = isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME;
$page_description = isset($page_description) ? $page_description : SITE_DESCRIPTION;

// الحصول على بيانات المستخدم إذا كان مسجل الدخول
$user = null;
if (Auth::check()) {
    $user = User::find(Session::get('user_id'));
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
    <!-- أيقونات ورابط الخطوط -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

    <!-- ملفات الأنماط -->
    <link rel="stylesheet" href="<?php echo Router::url('assets/css/style.css'); ?>">
    
    <!-- أي أكواد إضافية في الهيدر -->
    <?php if (isset($extra_header_code)) { echo $extra_header_code; } ?>
</head>
<body>

<header class="main-header">
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="<?php echo Router::url('/'); ?>"><?php echo SITE_NAME; ?></a>
            
            <div class="navbar-menu">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="<?php echo Router::url('/'); ?>"><?php echo LanguageEngine::get('common', 'home'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo Router::url('portfolio.php'); ?>">معرض الأعمال</a></li>
                    <!-- يمكنك إضافة المزيد من الروابط هنا -->
                </ul>
            </div>

            <div class="navbar-user">
                <?php if (Auth::check() && $user): ?>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <img src="<?php echo $user->avatar ?? 'assets/images/default-avatar.png'; ?>" alt="<?php echo htmlspecialchars($user->username); ?>" class="avatar-sm">
                            <span><?php echo htmlspecialchars($user->username); ?></span>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-content">
                            <a href="<?php echo Router::url('dashboard.php'); ?>">لوحة التحكم</a>
                            <a href="<?php echo Router::url('profile.php'); ?>">تعديل الملف الشخصي</a>
                            <?php if (Auth::isAdmin()): ?>
                                <a href="<?php echo Router::url('admin/dashboard.php'); ?>">لوحة الإدارة</a>
                            <?php endif; ?>
                            <a href="<?php echo Router::url('logout.php'); ?>">تسجيل الخروج</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo Router::url('login.php'); ?>" class="btn btn-sm">تسجيل الدخول</a>
                    <a href="<?php echo Router::url('register.php'); ?>" class="btn btn-sm btn-primary">إنشاء حساب</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<main class="main-content">
    <div class="container">