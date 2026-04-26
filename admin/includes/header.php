<?php
require_once __DIR__ . '/../core/init.php';

// التحقق من تسجيل الدخول والصلاحيات الإدارية
if (!Auth::isLoggedIn() || !Auth::hasPermission("access_admin_panel")) {
    redirect("/login.php");
}

$page_title = "لوحة التحكم";
$page_description = "إدارة نظام Final Max CMS";

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
    <!-- أيقونات ورابط الخطوط -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

    <!-- ملفات الأنماط -->
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/admin.css'); ?>">
    
    <!-- أي أكواد إضافية في الهيدر -->
    <?php if (isset($extra_header_code)) { echo $extra_header_code; } ?>
</head>
<body>

<header class="admin-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?php echo url('admin/index.php'); ?>" class="logo">لوحة تحكم <?php echo SITE_NAME; ?></a>
            <nav class="admin-nav">
                <ul>
                    <li><a href="<?php echo url('/'); ?>" target="_blank"><i class="fas fa-home"></i> عرض الموقع</a></li>
                    <li><a href="<?php echo url('logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <ul class="list-unstyled">
            <li><a href="<?php echo url('admin/index.php'); ?>"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
            <li><a href="<?php echo url('admin/users.php'); ?>"><i class="fas fa-users"></i> المستخدمون</a></li>
            <li><a href="<?php echo url('admin/categories.php'); ?>"><i class="fas fa-folder-open"></i> الفئات</a></li>
            <li><a href="<?php echo url('admin/posts.php'); ?>"><i class="fas fa-newspaper"></i> المشاركات</a></li>
            <li><a href="<?php echo url('admin/comments.php'); ?>"><i class="fas fa-comments"></i> التعليقات</a></li>
            <li><a href="<?php echo url('admin/settings.php'); ?>"><i class="fas fa-cogs"></i> الإعدادات</a></li>
            <!-- يمكن إضافة المزيد من الروابط هنا -->
        </ul>
    </aside>
    <main class="admin-content">
        <div class="container-fluid py-4">


