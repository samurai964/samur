<?php
require_once __DIR__ . '/../bootstrap.php';

// 🔐 تحقق الأدمن
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$currentUserId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$currentUserId]);
$role = $stmt->fetchColumn();

if ($role !== 'admin') {
    header("Location: /admin/no-access.php");
    exit;
}

// =====================
// 📌 جلب المستخدم
// =====================
$user_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    die("❌ المستخدم غير موجود");
}

// =====================
// 📌 التعديل
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Security::validateCSRFToken($_POST['csrf'] ?? '')) {
        $error = "❌ خطأ أمني";
    } else {

        // 🔥 حماية الأدمن الرئيسي
        if ($user_id == 1) {
            $error = "❌ لا يمكن تعديل الأدمن الرئيسي";
        } else {

            $username = Security::sanitizeInput($_POST['username']);
            $email = Security::sanitizeInput($_POST['email']);
            $role = $_POST['role'];

            $pdo->prepare("
                UPDATE users SET username=?, email=?, role=?
                WHERE id=?
            ")->execute([$username, $email, $role, $user_id]);

            // تغيير كلمة المرور (اختياري)
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

                $pdo->prepare("
                    UPDATE users SET password=?
                    WHERE id=?
                ")->execute([$password, $user_id]);
            }

            $success = "✅ تم التحديث بنجاح";
        }
    }
}

// =====================
// 📌 العرض
// =====================
ob_start();
?>

<h1>✏️ تعديل المستخدم</h1>

<?php if (!empty($error)): ?>

<div style="color:red"><?= $error ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>

<div style="color:green"><?= $success ?></div>
<?php endif; ?>

<form method="POST">

<input type="hidden" name="csrf" value="<?= Security::generateCSRFToken() ?>">

<label>الاسم</label><br>
<input name="username" value="<?= htmlspecialchars($userData['username']) ?>"><br><br>

<label>الإيميل</label><br>
<input name="email" value="<?= htmlspecialchars($userData['email']) ?>"><br><br>

<label>كلمة المرور (اختياري)</label><br>
<input type="password" name="password"><br><br>

<label>الدور</label><br>
<select name="role">
    <option value="user" <?= $userData['role']=='user'?'selected':'' ?>>مستخدم</option>
    <option value="admin" <?= $userData['role']=='admin'?'selected':'' ?>>أدمن</option>
</select><br><br>

<button>💾 حفظ</button>


</form>

<?php
$content = ob_get_clean();
$layout->render($content);
?>
