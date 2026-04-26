<h1 class="page-title">🏠 إعدادات الصفحة الرئيسية</h1>

<form method="POST" class="settings-form">

<!-- ================= TYPE ================= -->

<div class="settings-card">
    <h3>📌 نوع الصفحة الرئيسية</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>نوع العرض</label>
        <select name="home_layout">
            <option value="latest">أحدث المواضيع</option>
            <option value="sections">حسب الأقسام</option>
            <option value="custom">مخصص</option>
        </select>
    </div>

    <div class="form-group">
        <label>عدد المواضيع</label>
        <input type="number" name="posts_limit" value="10">
    </div>

</div>

</div>

<!-- ================= DISPLAY STYLE ================= -->

<div class="settings-card">
    <h3>🎨 شكل عرض المواضيع</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>نمط العرض</label>
        <select name="post_style">
            <option value="grid">مربعات</option>
            <option value="horizontal">مستطيل عرضي</option>
            <option value="vertical">مستطيل طولي</option>
            <option value="list">قائمة</option>
        </select>
    </div>

    <div class="form-group">
        <label>عدد الأعمدة</label>
        <input type="number" name="columns" value="3">
    </div>

</div>

</div>

<!-- ================= SLIDER ================= -->

<div class="settings-card">
    <h3>🎞️ السلايدر / الشريط المتحرك</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>تفعيل السلايدر</label>
        <select name="enable_slider">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>عدد العناصر</label>
        <input type="number" name="slider_limit" value="5">
    </div>

    <div class="form-group">
        <label>نوع الحركة</label>
        <select name="slider_type">
            <option value="scroll">تمرير</option>
            <option value="fade">تلاشي</option>
            <option value="static">ثابت</option>
        </select>
    </div>

</div>

</div>

<!-- ================= SECTIONS ================= -->

<div class="settings-card">
    <h3>📂 عرض الأقسام</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>عرض حسب الأقسام</label>
        <select name="enable_sections">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>عدد المواضيع لكل قسم</label>
        <input type="number" name="section_limit" value="5">
    </div>

</div>

</div>

<!-- ================= ADS ================= -->

<div class="settings-card">
    <h3>📢 إعلانات الصفحة الرئيسية</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>إعلان أعلى الصفحة</label>
        <select name="ad_top">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>إعلان وسط الصفحة</label>
        <select name="ad_middle">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>إعلان أسفل الصفحة</label>
        <select name="ad_bottom">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

</div>

</div>

<!-- ================= PERFORMANCE ================= -->

<div class="settings-card">
    <h3>⚡ تحسين الأداء</h3>

<div class="settings-grid">

    <div class="form-group">
        <label>تفعيل الكاش</label>
        <select name="home_cache">
            <option value="1">مفعل</option>
            <option value="0">معطل</option>
        </select>
    </div>

    <div class="form-group">
        <label>مدة الكاش (ثواني)</label>
        <input type="number" name="cache_time" value="60">
    </div>

</div>

</div>

<div class="save-wrapper">
    <button type="submit" class="btn-save">💾 حفظ الإعدادات</button>
</div>

</form>
