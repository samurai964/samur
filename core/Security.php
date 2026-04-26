<?php

/**
 * Final Max CMS - Security Class
 * فئة الأمان المتقدمة لحماية النظام
 */
class Security {
    private $pdo;
    private $prefix;
    
    public function __construct($pdo, $prefix) {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    /**
     * حماية من هجمات XSS
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * تنظيف HTML المسموح
     */
    public static function sanitizeHtml($html, $allowedTags = null) {
        if ($allowedTags === null) {
            $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
        }
        
        return strip_tags($html, $allowedTags);
    }

    /**
     * التحقق من CSRF Token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * إنشاء CSRF Token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * التحقق من قوة كلمة المرور
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على حرف كبير واحد على الأقل';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على حرف صغير واحد على الأقل';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على رمز خاص واحد على الأقل';
        }
        
        return empty($errors) ? true : $errors;
    }

    /**
     * تشفير كلمة المرور
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    /**
     * التحقق من كلمة المرور
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * حماية من Rate Limiting
     */
    public function checkRateLimit($action, $identifier, $maxAttempts = 5, $timeWindow = 300) {
        try {
            // تنظيف المحاولات القديمة
            $stmt = $this->pdo->prepare("
                DELETE FROM `{$this->prefix}rate_limits` 
                WHERE `created_at` < DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$timeWindow]);

            // عد المحاولات الحالية
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM `{$this->prefix}rate_limits` 
                WHERE `action` = ? AND `identifier` = ?
            ");
            $stmt->execute([$action, $identifier]);
            $attempts = $stmt->fetchColumn();

            if ($attempts >= $maxAttempts) {
                return false;
            }

            // إضافة محاولة جديدة
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}rate_limits` (`action`, `identifier`) 
                VALUES (?, ?)
            ");
            $stmt->execute([$action, $identifier]);

            return true;
        } catch (PDOException $e) {
            error_log("Rate limit error: " . $e->getMessage());
            return true; // السماح في حالة الخطأ
        }
    }

    /**
     * حظر IP
     */
    public function banIP($ip, $reason = '', $duration = null) {
        try {
            $expiresAt = $duration ? date('Y-m-d H:i:s', time() + $duration) : null;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}banned_ips` (`ip_address`, `reason`, `expires_at`) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE `reason` = VALUES(`reason`), `expires_at` = VALUES(`expires_at`)
            ");
            $stmt->execute([$ip, $reason, $expiresAt]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Ban IP error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * التحقق من حظر IP
     */
    public function isIPBanned($ip) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM `{$this->prefix}banned_ips` 
                WHERE `ip_address` = ? AND (`expires_at` IS NULL OR `expires_at` > NOW())
            ");
            $stmt->execute([$ip]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Check IP ban error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * حظر البريد الإلكتروني
     */
    public function banEmail($email, $reason = '') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}banned_emails` (`email`, `reason`) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE `reason` = VALUES(`reason`)
            ");
            $stmt->execute([$email, $reason]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Ban email error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * التحقق من حظر البريد الإلكتروني
     */
    public function isEmailBanned($email) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM `{$this->prefix}banned_emails` 
                WHERE `email` = ?
            ");
            $stmt->execute([$email]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Check email ban error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * تسجيل النشاط المشبوه
     */
    public function logSuspiciousActivity($userId, $action, $details, $ipAddress) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}security_logs` 
                (`user_id`, `action`, `details`, `ip_address`) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $action, $details, $ipAddress]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Security log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * التحقق من صحة البريد الإلكتروني
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * التحقق من صحة URL
     */
    public static function validateURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * تنظيف اسم الملف
     */
    public static function sanitizeFilename($filename) {
        // إزالة الأحرف الخطيرة
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $filename);
        
        // منع الملفات الخطيرة
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jar'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($extension, $dangerousExtensions)) {
            $filename .= '.txt';
        }
        
        return $filename;
    }

    /**
     * التحقق من نوع الملف المسموح
     */
    public static function isAllowedFileType($filename, $allowedTypes = null) {
        if ($allowedTypes === null) {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'rar'];
        }
        
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedTypes);
    }

    /**
     * إنشاء رمز عشوائي آمن
     */
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * تشفير البيانات الحساسة
     */
    public static function encryptData($data, $key = null) {
        if ($key === null) {
            $key = ENCRYPTION_KEY ?? 'default_encryption_key_change_me';
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }

    /**
     * فك تشفير البيانات
     */
    public static function decryptData($encryptedData, $key = null) {
        if ($key === null) {
            $key = ENCRYPTION_KEY ?? 'default_encryption_key_change_me';
        }
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * التحقق من الجلسة الآمنة
     */
    public static function validateSecureSession() {
        // التحقق من HTTPS في الإنتاج
        if (!isset($_SERVER['HTTPS']) && $_SERVER['SERVER_NAME'] !== 'localhost') {
            return false;
        }
        
        // التحقق من User Agent
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        } elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            return false;
        }
        
        // التحقق من IP (اختياري - قد يسبب مشاكل مع البروكسي)
        if (defined('STRICT_IP_CHECK') && STRICT_IP_CHECK) {
            if (!isset($_SESSION['ip_address'])) {
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
            } elseif ($_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * تنظيف الجلسات المنتهية الصلاحية
     */
    public function cleanupExpiredSessions() {
        try {
            // حذف الجلسات المنتهية الصلاحية
            $stmt = $this->pdo->prepare("
                DELETE FROM `{$this->prefix}user_sessions` 
                WHERE `expires_at` < NOW()
            ");
            $stmt->execute();

            // حذف محاولات Rate Limiting القديمة
            $stmt = $this->pdo->prepare("
                DELETE FROM `{$this->prefix}rate_limits` 
                WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute();

            // حذف الـ IPs المحظورة المنتهية الصلاحية
            $stmt = $this->pdo->prepare("
                DELETE FROM `{$this->prefix}banned_ips` 
                WHERE `expires_at` IS NOT NULL AND `expires_at` < NOW()
            ");
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            error_log("Cleanup error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * فحص الملفات المرفوعة
     */
    public static function validateUploadedFile($file, $maxSize = 5242880, $allowedTypes = null) {
        $errors = [];
        
        // التحقق من وجود الملف
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'لم يتم رفع الملف بشكل صحيح';
            return $errors;
        }
        
        // التحقق من حجم الملف
        if ($file['size'] > $maxSize) {
            $errors[] = 'حجم الملف كبير جداً. الحد الأقصى: ' . number_format($maxSize / 1024 / 1024, 2) . ' ميجابايت';
        }
        
        // التحقق من نوع الملف
        if (!self::isAllowedFileType($file['name'], $allowedTypes)) {
            $errors[] = 'نوع الملف غير مسموح';
        }
        
        // التحقق من MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip', 'application/x-rar-compressed'
        ];
        
        if ($allowedTypes === null && !in_array($mimeType, $allowedMimes)) {
            $errors[] = 'نوع الملف غير آمن';
        }
        
        return $errors;
    }

    /**
     * إنشاء رمز التحقق (Captcha) بسيط
     */
    public static function generateCaptcha() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $answer = $num1 + $num2;
        
        $_SESSION['captcha_answer'] = $answer;
        
        return [
            'question' => "كم يساوي {$num1} + {$num2}؟",
            'answer' => $answer
        ];
    }

    /**
     * التحقق من رمز التحقق
     */
    public static function validateCaptcha($userAnswer) {
        if (!isset($_SESSION['captcha_answer'])) {
            return false;
        }
        
        $isValid = (int)$userAnswer === (int)$_SESSION['captcha_answer'];
        unset($_SESSION['captcha_answer']); // استخدام واحد فقط
        
        return $isValid;
    }

    /**
     * حماية من SQL Injection (إضافية)
     */
    public static function sanitizeForSQL($input) {
        // هذه الدالة للاستخدام مع PDO prepared statements فقط
        return trim($input);
    }

    /**
     * التحقق من صحة التاريخ
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * التحقق من صحة رقم الهاتف
     */
    public static function validatePhoneNumber($phone) {
        // تنظيف الرقم
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // التحقق من الطول والتنسيق
        return preg_match('/^(\+?[1-9]\d{1,14})$/', $phone);
    }

    /**
     * إنشاء hash آمن للملفات
     */
    public static function generateFileHash($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        return hash_file('sha256', $filePath);
    }

    /**
     * التحقق من سلامة الملف
     */
    public static function verifyFileIntegrity($filePath, $expectedHash) {
        $currentHash = self::generateFileHash($filePath);
        return $currentHash && hash_equals($expectedHash, $currentHash);
    }
}

?>

