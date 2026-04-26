<?php
$page_title = 'إدارة الدورات';
include '../templates/admin/header.php';

// التحقق من صلاحيات المدير
if (!Auth::hasPermission('manage_courses')) {
    redirect('/admin/dashboard.php');
}

$courseModel = new CourseModel();

// معالجة طلبات الحذف/التفعيل/التعطيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $course_id = (int)($_POST['course_id'] ?? 0);

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $this->setError('خطأ في التحقق من الأمان');
    } else {
        switch ($action) {
            case 'delete':
                if ($courseModel->deleteCourse($course_id)) {
                    $this->setSuccess('تم حذف الدورة بنجاح');
                } else {
                    $this->setError('فشل حذف الدورة');
                }
                break;
            case 'approve':
                if ($courseModel->updateCourseStatus($course_id, 'approved')) {
                    $this->setSuccess('تمت الموافقة على الدورة بنجاح');
                } else {
                    $this->setError('فشل الموافقة على الدورة');
                }
                break;
            case 'reject':
                if ($courseModel->updateCourseStatus($course_id, 'rejected')) {
                    $this->setSuccess('تم رفض الدورة بنجاح');
                } else {
                    $this->setError('فشل رفض الدورة');
                }
                break;
        }
    }
}

$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$courses_data = $courseModel->getCourses(['page' => $page, 'limit' => $limit, 'search' => $search, 'status' => $status]);
$courses = $courses_data['courses'];
$total_courses = $courses_data['total'];
$total_pages = $courses_data['pages'];

?>

<div class="admin-page">
    <div class="page-header">
        <h2>إدارة الدورات</h2>
        <a href="/admin/courses/add.php" class="btn btn-primary">+ إضافة دورة جديدة</a>
    </div>

    <div class="filters-bar">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="بحث بعنوان الدورة..." value="<?= htmlspecialchars($search) ?>">
            <select name="status">
                <option value="">جميع الحالات</option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>موافق عليها</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>معلقة</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>مرفوضة</option>
            </select>
            <button type="submit" class="btn btn-secondary">تصفية</button>
        </form>
    </div>

    <?php if (!empty($courses)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>العنوان</th>
                        <th>المدرب</th>
                        <th>السعر</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?= $course['id'] ?></td>
                            <td><?= htmlspecialchars($course['title']) ?></td>
                            <td><?= htmlspecialchars($course['username']) ?></td>
                            <td><?= format_currency($course['price']) ?></td>
                            <td><span class="status-badge status-<?= $course['status'] ?>"><?= htmlspecialchars($course['status']) ?></span></td>
                            <td>
                                <a href="/admin/courses/edit.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-info">تعديل</a>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('هل أنت متأكد؟');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                    <?php if ($course['status'] === 'pending'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">موافقة</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-warning">رفض</button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="/admin/courses.php?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&status=<?= htmlspecialchars($status) ?>" 
                   class="pagination-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php else: ?>
        <div class="no-results">لا توجد دورات</div>
    <?php endif; ?>
</div>

<?php include '../templates/admin/footer.php'; ?>

