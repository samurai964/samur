<?php
if (!defined('ROOT_PATH')) {
define('ROOT_PATH', __DIR__);
}

// =====================
// 🔐 SESSION
// =====================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================
// 🐞 ERRORS (للتطوير فقط)
// =====================
ini_set("display_errors", 1);
error_reporting(E_ALL);

// =====================
// 🗄 DATABASE
// =====================
try {
    $pdo = new PDO("mysql:host=localhost;dbname=samu;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// =====================
// 📦 CORE (مرة واحدة فقط)
// =====================
require_once __DIR__ . '/core/Sidebar.php';
require_once __DIR__ . '/core/SidebarRenderer.php';
require_once __DIR__ . '/core/Ads.php';
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/core/Menu.php';
require_once __DIR__ . '/core/JWT.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/core/helpers.php';
// =====================
// ⚙️ SYSTEMS
// =====================
require_once __DIR__ . '/core/Service.php';
require_once __DIR__ . '/core/Project.php';
require_once __DIR__ . '/core/Bid.php';
require_once __DIR__ . '/core/Wallet.php';
require_once __DIR__ . '/core/Withdraw.php';
require_once __DIR__ . '/core/Admin.php';
require_once ROOT_PATH . '/core/Hook.php';
// =====================
// 🧠 CONTENT SYSTEM
// =====================
require_once __DIR__ . '/core/ContentEngine.php';
require_once __DIR__ . '/core/Category.php';
require_once __DIR__ . '/core/Tag.php';

// =====================
// ❌ SEO مؤقتًا معطل
// =====================
// require_once __DIR__ . '/core/SEO.php';

// =====================
// 🚀 INIT OBJECTS
// =====================
$ads = new Ads($pdo);
$sidebar = new Sidebar($pdo);
$user = new User($pdo);
$menu = new Menu($pdo);
$security = new Security($pdo, '');
$wallet = new Wallet($pdo);
$withdraw = new Withdraw($pdo);
require_once __DIR__ . '/core/Settings.php';
Settings::load($pdo);

// 🌍 تطبيق المنطقة الزمنية
date_default_timezone_set(Settings::get('timezone', 'UTC'));

require_once ROOT_PATH . '/core/ModuleManager.php';

new ModuleManager(ROOT_PATH . '/modules');

require_once __DIR__ . '/core/LanguageEngine.php';

// إنشاء instance عالمي
global $language_engine;
$language_engine = new LanguageEngine($pdo);

?>
