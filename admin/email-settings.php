<h1 class="page-title">📧 إعدادات البريد</h1>

<form method="POST" class="settings-form">

<!-- ================= MAIL DRIVER ================= -->

<div class="settings-card">
    <h3>⚙️ إعدادات الإرسال</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>نوع الإرسال</label>
        <select name="mail_driver">
            <option value="smtp">SMTP</option>
            <option value="php_mail">PHP Mail</option>
            <option value="api">Mail API</option>
        </select>
    </div>

    <div class="form-group">
        <label>البريد المرسل</label>
        <input type="email" name="mail_from">
    </div>

    <div class="form-group">
        <label>اسم المرسل</label>
        <input type="text" name="mail_name">
    </div>

</div>

</div>

<!-- ================= SMTP ================= -->

<div class="settings-card">
    <h3>📡 إعدادات SMTP</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>SMTP Host</label>
        <input type="text" name="smtp_host">
    </div>

    <div class="form-group">
        <label>SMTP Port</label>
        <input type="number" name="smtp_port" value="587">
    </div>

    <div class="form-group">
        <label>التشفير</label>
        <select name="smtp_encryption">
            <option value="tls">TLS</option>
            <option value="ssl">SSL</option>
        </select>
    </div>

    <div class="form-group">
        <label>اسم المستخدم</label>
        <input type="text" name="smtp_user">
    </div>

    <div class="form-group">
        <label>كلمة المرور</label>
        <input type="password" name="smtp_pass">
    </div>

</div>

</div>

<!-- ================= API MAIL ================= -->

<div class="settings-card">
    <h3>🌐 Mail API</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>API Key</label>
        <input type="text" name="mail_api_key">
    </div>

    <div class="form-group">
        <label>مزود الخدمة</label>
        <select name="mail_provider">
            <option value="sendgrid">SendGrid</option>
            <option value="mailgun">Mailgun</option>
            <option value="custom">Custom</option>
        </select>
    </div>

</div>

</div>

<!-- ================= NEWSLETTER ================= -->

<div class="settings-card">
    <h3>📨 النشرة البريدية</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>تفعيل النشرة</label>
        <select name="newsletter_enabled">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>عدد الرسائل في الدفعة</label>
        <input type="number" name="batch_size" value="50">
    </div>

    <div class="form-group">
        <label>تأخير الإرسال (ثواني)</label>
        <input type="number" name="send_delay" value="2">
    </div>

</div>

</div>

<!-- ================= TEST MAIL ================= -->

<div class="settings-card">
    <h3>🧪 اختبار البريد</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>بريد الاختبار</label>
        <input type="email" name="test_email">
    </div>

</div>

<button type="submit" name="send_test" class="btn-secondary">
    ✉️ إرسال رسالة اختبار
</button>

</div>

<!-- ================= SAVE ================= -->

<div class="save-wrapper">
    <button type="submit" class="btn-save">💾 حفظ الإعدادات</button>
</div>

</form>
