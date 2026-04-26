<h2>🌍 قائمة اللغات</h2>

<table class="table">
    <tr>
        <th>اللغة</th>
        <th>الكود</th>
        <th>الحالة</th>
    </tr>

<?php foreach ($languages ?? [] as $lang): ?>
<tr>
    <td><?= htmlspecialchars($lang['native_name'] ?? $lang['name']) ?></td>
    <td><?= $lang['code'] ?></td>
    <td><?= !empty($lang['is_active']) ? '✔ مفعلة' : '❌ معطلة' ?></td>
</tr>
<?php endforeach; ?>

</table>
