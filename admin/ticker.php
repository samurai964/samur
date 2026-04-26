<?php

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // checkbox fix
    $_POST['ticker_enabled'] = isset($_POST['ticker_enabled']) ? 1 : 0;

    // تنظيف القيم
    $data = [
        'ticker_enabled'   => $_POST['ticker_enabled'],
        'ticker_speed'     => max(5, intval($_POST['ticker_speed'] ?? 20)),
        'ticker_bg'        => $_POST['ticker_bg'] ?? '#0f172a',
        'ticker_color'     => $_POST['ticker_color'] ?? '#ffffff',
        'ticker_sections'  => trim($_POST['ticker_sections'] ?? ''),
        'ticker_direction' => $_POST['ticker_direction'] ?? 'rtl',
    ];

    foreach ($data as $key => $value) {
    Settings::set($key, $value);
}

    $success = true;
}
?>

<style>
.ticker-box {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    max-width: 600px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}
.ticker-box h2 {
    margin-bottom: 20px;
}
.ticker-box label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
}
.ticker-box input, .ticker-box select {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border-radius: 6px;
    border: 1px solid #ddd;
}
.ticker-box button {
    margin-top: 20px;
    padding: 10px;
    background: #0f172a;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}
.success {
    background: #dcfce7;
    color: #166534;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}
</style>

<div class="ticker-box">

<h2>⚡ إعدادات الشريط المتحرك</h2>

<?php if ($success): ?>

<div class="success">✅ تم حفظ الإعدادات بنجاح</div>
<?php endif; ?>

<form method="POST">

<label>
<input type="checkbox" name="ticker_enabled" value="1" <?= setting('ticker_enabled') ? 'checked' : '' ?>>
 تفعيل الشريط
</label>

<label>السرعة (بالثواني)</label> <input type="number" name="ticker_speed" value="<?= setting('ticker_speed', 20) ?>">

<label>لون الخلفية</label> <input type="color" name="ticker_bg" value="<?= setting('ticker_bg', '#0f172a') ?>">

<label>لون النص</label> <input type="color" name="ticker_color" value="<?= setting('ticker_color', '#ffffff') ?>">

<label>اتجاه الحركة</label> <select name="ticker_direction">
<option value="rtl" <?= setting('ticker_direction') == 'rtl' ? 'selected' : '' ?>>يمين → يسار</option>
<option value="ltr" <?= setting('ticker_direction') == 'ltr' ? 'selected' : '' ?>>يسار → يمين</option> </select>

<label>الأقسام (IDs مفصولة بفاصلة)</label> <input type="text" name="ticker_sections" value="<?= setting('ticker_sections') ?>">

<button type="submit">💾 حفظ الإعدادات</button>

</form>

</div>
