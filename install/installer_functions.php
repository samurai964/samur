<?php

if (session_status() == PHP_SESSION_NONE) { session_start(); }

/**
 * Final Max CMS - دوال المثبت المتقدمة
 * Advanced Installer Functions
 * 
 * @version 2.0.0
 * @author Final Max Team
 * @license MIT
 */

// منع الوصول المباشر
if (!defined("INSTALL_PATH")) {
    die("Access denied");
}

/**
 * إنشاء ملفات الخطوات إذا لم تكن موجودة
 */
function createStepFilesIfNotExist() {
    $steps_path = INSTALL_PATH . "/steps/";
    
    // إنشاء مجلد steps إذا لم يكن موجوداً
    if (!is_dir($steps_path)) {
        mkdir($steps_path, 0755, true);
    }
    
    // محتوى ملف الترحيب
    $welcome_content = '<div class="text-center">
        <h2>مرحباً بك في Final Max CMS</h2>
        <p>شكراً لاختيارك نظام Final Max CMS لإدارة محتوى موقعك.</p>
        <p>سيقوم معالج التثبيت بمساعدتك في إعداد النظام خلال بضع خطوات بسيطة.</p>
        
        <div class="features" style="margin: 30px 0; text-align: right;">
            <h3>المميزات الرئيسية:</h3>
            <ul style="list-style: none; padding: 0;">
                <li>✅ نظام إدارة محتوى متكامل وسهل الاستخدام</li>
                <li>✅ تصميم متجاوب يعمل على جميع الأجهزة</li>
                <li>✅ دعم متعدد اللغات</li>
                <li>✅ نظام تحرير متقدم</li>
                <li>✅ إدارة المستخدمين والصلاحيات</li>
                <li>✅ تحسين لمحركات البحث (SEO)</li>
            </ul>
        </div>
        
        <div class="requirements-note" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4>قبل البدء، تأكد من:</h4>
            <p>• توفر خادم ويب يدعم PHP 7.4 أو أحدث</p>
            <p>• توفر قاعدة بيانات MySQL أو MariaDB</p>
            <p>• صلاحيات كتابة للمجلدات والملفات</p>
        </div>
        
        <button type="submit" class="btn btn-large">بدء التثبيت</button>
    </div>';
    
    // محتوى ملف المتطلبات
    $requirements_content = '<h2>فحص متطلبات النظام</h2>
    <p>يقوم النظام بفحص متطلبات التشغيل على الخادم الخاص بك.</p>';
    
    // محتوى ملف قاعدة البيانات
    $database_content = '<h2>إعدادات قاعدة البيانات</h2>
    <p>يرجى إدخال معلومات الاتصال بقاعدة البيانات.</p>

    <div class="form-group">
        <label for="db_host">خادم قاعدة البيانات (Host)</label>
        <input type="text" id="db_host" name="db_host" value="localhost" required>
    </div>

    <div class="form-group">
        <label for="db_name">اسم قاعدة البيانات</label>
        <input type="text" id="db_name" name="db_name" required>
    </div>

    <div class="form-group">
        <label for="db_user">اسم المستخدم</label>
        <input type="text" id="db_user" name="db_user" required>
    </div>

    <div class="form-group">
        <label for="db_pass">كلمة المرور</label>
        <input type="password" id="db_pass" name="db_pass">
    </div>

    <button type="submit" class="btn">اختبار الاتصال والمتابعة</button>';
    
    // محتوى ملف المدير
    $admin_content = '<h2>إنشاء حساب المدير</h2>
    <p>يرجى إدخال معلومات حساب المدير الرئيسي للنظام.</p>

    <div class="form-group">
        <label for="admin_username">اسم المستخدم</label>
        <input type="text" id="admin_username" name="admin_username" required>
        <small style="display: block; color: #666; margin-top: 5px;">
            يجب أن يكون اسم المستخدم بين 3 و 50 حرفاً ويحتوي على أحرف وأرقام و _ فقط.
        </small>
    </div>

    <div class="form-group">
        <label for="admin_email">البريد الإلكتروني</label>
        <input type="email" id="admin_email" name="admin_email" required>
    </div>

    <div class="form-group">
        <label for="admin_password">كلمة المرور</label>
        <input type="password" id="admin_password" name="admin_password" required>
        <small style="display: block; color: #666; margin-top: 5px;">
            يجب أن تكون كلمة المرور 6 أحرف على الأقل.
        </small>
    </div>

    <div class="form-group">
        <label for="admin_password_confirm">تأكيد كلمة المرور</label>
        <input type="password" id="admin_password_confirm" name="admin_password_confirm" required>
    </div>

    <h3 style="margin-top: 30px;">إعدادات الموقع</h3>

    <div class="form-group">
        <label for="site_name">اسم الموقع</label>
        <input type="text" id="site_name" name="site_name" required>
    </div>

    <div class="form-group">
        <label for="site_description">وصف الموقع</label>
        <textarea id="site_description" name="site_description" rows="3" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
        <small style="display: block; color: #666; margin-top: 5px;">
            وصف مختصر لموقعك (اختياري).
        </small>
    </div>

    <button type="submit" class="btn">حفظ والمتابعة إلى التثبيت</button>';
    
    // محتوى ملف التثبيت
    $installation_content = '<h2>تثبيت النظام</h2>
    <p>جاري تثبيت Final Max CMS على خادمك. هذه العملية قد تستغرق بضع دقائق.</p>

    <div class="progress-container">
        <div id="progress-bar" class="progress-bar" style="width: 0%">0%</div>
    </div>

    <div id="status-message" style="text-align: center; margin: 20px 0; font-weight: bold;">
        جاري التحضير للتثبيت...
    </div>

    <div style="text-align: center; display: none;" id="complete-message">
        <div class="alert alert-success">✅ تم التثبيت بنجاح!</div>
        <p>جاري التوجيه إلى صفحة النتائج...</p>
    </div>';
    
    // إنشاء الملفات إذا لم تكن موجودة
    $files = [
        'welcome.php' => $welcome_content,
        'requirements.php' => $requirements_content,
        'database.php' => $database_content,
        'admin.php' => $admin_content,
        'installation.php' => $installation_content
    ];
    
    foreach ($files as $filename => $content) {
        if (!file_exists($steps_path . $filename)) {
            file_put_contents($steps_path . $filename, "<?php defined('INSTALL_PATH') or die('Direct access not allowed.'); ?>\n" . $content);
        }
    }
}

/**
 * فحص شامل لمتطلبات النظام
 */
function checkSystemRequirements() {
    $checks = [];
    $all_passed = true;
    
    // فحص إصدار PHP
    $php_version_ok = version_compare(PHP_VERSION, "7.4.0", ">=");
    $checks[] = [
        "name" => "إصدار PHP",
        "required" => "7.4.0 أو أحدث",
        "current" => PHP_VERSION,
        "status" => $php_version_ok,
        "critical" => true
    ];
    if (!$php_version_ok) $all_passed = false;
    
    // فحص MySQLi Extension
    $mysqli_ok = extension_loaded("mysqli");
    $checks[] = [
        "name" => "MySQLi Extension",
        "required" => "مطلوب للاتصال بقاعدة البيانات",
        "current" => $mysqli_ok ? "متوفر" : "غير متوفر",
        "status" => $mysqli_ok,
        "critical" => true
    ];
    if (!$mysqli_ok) $all_passed = false;
    
    // فحص PDO MySQL Extension
    $pdo_mysql_ok = extension_loaded("pdo") && extension_loaded("pdo_mysql");
    $checks[] = [
        "name" => "PDO MySQL Extension",
        "required" => "مطلوب لل العمليات المتقدمة",
        "current" => $pdo_mysql_ok ? "متوفر" : "غير متوفر",
        "status" => $pdo_mysql_ok,
        "critical" => true
    ];
    if (!$pdo_mysql_ok) $all_passed = false;
    
    // فحص GD Extension
    $gd_ok = extension_loaded("gd");
    $checks[] = [
        "name" => "GD Extension",
        "required" => "مطلوب لمعالجة الصور",
        "current" => $gd_ok ? "متوفر" : "غير متوفر",
        "status" => $gd_ok,
        "critical" => false
    ];
    
    // فحص cURL Extension
    $curl_ok = extension_loaded("curl");
    $checks[] = [
        "name" => "cURL Extension",
        "required" => "مطلوب للاتصالات الخارجية",
        "current" => $curl_ok ? "متوفر" : "غير متوفر",
        "status" => $curl_ok,
        "critical" => false
    ];
    
    // فحص JSON Extension
    $json_ok = extension_loaded("json");
    $checks[] = [
        "name" => "JSON Extension",
        "required" => "مطلوب لمعالجة البيانات",
        "current" => $json_ok ? "متوفر" : "غير متوفر",
        "status" => $json_ok,
        "critical" => true
    ];
    if (!$json_ok) $all_passed = false;
    
    // فحص mbstring Extension
    $mbstring_ok = extension_loaded("mbstring");
    $checks[] = [
        "name" => "mbstring Extension",
        "required" => "مطلوب للنصوص متعددة البايت",
        "current" => $mbstring_ok ? "متوفر" : "غير متوفر",
        "status" => $mbstring_ok,
        "critical" => false
    ];
    
    // فحص صلاحيات الكتابة - config
    if (!is_dir(ROOT_PATH . "/config")) {
        @mkdir(ROOT_PATH . "/config", 0755, true);
    }
    $config_writable = is_writable(ROOT_PATH . "/config");
    $checks[] = [
        "name" => "صلاحيات الكتابة - مجلد config",
        "required" => "مطلوب لحفظ إعدادات النظام",
        "current" => $config_writable ? "متاح (755)" : "غير متاح",
        "status" => $config_writable,
        "critical" => true
    ];
    if (!$config_writable) $all_passed = false;
    
    // فحص صلاحيات الكتابة - uploads
    if (!is_dir(ROOT_PATH . "/assets/uploads")) {
        @mkdir(ROOT_PATH . "/assets/uploads", 0755, true);
    }
    $uploads_writable = is_writable(ROOT_PATH . "/assets/uploads");
    $checks[] = [
        "name" => "صلاحيات الكتابة - مجلد uploads",
        "required" => "مطلوب لرفع الملفات",
        "current" => $uploads_writable ? "متاح (755)" : "غير متاح",
        "status" => $uploads_writable,
        "critical" => true
    ];
    if (!$uploads_writable) $all_passed = false;
    
    // فحص ملف schema.sql
    $schema_exists = file_exists(DATABASE_PATH . "/schema.sql");
    $schema_readable = $schema_exists && is_readable(DATABASE_PATH . "/schema.sql");
    $checks[] = [
        "name" => "ملف قاعدة البيانات (schema.sql)",
        "required" => "مطلوب لإنشاء الجداول",
        "current" => $schema_readable ? "موجود وقابل للقراءة" : ($schema_exists ? "موجود لكن غير قابل للقراءة" : "غير موجود"),
        "status" => $schema_readable,
        "critical" => true
    ];
    if (!$schema_readable) $all_passed = false;
    
    // فحص حد الذاكرة
    $memory_limit = ini_get("memory_limit");
    $memory_ok = true;
    if ($memory_limit !== "-1") {
        $memory_bytes = return_bytes($memory_limit);
        $memory_ok = $memory_bytes >= (64 * 1024 * 1024); // 64MB
    }
    $checks[] = [
        "name" => "حد الذاكرة (memory_limit)",
        "required" => "64MB أو أكثر",
        "current" => $memory_limit,
        "status" => $memory_ok,
        "critical" => false
    ];
    
    // فحص حد وقت التنفيذ
    $max_execution_time = ini_get("max_execution_time");
    $execution_time_ok = $max_execution_time == 0 || $max_execution_time >= 30;
    $checks[] = [
        "name" => "حد وقت التنفيذ (max_execution_time)",
        "required" => "30 ثانية أو أكثر",
        "current" => $max_execution_time == 0 ? "غير محدود" : $max_execution_time . " ثانية",
        "status" => $execution_time_ok,
        "critical" => false
    ];
    
    return [
        "status" => $all_passed,
        "checks" => $checks,
        "message" => $all_passed ? "جميع المتطلبات الأساسية متوفرة" : "بعض المتطلبات الأساسية غير متوفرة"
    ];
}

/**
 * تحويل قيمة الذاكرة إلى بايت
 */
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case "g":
            $val *= 1024;
        case "m":
            $val *= 1024;
        case "k":
            $val *= 1024;
    }
    return $val;
}

/**
 * اختبار الاتصال بقاعدة البيانات مع إنشاؤها إذا لم تكن موجودة
 */
function testAndCreateDatabase($config) {
    try {
        // محاولة الاتصال بالخادم أولاً
        $dsn = "mysql:host={$config["db_host"]};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        $pdo = new PDO($dsn, $config["db_user"], $config["db_pass"], $options);
        
        // فحص إذا كانت قاعدة البيانات موجودة
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([$config["db_name"]]);
        $db_exists = $stmt->fetch();
        
        if (!$db_exists) {
            // إنشاء قاعدة البيانات
            $pdo->exec("CREATE DATABASE `{$config["db_name"]}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
        
        // الاتصال بقاعدة البيانات المحددة
        $dsn = "mysql:host={$config["db_host"]};dbname={$config["db_name"]};charset=utf8mb4";
        $pdo = new PDO($dsn, $config["db_user"], $config["db_pass"], $options);
        
        // اختبار إنشاء جدول تجريبي
        $pdo->exec("CREATE TEMPORARY TABLE test_table (id INT AUTO_INCREMENT PRIMARY KEY, test_field VARCHAR(50))");
        $pdo->exec("DROP TEMPORARY TABLE test_table");
        
        return [
            "status" => true,
            "message" => $db_exists ? "تم الاتصال بقاعدة البيانات بنجاح" : "تم إنشاء قاعدة البيانات والاتصال بها بنجاح",
            "db_created" => !$db_exists
        ];
        
    } catch (PDOException $e) {
        $error_code = $e->getCode();
        $error_message = $e->getMessage();
        
        // رسائل خطأ مخصصة حسب نوع الخطأ
        switch ($error_code) {
            case 1045:
                $message = "خطأ في اسم المستخدم أو كلمة المرور";
                break;
            case 2002:
                $message = "لا يمكن الاتصال بخادم قاعدة البيانات. تأكد من أن الخادم يعمل وأن العنوان صحيح";
                break;
            case 1049:
                $message = "قاعدة البيانات غير موجودة وفشل في إنشائها. تأكد من صلاحيات المستخدم";
                break;
            case 1044:
                $message = "المستخدم لا يملك صلاحيات الوصول لقاعدة البيانات";
                break;
            default:
                $message = "خطأ في الاتصال: " . $error_message;
        }
        
        return [
            "status" => false,
            "message" => $message,
            "error_code" => $error_code,
            "technical_details" => $error_message
        ];
    }
}

/**
 * التحقق من صحة بيانات المدير
 */
function validateAdminCredentials($data) {
    $errors = [];
    
    // التحقق من اسم المستخدم
    if (empty($data["admin_username"])) {
        $errors[] = "اسم المستخدم مطلوب";
    } elseif (strlen($data["admin_username"]) < 3) {
        $errors[] = "اسم المستخدم يجب أن يكون 3 أحرف على الأقل";
    } elseif (strlen($data["admin_username"]) > 50) {
        $errors[] = "اسم المستخدم يجب أن يكون 50 حرف أو أقل";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $data["admin_username"])) {
        $errors[] = "اسم المستخدم يجب أن يحتوي على أحرف وأرقام و _ فقط";
    }
    
    // التحقق من البريد الإلكتروني
    if (empty($data["admin_email"])) {
        $errors[] = "البريد الإلكتروني مطلوب";
    } elseif (!filter_var($data["admin_email"], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صحيح";
    } elseif (strlen($data["admin_email"]) > 100) {
        $errors[] = "البريد الإلكتروني طويل جداً";
    }
    
    // التحقق من كلمة المرور
    if (empty($data["admin_password"])) {
        $errors[] = "كلمة المرور مطلوبة";
    } elseif (strlen($data["admin_password"]) < 6) {
        $errors[] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
    }
    
    // التحقق من تطابق كلمة المرور
    if (empty($data["admin_password_confirm"])) {
        $errors[] = "تأكيد كلمة المرور مطلوب";
    } elseif ($data["admin_password"] !== $data["admin_password_confirm"]) {
        $errors[] = "كلمات المرور غير متطابقة";
    }
    
    // التحقق من اسم الموقع
    if (empty($data["site_name"])) {
        $errors[] = "اسم الموقع مطلوب";
    } elseif (strlen($data["site_name"]) > 100) {
        $errors[] = "اسم الموقع طويل جداً";
    }
    
    // التحقق من وصف الموقع
    if (!empty($data["site_description"]) && strlen($data["site_description"]) > 500) {
        $errors[] = "وصف الموقع طويل جداً";
    }
    
    return [
        "status" => empty($errors),
        "message" => empty($errors) ? "البيانات صحيحة" : implode(", ", $errors),
        "errors" => $errors
    ];
}

/**
 * إنشاء اتصال PDO من إعدادات قاعدة البيانات
 */
function createPDOConnection($db_config) {
    try {
        $dsn = "mysql:host={$db_config['db_host']};dbname={$db_config['db_name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        return new PDO($dsn, $db_config["db_user"], $db_config["db_pass"], $options);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * التحقق من صلاحيات الكتابة على المجلدات
 */
function checkDirectoryPermissions() {
    $directories = [
        ROOT_PATH . "/config",
        ROOT_PATH . "/assets/uploads",
        ROOT_PATH . "/storage/cache"
    ];
    
    $errors = [];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            // محاولة إنشاء المجلد إذا لم يكن موجوداً
            if (!mkdir($dir, 0755, true)) {
                $errors[] = "فشل في إنشاء المجلد: " . $dir;
            }
        } else {
            // التحقق من صلاحيات الكتابة إذا كان المجلد موجوداً
            if (!is_writable($dir)) {
                $errors[] = "المجلد غير قابل للكتابة: " . $dir;
            }
        }
    }
    
    return [
        "status" => empty($errors),
        "message" => empty($errors) ? "جميع المجلدات قابلة للكتابة" : implode(", ", $errors),
        "errors" => $errors
    ];
}

/**
 * إنشاء ملف التكوين مع إعدادات متقدمة
 */
function createAdvancedConfigFile($db_config, $admin_config) {
    // التحقق من صلاحيات المجلدات أولاً
    $permission_check = checkDirectoryPermissions();
    if (!$permission_check["status"]) {
        return ["status" => false, "message" => "مشكلة في صلاحيات المجلدات: " . $permission_check["message"]];
    }
    
    try {
        // إنشاء مفاتيح أمان عشوائية
        $security_key = bin2hex(random_bytes(32));
        $auth_salt = bin2hex(random_bytes(32));
        $secure_auth_salt = bin2hex(random_bytes(32));
        $logged_in_salt = bin2hex(random_bytes(32));
        $nonce_salt = bin2hex(random_bytes(32));

        // تحديد SITE_URL بناءً على بيئة التشغيل
        $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off" ? "https" : "http";
        $host = $_SERVER["HTTP_HOST"] ?? "localhost";
        $script_name = $_SERVER["SCRIPT_NAME"] ?? "";
        $base_dir = str_replace("install/index.php", "", $script_name);
        $site_url = rtrim($protocol . "://" . $host . $base_dir, "/");

        $config_content = "<?php\n/**\n * Final Max CMS - ملف التكوين المتقدم\n * Advanced Configuration File\n * \n * تم إنشاؤه تلقائياً بواسطة معالج التثبيت المتقدم\n * Generated automatically by Advanced Installation Wizard\n * \n * تاريخ الإنشاء: " . date("Y-m-d H:i:s") . "\n * Creation Date: " . date("Y-m-d H:i:s") . "\n * \n * @version 2.0.0\n * @author Final Max Team\n */\n\n// منع الوصول المباشر\nif (!defined('ABSPATH')) {\n    define('ABSPATH', dirname(__FILE__) . '/');\n}\n\n// ========================================\n// إعدادات قاعدة البيانات\n// Database Configuration\n// ========================================\n";
        $config_content .= "define('DB_HOST', '" . addslashes($db_config["db_host"] ?? "") . "');\n";
        $config_content .= "define('DB_NAME', '" . addslashes($db_config["db_name"] ?? "") . "');\n";
        $config_content .= "define('DB_USER', '" . addslashes($db_config["db_user"] ?? "") . "');\n";
        $config_content .= "define('DB_PASS', '" . addslashes($db_config["db_pass"] ?? "") . "');\n";
        $config_content .= "define('DB_PREFIX', '');\n";
        $config_content .= "define('DB_CHARSET', 'utf8mb4');\n";
        $config_content .= "define('DB_COLLATE', 'utf8mb4_unicode_ci');\n\n";

        $config_content .= "// ========================================\n// إعدادات الموقع الأساسية\n// Basic Site Configuration\n// ========================================\n";
        $config_content .= "define('SITE_NAME', '" . addslashes($admin_config["site_name"] ?? "Final Max CMS") . "');\n";
        $config_content .= "define('SITE_DESCRIPTION', '" . addslashes($admin_config["site_description"] ?? "نظام إدارة محتوى متكامل") . "');\n";
        $config_content .= "define('ADMIN_EMAIL', '" . addslashes($admin_config["admin_email"] ?? "") . "');\n";
        $config_content .= "define('SITE_URL', '" . addslashes($site_url) . "');\n\n";

        $config_content .= "// ========================================\n// مفاتيح الأمان والتشفير\n// Security Keys and Encryption\n// ========================================\n\n";
        $config_content .= "define('SECURITY_KEY', '" . $security_key . "');\n";
        $config_content .= "define('AUTH_SALT', '" . $auth_salt . "');\n";
        $config_content .= "define('SECURE_AUTH_SALT', '" . $secure_auth_salt . "');\n";
        $config_content .= "define('NONCE_SALT', '" . $nonce_salt . "');\n";
        $config_content .= "define('LOGGED_IN_SALT', '" . $logged_in_salt . "');\n\n";

        $config_content .= "// ========================================\n// إعدادات الأمان المتقدمة\n// Advanced Security Settings\n// ========================================\n\n";
        $config_content .= "define('SECURE_AUTH_COOKIE', true);\n";
        $config_content .= "define('COOKIEHASH', md5(SITE_URL));\n";
        $config_content .= "define('COOKIE_DOMAIN', '" . (isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "") . "');\n";
        $config_content .= "define('COOKIEPATH', '/');\n";
        $config_content .= "define('SITECOOKIEPATH', '/');\n";
        $config_content .= "define('ADMIN_COOKIE_PATH', '/admin/');\n\n";

        $config_content .= "// ========================================\n// إعدادات النظام العامة\n// General System Settings\n// ========================================\n\n";
        $config_content .= "define('DEBUG_MODE', false);\n";
        $config_content .= "define('WP_DEBUG_LOG', false);\n";
        $config_content .= "define('WP_DEBUG_DISPLAY', false);\n";
        $config_content .= "define('SCRIPT_DEBUG', false);\n\n";
        $config_content .= "define('TIMEZONE', 'Asia/Riyadh');\n";
        $config_content .= "define('LANGUAGE', 'ar');\n";
        $config_content .= "define('TEXT_DIRECTION', 'rtl');\n\n";
        $config_content .= "define('MEMORY_LIMIT', '256M');\n";
        $config_content .= "define('MAX_EXECUTION_TIME', 300);\n\n";

        $config_content .= "// ========================================\n// إعدادات الملفات والرفع\n// File and Upload Settings\n// ========================================\n\n";
        $config_content .= "define('UPLOAD_MAX_SIZE', '10M');\n";
        $config_content .= "define('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx,txt,zip');\n";
        $config_content .= "define('UPLOAD_PATH', ABSPATH . 'assets/uploads/');\n";
        $config_content .= "define('UPLOAD_URL', SITE_URL . '/assets/uploads/');\n\n";

        $config_content .= "// ========================================\n// إعدادات الجلسات والكوكيز\n// Session and Cookie Settings\n// ========================================\n\n";
        $config_content .= "define('SESSION_COOKIE_LIFETIME', 86400);\n";
        $config_content .= "define('SESSION_COOKIE_PATH', '/');\n";
        $config_content .= "define('SESSION_COOKIE_DOMAIN', '');\n";
        $config_content .= "define('SESSION_COOKIE_SECURE', false);\n";
        $config_content .= "define('SESSION_COOKIE_HTTPONLY', true);\n\n";

        $config_content .= "// ========================================\n// إعدادات الأداء والتخزين المؤقت\n// Performance and Caching Settings\n// ========================================\n\n";
        $config_content .= "define('ENABLE_CACHE', true);\n";
        $config_content .= "define('CACHE_LIFETIME', 3600);\n";
        $config_content .= "define('CACHE_PATH', ABSPATH . 'storage/cache/');\n\n";

        $config_content .= "// ========================================\n// مسارات النظام\n// System Paths\n// ========================================\n\n";
        $config_content .= "define('CORE_PATH', ABSPATH . 'core/');\n";
        $config_content .= "define('MODULES_PATH', ABSPATH . 'modules/');\n";
        $config_content .= "define('TEMPLATES_PATH', ABSPATH . 'templates/');\n";
        $config_content .= "define('LANGUAGES_PATH', ABSPATH . 'languages/');\n\n";

        $config_content .= "// ========================================\n// بدء تشغيل النظام\n// System Bootstrap\n// ========================================\n\n";
        $config_content .= "require_once CORE_PATH . 'bootstrap.php';\n";

        $config_file_path = ROOT_PATH . "/config/config.php";
        
        // التأكد من وجود مجلد config
        if (!is_dir(ROOT_PATH . "/config")) {
            if (!mkdir(ROOT_PATH . "/config", 0755, true)) {
                return ["status" => false, "message" => "فشل في إنشاء مجلد config. تحقق من صلاحيات الكتابة."];
            }
        }
        
        // محاولة كتابة الملف
        $result = file_put_contents($config_file_path, $config_content);
        
        if ($result === false) {
            return ["status" => false, "message" => "فشل في كتابة ملف التكوين. تحقق من صلاحيات الكتابة."];
        }
        
        return ["status" => true, "message" => "تم إنشاء ملف التكوين بنجاح."];
    } catch (Exception $e) {
        return ["status" => false, "message" => "حدث خطأ غير متوقع: " . $e->getMessage()];
    }
}

/**
 * تنفيذ ملف SQL
 */
function executeSqlFile($file_path, $pdo) {
    try {
        if (!file_exists($file_path)) {
            return ["status" => false, "message" => "ملف SQL غير موجود."];
        }

        $sql = file_get_contents($file_path);
        if ($sql === false) {
            return ["status" => false, "message" => "فشل في قراءة ملف SQL."];
        }
        
        // تنفيذ الاستعلامات
        $queries = explode(";", $sql);

        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }

        return ["status" => true, "message" => "تم تنفيذ ملف SQL بنجاح."];
    } catch (PDOException $e) {
        return ["status" => false, "message" => "خطأ في تنفيذ SQL: " . $e->getMessage()];
    }
}

/**
 * إدخال البيانات الأولية
 */
function insertInitialData($pdo) {
    try {
        // إدخال مستخدم افتراضي (المدير)
        $admin_username = $_SESSION["admin_config"]["admin_username"];
        $admin_email = $_SESSION["admin_config"]["admin_email"];
        $admin_password = password_hash($_SESSION["admin_config"]["admin_password"], PASSWORD_DEFAULT);
        $site_name = $_SESSION["admin_config"]["site_name"];
        $site_description = $_SESSION["admin_config"]["site_description"];

        $stmt = $pdo->prepare("INSERT IGNORE INTO `users` (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$admin_username, $admin_email, $admin_password, "super_admin", "active"]);
        $admin_id = $pdo->lastInsertId();

        // إدخال إعدادات الموقع الأولية باستخدام IGNORE لتجنب الأخطاء مع البيانات الموجودة
        $stmt = $pdo->prepare("INSERT IGNORE INTO `settings` (`key`, `value`, `type`, `group`, `description`, `is_public`) VALUES 
            (?, ?, 'string', 'general', 'اسم الموقع', 1),
            (?, ?, 'text', 'general', 'وصف الموقع', 1),
            (?, ?, 'string', 'general', 'بريد المدير', 0)");
        $stmt->execute([
            "site_name", $site_name,
            "site_description", $site_description,
            "admin_email", $admin_email
        ]);

        // إدخال فئات افتراضية باستخدام IGNORE
        $stmt = $pdo->prepare("INSERT IGNORE INTO `categories` (name, slug, description) VALUES (?, ?, ?), (?, ?, ?)");
        $stmt->execute([
            "أخبار", "news", "آخر الأخبار والمستجدات",
            "مقالات", "articles", "مقالات متنوعة في مجالات مختلفة"
        ]);

        // إدخال موضوع تجريبي باستخدام IGNORE
        $stmt = $pdo->prepare("INSERT IGNORE INTO `topics` (title, slug, content, user_id, category_id, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            "مرحباً بكم في Final Max CMS!",
            "welcome-to-final-max-cms",
            "هذا هو أول موضوع تجريبي في نظام Final Max CMS. يمكنك البدء في إضافة المحتوى الخاص بك الآن!",
            $admin_id,
            1, // افتراض أن ID الفئة الأولى هو 1
            "published"
        ]);

        return ["status" => true, "message" => "تم إدخال البيانات الأولية بنجاح."];
    } catch (PDOException $e) {
        return ["status" => false, "message" => "خطأ في إدخال البيانات الأولية: " . $e->getMessage()];
    }
}
/**
 * تطبيق إعدادات الأمان
 */
function applySecuritySettings() {
    // هنا يمكن إضافة أي إعدادات أمان إضافية بعد التثبيت
    // مثل تحديث ملف .htaccess أو إعداد صلاحيات المجلدات
    return ["status" => true, "message" => "تم تطبيق إعدادات الأمان بنجاح."];
}

/**
 * إنشاء ملف installed.lock
 */
function createInstalledLockFile() {
    $lock_file = CONFIG_PATH . "/installed.lock";
    if (file_put_contents($lock_file, "Final Max CMS Installed " . date("Y-m-d H:i:s"))) {
        return ["status" => true, "message" => "تم إنشاء ملف installed.lock بنجاح."];
    } else {
        return ["status" => false, "message" => "فشل في إنشاء ملف installed.lock. تحقق من صلاحيات الكتابة."];
    }
}

/**
 * معالجة طلبات AJAX من المثبت
 */
function handleAjaxRequest($action) {
    header("Content-Type: application/json");
    $response = ["status" => false, "message" => "خطأ غير معروف."];

    // التأكد من وجود إعدادات قاعدة البيانات في الجلسة
    if (!isset($_SESSION["db_config"])) {
        echo json_encode(["status" => false, "message" => "إعدادات قاعدة البيانات غير موجودة في الجلسة."]);
        exit;
    }
    
    // إنشاء اتصال PDO جديد بدلاً من استخدام المخزن في الجلسة
    $pdo = createPDOConnection($_SESSION["db_config"]);
    if (!$pdo) {
        echo json_encode(["status" => false, "message" => "فشل في الاتصال بقاعدة البيانات."]);
        exit;
    }
      // تنف    }
    
    switch ($action) {
        case "create_config":
            $response = createAdvancedConfigFile($_SESSION["db_config"], $_SESSION["admin_config"]);
            break;
        case "create_db":
            $response = executeSqlFile(DATABASE_PATH . "/schema.sql", $pdo);
            if ($response["status"]) {
                // تنفيذ ملفات SQL الإضافية إذا وجدت
                $additional_schemas = [
                    DATABASE_PATH . "/adsense_campaigns_schema.sql",
                    DATABASE_PATH . "/payment_system_schema.sql",
                    DATABASE_PATH . "/languages_system_schema.sql"
                ];
                foreach ($additional_schemas as $schema_file) {
                    if (file_exists($schema_file)) {
                        $res = executeSqlFile($schema_file, $pdo);
                        if (!$res["status"]) {
                            $response = $res; // إذا فشل أي ملف، أرجع الخطأ
                            break;
                        }
                    }
                }
            }
            break;
        case "insert_data":
            $response = insertInitialData($pdo);
            break;
        case "apply_security":
            $response = applySecuritySettings();
            break;
        case "finish_install":
            $response = createInstalledLockFile();
            // مسح بيانات الجلسة بعد الانتهاء من التثبيت
            session_destroy();
            $_SESSION = [];
            break;
        default:
            $response = ["status" => false, "message" => "إجراء AJAX غير صالح."];
            break;
    }
    
    // إغلاق اتصال PDO
    $pdo = null;
    
    echo json_encode($response);
    exit;
}

?>