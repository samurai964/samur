<h2>فحص متطلبات النظام</h2>
<p>يقوم النظام بفحص متطلبات التشغيل على الخادم الخاص بك.</p>

<?php
$requirements = checkSystemRequirements();
?>

<div class="requirements-list">
    <?php foreach ($requirements['checks'] as $check): ?>
        <div class="form-group" style="padding: 10px; border-bottom: 1px solid #eee;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong><?php echo $check['name']; ?></strong>
                    <div style="font-size: 0.9em; color: #666;">
                        المطلوب: <?php echo $check['required']; ?> | 
                        المتوفر: <?php echo $check['current']; ?>
                    </div>
                </div>
                <div>
                    <?php if ($check['status']): ?>
                        <span style="color: green; font-weight: bold;">✓</span>
                    <?php else: ?>
                        <span style="color: <?php echo $check['critical'] ? 'red' : 'orange'; ?>; font-weight: bold;">
                            <?php echo $check['critical'] ? '✗' : '!'; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($requirements['status']): ?>
    <div class="alert alert-success">
        ✅ جميع المتطلبات الأساسية متوفرة. يمكنك المتابعة إلى الخطوة التالية.
    </div>
    <button type="submit" class="btn">المتابعة إلى إعداد قاعدة البيانات</button>
<?php else: ?>
    <div class="alert alert-error">
        ❌ بعض المتطلبات الأساسية غير متوفرة. يجب حل المشكلات الحمراء قبل المتابعة.
    </div>
    <button type="button" class="btn" onclick="window.location.reload()">إعادة الفحص</button>
<?php endif; ?>