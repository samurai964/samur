<?php
require_once __DIR__ . 
'/../includes/header.php';
require_once __DIR__ . 
'/includes/admin_header.php';

if (!is_admin()) {
    redirect('login.php');
}

$posts = get_all_posts();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Edit/Delete post logic here
    // For simplicity, we'll just show the list
}

?>

<div class="admin-content">
    <h2>إدارة المشاركات</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <p><a href="#" class="btn btn-primary">إضافة مشاركة جديدة</a></p>

    <h3>المشاركات الحالية</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>العنوان</th>
                <th>الكاتب</th>
                <th>الفئة</th>
                <th>تاريخ النشر</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo $post['id']; ?></td>
                        <td><?php echo htmlspecialchars($post['title']); ?></td>
                        <td><?php echo htmlspecialchars($post['username']); ?></td>
                        <td><?php echo htmlspecialchars(get_category_name_by_id($post['category_id'])); ?></td>
                        <td><?php echo $post['created_at']; ?></td>
                        <td>
                            <form action="posts.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                <button type="submit" name="delete_post" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه المشاركة؟');">حذف</button>
                            </form>
                            <a href="#" class="btn btn-info">تعديل</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">لا توجد مشاركات حالياً.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>


