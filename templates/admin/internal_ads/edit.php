<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الإعلان - Final Max CMS</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/main.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/admin.css">
</head>
<body>
    <?php include ROOT_PATH . '/templates/frontend/partials/header.php'; ?>

    <main class="container admin-container">
        <div class="admin-header">
            <h1>تعديل الإعلان: <?php echo htmlspecialchars($data['ad']['title']); ?></h1>
            <a href="/admin/internal-ads" class="btn btn-secondary">العودة للقائمة</a>
        </div>

        <?php if (isset($_SESSION["error_message"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error_message"]; unset($_SESSION["error_message"]); ?></div>
        <?php endif; ?>

        <form action="/admin/internal-ads/edit?id=<?php echo $data['ad']['id']; ?>" method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo $data['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="title">عنوان الإعلان *</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($data['ad']['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="content">محتوى الإعلان *</label>
                <textarea id="content" name="content" rows="4" required><?php echo htmlspecialchars($data['ad']['content']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="image_url">رابط الصورة</label>
                <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($data['ad']['image_url']); ?>" placeholder="https://example.com/image.jpg">
                <?php if (!empty($data['ad']['image_url'])): ?>
                    <img id="image-preview" src="<?php echo htmlspecialchars($data['ad']['image_url']); ?>" style="max-width: 200px; margin-top: 10px; border-radius: 4px;">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="link_url">رابط الإعلان</label>
                <input type="url" id="link_url" name="link_url" value="<?php echo htmlspecialchars($data['ad']['link_url']); ?>" placeholder="https://example.com">
            </div>

            <div class="form-group">
                <label for="position">موضع الإعلان *</label>
                <select id="position" name="position" required>
                    <option value="">اختر الموضع</option>
                    <option value="header" <?php echo $data['ad']['position'] === 'header' ? 'selected' : ''; ?>>أعلى الصفحة (Header)</option>
                    <option value="sidebar" <?php echo $data['ad']['position'] === 'sidebar' ? 'selected' : ''; ?>>الشريط الجانبي</option>
                    <option value="content_top" <?php echo $data['ad']['position'] === 'content_top' ? 'selected' : ''; ?>>أعلى المحتوى</option>
                    <option value="content_middle" <?php echo $data['ad']['position'] === 'content_middle' ? 'selected' : ''; ?>>وسط المحتوى</option>
                    <option value="content_bottom" <?php echo $data['ad']['position'] === 'content_bottom' ? 'selected' : ''; ?>>أسفل المحتوى</option>
                    <option value="footer" <?php echo $data['ad']['position'] === 'footer' ? 'selected' : ''; ?>>أسفل الصفحة (Footer)</option>
                    <option value="popup" <?php echo $data['ad']['position'] === 'popup' ? 'selected' : ''; ?>>نافذة منبثقة</option>
                    <option value="banner" <?php echo $data['ad']['position'] === 'banner' ? 'selected' : ''; ?>>بانر كبير</option>
                </select>
            </div>

            <div class="form-group">
                <label>الصفحات المستهدفة</label>
                <?php 
                $selectedPages = json_decode($data['ad']['pages'], true) ?: [];
                ?>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="pages[]" value="home" <?php echo in_array('home', $selectedPages) ? 'checked' : ''; ?>> الصفحة الرئيسية</label>
                    <label><input type="checkbox" name="pages[]" value="topics" <?php echo in_array('topics', $selectedPages) ? 'checked' : ''; ?>> المواضيع</label>
                    <label><input type="checkbox" name="pages[]" value="topic_detail" <?php echo in_array('topic_detail', $selectedPages) ? 'checked' : ''; ?>> تفاصيل الموضوع</label>
                    <label><input type="checkbox" name="pages[]" value="services" <?php echo in_array('services', $selectedPages) ? 'checked' : ''; ?>> الخدمات</label>
                    <label><input type="checkbox" name="pages[]" value="courses" <?php echo in_array('courses', $selectedPages) ? 'checked' : ''; ?>> الدورات</label>
                    <label><input type="checkbox" name="pages[]" value="ads" <?php echo in_array('ads', $selectedPages) ? 'checked' : ''; ?>> الإعلانات</label>
                    <label><input type="checkbox" name="pages[]" value="profile" <?php echo in_array('profile', $selectedPages) ? 'checked' : ''; ?>> الملف الشخصي</label>
                    <label><input type="checkbox" name="pages[]" value="categories" <?php echo in_array('categories', $selectedPages) ? 'checked' : ''; ?>> الأقسام</label>
                    <label><input type="checkbox" name="pages[]" value="wallet" <?php echo in_array('wallet', $selectedPages) ? 'checked' : ''; ?>> المحفظة</label>
                    <label><input type="checkbox" name="pages[]" value="points" <?php echo in_array('points', $selectedPages) ? 'checked' : ''; ?>> النقاط</label>
                </div>
                <small class="form-help">إذا لم تحدد أي صفحة، سيظهر الإعلان في جميع الصفحات</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">تاريخ البداية</label>
                    <input type="datetime-local" id="start_date" name="start_date" value="<?php echo $data['ad']['start_date'] ? date('Y-m-d\TH:i', strtotime($data['ad']['start_date'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">تاريخ النهاية</label>
                    <input type="datetime-local" id="end_date" name="end_date" value="<?php echo $data['ad']['end_date'] ? date('Y-m-d\TH:i', strtotime($data['ad']['end_date'])) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="priority">الأولوية</label>
                <input type="number" id="priority" name="priority" value="<?php echo $data['ad']['priority']; ?>" min="1" max="100">
                <small class="form-help">الأولوية الأعلى تظهر أولاً (1-100)</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" <?php echo $data['ad']['is_active'] ? 'checked' : ''; ?>>
                    تفعيل الإعلان
                </label>
            </div>

            <!-- إحصائيات الإعلان -->
            <div class="ad-stats">
                <h3>إحصائيات الإعلان</h3>
                <div class="stats-row">
                    <div class="stat-item">
                        <strong>المشاهدات:</strong> <?php echo number_format($data['ad']['views']); ?>
                    </div>
                    <div class="stat-item">
                        <strong>النقرات:</strong> <?php echo number_format($data['ad']['clicks']); ?>
                    </div>
                    <div class="stat-item">
                        <strong>معدل النقر:</strong> 
                        <?php 
                        $clickRate = $data['ad']['views'] > 0 ? round(($data['ad']['clicks'] / $data['ad']['views']) * 100, 2) : 0;
                        echo $clickRate . '%';
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
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
                if (url) {
                    preview.src = url;
                } else {
                    preview.remove();
                }
            } else if (url) {
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

