<?php
/**
 * Final Max CMS - ملف التكوين المتقدم
 * Advanced Configuration File
 * 
 * تم إنشاؤه تلقائياً بواسطة معالج التثبيت المتقدم
 * Generated automatically by Advanced Installation Wizard
 * 
 * تاريخ الإنشاء: 2025-08-29 22:10:17
 * Creation Date: 2025-08-29 22:10:17
 * 
 * @version 2.0.0
 * @author Final Max Team
 */

// منع الوصول المباشر
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// ========================================
// إعدادات قاعدة البيانات
// Database Configuration
// ========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'samu');
define('DB_USER', '');
define('DB_PASS', '123456');
define('DB_PREFIX', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// ========================================
// إعدادات الموقع الأساسية
// Basic Site Configuration
// ========================================
define('SITE_NAME', 'اشور ويب');
define('SITE_DESCRIPTION', 'ghghghgh');
define('ADMIN_EMAIL', 'nsamoray9@gmail.com');
define('SITE_URL', 'https://localhost');

// ========================================
// مفاتيح الأمان والتشفير
// Security Keys and Encryption
// ========================================

define('SECURITY_KEY', '8a024ccef52034f976d953c3bf78a94d3245dc5a8e268f2aabe54ea1a494818a');
define('AUTH_SALT', 'e9c47a55b9c60ab632a9501dfcaee0d6e73108ddc231b78192bd95e759350863');
define('SECURE_AUTH_SALT', '6361d2c6f4dbed97800b4cc2ba60547663aa86cd1a79b2a37de9bf6795e20b12');
define('NONCE_SALT', 'b97decfddcd660c1135c9f5e159f135c0175279545a3496077d1eae6cafb5b81');
define('LOGGED_IN_SALT', 'd332a8b51246e99c6d44259334fbbb05fa66419fd34f8eb13c4b4ff79e639de4');

// ========================================
// إعدادات الأمان المتقدمة
// Advanced Security Settings
// ========================================

define('SECURE_AUTH_COOKIE', true);
define('COOKIEHASH', md5(SITE_URL));
define('COOKIE_DOMAIN', 'localhost');
define('COOKIEPATH', '/');
define('SITECOOKIEPATH', '/');
define('ADMIN_COOKIE_PATH', '/admin/');

// ========================================
// إعدادات النظام العامة
// General System Settings
// ========================================

define('DEBUG_MODE', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);

define('TIMEZONE', 'Asia/Riyadh');
define('LANGUAGE', 'ar');
define('TEXT_DIRECTION', 'rtl');

define('MEMORY_LIMIT', '256M');
define('MAX_EXECUTION_TIME', 300);

// ========================================
// إعدادات الملفات والرفع
// File and Upload Settings
// ========================================

define('UPLOAD_MAX_SIZE', '10M');
define('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx,txt,zip');
define('UPLOAD_PATH', ABSPATH . 'assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

// ========================================
// إعدادات الجلسات والكوكيز
// Session and Cookie Settings
// ========================================

define('SESSION_COOKIE_LIFETIME', 86400);
define('SESSION_COOKIE_PATH', '/');
define('SESSION_COOKIE_DOMAIN', '');
define('SESSION_COOKIE_SECURE', false);
define('SESSION_COOKIE_HTTPONLY', true);

// ========================================
// إعدادات الأداء والتخزين المؤقت
// Performance and Caching Settings
// ========================================

define('ENABLE_CACHE', true);
define('CACHE_LIFETIME', 3600);
define('CACHE_PATH', ABSPATH . 'storage/cache/');

// ========================================
// مسارات النظام
// System Paths
// ========================================

define('CORE_PATH', ABSPATH . 'core/');
define('MODULES_PATH', ABSPATH . 'modules/');
define('TEMPLATES_PATH', ABSPATH . 'templates/');
define('LANGUAGES_PATH', ABSPATH . 'languages/');

// ========================================
// بدء تشغيل النظام
// System Bootstrap
// ========================================

require_once CORE_PATH . 'bootstrap.php';
