<h2>تثبيت النظام</h2>
<p>جاري تثبيت Final Max CMS على خادمك. هذه العملية قد تستغرق بضع دقائق.</p>

<div class="progress-container">
    <div id="progress-bar" class="progress-bar" style="width: 0%">0%</div>
</div>

<div id="status-message" style="text-align: center; margin: 20px 0; font-weight: bold;">
    جاري التحضير للتثبيت...
</div>

<div style="text-align: center; display: none;" id="complete-message">
    <div class="alert alert-success">✅ تم التثبيت بنجاح!</div>
    <p>جاري التوجيه إلى صفحة النتائج...</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // بدء عملية التثبيت تلقائياً
    performInstallation();
});
</script>