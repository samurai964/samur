<?php
// ✅ View فقط - Dashboard
?>

<div class="language-management-container">

<!-- Header -->
<div class="page-header">
    <h1>🌍 إدارة اللغات</h1>
    <p>لوحة تحكم نظام اللغات</p>
</div>

<!-- Alerts -->
<?php if (!empty($message)): ?>
    <div class="alert success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="grid">

    <div class="card">
        <h3>⚡ إجراءات سريعة</h3>
        <div class="actions">
            <a href="/admin/languages-list" class="btn">📋 عرض اللغات</a>
            <a href="/admin/language-packages" class="btn">📦 إدارة الحزم</a>
            <a href="/admin/upload-language" class="btn">📤 رفع لغة</a>
        </div>
    </div>

    <div class="card">
        <h3>📊 الإحصائيات</h3>
        <p>عدد اللغات: <?= $language_stats['total_languages'] ?? 0 ?></p>
        <p>اللغات النشطة: <?= $language_stats['active_languages'] ?? 0 ?></p>
    </div>

</div>

<!-- Active Languages -->
<div class="card">
    <h3>🌐 اللغات النشطة</h3>

    <?php if (!empty($active_languages)): ?>
        <div class="language-list">
            <?php foreach ($active_languages as $lang): ?>
                <div class="lang-item">
                    <strong><?= htmlspecialchars($lang['name']) ?></strong>
                    <span>(<?= $lang['code'] ?>)</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>لا توجد لغات نشطة</p>
    <?php endif; ?>

</div>

</div>
