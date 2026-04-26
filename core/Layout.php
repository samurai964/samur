<?php

class Layout {

    public function render($view, $data = []) {

        // 🔥 تنظيف Buffer (بدون التأثير على admin)
        /* لا تقم بحذف buffer لأنه يكسر عرض الهيدر */


        // 🔥 تمرير البيانات للـ View
        extract($data);

        // 🔥 تحديد مسار الملف
        $viewFile = ROOT_PATH . '/templates/frontend/' . $view . '.php';

        if (!file_exists($viewFile)) {
            die("❌ View not found: " . $viewFile);
        }

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">

<title><?= Settings::get('site_name', 'الموقع') ?></title>

<link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/app.css">

<!-- 🔥 نظام الألوان الديناميكي -->

<style>
:root {
    --primary: <?= Settings::get('primary_color', '#6366f1') ?>;
    --secondary: <?= Settings::get('secondary_color', '#4f46e5') ?>;
    --bg: <?= Settings::get('background_color', '#f1f5f9') ?>;
    --text: <?= Settings::get('text_color', '#1e293b') ?>;

    --header-bg: <?= Settings::get('header_bg', '#ffffff') ?>;
    --footer-bg: <?= Settings::get('footer_bg', '#111827') ?>;
    --sidebar-bg: <?= Settings::get('sidebar_bg', '#f8fafc') ?>;

    --post-bg: <?= Settings::get('post_bg', '#ffffff') ?>;
    --comment-bg: <?= Settings::get('comment_bg', '#f9fafb') ?>;

    --radius: <?= Settings::get('border_radius', '10') ?>px;
}
</style>

</head>

<body class="frontend">

<?php require ROOT_PATH . '/templates/frontend/partials/header.php'; ?>

<div class="page-wrapper">

<!-- RIGHT SIDEBAR -->

<aside class="sidebar sidebar-right">
    <h3>القائمة</h3>
    <ul>
        <li><a href="#">عن الموقع</a></li>
        <li><a href="#">اتصل بنا</a></li>
    </ul>
</aside>

<!-- CONTENT -->

<main class="main-content">
    <div class="container">
        <?php require $viewFile; ?>
    </div>
</main>

<!-- LEFT SIDEBAR -->

<aside class="sidebar sidebar-left">
    <h3>المستخدم</h3>
    <p>مرحباً بك 👋</p>
</aside>

</div>

<?php require ROOT_PATH . '/templates/frontend/partials/footer.php'; ?>

</body>
</html>
<?php
    }
}
?>
