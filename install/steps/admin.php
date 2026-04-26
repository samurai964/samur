<h2>إنشاء حساب المدير</h2>
<p>يرجى إدخال معلومات حساب المدير الرئيسي للنظام.</p>

<div class="form-group">
    <label for="admin_username">اسم المستخدم</label>
    <input type="text" id="admin_username" name="admin_username" required>
    <small style="display: block; color: #666; margin-top: 5px;">
        يجب أن يكون اسم المستخدم بين 3 و 50 حرفاً ويحتوي على أحرف وأرقام و _ فقط.
    </small>
</div>

<div class="form-group">
    <label for="admin_email">البريد الإلكتروني</label>
    <input type="email" id="admin_email" name="admin_email" required>
</div>

<div class="form-group">
    <label for="admin_password">كلمة المرور</label>
    <input type="password" id="admin_password" name="admin_password" required>
    <small style="display: block; color: #666; margin-top: 5px;">
        يجب أن تكون كلمة المرور 6 أحرف على الأقل.
    </small>
</div>

<div class="form-group">
    <label for="admin_password_confirm">تأكيد كلمة المرور</label>
    <input type="password" id="admin_password_confirm" name="admin_password_confirm" required>
</div>

<h3 style="margin-top: 30px;">إعدادات الموقع</h3>

<div class="form-group">
    <label for="site_name">اسم الموقع</label>
    <input type="text" id="site_name" name="site_name" required>
</div>

<div class="form-group">
    <label for="site_description">وصف الموقع</label>
    <textarea id="site_description" name="site_description" rows="3" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
    <small style="display: block; color: #666; margin-top: 5px;">
        وصف مختصر لموقعك (اختياري).
    </small>
</div>

<button type="submit" class="btn">حفظ والمتابعة إلى التثبيت</button>