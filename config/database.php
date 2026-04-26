<?php

// إعدادات قاعدة البيانات (مع منع التكرار)
if (!defined("DB_HOST")) {
    define("DB_HOST", "localhost");
}

if (!defined("DB_NAME")) {
    define("DB_NAME", "samu");
}

if (!defined("DB_USER")) {
    define("DB_USER", "root");
}

if (!defined("DB_PASS")) {
    define("DB_PASS", "");
}

if (!defined("DB_PREFIX")) {
    define("DB_PREFIX", "");
}

if (!defined("DB_CHARSET")) {
    define("DB_CHARSET", "utf8mb4");
}

if (!defined("DB_COLLATE")) {
    define("DB_COLLATE", "utf8mb4_unicode_ci");
}

// الاتصال بقاعدة البيانات باستخدام PDO
if (!isset($pdo)) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
    }
}

?>
