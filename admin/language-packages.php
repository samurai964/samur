<h2>📦 حزم اللغات</h2>

<table class="table">
    <tr>
        <th>الحزمة</th>
        <th>الإصدار</th>
        <th>الحالة</th>
        <th>الإجراء</th>
    </tr>

<?php foreach ($packages ?? [] as $pkg): ?>
<tr>
    <td><?= htmlspecialchars($pkg['package_name']) ?></td>
    <td><?= $pkg['version'] ?></td>
    <td><?= $pkg['is_installed'] ? '✔ مثبتة' : '❌ غير مثبتة' ?></td>
    <td>
        <?php if (!$pkg['is_installed']): ?>
        <form method="POST" action="/admin/install-language">
            <input type="hidden" name="package_name" value="<?= $pkg['package_name'] ?>">
            <button>تثبيت</button>
        </form>
        <?php else: ?>
            ✔
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>

</table>
