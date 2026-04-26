<h1 class="page-title">🎨 إعدادات التصميم</h1>

<form method="POST" class="settings-form">

<!-- ================= COLORS ================= -->

<div class="settings-card">
    <h3>🎨 الألوان الرئيسية</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>لون الموقع الأساسي</label>
        <input type="color" name="primary_color">
    </div>

    <div class="form-group">
        <label>لون ثانوي</label>
        <input type="color" name="secondary_color">
    </div>

    <div class="form-group">
        <label>لون الخلفية</label>
        <input type="color" name="background_color">
    </div>

    <div class="form-group">
        <label>لون النص</label>
        <input type="color" name="text_color">
    </div>

</div>

</div>

<!-- ================= HEADER ================= -->

<div class="settings-card">
    <h3>📌 الهيدر</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>لون الهيدر</label>
        <input type="color" name="header_bg">
    </div>

    <div class="form-group">
        <label>لون النص في الهيدر</label>
        <input type="color" name="header_text">
    </div>

</div>

</div>

<!-- ================= FOOTER ================= -->

<div class="settings-card">
    <h3>📎 الفوتر</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>لون الفوتر</label>
        <input type="color" name="footer_bg">
    </div>

    <div class="form-group">
        <label>لون النص في الفوتر</label>
        <input type="color" name="footer_text">
    </div>

</div>

</div>

<!-- ================= SIDEBAR ================= -->

<div class="settings-card">
    <h3>📂 القوائم الجانبية</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>لون الخلفية</label>
        <input type="color" name="sidebar_bg">
    </div>

    <div class="form-group">
        <label>لون الروابط</label>
        <input type="color" name="sidebar_text">
    </div>

</div>

</div>

<!-- ================= CONTENT ================= -->

<div class="settings-card">
    <h3>📝 المحتوى</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>خلفية المواضيع</label>
        <input type="color" name="post_bg">
    </div>

    <div class="form-group">
        <label>خلفية التعليقات</label>
        <input type="color" name="comment_bg">
    </div>

</div>

</div>

<!-- ================= UI ================= -->

<div class="settings-card">
    <h3>✨ واجهة المستخدم</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>حواف العناصر</label>
        <input type="number" name="border_radius" value="10">
    </div>

    <div class="form-group">
        <label>الخط</label>
        <select name="font_family">
            <option value="default">افتراضي</option>
            <option value="cairo">Cairo</option>
            <option value="tajawal">Tajawal</option>
        </select>
    </div>

</div>

</div>

<div class="save-wrapper">
    <button type="submit" class="btn-save">💾 حفظ التصميم</button>
</div>

</form>
