<?php
require_once 'bootstrap.php';

$project = new Project($pdo);

$error = null;

// Rate Limit
if (!$security->checkRateLimit('project_add', $_SERVER['REMOTE_ADDR'], 3, 60)) {
    $error = "❌ تم حظر المحاولات مؤقتاً";
}

// إضافة مشروع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && !$error) {

    if (!Security::validateCSRFToken($_POST['csrf'])) {
        $error = "❌ طلب غير صالح";
    } else {

        $title = Security::sanitizeInput($_POST['title'] ?? '');
        $desc  = Security::sanitizeInput($_POST['description'] ?? '');
        $budget = Security::sanitizeInput($_POST['budget'] ?? '');

        $project->create($_SESSION['user_id'], $title, $desc, $budget);

        header("Location: projects.php");
        exit;
    }
}

$projects = $project->getAll();

ob_start();
?>

<style>
.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.project-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 15px;
    transition: 0.3s;
}

.project-card:hover {
    transform: scale(1.02);
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.project-title {
    font-size: 18px;
    font-weight: bold;
}

.project-budget {
    color: green;
    font-weight: bold;
    margin-top: 10px;
}

.project-status {
    margin-top: 5px;
    font-size: 12px;
    color: #555;
}

.form-box {
    background: #f9f9f9;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 10px;
}
</style>

<h1>📂 المشاريع</h1>

<?php if ($error): ?>

<div style="background:#ffdddd;color:#900;padding:10px;margin:10px 0;">
    <?= $error ?>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['user_id'])): ?>

<div class="form-box">
<form method="POST">
    <input type="hidden" name="csrf" value="<?= Security::generateCSRFToken() ?>">

<input name="title" placeholder="عنوان المشروع"><br><br>
<textarea name="description" placeholder="تفاصيل المشروع"></textarea><br><br>
<input name="budget" type="number" placeholder="الميزانية"><br><br>

<button>➕ نشر مشروع</button>

</form>
</div>
<?php endif; ?>

<div class="projects-grid">

<?php foreach ($projects as $p): ?>

<?php
$title  = htmlspecialchars($p['title'] ?? '');
$desc   = htmlspecialchars($p['description'] ?? '');
$budget = htmlspecialchars($p['budget'] ?? '');
$status = htmlspecialchars($p['status'] ?? 'open');
?>

<div class="project-card">
    <div class="project-title"><a href="project.php?id=<?= $p['id'] ?>">
    <?= $title ?>
</a></div>

<?php if ($desc): ?>
    <p><?= substr($desc, 0, 100) ?>...</p>
<?php endif; ?>

<div class="project-budget">💰 $<?= $budget ?></div>

<div class="project-status">
    الحالة: <?= $status == 'open' ? '🟢 مفتوح' : '🔴 مغلق' ?>
</div>

</div>

<?php endforeach; ?>

</div>

<?php
$content = ob_get_clean();
$layout->render($content);
