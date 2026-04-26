<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/DashboardWidgets.php';
require_once __DIR__ . '/../core/helpers.php';

echo Settings::get('site_name');
// 🔐 تحقق تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// 🔐 تحقق الأدمن
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'admin') {
    header("Location: /admin/no-access.php");
    exit;
}

// =====================
// 🧠 دوال آمنة
// =====================
function db_count($pdo, $table) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table`");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function db_sum($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(`$column`),0) FROM `$table`");
        $stmt->execute();
        return (float)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

// =====================
// 📊 تسجيل Widgets الأساسية
// =====================
register_dashboard_widget([
    'title' => 'المستخدمين',
    'value' => db_count($pdo, 'users'),
    'icon'  => '👤'
]);

register_dashboard_widget([
    'title' => 'الخدمات',
    'value' => db_count($pdo, 'services'),
    'icon'  => '💼'
]);

register_dashboard_widget([
    'title' => 'المشاريع',
    'value' => db_count($pdo, 'projects'),
    'icon'  => '📂'
]);

register_dashboard_widget([
    'title' => 'العمليات',
    'value' => db_count($pdo, 'transactions'),
    'icon'  => '💰'
]);

register_dashboard_widget([
    'title' => 'الطلبات',
    'value' => db_count($pdo, 'orders'),
    'icon'  => '🧾'
]);

register_dashboard_widget([
    'title' => 'الدورات',
    'value' => db_count($pdo, 'courses'),
    'icon'  => '📚'
]);

register_dashboard_widget([
    'title' => 'الإعلانات',
    'value' => db_count($pdo, 'ads'),
    'icon'  => '📢'
]);

// =====================
// 💰 المال
// =====================
$totalRevenue = db_sum($pdo, 'transactions', 'amount');
$siteProfit = $totalRevenue * 0.10;

register_dashboard_widget([
    'title' => 'إجمالي الأموال',
    'value' => '$' . number_format($totalRevenue,2),
    'icon'  => '💵'
]);

register_dashboard_widget([
    'title' => 'أرباح الموقع',
    'value' => '$' . number_format($siteProfit,2),
    'icon'  => '📈'
]);

// =====================
// 📦 تحميل Widgets (Plugins)
// =====================
$widgets = DashboardWidgets::get();

// =====================
// 📌 العرض
// =====================
ob_start();
?>

<h1>🛠 لوحة التحكم</h1>

<style>
.admin-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
    gap: 15px;
    margin-top: 20px;
}

.card {
    background: #1e293b;
    color: #fff;
    padding: 20px;
    border-radius: 10px;
    font-size: 16px;
    text-align: center;
}

.card strong {
    display:block;
    font-size:22px;
    margin-top:10px;
}
</style>

<div class="admin-cards">

<?php foreach ($widgets as $w): ?>

<div class="card">
    <?= $w['icon'] ?? '📊' ?>
    <div><?= $w['title'] ?></div>
    <strong><?= $w['value'] ?></strong>
</div>

<?php endforeach; ?>

</div>

<?php
echo ob_get_clean();
?>
