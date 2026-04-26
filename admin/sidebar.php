<aside class="admin-sidebar">

<h2>⚙️ إعدادات النظام</h2>
<!-- ⚙️ عام -->

<div class="menu-group">
    <div class="menu-title">⚙️ الإعدادات العامة</div>
    <div class="menu-items">
        <a href="/admin/settings">إعدادات الموقع</a>
        <a href="/admin/system-settings">إعدادات النظام</a>
        <a href="/admin/email-settings">إعدادات البريد</a>
        <a href="/admin/api-settings">إعدادات API</a>
        <a href="/admin/design">🎨 الألوان والتصميم</a>
    </div>
</div>

<!-- 🏠 الصفحة الرئيسية -->

<div class="menu-group">
    <div class="menu-title">🏠 الصفحة الرئيسية</div>
    <div class="menu-items">
        <a href="/admin/home-settings">إعدادات الصفحة الرئيسية</a>
        <a href="/admin/sidebars">إعدادات القوائم الجانبية</a>
        <a href="/admin/ticker">الشريط المتحرك</a>
    </div>
</div>

<!-- 🌍 اللغات -->

<div class="menu-group">
    <div class="menu-title">🌍 اللغات</div>
    <div class="menu-items">
        <a href="/admin/languages">إدارة اللغات</a>
        <a href="/admin/languages-list">📋 قائمة اللغات</a>
        <a href="/admin/language-packages">📦 الحزم</a>
        <a href="/admin/upload-language">📤 رفع لغة (ZIP)</a>
    </div>
</div>

<!-- 🎨 القوالب -->

<div class="menu-group">
    <div class="menu-title">🎨 القوالب</div>
    <div class="menu-items">
        <a href="/admin/themes">إدارة القوالب</a>
    </div>
</div>

<!-- 👥 المستخدمين -->

<div class="menu-group">
    <div class="menu-title">👥 المستخدمين</div>
    <div class="menu-items">
        <a href="/admin/user-settings">إعدادات المستخدمين</a>
        <a href="/admin/security">الأمان</a>
    </div>
</div>

<!-- 📄 المحتوى -->

<div class="menu-group">
    <div class="menu-title">📄 المحتوى</div>
    <div class="menu-items">
        <a href="/admin/content-settings">إعدادات المحتوى</a>
        <a href="/admin/comments-settings">التعليقات</a>
    </div>
</div>

<!-- 📢 الإعلانات -->

<div class="menu-group">
    <div class="menu-title">📢 الإعلانات</div>
    <div class="menu-items">
        <a href="/admin/ads-settings">الإعلانات الداخلية (HTML)</a>
        <a href="/admin/user-ads">إعلانات الأعضاء</a>
    </div>
</div>

<!-- 🔍 SEO -->

<div class="menu-group">
    <div class="menu-title">🔍 SEO</div>
    <div class="menu-items">
        <a href="/admin/seo-settings">إعدادات SEO</a>
    </div>
</div>

<!-- ⚡ الأداء -->

<div class="menu-group">
    <div class="menu-title">⚡ الأداء</div>
    <div class="menu-items">
        <a href="/admin/performance-settings">الأداء</a>
        <a href="/admin/cache-settings">الكاش</a>
    </div>
</div>

<!-- 🔌 الإضافات -->

<div class="menu-group">
    <div class="menu-title">🔌 الإضافات</div>
    <div class="menu-items">
        <a href="/admin/plugins-settings">الإضافات</a>
    </div>
</div>

<!-- 🛠 النظام -->

<div class="menu-group">
    <div class="menu-title">🛠 النظام</div>
    <div class="menu-items">
        <a href="/admin/backup-settings">النسخ الاحتياطي</a>
        <a href="/admin/logs">السجلات</a>
    </div>
</div>

<hr>

<a href="/logout" class="logout">🚪 تسجيل الخروج</a>

<script>
(function(){

    var groups = document.querySelectorAll('.menu-group');

    for (var i = 0; i < groups.length; i++) {

        (function(group){

            var title = group.querySelector('.menu-title');

            title.onclick = function(){

                for (var j = 0; j < groups.length; j++) {
                    if (groups[j] !== group) {
                        groups[j].classList.remove('active');
                    }
                }

                if (group.classList.contains('active')) {
                    group.classList.remove('active');
                } else {
                    group.classList.add('active');
                }

            };

        })(groups[i]);

    }

})();
</script>

</aside>
