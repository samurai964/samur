<?php
require_once __DIR__ . 
'/../includes/header.php';
require_once __DIR__ . 
'/includes/admin_header.php';

if (!is_admin()) {
    redirect('login.php');
}

$comments = get_all_comments();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Edit/Delete comment logic here
    // For simplicity, we'll just show the list
}

?>

<div class="admin-content">
    <h2>إدارة التعليقات</h2>

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

    <h3>التعليقات الحالية</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>المحتوى</th>
                <th>المستخدم</th>
                <th>المشاركة</th>
                <th>تاريخ النشر</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td><?php echo $comment['id']; ?></td>
                        <td><?php echo htmlspecialchars($comment['content']); ?></td>
                        <td><?php echo htmlspecialchars($comment['username']); ?></td>
                        <td><?php echo htmlspecialchars($comment['post_title']); ?></td>
                        <td><?php echo $comment['created_at']; ?></td>
                        <td>
                            <form action="comments.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                <button type="submit" name="delete_comment" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا التعليق؟');">حذف</button>
                            </form>
                            <a href="#" class="btn btn-info">تعديل</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">لا توجد تعليقات حالياً.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>


