<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الإعلانات الداخلية - Final Max CMS</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/main.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/admin.css">
</head>
<body>
    <?php include ROOT_PATH . '/templates/frontend/partials/header.php'; ?>

    <main class="container admin-container">
        <div class="admin-header">
            <h1>إدارة الإعلانات الداخلية</h1>
            <a href="/admin/internal-ads/add" class="btn btn-primary">إضافة إعلان جديد</a>
        </div>

        <?php if (isset($_SESSION["error_message"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error_message"]; unset($_SESSION["error_message"]); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION["success_message"])): ?>
            <div class="alert alert-success"><?php echo $_SESSION["success_message"]; unset($_SESSION["success_message"]); ?></div>
        <?php endif; ?>

        <!-- إحصائيات الإعلانات -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>إجمالي الإعلانات</h3>
                <div class="stat-number"><?php echo $data['stats']['total_ads']; ?></div>
            </div>
            <div class="stat-card">
                <h3>الإعلانات النشطة</h3>
                <div class="stat-number"><?php echo $data['stats']['active_ads']; ?></div>
            </div>
            <div class="stat-card">
                <h3>إجمالي المشاهدات</h3>
                <div class="stat-number"><?php echo number_format($data['stats']['total_views']); ?></div>
            </div>
            <div class="stat-card">
                <h3>إجمالي النقرات</h3>
                <div class="stat-number"><?php echo number_format($data['stats']['total_clicks']); ?></div>
            </div>
            <div class="stat-card">
                <h3>معدل النقر</h3>
                <div class="stat-number"><?php echo $data['stats']['click_rate']; ?>%</div>
            </div>
        </div>

        <!-- جدول الإعلانات -->
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>المعرف</th>
                        <th>العنوان</th>
                        <th>الموضع</th>
                        <th>الصفحات</th>
                        <th>الأولوية</th>
                        <th>المشاهدات</th>
                        <th>النقرات</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['ads'])): ?>
                        <tr>
                            <td colspan="9" class="text-center">لا توجد إعلانات.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['ads'] as $ad): ?>
                            <tr>
                                <td><?php echo $ad['id']; ?></td>
                                <td><?php echo htmlspecialchars($ad['title']); ?></td>
                                <td><?php echo htmlspecialchars($ad['position']); ?></td>
                                <td>
                                    <?php 
                                    $pages = json_decode($ad['pages'], true);
                                    if (empty($pages)) {
                                        echo 'جميع الصفحات';
                                    } else {
                                        echo implode(', ', $pages);
                                    }
                                    ?>
                                </td>
                                <td><?php echo $ad['priority']; ?></td>
                                <td><?php echo number_format($ad['views']); ?></td>
                                <td><?php echo number_format($ad['clicks']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $ad['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $ad['is_active'] ? 'نشط' : 'غير نشط'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="/admin/internal-ads/edit?id=<?php echo $ad['id']; ?>" class="btn btn-sm btn-secondary">تعديل</a>
                                    <button onclick="toggleAdStatus(<?php echo $ad['id']; ?>)" class="btn btn-sm <?php echo $ad['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                        <?php echo $ad['is_active'] ? 'إلغاء تفعيل' : 'تفعيل'; ?>
                                    </button>
                                    <button onclick="deleteAd(<?php echo $ad['id']; ?>)" class="btn btn-sm btn-danger">حذف</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- الترقيم -->
        <?php if ($data['totalPages'] > 1): ?>
            <div class="pagination">
                <?php if ($data['currentPage'] > 1): ?>
                    <a href="?page=<?php echo $data['currentPage'] - 1; ?>" class="btn btn-secondary">السابق</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $data['totalPages']; $i++): ?>
                    <?php if ($i == $data['currentPage']): ?>
                        <span class="current-page"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($data['currentPage'] < $data['totalPages']): ?>
                    <a href="?page=<?php echo $data['currentPage'] + 1; ?>" class="btn btn-secondary">التالي</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include ROOT_PATH . '/templates/frontend/partials/footer.php'; ?>

    <script>
        function toggleAdStatus(adId) {
            if (confirm('هل أنت متأكد من تغيير حالة هذا الإعلان؟')) {
                fetch('/admin/internal-ads/toggle-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ad_id=' + adId + '&csrf_token=<?php echo $data["csrf_token"]; ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'حدث خطأ');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في الاتصال');
                });
            }
        }

        function deleteAd(adId) {
            if (confirm('هل أنت متأكد من حذف هذا الإعلان؟ لا يمكن التراجع عن هذا الإجراء.')) {
                fetch('/admin/internal-ads/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ad_id=' + adId + '&csrf_token=<?php echo $data["csrf_token"]; ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'حدث خطأ');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في الاتصال');
                });
            }
        }
    </script>
</body>
</html>

