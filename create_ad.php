<?php
require_once 'bootstrap.php';

if (!$user->isLogged()) {
    die("يجب تسجيل الدخول");
}

if (isset($_POST['create_ad'])) {

    $title = $_POST['title'];
    $contentAd = $_POST['content'];
    $placement = $_POST['placement'];
    $budget = $_POST['budget'];

    if ($user->getBalance() >= $budget) {

        $ads->createAd($user->getUser()['id'], $title, $contentAd, $placement, $budget);
        $user->subtractBalance($budget);

        $message = "تم إنشاء الإعلان بنجاح";

    } else {
        $error = "رصيدك غير كافي";
    }
}

ob_start();
?>

<h2>إنشاء إعلان</h2>

<?php if (isset($message)) echo "<div style='color:green'>$message</div>"; ?>

<?php if (isset($error)) echo "<div style='color:red'>$error</div>"; ?>

<form method="POST">
    <input type="text" name="title" placeholder="عنوان الإعلان"><br><br>
    <textarea name="content" placeholder="كود الإعلان"></textarea><br><br>

<select name="placement">
    <option value="sidebar">Sidebar</option>
    <option value="content_top">أعلى المحتوى</option>
</select><br><br>

<input type="number" step="0.01" name="budget" placeholder="الميزانية"><br><br>

<button name="create_ad">إنشاء</button>

</form>

<?php
$content = ob_get_clean();

$layout->render($content);
?>