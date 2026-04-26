<?php

require_once __DIR__ . '/Flash.php';

class AdminLayout {

    public function render($content, $data = []) {

        extract($data);

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body class="admin">

<!-- 🔥 SIDEBAR -->
<?php require __DIR__ . '/../admin/sidebar.php'; ?>

<!-- 🔥 MAIN -->
<div class="admin-main">

    <!-- HEADER -->
    <?php require __DIR__ . '/../templates/admin/header.php'; ?>

    <!-- CONTENT -->
    <main class="admin-content">
        <?php
        if (!file_exists($content)) {
            die("View not found: " . $content);
        }
        require $content;
        ?>
    </main>

    <!-- FOOTER -->
    <?php require __DIR__ . '/../templates/admin/footer.php'; ?>

</div>

</body>
</html>

<?php
    }
}
?>
