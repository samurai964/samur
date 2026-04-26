<h2>إعدادات قاعدة البيانات</h2>
<p>يرجى إدخال معلومات الاتصال بقاعدة البيانات.</p>

<div class="form-group">
    <label for="db_host">خادم قاعدة البيانات (Host)</label>
    <input type="text" id="db_host" name="db_host" value="localhost" required>
</div>

<div class="form-group">
    <label for="db_name">اسم قاعدة البيانات</label>
    <input type="text" id="db_name" name="db_name" required>
</div>

<div class="form-group">
    <label for="db_user">اسم المستخدم</label>
    <input type="text" id="db_user" name="db_user" required>
</div>

<div class="form-group">
    <label for="db_pass">كلمة المرور</label>
    <input type="password" id="db_pass" name="db_pass">
</div>

<button type="submit" class="btn">اختبار الاتصال والمتابعة</button>