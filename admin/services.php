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

// حذف
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];

    if (Security::validateCSRFToken($_POST['csrf'] ?? '')) {
        $pdo->prepare("DELETE FROM services WHERE id=?")->execute([$id]);
    }
}

$services = $pdo->query("SELECT * FROM services ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>💼 إدارة الخدمات</h1>

<table border="1" width="100%">
<tr>
    <th>ID</th>
    <th>العنوان</th>
    <th>السعر</th>
    <th>إجراء</th>
</tr>

<?php foreach ($services as $s): ?>

<tr>
<td><?= $s['id'] ?></td>
<td><?= htmlspecialchars($s['title']) ?></td>
<td><?= $s['price'] ?></td>

<td>
<form method="POST">
<input type="hidden" name="csrf" value="<?= Security::generateCSRFToken() ?>">
<input type="hidden" name="id" value="<?= $s['id'] ?>">
<button>حذف</button>
</form>
</td>

</tr>

<?php endforeach; ?>

</table>
