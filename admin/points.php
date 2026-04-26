<?php
$page_title = 'إدارة النقاط';
include '../templates/admin/header.php';

// التحقق من صلاحيات المدير
if (!Auth::hasPermission('manage_points')) {
    redirect('/admin/dashboard.php');
}

$pointsModel = new PointsModel();

// معالجة طلبات التحديث
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $this->setError('خطأ في التحقق من الأمان');
    } else {
        $settings = [
            'register_points' => (int)($_POST['register_points'] ?? 0),
            'login_points' => (int)($_POST['login_points'] ?? 0),
            'topic_create_points' => (int)($_POST['topic_create_points'] ?? 0),
            'comment_points' => (int)($_POST['comment_points'] ?? 0),
            'daily_login_limit' => (int)($_POST['daily_login_limit'] ?? 0),
            'online_time_points_per_minute' => (float)($_POST['online_time_points_per_minute'] ?? 0.0),
            'min_online_time_for_points' => (int)($_POST['min_online_time_for_points'] ?? 0),
        ];

        if ($pointsModel->updatePointsSettings($settings)) {
            $this->setSuccess('تم تحديث إعدادات النقاط بنجاح');
        } else {
            $this->setError('فشل تحديث إعدادات النقاط');
        }
    }
}

$settings = $pointsModel->getPointsSettings();

?>

<div class="admin-page">
    <div class="page-header">
        <h2>إدارة النقاط</h2>
    </div>

    <form method="POST" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label for="register_points">نقاط التسجيل:</label>
            <input type="number" id="register_points" name="register_points" value="<?= htmlspecialchars($settings['register_points'] ?? 0) ?>" class="form-control">
        </div>

        <div class="form-group">
            <label for="login_points">نقاط تسجيل الدخول اليومي:</label>
            <input type="number" id="login_points" name="login_points" value="<?= htmlspecialchars($settings['login_points'] ?? 0) ?>" class="form-control">
        </div>

        <div class="form-group">
            <label for="daily_login_limit">حد تسجيل الدخول اليومي (مرات):</label>
            <input type="number" id="daily_login_limit" name="daily_login_limit" value="<?= htmlspecialchars($settings['daily_login_limit'] ?? 0) ?>" class="form-control">
        </div>

        <div class="form-group">
            <label for="topic_create_points">نقاط إنشاء موضوع:</label>
            <input type="number" id="topic_create_points" name="topic_create_points" value="<?= htmlspecialchars($settings['topic_create_points'] ?? 0) ?>" class="form-control">
        </div>

        <div class="form-group">
            <label for="comment_points">نقاط إضافة تعليق:</label>
            <input type="number" id="comment_points" name="comment_points" value="<?= htmlspecialchars($settings['comment_points'] ?? 0) ?>" class="form-control">
        </div>

        <div class="form-group">
            <label for="online_time_points_per_minute">نقاط لكل دقيقة اتصال (عشري):</label>
            <input type="number" step="0.01" id="online_time_points_per_minute" name="online_time_points_per_minute" value="<?= htmlspecialchars($settings['online_time_points_per_minute'] ?? 0.0) ?>" class="form-control">
        </div>

        <div class="form-group">
            <label for="min_online_time_for_points">الحد الأدنى لوقت الاتصال لكسب النقاط (بالدقائق):</label>
            <input type="number" id="min_online_time_for_points" name="min_online_time_for_points" value="<?= htmlspecialchars($settings['min_online_time_for_points'] ?? 0) ?>" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
    </form>

    <h3 style="margin-top: 30px;">سجل النقاط</h3>
    <?php
    $page = (int)($_GET['log_page'] ?? 1);
    $limit = 20;
    $points_log_data = $pointsModel->getPointsLog(['page' => $page, 'limit' => $limit]);
    $points_log = $points_log_data['log'];
    $total_log_entries = $points_log_data['total'];
    $total_log_pages = $points_log_data['pages'];
    ?>

    <?php if (!empty($points_log)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>المستخدم</th>
                        <th>النقاط</th>
                        <th>النوع</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($points_log as $log_entry): ?>
                        <tr>
                            <td><?= $log_entry['id'] ?></td>
                            <td><?= htmlspecialchars($log_entry['username']) ?></td>
                            <td><?= $log_entry['points_change'] ?></td>
                            <td><?= htmlspecialchars($log_entry['type']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($log_entry['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            <?php for ($i = 1; $i <= $total_log_pages; $i++): ?>
                <a href="/admin/points.php?log_page=<?= $i ?>" 
                   class="pagination-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php else: ?>
        <div class="no-results">لا يوجد سجل نقاط</div>
    <?php endif; ?>
</div>

<?php include '../templates/admin/footer.php'; ?>

