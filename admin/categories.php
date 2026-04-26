<?php
require_once __DIR__ . 
'/../includes/header.php';
require_once __DIR__ . 
'/includes/admin_header.php';

if (!is_admin()) {
    redirect('login.php');
}

$categories = get_all_categories();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);

        if (empty($name)) {
            $errors[] = 'اسم الفئة مطلوب.';
        }

        if (empty($errors)) {
            if (add_category($name, $description)) {
                $success = 'تم إضافة الفئة بنجاح!';
                $categories = get_all_categories(); // Refresh list
            } else {
                $errors[] = 'حدث خطأ أثناء إضافة الفئة.';
            }
        }
    } elseif (isset($_POST['edit_category'])) {
        $id = (int)$_POST['id'];
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);

        if (empty($name)) {
            $errors[] = 'اسم الفئة مطلوب.';
        }

        if (empty($errors)) {
            if (update_category($id, $name, $description)) {
                $success = 'تم تحديث الفئة بنجاح!';
                $categories = get_all_categories(); // Refresh list
            } else {
                $errors[] = 'حدث خطأ أثناء تحديث الفئة.';
            }
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = (int)$_POST['id'];
        if (delete_category($id)) {
            $success = 'تم حذف الفئة بنجاح!';
            $categories = get_all_categories(); // Refresh list
        } else {
            $errors[] = 'حدث خطأ أثناء حذف الفئة.';
        }
    }
}

?>

<div class="admin-content">
    <h2>إدارة الفئات</h2>

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

    <h3>إضافة فئة جديدة</h3>
    <form action="categories.php" method="POST" class="admin-form">
        <div class="form-group">
            <label for="name">اسم الفئة:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="description">الوصف:</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <button type="submit" name="add_category" class="btn btn-primary">إضافة فئة</button>
    </form>

    <h3>الفئات الحالية</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>الاسم</th>
                <th>الوصف</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo $category['id']; ?></td>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                        <td>
                            <form action="categories.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                <button type="submit" name="delete_category" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه الفئة؟');">حذف</button>
                            </form>
                            <!-- يمكنك إضافة زر تعديل يفتح نموذج تعديل -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">لا توجد فئات حالياً.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>


