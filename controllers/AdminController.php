<?php
require_once __DIR__ . '/../modules/languages/LanguagePackageManager.php';
require_once __DIR__ . '/../core/AdminLayout.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/Flash.php';
require_once __DIR__ . '/../core/Audit.php';

class AdminController {

    private $auth;
    private $pdo;
    private $prefix;

    public function __construct() {

        $dbPath = __DIR__ . '/../database.php';

        if (!file_exists($dbPath)) {
            $dbPath = __DIR__ . '/../config/database.php';
        }

        $pdo = null;
        require $dbPath;

        // 🔥 تأكيد تحميل PDO
        if (!$pdo) {
            die("❌ Database connection failed");
        }

        $this->pdo = $pdo;
        $this->prefix = defined('DB_PREFIX') ? DB_PREFIX : '';

        $this->auth = new Auth($this->pdo, $this->prefix);

        Logger::log("Admin accessed: " . ($_SESSION['user_id'] ?? 'guest'));
    }

    /* ================= VIEW HELPER ================= */
    private function view($file) {

        $path = realpath(__DIR__ . '/../admin/' . $file);

        if (!$path) {
            die("❌ View not found: " . $file);
        }

        return $path;
    }

    /* ================= SETTINGS ================= */
    public function settingsPage() {

        // 🔐 تحقق تسجيل الدخول
        if (!isset($_SESSION['user_id'])) {
            die("❌ USER NOT LOGGED IN");
        }

        // 🔐 تحقق الأدمن
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $role = $stmt->fetchColumn();

        if ($role !== 'admin') {
            die("❌ NOT ADMIN");
        }

        require_once __DIR__ . '/../models/SettingsModel.php';

        $settingsModel = new SettingsModel();

        // 🔥 حفظ الإعدادات
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🔥 حذف csrf_token من البيانات
    $data = $_POST;
    unset($data['csrf_token']);

    $settingsModel->updateSettings($data);
}
        }

        // 🔥 جلب الإعدادات
        $settings = $settingsModel->getSettings();

if (!is_array($settings)) {
    $settings = [];
}

        $content = $this->view('settings.php');

        $layout = new AdminLayout();
        $layout->render($content, [
            'settings' => $settings
        ]);
    }

    /* ================= DASHBOARD ================= */
    public function dashboard() {

        $users = $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

        $data = [
            'users' => $users
        ];

        $content = $this->view('dashboard.php');

        $layout = new AdminLayout();
        $layout->render($content, $data);
    }

public function systemSettingsPage() {

    if (!isset($_SESSION['user_id'])) {
        die("❌ LOGIN REQUIRED");
    }

    $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        die("❌ NOT ADMIN");
    }

    $content = $this->view('system-settings.php');

    $layout = new AdminLayout();
    $layout->render($content);
}

public function emailSettingsPage() {

    if (!isset($_SESSION['user_id'])) {
        die("❌ LOGIN REQUIRED");
    }

    $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        die("❌ NOT ADMIN");
    }

    $content = $this->view('email-settings.php');

    $layout = new AdminLayout();
    $layout->render($content);
}

public function apiSettingsPage() {

    if (!isset($_SESSION['user_id'])) {
        die("❌ LOGIN REQUIRED");
    }

    $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        die("❌ NOT ADMIN");
    }

    $content = $this->view('api-settings.php');

    $layout = new AdminLayout();
    $layout->render($content);
}

public function designSettingsPage() {

    // 🔐 التحقق من تسجيل الدخول
    if (!isset($_SESSION['user_id'])) {
        die("❌ LOGIN REQUIRED");
    }

    // 🔐 التحقق من صلاحية المدير
    $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        die("❌ NOT ADMIN");
    }

    // 📄 تحميل صفحة التصميم
    $content = $this->view('design.php');

    // 🎨 عرض الصفحة داخل Layout
    $layout = new AdminLayout();
    $layout->render($content);
}

public function homeSettingsPage() {

    // 🔐 تحقق تسجيل الدخول
    if (!isset($_SESSION['user_id'])) {
        die("❌ LOGIN REQUIRED");
    }

    // 🔐 تحقق صلاحية الأدمن
    $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        die("❌ NOT ADMIN");
    }

    // 💾 حفظ الإعدادات (اختياري الآن)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($_POST as $key => $value) {
            Settings::set($key, $value);
        }
    }

    // 📄 تحميل الصفحة
    $content = $this->view('home-settings.php');

    // 🎨 عرض داخل Layout
    $layout = new AdminLayout();
    $layout->render($content);
}

public function tickerPage() {

    // 🔐 تحقق تسجيل الدخول
    if (!isset($_SESSION['user_id'])) {
        die("❌ LOGIN REQUIRED");
    }

    // 🔐 تحقق الأدمن
    $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        die("❌ NOT ADMIN");
    }

    // 📄 تحميل الصفحة
    $content = $this->view('ticker.php');

    // 🎨 عرض داخل Layout
    $layout = new AdminLayout();
    $layout->render($content);
}

public function languagesPage() {

    if (!isset($_SESSION['user_id'])) {
        die("LOGIN REQUIRED");
    }

    global $pdo;

    // ✔ تحميل الكلاسات هنا (وليس في view)
    require_once __DIR__ . '/../core/LanguageEngine.php';
    require_once __DIR__ . '/../modules/languages/LanguagePackageManager.php';

    $language_engine = new LanguageEngine($pdo);
    $package_manager = new LanguagePackageManager($pdo);

    // ✔ تجهيز البيانات
    $active_languages = $language_engine->getActiveLanguages();

    // ✔ تمرير للـ view
    $content = __DIR__ . '/../admin/languages.php';

    $layout = new AdminLayout();
    $layout->render($content);
}

public function changeLanguage() {

    $lang = $_GET['lang'] ?? null;

    if (!$lang) {
        header("Location: /admin");
        exit;
    }

    global $language_engine;

    try {
        $language_engine->setLanguage($lang);
    } catch (Exception $e) {
        // تجاهل الخطأ
    }

    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/admin'));
}

public function installLanguage() {

    global $pdo;

    $manager = new LanguagePackageManager($pdo);

    $package = $_POST['package_name'] ?? null;

    if (!$package) {
        die("No package selected");
    }

    $result = $manager->installPackage($package);

    if ($result['success']) {
        echo "✅ Installed successfully";
    } else {
        echo "❌ " . $result['message'];
    }
}

public function languagePackages() {
    $manager = new LanguagePackageManager($this->pdo);
    $packages = $manager->searchAvailablePackages();

    $content = __DIR__ . '/../admin/language-packages.php';
    (new AdminLayout())->render($content);
}

public function uploadLanguagePage() {
    $content = __DIR__ . '/../admin/upload-language.php';
    (new AdminLayout())->render($content);
}

public function languagesList() {
    $stmt = $this->pdo->query("SELECT * FROM fmc_languages");
    $languages = $stmt->fetchAll();

    $content = __DIR__ . '/../admin/languages-list.php';
    (new AdminLayout())->render($content);
}

public function activateTheme() {

    $theme = $_POST['theme'];

    Settings::set('active_theme', $theme);

    header("Location: /admin/themes");
}

public function deleteTheme() {

    $theme = $_POST['theme'];

    $path = ROOT_PATH . '/themes/' . $theme;

    if (is_dir($path)) {
        // حذف آمن
        array_map('unlink', glob("$path/*.*"));
        rmdir($path);
    }

    header("Location: /admin/themes");
}

public function uploadTheme() {

    $file = $_FILES['theme_zip'];

    $zip = new ZipArchive;
    $tmp = $file['tmp_name'];

    if ($zip->open($tmp) === TRUE) {

        $extractPath = ROOT_PATH . '/themes/';
        $zip->extractTo($extractPath);
        $zip->close();
    }

    header("Location: /admin/themes");
}

public function saveThemeSettings() {

    $data = json_decode(file_get_contents("php://input"), true);

    $customizer = new ThemeCustomizer($GLOBALS['pdo']);

    foreach ($data as $key => $value) {
        $customizer->set($key, $value);
    }

    echo json_encode(['status' => 'ok']);
}


}
?>
