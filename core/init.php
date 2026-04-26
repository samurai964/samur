<?php

// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق مما إذا كان ROOT_PATH معرّف مسبقاً
if (!defined("ROOT_PATH")) {
    // تعريف المسارات الأساسية
    define("ROOT_PATH", realpath(dirname(__FILE__) . "/..") . DIRECTORY_SEPARATOR);
}

// التحقق مما إذا كان SITE_URL معرّف مسبقاً
if (!defined("SITE_URL")) {
    // تحديد SITE_URL بناءً على بيئة التشغيل
    $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off" ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    $script_name = $_SERVER["SCRIPT_NAME"] ?? "";
    $base_dir = str_replace("core/init.php", "", $script_name);
    define("SITE_URL", rtrim($protocol . "://" . $host . $base_dir, "/") . "/");
}

// تضمين ملف الإعدادات إذا كان موجوداً
$config_file = ROOT_PATH . "config" . DIRECTORY_SEPARATOR . "config.php";
if (file_exists($config_file)) {
    require_once $config_file;
} else {
    // إذا لم يتم التثبيت بعد، توجيه إلى صفحة التثبيت
    if (strpos($_SERVER["SCRIPT_NAME"], "install") === false) {
        header("Location: " . SITE_URL . "install/");
        exit;
    }
}

// تضمين ملف قاعدة البيانات إذا كان موجوداً
$database_file = ROOT_PATH . "core" . DIRECTORY_SEPARATOR . "Database.php";
if (file_exists($database_file)) {
    require_once $database_file;
    
    // تهيئة قاعدة البيانات إذا كان ملف الإعدادات موجوداً
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        $db = Database::getInstance();
    }
}

// تضمين ملف الدوال المساعدة إذا كان موجوداً
$functions_file = ROOT_PATH . "includes" . DIRECTORY_SEPARATOR . "functions.php";
if (file_exists($functions_file)) {
    require_once $functions_file;
}

// تضمين ملفات الموديل (اختياري، يمكن تحميلها عند الحاجة)
$user_model_file = ROOT_PATH . "models" . DIRECTORY_SEPARATOR . "User.php";
if (file_exists($user_model_file)) {
    require_once $user_model_file;
}

// إعدادات المنطقة الزمنية
if (defined('TIMEZONE')) {
    date_default_timezone_set(TIMEZONE);
} else {
    date_default_timezone_set('Asia/Riyadh');
}

// إعدادات الإبلاغ عن الأخطاء
if (defined('DEBUG_MODE')) {
    ini_set("display_errors", DEBUG_MODE ? 1 : 0);
    error_reporting(DEBUG_MODE ? E_ALL : 0);
} else {
    ini_set("display_errors", 0);
    error_reporting(0);
}

// وظيفة التحميل التلقائي للفئات (Autoloading classes)
spl_autoload_register(function ($class_name) {
    $class_file = ROOT_PATH . "core" . DIRECTORY_SEPARATOR . $class_name . ".php";
    if (file_exists($class_file)) {
        require_once $class_file;
        return;
    }
    
    $model_file = ROOT_PATH . "models" . DIRECTORY_SEPARATOR . $class_name . ".php";
    if (file_exists($model_file)) {
        require_once $model_file;
        return;
    }
    
    $controller_file = ROOT_PATH . "controllers" . DIRECTORY_SEPARATOR . $class_name . ".php";
    if (file_exists($controller_file)) {
        require_once $controller_file;
        return;
    }
});

// وظيفة التمهيد (Bootstrap)
function bootstrap() {
    // يمكنك إضافة أي منطق تهيئة إضافي هنا
    // مثل التحقق من التثبيت، أو تحميل الإعدادات من قاعدة البيانات
}

bootstrap();

?>