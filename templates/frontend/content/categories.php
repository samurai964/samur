<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأقسام - Final Max CMS</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/main.css">
</head>
<body>
    <?php include ROOT_PATH . 
        '/templates/frontend/partials/header.php'; ?>

    <main class="container">
        <h1>الأقسام</h1>
        
        <?php if (isset($_SESSION["error_message"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error_message"]; unset($_SESSION["error_message"]); ?></div>
        <?php endif; ?>
        
        <?php if (empty($data["categories"])): ?>
            <div class="alert alert-info">لا توجد أقسام متاحة حالياً.</div>
        <?php else: ?>
            <div class="categories-grid">
                <?php foreach ($data["categories"] as $category): ?>
                    <div class="category-card">
                        <h3><a href="/topics?category=<?php echo $category["id"]; ?>"><?php echo htmlspecialchars($category["name"]); ?></a></h3>
                        <?php if (!empty($category["description"])): ?>
                            <p class="category-description"><?php echo htmlspecialchars($category["description"]); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($category["subcategories"])): ?>
                            <div class="subcategories">
                                <h4>الأقسام الفرعية:</h4>
                                <ul>
                                    <?php foreach ($category["subcategories"] as $subcategory): ?>
                                        <li>
                                            <a href="/topics?category=<?php echo $subcategory["id"]; ?>">
                                                <?php echo htmlspecialchars($subcategory["name"]); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="category-stats">
                            <?php
                            // يمكن إضافة إحصائيات هنا مثل عدد المواضيع في كل قسم
                            ?>
                        </div>
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

