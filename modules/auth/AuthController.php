<?php
/**
 * Final Max CMS - Auth Controller
 * متحكم المصادقة والتسجيل
 */

require_once '../core/Controller.php';
require_once '../core/Auth.php';
require_once '../core/Security.php';

class AuthController extends Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function login() {
        // إذا كان المستخدم مسجل دخول بالفعل، إعادة توجيه
        if (is_logged_in()) {
            $redirect = $_GET['redirect'] ?? '/';
            redirect($redirect);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
        } else {
            $this->showLoginForm();
        }
    }
    
    /**
     * معالجة تسجيل الدخول
     */
    private function handleLogin() {
        // التحقق من CSRF
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->setError('خطأ في التحقق من الأمان');
            $this->showLoginForm();
            return;
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // التحقق من المدخلات
        if (empty($username) || empty($password)) {
            $this->setError('يرجى إدخال اسم المستخدم وكلمة المرور');
            $this->showLoginForm();
            return;
        }
        
        // التحقق من Rate Limiting
        $ip = get_client_ip();
        if (is_rate_limited('login', $ip, 5, 300)) { // 5 محاولات كل 5 دقائق
            $this->setError('تم تجاوز عدد محاولات تسجيل الدخول المسموحة. حاول مرة أخرى بعد 5 دقائق.');
            $this->showLoginForm();
            return;
        }
        
        try {
            $db = $this->getDB();
            
            // البحث عن المستخدم
            $stmt = $db->prepare("
                SELECT id, username, email, password, role, status, login_attempts, locked_until
                FROM users 
                WHERE (username = ? OR email = ?) AND status != 'deleted'
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                record_rate_limit('login', $ip);
                $this->setError('اسم المستخدم أو كلمة المرور غير صحيحة');
                $this->showLoginForm();
                return;
            }
            
            // التحقق من حالة الحساب
            if ($user['status'] === 'banned') {
                $this->setError('تم حظر حسابك. تواصل مع الإدارة للمزيد من المعلومات.');
                $this->showLoginForm();
                return;
            }
            
            if ($user['status'] === 'inactive') {
                $this->setError('حسابك غير مفعل. تحقق من بريدك الإلكتروني لتفعيل الحساب.');
                $this->showLoginForm();
                return;
            }
            
            // التحقق من القفل المؤقت
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $unlock_time = date('H:i', strtotime($user['locked_until']));
                $this->setError("حسابك مقفل مؤقتاً حتى الساعة {$unlock_time}");
                $this->showLoginForm();
                return;
            }
            
            // التحقق من كلمة المرور
            if (!password_verify($password, $user['password'])) {
                // زيادة عدد المحاولات الفاشلة
                $attempts = $user['login_attempts'] + 1;
                $locked_until = null;
                
                if ($attempts >= 5) {
                    $locked_until = date('Y-m-d H:i:s', time() + 1800); // قفل لمدة 30 دقيقة
                }
                
                $stmt = $db->prepare("
                    UPDATE users 
                    SET login_attempts = ?, locked_until = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$attempts, $locked_until, $user['id']]);
                
                record_rate_limit('login', $ip);
                
                if ($locked_until) {
                    $this->setError('تم قفل حسابك مؤقتاً بسبب المحاولات الفاشلة المتكررة');
                } else {
                    $remaining = 5 - $attempts;
                    $this->setError("كلمة المرور غير صحيحة. تبقى {$remaining} محاولة");
                }
                
                $this->showLoginForm();
                return;
            }
            
            // تسجيل دخول ناجح
            $this->loginUser($user, $remember);
            
            // إعادة تعيين محاولات تسجيل الدخول
            $stmt = $db->prepare("
                UPDATE users 
                SET login_attempts = 0, locked_until = NULL, last_login = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            // تسجيل النشاط
            log_activity($user['id'], 'login', 'تسجيل دخول ناجح', [
                'ip' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // إعادة التوجيه
            $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '/';
            if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                $redirect = '/admin';
            }
            
            $this->setSuccess('مرحباً بك، تم تسجيل الدخول بنجاح');
            redirect($redirect);
            
        } catch (Exception $e) {
            error_log("خطأ في تسجيل الدخول: " . $e->getMessage());
            $this->setError('حدث خطأ في النظام. حاول مرة أخرى.');
            $this->showLoginForm();
        }
    }
    
    /**
     * تسجيل دخول المستخدم
     */
    private function loginUser($user, $remember = false) {
        // إنشاء جلسة جديدة
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // تذكر المستخدم
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 يوم
            
            setcookie('remember_token', $token, $expires, '/', '', true, true);
            
            // حفظ التوكن في قاعدة البيانات
            $db = $this->getDB();
            $stmt = $db->prepare("
                INSERT INTO user_tokens (user_id, token, type, expires_at) 
                VALUES (?, ?, 'remember', ?)
            ");
            $stmt->execute([$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', $expires)]);
        }
    }
    
    /**
     * عرض نموذج تسجيل الدخول
     */
    private function showLoginForm() {
        $page_title = 'تسجيل الدخول';
        include '../templates/frontend/auth/login.php';
    }
    
    /**
     * تسجيل الخروج
     */
    public function logout() {
        if (is_logged_in()) {
            // تسجيل النشاط
            log_activity($_SESSION['user_id'], 'logout', 'تسجيل خروج');
            
            // حذف توكن التذكر
            if (isset($_COOKIE['remember_token'])) {
                $db = $this->getDB();
                $stmt = $db->prepare("DELETE FROM user_tokens WHERE token = ? AND type = 'remember'");
                $stmt->execute([hash('sha256', $_COOKIE['remember_token'])]);
                
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }
        }
        
        // تدمير الجلسة
        session_destroy();
        
        $this->setSuccess('تم تسجيل الخروج بنجاح');
        redirect('/login');
    }
    
    /**
     * عرض صفحة التسجيل
     */
    public function register() {
        // التحقق من تفعيل التسجيل
        if (!get_setting('registration_enabled', true)) {
            $this->setError('التسجيل معطل حالياً');
            redirect('/');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRegister();
        } else {
            $this->showRegisterForm();
        }
    }
    
    /**
     * معالجة التسجيل
     */
    private function handleRegister() {
        // التحقق من CSRF
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->setError('خطأ في التحقق من الأمان');
            $this->showRegisterForm();
            return;
        }
        
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $terms = isset($_POST['terms']);
        
        // التحقق من المدخلات
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'اسم المستخدم مطلوب';
        } elseif (strlen($username) < 3) {
            $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام فقط';
        }
        
        if (empty($email)) {
            $errors[] = 'البريد الإلكتروني مطلوب';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد الإلكتروني غير صحيح';
        }
        
        if (empty($password)) {
            $errors[] = 'كلمة المرور مطلوبة';
        } elseif (strlen($password) < 8) {
            $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'تأكيد كلمة المرور غير متطابق';
        }
        
        if (!$terms) {
            $errors[] = 'يجب الموافقة على شروط الاستخدام';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setError($error);
            }
            $this->showRegisterForm();
            return;
        }
        
        try {
            $db = $this->getDB();
            
            // التحقق من عدم وجود المستخدم
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $this->setError('اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل');
                $this->showRegisterForm();
                return;
            }
            
            // إنشاء الحساب
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $verification_token = bin2hex(random_bytes(32));
            $status = get_setting('email_verification_required', true) ? 'pending' : 'active';
            
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, 
                                 email_verification_token, status, points, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $initial_points = get_setting('registration_points', 50);
            
            $stmt->execute([
                $username, $email, $hashed_password, $first_name, $last_name,
                $verification_token, $status, $initial_points
            ]);
            
            $user_id = $db->lastInsertId();
            
            // إضافة نقاط التسجيل
            if ($initial_points > 0) {
                $stmt = $db->prepare("
                    INSERT INTO points (user_id, points, type, action, description) 
                    VALUES (?, ?, 'earned', 'registration', 'نقاط التسجيل في الموقع')
                ");
                $stmt->execute([$user_id, $initial_points]);
            }
            
            // إرسال بريد التفعيل
            if ($status === 'pending') {
                $this->sendVerificationEmail($email, $verification_token);
                $message = 'تم إنشاء حسابك بنجاح. تحقق من بريدك الإلكتروني لتفعيل الحساب.';
            } else {
                $message = 'تم إنشاء حسابك بنجاح. يمكنك الآن تسجيل الدخول.';
            }
            
            // تسجيل النشاط
            log_activity($user_id, 'register', 'تسجيل حساب جديد', [
                'ip' => get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            $this->setSuccess($message);
            redirect('/login');
            
        } catch (Exception $e) {
            error_log("خطأ في التسجيل: " . $e->getMessage());
            $this->setError('حدث خطأ في النظام. حاول مرة أخرى.');
            $this->showRegisterForm();
        }
    }
    
    /**
     * عرض نموذج التسجيل
     */
    private function showRegisterForm() {
        $page_title = 'إنشاء حساب جديد';
        include '../templates/frontend/auth/register.php';
    }
    
    /**
     * تفعيل الحساب
     */
    public function verify($token) {
        if (empty($token)) {
            $this->setError('رمز التفعيل غير صحيح');
            redirect('/login');
            return;
        }
        
        try {
            $db = $this->getDB();
            
            $stmt = $db->prepare("
                SELECT id, username, email 
                FROM users 
                WHERE email_verification_token = ? AND status = 'pending'
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->setError('رمز التفعيل غير صحيح أو منتهي الصلاحية');
                redirect('/login');
                return;
            }
            
            // تفعيل الحساب
            $stmt = $db->prepare("
                UPDATE users 
                SET status = 'active', email_verified = 1, email_verification_token = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            // تسجيل النشاط
            log_activity($user['id'], 'email_verified', 'تفعيل البريد الإلكتروني');
            
            $this->setSuccess('تم تفعيل حسابك بنجاح. يمكنك الآن تسجيل الدخول.');
            redirect('/login');
            
        } catch (Exception $e) {
            error_log("خطأ في تفعيل الحساب: " . $e->getMessage());
            $this->setError('حدث خطأ في النظام. حاول مرة أخرى.');
            redirect('/login');
        }
    }
    
    /**
     * إرسال بريد التفعيل
     */
    private function sendVerificationEmail($email, $token) {
        $verification_url = BASE_URL . "/verify/{$token}";
        $site_name = get_setting('site_name', 'Final Max CMS');
        
        $subject = "تفعيل حسابك في {$site_name}";
        $message = "
            <h2>مرحباً بك في {$site_name}</h2>
            <p>شكراً لك على التسجيل في موقعنا. لتفعيل حسابك، يرجى النقر على الرابط التالي:</p>
            <p><a href='{$verification_url}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>تفعيل الحساب</a></p>
            <p>أو انسخ الرابط التالي في متصفحك:</p>
            <p>{$verification_url}</p>
            <p>إذا لم تقم بإنشاء هذا الحساب، يرجى تجاهل هذا البريد.</p>
        ";
        
        send_email($email, $subject, $message);
    }
}

