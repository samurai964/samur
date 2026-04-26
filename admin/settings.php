<?php
// View فقط - لا منطق هنا
?>

<h2>⚙️ إعدادات النظام</h2>

<form method="POST" action="/admin/settings">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

<!-- 🌐 عام -->

<div class="settings-section">
<h3>🌐 إعدادات عامة</h3>

<div class="settings-grid">

<div>
<label>اسم الموقع</label>
<input name="site_name" value="<?= $settings['site_name'] ?? '' ?>">
</div>

<div>
<label>البريد</label>
<input name="admin_email" value="<?= $settings['admin_email'] ?? '' ?>">
</div>

<div>
<label>اللغة</label>
<input name="site_language" value="<?= $settings['site_language'] ?? 'ar' ?>">
</div>

<div>
<label>المنطقة الزمنية</label>

<select name="timezone">
<?php
$timezones = DateTimeZone::listIdentifiers();
$currentTZ = $settings['timezone'] ?? 'UTC';

foreach ($timezones as $tz):
?>

<option value="<?= $tz ?>" <?= $tz === $currentTZ ? 'selected' : '' ?>>
<?= $tz ?>
</option>
<?php endforeach; ?>
</select>

</div>

</div>

<label>وصف الموقع</label>

<textarea name="site_description"><?= $settings['site_description'] ?? '' ?></textarea>

</div>

<!-- ⚙️ النظام -->

<div class="settings-section">
<h3>⚙️ إعدادات النظام</h3>

<div class="settings-grid">

<div>
<label>عدد العناصر</label>
<input type="number" name="items_per_page" value="<?= $settings['items_per_page'] ?? 10 ?>">
</div>

<div>
<label>العملة</label>
<input name="currency" value="<?= $settings['currency'] ?? 'USD' ?>">
</div>

<div>
<label>نسبة العمولة (%)</label>
<input name="commission_rate" value="<?= $settings['commission_rate'] ?? 10 ?>">
</div>

<div>
<label>تفعيل التسجيل</label>

<input type="hidden" name="allow_registration" value="0">
<input type="checkbox" name="allow_registration" value="1"
<?= !empty($settings['allow_registration']) ? 'checked' : '' ?>>

</div>

<div>
<label>وضع الصيانة</label>

<input type="hidden" name="maintenance_mode" value="0">
<input type="checkbox" name="maintenance_mode" value="1"
<?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?>>

</div>

</div>

<label>رسالة الصيانة</label>

<textarea name="maintenance_message"><?= $settings['maintenance_message'] ?? '' ?></textarea>

</div>

<!-- 🔍 SEO -->

<div class="settings-section">
<h3>🔍 SEO</h3>

<div class="settings-grid">

<div>
<label>Keywords</label>
<input name="meta_keywords" value="<?= $settings['meta_keywords'] ?? '' ?>">
</div>

</div>

<label>Description</label>

<textarea name="meta_description"><?= $settings['meta_description'] ?? '' ?></textarea>

<label>Analytics</label>

<textarea name="analytics_code"><?= $settings['analytics_code'] ?? '' ?></textarea>

</div>

<!-- 🔐 الأمان -->

<div class="settings-section">
<h3>🔐 الأمان</h3>

<div class="settings-grid">

<div>
<label>محاولات تسجيل الدخول</label>
<input type="number" name="max_login_attempts" value="<?= $settings['max_login_attempts'] ?? 5 ?>">
</div>

<div>
<label>مدة الجلسة</label>
<input type="number" name="session_timeout" value="<?= $settings['session_timeout'] ?? 30 ?>">
</div>

<div>
<label>تفعيل الكاش</label>

<input type="hidden" name="enable_cache" value="0">
<input type="checkbox" name="enable_cache" value="1"
<?= !empty($settings['enable_cache']) ? 'checked' : '' ?>>

</div>

</div>

</div>

<br>
<button class="btn-save" type="submit">
    💾 حفظ الإعدادات
</button>

</form>
