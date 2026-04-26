<?php
require_once __DIR__ . '/../bootstrap.php';

// 🔐 تحقق الأدمن
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetchColumn() !== 'admin') {
    header("Location: /admin/no-access.php");
    exit;
}

// =====================
// العمليات
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if (Security::validateCSRFToken($_POST['csrf'] ?? '')) {

        if ($user_id != 1) {

            switch ($action) {
                case 'delete':
                    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$user_id]);
                    break;

                case 'make_admin':
                    $pdo->prepare("UPDATE users SET role='admin' WHERE id=?")->execute([$user_id]);
                    break;

                case 'make_user':
                    $pdo->prepare("UPDATE users SET role='user' WHERE id=?")->execute([$user_id]);
                    break;

                case 'ban':
                    $pdo->prepare("UPDATE users SET banned=1 WHERE id=?")->execute([$user_id]);
                    break;

                case 'unban':
                    $pdo->prepare("UPDATE users SET banned=0 WHERE id=?")->execute([$user_id]);
                    break;
            }
        }
    }
}

// =====================
// البيانات
// =====================
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

?>

<h1>👤 إدارة المستخدمين</h1>

<table border="1" width="100%">
<tr>
    <th>ID</th>
    <th>الاسم</th>
    <th>الإيميل</th>
    <th>الدور</th>
    <th>الحالة</th>
    <th>إجراء</th>
</tr>

<?php foreach ($users as $u): ?>

<tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= $u['role'] ?></td>
    <td><?= $u['banned'] ? '🚫 محظور' : '✅ نشط' ?></td>

<td>
<form method="POST">
<input type="hidden" name="csrf" value="<?= Security::generateCSRFToken() ?>">
<input type="hidden" name="user_id" value="<?= $u['id'] ?>">

<button name="action" value="ban">حظر</button> <button name="action" value="unban">فك الحظر</button> <button name="action" value="delete">حذف</button>

</form>
</td>

</tr>

<?php endforeach; ?>

</table>
