<?php 
$currentPage = 'home';
?>

<!-- الشريط المتحرك (مفصول بين الهيدر والمحتوى) -->

<div class="ticker-wrapper">
    <?php 
    $ticker = ROOT_PATH . '/templates/frontend/partials/enhanced_ticker.php';
    if (file_exists($ticker)) {
        include $ticker;
    }
    ?>
</div>

<main class="container">

<?php if (function_exists('displayInternalAds')) displayInternalAds('content_top', 'home'); ?>

<div class="hero-section fade-in">
    <h1>مرحباً بك في Final Max CMS</h1>
    <p class="hero-subtitle">
        نظام إدارة محتوى وخدمات مهنية متكامل
    </p>
</div>

<div class="features-grid">
    <div class="feature-item card">
        <h3>الخدمات</h3>
        <p>استعرض الخدمات</p>
    </div>

<div class="feature-item card">
    <h3>الدورات</h3>
    <p>تعلم مهارات جديدة</p>
</div>

</div>

<?php if (function_exists('displayInternalAds')) displayInternalAds('content_middle', 'home'); ?>

<?php if (!is_logged_in()): ?>

<div class="auth-prompt card text-center">
    <h2>ابدأ الآن</h2>
</div>

<?php endif; ?>

<?php if (function_exists('displayInternalAds')) displayInternalAds('content_bottom', 'home'); ?>

</main>
