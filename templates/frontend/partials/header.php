<?php

require_once ROOT_PATH . '/core/Settings.php';
require_once ROOT_PATH . '/templates/frontend/partials/ads_display.php';

// 🔥 ربط نظام اللغة
global $language_engine;
$current_lang = function_exists('get_current_language') ? get_current_language() : 'ar';
$direction = function_exists('get_text_direction') ? get_text_direction() : 'rtl';
$languages = function_exists('get_active_languages') ? get_active_languages() : [];

?>

<!-- HTML DIR + LANG -->

<script>
document.documentElement.setAttribute('dir', '<?= $direction ?>');
document.documentElement.setAttribute('lang', '<?= $current_lang ?>');
</script>

<?php displayInternalAds('header', $currentPage ?? ''); ?>

<header class="header">
    <div class="container">
        <div class="header-content">

        <a href="/" class="logo">
            <?= Settings::get('site_name', 'My CMS') ?>
        </a>

        <nav>
            <ul class="nav-menu">

                <li><a href="/"><?= __('home') ?></a></li>
                <li><a href="/topics"><?= __('topics') ?></a></li>
                <li><a href="/services"><?= __('services') ?></a></li>
                <li><a href="/courses"><?= __('courses') ?></a></li>

                <?php if (is_logged_in()): ?>
                    <li><a href="/profile"><?= __('profile') ?></a></li>

                    <?php if (has_role("admin")): ?>
                        <li><a href="/admin"><?= __('dashboard') ?></a></li>
                    <?php endif; ?>

                    <li><a href="/logout"><?= __('logout') ?></a></li>
                <?php else: ?>
                    <li><a href="/login"><?= __('login') ?></a></li>
                    <li><a href="/register"><?= __('register') ?></a></li>
                <?php endif; ?>

            </ul>
        </nav>

        <!-- 🌍 زر تغيير اللغة -->
        <div class="lang-switcher">
            <?php foreach ($languages as $lang): ?>
                <a href="/admin/change-lang?lang=<?= $lang['code'] ?>"
                   class="<?= $current_lang == $lang['code'] ? 'active' : '' ?>">
                    <?= $lang['code'] ?>
                </a>
            <?php endforeach; ?>
        </div>

    </div>
</div>

</header>
