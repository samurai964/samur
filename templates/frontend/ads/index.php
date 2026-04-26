<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الحملات الإعلانية - Final Max CMS</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/main.css">
</head>
<body>
    <?php include ROOT_PATH . 
        '/templates/frontend/partials/header.php'; ?>

    <main class="container">
        <h1>الحملات الإعلانية</h1>
        <p>استكشف أحدث الحملات الإعلانية والعروض الترويجية.</p>
        
        <?php if (isset($_SESSION["error_message"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error_message"]; unset($_SESSION["error_message"]); ?></div>
        <?php endif; ?>

        <?php if (empty($data["ads"])): ?>
            <div class="alert alert-info">لا توجد حملات إعلانية متاحة حالياً.</div>
        <?php else: ?>
            <div class="ads-grid">
                <?php foreach ($data["ads"] as $ad): ?>
                    <div class="ad-card">
                        <?php if (!empty($ad["image_url"])): ?>
                            <img src="<?php echo htmlspecialchars($ad["image_url"]); ?>" alt="<?php echo htmlspecialchars($ad["title"]); ?>" class="ad-image">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($ad["title"]); ?></h3>
                        <p><?php echo htmlspecialchars($ad["description"]); ?></p>
                        <?php if (!empty($ad["link_url"])): ?>
                            <a href="<?php echo htmlspecialchars($ad["link_url"]); ?>" class="btn btn-primary" target="_blank">مشاهدة التفاصيل</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include ROOT_PATH . 
        '/templates/frontend/partials/footer.php'; ?>
    <script src="<?php echo ASSETS_URL; ?>/js/script.js"></script>
</body>
</html>

