<?php

$themeManager = new ThemeManager();
$themes = $themeManager->getThemes();
$active = Settings::get('active_theme', 'default');

?>

<div class="page-header">
    <h2>🎨 إدارة القوالب</h2>
</div>

<!-- رفع قالب -->

<div class="card">
    <h3>📦 رفع قالب جديد</h3>

<form method="POST" enctype="multipart/form-data" action="/admin/upload-theme">
    <input type="file" name="theme_zip" required>
    <button class="btn btn-primary">رفع وتثبيت</button>
</form>

</div>

<!-- عرض القوالب -->

<div class="themes-grid">

<?php foreach ($themes as $theme): ?>

<div class="theme-card <?= $active == $theme['slug'] ? 'active' : '' ?>">

<div class="theme-header">
    <h3><?= $theme['name'] ?></h3>
    <span class="version">v<?= $theme['version'] ?></span>
</div>

<div class="theme-body">

    <p>المؤلف: <?= $theme['author'] ?? 'غير معروف' ?></p>

    <?php if ($active == $theme['slug']): ?>
        <span class="badge active">✔ مفعل</span>
    <?php else: ?>
        <span class="badge inactive">غير مفعل</span>
    <?php endif; ?>

</div>

<div class="theme-actions">

    <?php if ($active != $theme['slug']): ?>
    <form method="POST" action="/admin/activate-theme">
        <input type="hidden" name="theme" value="<?= $theme['slug'] ?>">
        <button class="btn btn-success">تفعيل</button>
    </form>
    <?php endif; ?>

    <form method="POST" action="/admin/delete-theme" onsubmit="return confirm('هل أنت متأكد؟')">
        <input type="hidden" name="theme" value="<?= $theme['slug'] ?>">
        <button class="btn btn-danger">حذف</button>
    </form>

</div>

</div>

<?php endforeach; ?>

</div>
