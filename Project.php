<?php
require_once 'bootstrap.php';

$bid = new Bid($pdo);

$id = $_GET['id'] ?? 0;

// جلب المشروع
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    die("المشروع غير موجود");
}

$error = null;

// قبول عرض
if (isset($_GET['accept']) && isset($_SESSION['user_id'])) {

    if ($project['user_id'] == $_SESSION['user_id']) {

        if (!$bid->acceptBid($_GET['accept'])) {
            $error = "❌ لا يوجد رصيد كافي";
        }

        header("Location: project.php?id=" . $id);
        exit;
    }
}

// إضافة عرض
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {

    if ($_SESSION['user_id'] == $project['user_id']) {
        $error = "❌ لا يمكنك التقديم على مشروعك";
    } elseif (!Security::validateCSRFToken($_POST['csrf'])) {
        $error = "❌ طلب غير صالح";
    } else {

        $amount = Security::sanitizeInput($_POST['amount']);
        $message = Security::sanitizeInput($_POST['message']);

        $bid->create($id, $_SESSION['user_id'], $amount, $message);

        header("Location: project.php?id=" . $id);
        exit;
    }
}

$bids = $bid->getByProject($id);

ob_start();
?>

<h1><?= htmlspecialchars($project['title']) ?></h1>

<p><?= htmlspecialchars($project['description']) ?></p>

<h3>💰 الميزانية: $<?= $project['budget'] ?></h3>

<?php if ($error): ?>

<div style="background:#ffdddd;color:#900;padding:10px;">
    <?= $error ?>
</div>
<?php endif; ?>

<hr>

<h2>📨 العروض</h2>

<?php foreach ($bids as $b): ?>

<div style="border:1px solid #ddd;padding:10px;margin:10px 0;">

<strong>$<?= $b['amount'] ?></strong>
<p><?= htmlspecialchars($b['message']) ?></p>
<small>👤 <?= $b['username'] ?></small>

<?php if ($project['user_id'] == ($_SESSION['user_id'] ?? null) && !$project['assigned_user_id']): ?>
    <br><br>
    <a href="project.php?id=<?= $id ?>&accept=<?= $b['id'] ?>">
        ✅ قبول العرض
    </a>
<?php endif; ?>

</div>

<?php endforeach; ?>

<?php if (
    isset($_SESSION['user_id']) &&
    $_SESSION['user_id'] != $project['user_id'] &&
    !$project['assigned_user_id']
): ?>

<hr>

<h3>➕ قدم عرضك</h3>

<form method="POST">
    <input type="hidden" name="csrf" value="<?= Security::generateCSRFToken() ?>">

<input name="amount"><br><br>
<textarea name="message"></textarea><br><br>

<button>إرسال العرض</button>

</form>
<?php endif; ?>

<?php
$content = ob_get_clean();
$layout->render($content);
