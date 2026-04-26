<?php
require_once 'bootstrap.php';

$id = $_GET['id'] ?? 0;

// جلب الموضوع
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("❌ الموضوع غير موجود");
}

// الإعلانات
function getAds($pdo, $placement) {
    $stmt = $pdo->prepare("SELECT * FROM ads WHERE placement = ? AND is_active = 1");
    $stmt->execute([$placement]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// تقسيم النص
$text = htmlspecialchars($post['content']);
$paragraphs = explode("\n", $text);

$final = "";

// إعلان أعلى داخل النص
foreach (getAds($pdo, 'content_top') as $ad) {
    $final .= "<div class='ad-box'>{$ad['content']}</div>";
}

foreach ($paragraphs as $i => $p) {

    $final .= "<p>$p</p>";

    // إعلان وسط
    if ($i == floor(count($paragraphs)/2)) {
        foreach (getAds($pdo, 'content_middle') as $ad) {
            $final .= "<div class='ad-box'>{$ad['content']}</div>";
        }
    }
}

// إعلان أسفل
foreach (getAds($pdo, 'content_bottom') as $ad) {
    $final .= "<div class='ad-box'>{$ad['content']}</div>";
}

ob_start();
?>

<h1><?= htmlspecialchars($post['title']) ?></h1>

<div style="background:#fff;padding:15px;border:1px solid #ddd;">
    <?= $final ?>
</div>

<?php
$content = ob_get_clean();
$layout->render($content);