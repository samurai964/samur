<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// تعريف المسارات الأساسية
define("INSTALL_PATH", dirname(__FILE__));
define("ROOT_PATH", dirname(INSTALL_PATH));
define("CONFIG_PATH", ROOT_PATH . "/config");
define("DATABASE_PATH", ROOT_PATH . "/database");

// حساب عنوان URL الأساسي للنظام
$base_url = sprintf(
    "%s://%s%s",
    isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off" ? "https" : "http",
    $_SERVER["SERVER_NAME"],
    rtrim(str_replace("install", "", dirname($_SERVER["SCRIPT_NAME"])), "/")
);

// فحص إذا كان النظام مثبت مسبقاً
if (file_exists(CONFIG_PATH . "/installed.lock")) {
    $admin_url = $base_url . "/admin";
    die('<div style="text-align:center;margin-top:100px;font-family:Arial;"><h2>النظام مثبت مسبقاً</h2><p>إذا كنت تريد إعادة التثبيت، احذف ملف config/installed.lock</p><p><a href="' . $base_url . '">الذهاب للموقع</a> | <a href="' . $admin_url . '">الذهاب للوحة التحكم</a></p></div>');
}

// تحميل دوال المثبت المتقدمة
require_once ROOT_PATH . "/core/init.php";
require_once INSTALL_PATH . "/installer_functions.php";

// الحصول على الخطوة الحالية
$step = isset($_GET["step"]) ? (int)$_GET["step"] : 1;
$error_message = '';
$success_message = '';

// معالجة البيانات المرسلة
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    switch ($step) {
        case 1:
            // الانتقال إلى الخطوة الثانية
            $_SESSION["install_started"] = true;
            header("Location: ?step=2");
            exit;
            break;
            
        case 2:
            // فحص المتطلبات باستخدام الدالة المتقدمة
            $requirements_check = checkSystemRequirements();
            if ($requirements_check["status"]) {
                $_SESSION["requirements_passed"] = true;
                header("Location: ?step=3");
                exit;
            } else {
                $error_message = $requirements_check["message"];
            }
            break;
            
        case 3:
            // اختبار قاعدة البيانات باستخدام الدالة المتقدمة
            $db_test = testAndCreateDatabase($_POST);
            if ($db_test["status"]) {
                $_SESSION["db_config"] = $_POST;
                header("Location: ?step=4");
                exit;
            } else {
                $error_message = $db_test["message"];
            }
            break;
            
        case 4:
            // التحقق من بيانات المدير باستخدام الدالة المتقدمة
            $admin_validation = validateAdminCredentials($_POST);
            if ($admin_validation["status"]) {
                $_SESSION["admin_config"] = $_POST;
                header("Location: ?step=5");
                exit;
            } else {
                $error_message = $admin_validation["message"];
            }
            break;
    }
}

// معالجة طلبات AJAX للتثبيت
if (isset($_GET["ajax"])) {
    handleAjaxRequest($_GET["ajax"]);
}

// إنشاء مجلد steps إذا لم يكن موجوداً
if (!is_dir(INSTALL_PATH . "/steps")) {
    mkdir(INSTALL_PATH . "/steps", 0755, true);
}

// إنشاء ملفات الخطوات إذا لم تكن موجودة
createStepFilesIfNotExist();

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Max CMS - معالج التثبيت التلقائي</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        .installer-container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .installer-header {
            background: #2c3e50;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .installer-header h1 {
            margin-bottom: 10px;
        }
        .steps {
            display: flex;
            justify-content: center;
            background: #34495e;
            padding: 15px;
            flex-wrap: wrap;
        }
        .step {
            margin: 0 10px;
            color: #ddd;
            font-weight: bold;
        }
        .step.active {
            color: #fff;
            border-bottom: 2px solid #3498db;
        }
        .installer-content {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            background: #3498db;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-secondary {
            background: #7f8c8d;
        }
        .btn-secondary:hover {
            background: #95a5a6;
        }
        .btn-large {
            padding: 15px 30px;
            margin: 10px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #66bb6a;
        }
        .progress-container {
            margin: 30px 0;
            background: #ecf0f1;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-bar {
            height: 30px;
            background: #3498db;
            text-align: center;
            line-height: 30px;
            color: white;
            transition: width 0.3s;
            width: 0%;
        }
        .text-center {
            text-align: center;
        }
        .features ul {
            list-style: none;
            padding: 0;
        }
        .features li {
            padding: 5px 0;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>Final Max CMS</h1>
            <p>معالج التثبيت التلقائي</p>
        </div>
        
        <div class="steps">
            <div class="step <?= $step >= 1 ? 'active' : '' ?>">1. الترحيب</div>
            <div class="step <?= $step >= 2 ? 'active' : '' ?>">2. المتطلبات</div>
            <div class="step <?= $step >= 3 ? 'active' : '' ?>">3. قاعدة البيانات</div>
            <div class="step <?= $step >= 4 ? 'active' : '' ?>">4. المدير</div>
            <div class="step <?= $step >= 5 ? 'active' : '' ?>">5. التثبيت</div>
            <div class="step <?= $step >= 6 ? 'active' : '' ?>">6. الانتهاء</div>
        </div>
        
        <div class="installer-content">
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($step == 6): ?>
                <h2>🎉 تم التثبيت بنجاح!</h2>
                <div class="alert alert-success">تهانينا! تم تثبيت Final Max CMS بنجاح.</div>
                <div class="text-center">
                    <a href="<?php echo $base_url; ?>/" class="btn btn-large">الذهاب إلى الموقع</a>
                    <a href="<?php echo $base_url; ?>/admin" class="btn btn-large btn-secondary">الذهاب إلى لوحة التحكم</a>
                </div>
            <?php else: ?>
                <form id="installer_form" method="post" action="?step=<?php echo $step; ?>">
                    <?php
                    switch ($step) {
                        case 1:
                            if (file_exists(INSTALL_PATH . '/steps/welcome.php')) {
                                include INSTALL_PATH . '/steps/welcome.php';
                            } else {
                                echo '<div class="text-center">
                                    <h2>مرحباً بك في Final Max CMS</h2>
                                    <p>شكراً لاختيارك نظام Final Max CMS لإدارة محتوى موقعك.</p>
                                    <p>سيقوم معالج التثبيت بمساعدتك في إعداد النظام خلال بضع خطوات بسيطة.</p>
                                    <button type="submit" class="btn btn-large">بدء التثبيت</button>
                                </div>';
                            }
                            break;
                        case 2:
                            if (file_exists(INSTALL_PATH . '/steps/requirements.php')) {
                                include INSTALL_PATH . '/steps/requirements.php';
                            } else {
                                echo '<h2>فحص متطلبات النظام</h2>';
                                $requirements = checkSystemRequirements();
                                echo '<div class="alert ' . ($requirements['status'] ? 'alert-success' : 'alert-error') . '">';
                                echo $requirements['message'];
                                echo '</div>';
                                if ($requirements['status']) {
                                    echo '<button type="submit" class="btn">المتابعة إلى إعداد قاعدة البيانات</button>';
                                } else {
                                    echo '<button type="button" class="btn" onclick="window.location.reload()">إعادة الفحص</button>';
                                }
                            }
                            break;
                        case 3:
                            if (file_exists(INSTALL_PATH . '/steps/database.php')) {
                                include INSTALL_PATH . '/steps/database.php';
                            } else {
                                echo '<h2>إعدادات قاعدة البيانات</h2>
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
                            }
                            break;
                        case 4:
                            if (file_exists(INSTALL_PATH . '/steps/admin.php')) {
                                include INSTALL_PATH . '/steps/admin.php';
                            } else {
                                echo '<h2>إنشاء حساب المدير</h2>
                                <div class="form-group">
                                    <label for="admin_username">اسم المستخدم</label>
                                    <input type="text" id="admin_username" name="admin_username" required>
                                </div>
                                <div class="form-group">
                                    <label for="admin_email">البريد الإلكتروني</label>
                                    <input type="email" id="admin_email" name="admin_email" required>
                                </div>
                                <div class="form-group">
                                    <label for="admin_password">كلمة المرور</label>
                                    <input type="password" id="admin_password" name="admin_password" required>
                                </div>
                                <div class="form-group">
                                    <label for="admin_password_confirm">تأكيد كلمة المرور</label>
                                    <input type="password" id="admin_password_confirm" name="admin_password_confirm" required>
                                </div>
                                <h3>إعدادات الموقع</h3>
                                <div class="form-group">
                                    <label for="site_name">اسم الموقع</label>
                                    <input type="text" id="site_name" name="site_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="site_description">وصف الموقع</label>
                                    <textarea id="site_description" name="site_description" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn">حفظ والمتابعة إلى التثبيت</button>';
                            }
                            break;
                        case 5:
                            if (file_exists(INSTALL_PATH . '/steps/installation.php')) {
                                include INSTALL_PATH . '/steps/installation.php';
                            } else {
                                echo '<h2>تثبيت النظام</h2>
                                <p>جاري تثبيت Final Max CMS على خادمك. هذه العملية قد تستغرق بضع دقائق.</p>
                                <div class="progress-container">
                                    <div id="progress-bar" class="progress-bar">0%</div>
                                </div>
                                <div id="status-message" style="text-align: center; margin: 20px 0; font-weight: bold;">
                                    جاري التحضير للتثبيت...
                                </div>';
                            }
                            break;
                    }
                    ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // معالجة التثبيت بالجافاسكريبت للخطوة 5
        <?php if ($step == 5): ?>
            function performInstallation() {
                const progressBar = document.getElementById('progress-bar');
                const statusMessage = document.getElementById('status-message');
                
                const steps = [
                    {action: 'create_config', name: 'إنشاء ملف التكوين'},
                    {action: 'create_db', name: 'إنشاء الجداول'},
                    {action: 'insert_data', name: 'إدخال البيانات الأولية'},
                    {action: 'apply_security', name: 'تطبيق إعدادات الأمان'},
                    {action: 'finish_install', name: 'اللمسات النهائية'}
                ];
                
                let currentStep = 0;
                
                function executeStep() {
                    if (currentStep >= steps.length) {
                        statusMessage.innerHTML = '✅ التثبيت مكتمل!';
                        setTimeout(() => {
                            window.location.href = '?step=6';
                        }, 2000);
                        return;
                    }
                    
                    statusMessage.innerHTML = `جاري ${steps[currentStep].name}...`;
                    
                    fetch(`?ajax=${steps[currentStep].action}`)
    .then(response => response.json())
    .then(data => {
                            if (data.status) {
            currentStep++;
            const progress = (currentStep / steps.length) * 100;
            progressBar.style.width = `${progress}%`;
            progressBar.innerHTML = `${Math.round(progress)}%`;
            setTimeout(executeStep, 500);
        } else {
            statusMessage.innerHTML = `❌ خطأ في ${steps[currentStep].name}: ${data.message}`;
            // إضافة زر لإعادة المحاولة
            const retryButton = document.createElement('button');
            retryButton.textContent = 'إعادة المحاولة';
            retryButton.className = 'btn';
            retryButton.style.marginTop = '10px';
            retryButton.onclick = () => {
                statusMessage.innerHTML = 'جاري إعادة المحاولة...';
                setTimeout(executeStep, 1000);
            };
            statusMessage.appendChild(document.createElement('br'));
            statusMessage.appendChild(retryButton);
        }
    })
    .catch(error => {
        statusMessage.innerHTML = '❌ فشل في الاتصال بالخادم: ' + error.message;
    });
                }
                
                executeStep();
            }
            
            // بدء التثبيت تلقائياً عند تحميل الصفحة
            setTimeout(performInstallation, 1000);
        <?php endif; ?>
    });
    </script>
</body>
</html>