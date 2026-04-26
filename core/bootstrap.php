<?php

// تشغيل الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('ROOT_PATH', dirname(__DIR__));
// قاعدة البيانات
require_once __DIR__ . '/../config/database.php';

// الحماية
require_once __DIR__ . '/CSRF.php';
require_once __DIR__ . '/Security.php';

// تسجيل
require_once __DIR__ . '/Logger.php';

// Auth
require_once __DIR__ . '/Auth.php';

// مساعدات
require_once __DIR__ . '/helpers.php';

// تحميل الإضافات
if (file_exists(__DIR__ . '/PluginManager.php')) {
    require_once __DIR__ . '/PluginManager.php';
    (new PluginManager())->loadPlugins();
}
// اعدادات النظام
require_once __DIR__ . '/Settings.php';
Settings::load($pdo);

date_default_timezone_set(Settings::get('timezone', 'UTC'));

require_once __DIR__ . '/LanguageEngine.php';

global $language_engine;
$language_engine = new LanguageEngine($pdo);

?>
