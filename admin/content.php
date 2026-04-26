<?php
$page_title = 'إدارة المحتوى';
include '../templates/admin/header.php';

// التحقق من صلاحيات المدير
if (!Auth::hasPermission('manage_content')) {
    redirect('/admin/dashboard.php');
}

$topicModel = new TopicModel();
$categoryModel = new CategoryModel();

// معالجة طلبات الحذف/التفعيل/التعطيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $item_id = (int)($_POST['item_id'] ?? 0);
    $type = $_POST['type'] ?? ''; // 'topic' or 'category'

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $this->setError('خطأ في التحقق من الأمان');
    } else {
        switch ($action) {
            case 'delete':
                if ($type === 'topic') {
                    if ($topicModel->deleteTopic($item_id)) {
                        $this->setSuccess('تم حذف الموضوع بنجاح');
                    } else {
                        $this->setError('فشل حذف الموضوع');
                    }
                } elseif ($type === 'category') {
                    if ($categoryModel->deleteCategory($item_id)) {
                        $this->setSuccess('تم حذف الفئة بنجاح');
                    } else {
                        $this->setError('فشل حذف الفئة');
                    }
                }
                break;
            case 'approve':
                if ($type === 'topic') {
                    if ($topicModel->updateTopicStatus($item_id, 'published')) {
                        $this->setSuccess('تم نشر الموضوع بنجاح');
                    } else {
                        $this->setError('فشل نشر الموضوع');
                    }
                }
                break;
            case 'reject':
                if ($type === 'topic') {
                    if ($topicModel->updateTopicStatus($item_id, 'rejected')) {
                        $this->setSuccess('تم رفض الموضوع بنجاح');
                    } else {
                        $this->setError('فشل رفض الموضوع');
                    }
                }
                break;
        }
    }
}

$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$category_id = $_GET['category_id'] ?? '';

$topics_data = $topicModel->getTopics(['page' => $page, 'limit' => $limit, 'search' => $search, 'status' => $status, 'category_id' => $category_id]);
$topics = $topics_data['topics'];
$total_topics = $topics_data['total'];
$total_pages = $topics_data['pages'];

$categories = $categoryModel->getCategories();

?>

<div class="admin-page">
    <div class="page-header">
        <h2>إدارة المحتوى</h2>
        <a href="/admin/content/add_topic.php" class="btn btn-primary">+ إضافة موضوع جديد</a>
        <a href="/admin/content/add_category.php" class="btn btn-secondary">+ إضافة فئة جديدة</a>
    </div>

    <div class="filters-bar">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="بحث بعنوان الموضوع..." value="<?= htmlspecialchars($search) ?>">
            <select name="status">
                <option value="">جميع الحالات</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>منشور</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>معلق</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>مرفوض</option>
            </select>
            <select name="category_id">
                <option value="">جميع الفئات</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (string)$category_id === (string)$cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">تصفية</button>
        </form>
    </div>

    <h3>المواضيع</h3>
    <?php if (!empty($topics)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>العنوان</th>
                        <th>الفئة</th>
                        <th>الكاتب</th>
                        <th>الحالة</th>
                        <th>المشاهدات</th>
                        <th>التعليقات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topics as $topic): ?>
                        <tr>
                            <td><?= $topic['id'] ?></td>
                            <td><?= htmlspecialchars($topic['title']) ?></td>
                            <td><?= htmlspecialchars($topic['category_name']) ?></td>
                            <td><?= htmlspecialchars($topic['username']) ?></td>
                            <td><span class="status-badge status-<?= $topic['status'] ?>"><?= htmlspecialchars($topic['status']) ?></span></td>
                            <td><?= format_number($topic['views']) ?></td>
                            <td><?= format_number($topic['comments_count']) ?></td>
                            <td>
                                <a href="/admin/content/edit_topic.php?id=<?= $topic['id'] ?>" class="btn btn-sm btn-info">تعديل</a>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('هل أنت متأكد؟');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="item_id" value="<?= $topic['id'] ?>">
                                    <input type="hidden" name="type" value="topic">
                                    <?php if ($topic['status'] === 'pending'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">نشر</button>
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
                <a href="/admin/content.php?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&status=<?= htmlspecialchars($status) ?>&category_id=<?= htmlspecialchars($category_id) ?>" 
                   class="pagination-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php else: ?>
        <div class="no-results">لا توجد مواضيع</div>
    <?php endif; ?>

    <h3>الفئات</h3>
    <?php if (!empty($categories)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>الاسم</th>
                        <th>الوصف</th>
                        <th>عدد المواضيع</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= $category['id'] ?></td>
                            <td><?= htmlspecialchars($category['name']) ?></td>
                            <td><?= htmlspecialchars(truncate_text($category['description'], 50)) ?></td>
                            <td><?= format_number($category['topics_count']) ?></td>
                            <td>
                                <a href="/admin/content/edit_category.php?id=<?= $category['id'] ?>" class="btn btn-sm btn-info">تعديل</a>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('هل أنت متأكد؟ سيتم حذف جميع المواضيع المرتبطة بهذه الفئة!');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="item_id" value="<?= $category['id'] ?>">
                                    <input type="hidden" name="type" value="category">
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-results">لا توجد فئات</div>
    <?php endif; ?>
</div>

<?php include '../templates/admin/footer.php'; ?>

