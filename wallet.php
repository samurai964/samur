<?php
require_once 'bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ يجب تسجيل الدخول");
}

// جلب المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$error = null;

// طلب سحب
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = Security::sanitizeInput($_POST['amount']);

    if (!$withdraw->request($_SESSION['user_id'], $amount)) {
        $error = "❌ الرصيد غير كافي";
    } else {
        $error = "✅ تم إرسال طلب السحب";
    }
}

// العمليات
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<h1>💰 محفظتي</h1>

<h3>الرصيد: $<?= $user['balance'] ?></h3>

<?php if ($error): ?>

<div style="background:#eee;padding:10px;margin:10px 0;">
    <?= $error ?>
</div>
<?php endif; ?>

<h3>💸 طلب سحب</h3>

<form method="POST">
    <input name="amount" placeholder="المبلغ">
    <button>طلب</button>
</form>

<hr>

<h3>📜 سجل العمليات</h3>

<?php foreach ($transactions as $t): ?>

<div style="border-bottom:1px solid #ddd;padding:5px;">
    <?= $t['type'] ?> - $<?= $t['amount'] ?>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
$layout->render($content);
