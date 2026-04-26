<?php
$page_title = 'إدارة دليل المواقع';
include '../templates/admin/header.php';

// التحقق من صلاحيات المدير
if (!Auth::hasPermission('manage_directory')) {
    redirect('/admin/dashboard.php');
}

$directoryModel = new DirectoryModel();

// معالجة طلبات الحذف/التفعيل/التعطيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $item_id = (int)($_POST['item_id'] ?? 0);
    $type = $_POST['type'] ?? ''; // 'website' or 'category' or 'review'

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $this->setError('خطأ في التحقق من الأمان');
    } else {
        switch ($action) {
            case 'delete':
                if ($type === 'website') {
                    if ($directoryModel->deleteWebsite($item_id)) {
                        $this->setSuccess('تم حذف الموقع بنجاح');
                    } else {
                        $this->setError('فشل حذف الموقع');
                    }
                } elseif ($type === 'category') {
                    if ($directoryModel->deleteCategory($item_id)) {
                        $this->setSuccess('تم حذف الفئة بنجاح');
                    } else {
                        $this->setError('فشل حذف الفئة');
                    }
                } elseif ($type === 'review') {
                    if ($directoryModel->deleteReview($item_id)) {
                        $this->setSuccess('تم حذف التقييم بنجاح');
                    } else {
                        $this->setError('فشل حذف التقييم');
                    }
                }
                break;
            case 'approve':
                if ($type === 'website') {
                    if ($directoryModel->updateWebsiteStatus($item_id, 'approved')) {
                        $this->setSuccess('تمت الموافقة على الموقع بنجاح');
                    } else {
                        $this->setError('فشل الموافقة على الموقع');
                    }
                } elseif ($type === 'review') {
                    if ($directoryModel->updateReviewStatus($item_id, 'approved')) {
                        $this->setSuccess('تمت الموافقة على التقييم بنجاح');
                    } else {
                        $this->setError('فشل الموافقة على التقييم');
                    }
                }
                break;
            case 'reject':
                if ($type === 'website') {
                    if ($directoryModel->updateWebsiteStatus($item_id, 'rejected')) {
                        $this->setSuccess('تم رفض الموقع بنجاح');
                    } else {
                        $this->setError('فشل رفض الموقع');
                    }
                } elseif ($type === 'review') {
                    if ($directoryModel->updateReviewStatus($item_id, 'rejected')) {
                        $this->setSuccess('تم رفض التقييم بنجاح');
                    } else {
                        $this->setError('فشل رفض التقييم');
                    }
                }
                break;
            case 'feature':
                if ($type === 'website') {
                    if ($directoryModel->updateWebsiteFeaturedStatus($item_id, true)) {
                        $this->setSuccess('تم تمييز الموقع بنجاح');
                    } else {
                        $this->setError('فشل تمييز الموقع');
                    }
                }
                break;
            case 'unfeature':
                if ($type === 'website') {
                    if ($directoryModel->updateWebsiteFeaturedStatus($item_id, false)) {
                        $this->setSuccess('تم إلغاء تمييز الموقع بنجاح');
                    } else {
                        $this->setError('فشل إلغاء تمييز الموقع');
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

$websites_data = $directoryModel->getWebsites(['page' => $page, 'limit' => $limit, 'search' => $search, 'status' => $status, 'category_id' => $category_id]);
$websites = $websites_data['websites'];
$total_websites = $websites_data['total'];
$total_pages = $websites_data['pages'];

$categories = $directoryModel->getCategories();

$reviews_page = (int)($_GET['reviews_page'] ?? 1);
$reviews_limit = 10;
$pending_reviews_data = $directoryModel->getReviews(['page' => $reviews_page, 'limit' => $reviews_limit, 'status' => 'pending']);
$pending_reviews = $pending_reviews_data['reviews'];
$total_pending_reviews = $pending_reviews_data['total'];
$total_reviews_pages = $pending_reviews_data['pages'];

?>

<div class="admin-page">
    <div class="page-header">
        <h2>إدارة دليل المواقع</h2>
        <a href="/admin/directory/add_website.php" class="btn btn-primary">+ إضافة موقع جديد</a>
        <a href="/admin/directory/add_category.php" class="btn btn-secondary">+ إضافة فئة جديدة</a>
    </div>

    <h3>المواقع</h3>
    <div class="filters-bar">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="بحث بعنوان الموقع..." value="<?= htmlspecialchars($search) ?>">
            <select name="status">
                <option value="">جميع الحالات</option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>موافق عليه</option>
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

    <?php if (!empty($websites)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>العنوان</th>
                        <th>الفئة</th>
                        <th>الحالة</th>
                        <th>مميز</th>
                        <th>التقييم</th>
                        <th>المشاهدات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($websites as $website): ?>
                        <tr>
                            <td><?= $website['id'] ?></td>
                            <td><a href="<?= htmlspecialchars($website['url']) ?>" target="_blank"><?= htmlspecialchars($website['title']) ?></a></td>
                            <td><?= htmlspecialchars($website['category_name']) ?></td>
                            <td><span class="status-badge status-<?= $website['status'] ?>"><?= htmlspecialchars($website['status']) ?></span></td>
                            <td><?= $website['is_featured'] ? 'نعم' : 'لا' ?></td>
                            <td><?= $website['rating'] ?> (<?= $website['reviews_count'] ?>)</td>
                            <td><?= format_number($website['views']) ?></td>
                            <td>
                                <a href="/admin/directory/edit_website.php?id=<?= $website['id'] ?>" class="btn btn-sm btn-info">تعديل</a>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('هل أنت متأكد؟');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="item_id" value="<?= $website['id'] ?>">
                                    <input type="hidden" name="type" value="website">
                                    <?php if ($website['status'] === 'pending'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">موافقة</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-warning">رفض</button>
                                    <?php endif; ?>
                                    <?php if ($website['is_featured']): ?>
                                        <button type="submit" name="action" value="unfeature" class="btn btn-sm btn-secondary">إلغاء تمييز</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="feature" class="btn btn-sm btn-primary">تمييز</button>
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
                <a href="/admin/directory.php?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&status=<?= htmlspecialchars($status) ?>&category_id=<?= htmlspecialchars($category_id) ?>" 
                   class="pagination-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php else: ?>
        <div class="no-results">لا توجد مواقع</div>
    <?php endif; ?>

    <h3 style="margin-top: 30px;">الفئات</h3>
    <?php if (!empty($categories)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>الاسم</th>
                        <th>الوصف</th>
                        <th>عدد المواقع</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= $category['id'] ?></td>
                            <td><?= htmlspecialchars($category['name']) ?></td>
                            <td><?= htmlspecialchars(truncate_text($category['description'], 50)) ?></td>
                            <td><?= format_number($category['websites_count']) ?></td>
                            <td>
                                <a href="/admin/directory/edit_category.php?id=<?= $category['id'] ?>" class="btn btn-sm btn-info">تعديل</a>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('هل أنت متأكد؟ سيتم حذف جميع المواقع المرتبطة بهذه الفئة!');">
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

    <h3 style="margin-top: 30px;">التقييمات المعلقة (<?= $total_pending_reviews ?>)</h3>
    <?php if (!empty($pending_reviews)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>الموقع</th>
                        <th>المستخدم</th>
                        <th>التقييم</th>
                        <th>التعليق</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_reviews as $review): ?>
                        <tr>
                            <td><?= $review['id'] ?></td>
                            <td><a href="/directory/<?= htmlspecialchars($review['website_slug']) ?>" target="_blank"><?= htmlspecialchars($review['website_title']) ?></a></td>
                            <td><?= htmlspecialchars($review['username']) ?></td>
                            <td><?= $review['rating'] ?> ⭐</td>
                            <td><?= htmlspecialchars(truncate_text($review['comment'], 100)) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($review['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('هل أنت متأكد؟');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="item_id" value="<?= $review['id'] ?>">
                                    <input type="hidden" name="type" value="review">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">موافقة</button>
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-warning">رفض</button>
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            <?php for ($i = 1; $i <= $total_reviews_pages; $i++): ?>
                <a href="/admin/directory.php?reviews_page=<?= $i ?>" 
                   class="pagination-link <?= $i == $reviews_page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php else: ?>
        <div class="no-results">لا توجد تقييمات معلقة</div>
    <?php endif; ?>
</div>

<?php include '../templates/admin/footer.php'; ?>

