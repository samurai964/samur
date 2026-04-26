<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة إعلان داخلي - Final Max CMS</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/main.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/admin.css">
</head>
<body>
    <?php include ROOT_PATH . '/templates/frontend/partials/header.php'; ?>

    <main class="container admin-container">
        <div class="admin-header">
            <h1>إضافة إعلان داخلي جديد</h1>
            <a href="/admin/internal-ads" class="btn btn-secondary">العودة للقائمة</a>
        </div>

        <?php if (isset($_SESSION["error_message"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error_message"]; unset($_SESSION["error_message"]); ?></div>
        <?php endif; ?>

        <form action="/admin/internal-ads/add" method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo $data['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="title">عنوان الإعلان *</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="content">محتوى الإعلان *</label>
                <textarea id="content" name="content" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="image_url">رابط الصورة</label>
                <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
            </div>

            <div class="form-group">
                <label for="link_url">رابط الإعلان</label>
                <input type="url" id="link_url" name="link_url" placeholder="https://example.com">
            </div>

            <div class="form-group">
                <label for="position">موضع الإعلان *</label>
                <select id="position" name="position" required>
                    <option value="">اختر الموضع</option>
                    <option value="header">أعلى الصفحة (Header)</option>
                    <option value="sidebar">الشريط الجانبي</option>
                    <option value="content_top">أعلى المحتوى</option>
                    <option value="content_middle">وسط المحتوى</option>
                    <option value="content_bottom">أسفل المحتوى</option>
                    <option value="footer">أسفل الصفحة (Footer)</option>
                    <option value="popup">نافذة منبثقة</option>
                    <option value="banner">بانر كبير</option>
                </select>
            </div>

            <div class="form-group">
                <label>الصفحات المستهدفة</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="pages[]" value="home"> الصفحة الرئيسية</label>
                    <label><input type="checkbox" name="pages[]" value="topics"> المواضيع</label>
                    <label><input type="checkbox" name="pages[]" value="topic_detail"> تفاصيل الموضوع</label>
                    <label><input type="checkbox" name="pages[]" value="services"> الخدمات</label>
                    <label><input type="checkbox" name="pages[]" value="courses"> الدورات</label>
                    <label><input type="checkbox" name="pages[]" value="ads"> الإعلانات</label>
                    <label><input type="checkbox" name="pages[]" value="profile"> الملف الشخصي</label>
                    <label><input type="checkbox" name="pages[]" value="categories"> الأقسام</label>
                    <label><input type="checkbox" name="pages[]" value="wallet"> المحفظة</label>
                    <label><input type="checkbox" name="pages[]" value="points"> النقاط</label>
                </div>
                <small class="form-help">إذا لم تحدد أي صفحة، سيظهر الإعلان في جميع الصفحات</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">تاريخ البداية</label>
                    <input type="datetime-local" id="start_date" name="start_date">
                </div>
                <div class="form-group">
                    <label for="end_date">تاريخ النهاية</label>
                    <input type="datetime-local" id="end_date" name="end_date">
                </div>
            </div>

            <div class="form-group">
                <label for="priority">الأولوية</label>
                <input type="number" id="priority" name="priority" value="1" min="1" max="100">
                <small class="form-help">الأولوية الأعلى تظهر أولاً (1-100)</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" checked>
                    تفعيل الإعلان
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">إضافة الإعلان</button>
                <a href="/admin/internal-ads" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </main>

    <?php include ROOT_PATH . '/templates/frontend/partials/footer.php'; ?>

    <script>
        // معاينة الصورة
        document.getElementById('image_url').addEventListener('input', function() {
            const url = this.value;
            const preview = document.getElementById('image-preview');
            
            if (preview) {
                preview.remove();
            }
            
            if (url) {
                const img = document.createElement('img');
                img.id = 'image-preview';
                img.src = url;
                img.style.maxWidth = '200px';
                img.style.marginTop = '10px';
                img.style.borderRadius = '4px';
                this.parentNode.appendChild(img);
            }
        });

        // التحقق من صحة التواريخ
        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = document.getElementById('start_date').value;
            const endDate = this.value;
            
            if (startDate && endDate && new Date(endDate) <= new Date(startDate)) {
                alert('تاريخ النهاية يجب أن يكون بعد تاريخ البداية');
                this.value = '';
            }
        });
    </script>
</body>
</html>

