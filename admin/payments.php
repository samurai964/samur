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

$payments = $pdo->query("
    SELECT t.id, t.amount, u.username 
    FROM transactions t
    LEFT JOIN users u ON u.id = t.user_id
    ORDER BY t.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>💳 إدارة المدفوعات</h1>

<table border="1" width="100%">
<tr>
    <th>ID</th>
    <th>المستخدم</th>
    <th>المبلغ</th>
</tr>

<?php foreach ($payments as $p): ?>

<tr>
<td><?= $p['id'] ?></td>
<td><?= htmlspecialchars($p['username']) ?></td>
<td><?= $p['amount'] ?></td>
</tr>

<?php endforeach; ?>

</table>
