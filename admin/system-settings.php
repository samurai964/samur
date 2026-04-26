<h1 class="page-title">⚙️ إعدادات النظام</h1>

<form method="POST" class="settings-form">

<!-- PERFORMANCE -->

<div class="settings-card">
    <h3>🚀 تحسين الأداء</h3>

<div class="settings-grid">
    <div class="form-group">
        <label>تفعيل الكاش</label>
        <select name="enable_cache">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>ضغط الملفات</label>
        <select name="minify_assets">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>تحسين الصور</label>
        <select name="optimize_images">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>
</div>

</div>

<!-- SECURITY -->

<div class="settings-card">
    <h3>🔐 الأمان</h3>

<div class="settings-grid">
    <div class="form-group">
        <label>عدد المحاولات</label>
        <input type="number" name="max_login_attempts" value="5">
    </div>

    <div class="form-group">
        <label>2FA</label>
        <select name="enable_2fa">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>حظر IP</label>
        <select name="auto_block_ip">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>
</div>

</div>

<!-- SESSION -->

<div class="settings-card">
    <h3>⏱️ الجلسات</h3>

<div class="settings-grid">
    <div class="form-group">
        <label>مدة الجلسة</label>
        <input type="number" name="session_timeout" value="60">
    </div>

    <div class="form-group">
        <label>تذكرني</label>
        <select name="remember_me">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>
</div>

</div>

<!-- API -->

<div class="settings-card">
    <h3>🔗 API</h3>

<div class="settings-grid">
    <div class="form-group">
        <label>API Key</label>
        <input type="text" name="api_key">
    </div>

    <div class="form-group">
        <label>تفعيل API</label>
        <select name="enable_api">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>
</div>

</div>

<!-- SYSTEM -->

<div class="settings-card">
    <h3>🛠 إعدادات النظام</h3>

<div class="settings-grid">
    <div class="form-group">
        <label>وضع الصيانة</label>
        <select name="maintenance_mode">
            <option value="0">معطل</option>
            <option value="1">مفعل</option>
        </select>
    </div>

    <div class="form-group">
        <label>رسالة الصيانة</label>
        <input type="text" name="maintenance_message">
    </div>

    <div class="form-group">
        <label>اللغة</label>
        <select name="system_language">
            <option value="ar">العربية</option>
            <option value="en">English</option>
        </select>
    </div>
</div>

</div>

<!-- RESOURCES -->

<div class="settings-card">
    <h3>⚡ إدارة الموارد</h3>

<div class="settings-grid">
    <div class="form-group">
        <label>Threads</label>
        <input type="number" name="max_threads" value="4">
    </div>

    <div class="form-group">
        <label>Async</label>
        <select name="enable_async">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>
</div>

</div>

<div class="save-wrapper">
    <button type="submit" class="btn-save">💾 حفظ الإعدادات</button>
</div>

</form>
