<h1 class="page-title">🔌 إعدادات API</h1>

<form method="POST" class="settings-form">

<!-- ================= API STATUS ================= -->

<div class="settings-card">
    <h3>⚙️ إعدادات API العامة</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>تفعيل API</label>
        <select name="api_enabled">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>نوع المصادقة</label>
        <select name="auth_type">
            <option value="token">Token</option>
            <option value="jwt">JWT</option>
            <option value="apikey">API Key</option>
        </select>
    </div>

    <div class="form-group">
        <label>API Version</label>
        <input type="text" name="api_version" value="v1">
    </div>

</div>

</div>

<!-- ================= API KEYS ================= -->

<div class="settings-card">
    <h3>🔑 مفاتيح API</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>المفتاح العام</label>
        <input type="text" name="public_key">
    </div>

    <div class="form-group">
        <label>المفتاح الخاص</label>
        <input type="password" name="private_key">
    </div>

    <div class="form-group">
        <label>توليد مفتاح جديد</label>
        <button type="button" class="btn-secondary">🔄 توليد</button>
    </div>

</div>

</div>

<!-- ================= RATE LIMIT ================= -->

<div class="settings-card">
    <h3>⚡ Rate Limiting</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>عدد الطلبات / دقيقة</label>
        <input type="number" name="rate_limit" value="60">
    </div>

    <div class="form-group">
        <label>تفعيل الحد</label>
        <select name="rate_limit_enabled">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

</div>

</div>

<!-- ================= SECURITY ================= -->

<div class="settings-card">
    <h3>🔐 الأمان</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>تقييد IP</label>
        <input type="text" name="allowed_ips" placeholder="127.0.0.1, 192.168.1.1">
    </div>

    <div class="form-group">
        <label>تفعيل CORS</label>
        <select name="enable_cors">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>تفعيل HTTPS فقط</label>
        <select name="force_https">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

</div>

</div>

<!-- ================= WEBHOOKS ================= -->

<div class="settings-card">
    <h3>🔗 Webhooks</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>Webhook URL</label>
        <input type="text" name="webhook_url">
    </div>

    <div class="form-group">
        <label>تفعيل Webhook</label>
        <select name="webhook_enabled">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

</div>

</div>

<!-- ================= LOGGING ================= -->

<div class="settings-card">
    <h3>📊 تسجيل الطلبات</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>تفعيل Logs</label>
        <select name="api_logging">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>مدة الاحتفاظ (أيام)</label>
        <input type="number" name="log_retention" value="7">
    </div>

</div>

</div>

<!-- ================= PERFORMANCE ================= -->

<div class="settings-card">
    <h3>🚀 تحسين الأداء</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>تفعيل Cache</label>
        <select name="api_cache">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>مدة الكاش (ثواني)</label>
        <input type="number" name="cache_ttl" value="60">
    </div>

</div>

</div>

<div class="save-wrapper">
    <button type="submit" class="btn-save">💾 حفظ الإعدادات</button>
</div>

</form>
