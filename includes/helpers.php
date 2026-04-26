<?php
/**
 * Final Max CMS - Helper Functions
 * دوال مساعدة للنظام
 */

/**
 * تنسيق الوقت النسبي (منذ كم من الوقت)
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'الآن';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return "منذ {$minutes} دقيقة" . ($minutes > 1 ? '' : '');
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return "منذ {$hours} ساعة" . ($hours > 1 ? '' : '');
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return "منذ {$days} يوم" . ($days > 1 ? '' : '');
    } elseif ($time < 31536000) {
        $months = floor($time / 2592000);
        return "منذ {$months} شهر" . ($months > 1 ? '' : '');
    } else {
        $years = floor($time / 31536000);
        return "منذ {$years} سنة" . ($years > 1 ? '' : '');
    }
}

/**
 * تنسيق الأرقام (1000 -> 1K)
 */
function format_number($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return number_format($number);
}

/**
 * تنسيق حجم الملف
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * تنظيف النص من HTML والمحارف الخاصة
 */
function clean_text($text, $length = null) {
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = trim($text);
    
    if ($length && strlen($text) > $length) {
        $text = substr($text, 0, $length) . '...';
    }
    
    return $text;
}

/**
 * إنشاء slug من النص العربي
 */
function create_slug($text) {
    // تحويل النص العربي إلى transliteration
    $arabic_to_latin = [
        'ا' => 'a', 'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'j',
        'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'th', 'ر' => 'r',
        'ز' => 'z', 'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd',
        'ط' => 't', 'ظ' => 'z', 'ع' => 'a', 'غ' => 'gh', 'ف' => 'f',
        'ق' => 'q', 'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
        'ه' => 'h', 'و' => 'w', 'ي' => 'y', 'ى' => 'a', 'ة' => 'h',
        'أ' => 'a', 'إ' => 'i', 'آ' => 'a', 'ؤ' => 'o', 'ئ' => 'e'
    ];
    
    $text = strtr($text, $arabic_to_latin);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text ?: 'post-' . time();
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من قوة كلمة المرور
 */
function validate_password($password) {
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
    
    return $errors;
}

/**
 * تشفير كلمة المرور
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * التحقق من كلمة المرور
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * إنشاء رمز عشوائي
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * إنشاء CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generate_token();
    }
    return $_SESSION['csrf_token'];
}

/**
 * التحقق من CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * تسجيل الأنشطة
 */
function logActivity($user_id, $action, $description = '', $ip_address = null) {
    global $pdo;
    
    if (!$ip_address) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $action, $description, $ip_address]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * جلب بيانات المستخدم
 */
function getUserById($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.*, up.phone_number, up.bio, up.website, up.location, 
               up.birth_date, up.gender, up.profile_views, up.social_links
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * تحديث آخر نشاط للمستخدم
 */
function updateUserLastActivity($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Failed to update user activity: " . $e->getMessage());
    }
}

/**
 * التحقق من الصلاحيات
 */
function hasPermission($user_role, $required_permission) {
    $permissions = [
        'super_admin' => ['*'],
        'admin' => [
            'manage_users', 'manage_content', 'manage_categories', 
            'manage_comments', 'view_analytics', 'manage_settings'
        ],
        'moderator' => [
            'manage_content', 'manage_comments', 'view_analytics'
        ],
        'user' => [
            'create_content', 'edit_own_content', 'comment'
        ]
    ];
    
    if (!isset($permissions[$user_role])) {
        return false;
    }
    
    return in_array('*', $permissions[$user_role]) || 
           in_array($required_permission, $permissions[$user_role]);
}

/**
 * إرسال إشعار
 */
function sendNotification($user_id, $title, $message, $type = 'info', $url = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, url) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $title, $message, $type, $url]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to send notification: " . $e->getMessage());
        return false;
    }
}

/**
 * جلب الإشعارات غير المقروءة
 */
function getUnreadNotifications($user_id, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * تحديد الإشعار كمقروء
 */
function markNotificationAsRead($notification_id, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notification_id, $user_id]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * إضافة نقاط للمستخدم
 */
function addUserPoints($user_id, $points, $action, $description = '') {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // إضافة النقاط للمستخدم
        $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->execute([$points, $user_id]);
        
        // تسجيل النقاط
        $stmt = $pdo->prepare("
            INSERT INTO points (user_id, points, type, action, description) 
            VALUES (?, ?, 'earned', ?, ?)
        ");
        $stmt->execute([$user_id, $points, $action, $description]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Failed to add user points: " . $e->getMessage());
        return false;
    }
}

/**
 * رفع الملفات
 */
function uploadFile($file, $upload_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'خطأ في رفع الملف'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'فشل في رفع الملف'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً'];
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'نوع الملف غير مدعوم'];
    }
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = uniqid() . '.' . $file_extension;
    $filepath = $upload_dir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'فشل في حفظ الملف'];
    }
}

/**
 * تغيير حجم الصورة
 */
function resizeImage($source, $destination, $max_width, $max_height, $quality = 90) {
    $image_info = getimagesize($source);
    if (!$image_info) {
        return false;
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $type = $image_info[2];
    
    // حساب الأبعاد الجديدة
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = $width * $ratio;
    $new_height = $height * $ratio;
    
    // إنشاء الصورة المصدر
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // إنشاء الصورة الجديدة
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // الحفاظ على الشفافية للـ PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }
    
    // تغيير الحجم
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // حفظ الصورة
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($new_image, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($new_image, $destination);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($new_image, $destination);
            break;
    }
    
    // تنظيف الذاكرة
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    return $result;
}

/**
 * إنشاء صورة مصغرة
 */
function createThumbnail($source, $destination, $size = 150) {
    return resizeImage($source, $destination, $size, $size);
}

/**
 * تنظيف اسم الملف
 */
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}

/**
 * التحقق من نوع الملف
 */
function getMimeType($file) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file);
    finfo_close($finfo);
    return $mime_type;
}

/**
 * إنشاء معاينة للنص
 */
function createExcerpt($text, $length = 150, $more = '...') {
    $text = strip_tags($text);
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $last_space = strrpos($text, ' ');
    
    if ($last_space !== false) {
        $text = substr($text, 0, $last_space);
    }
    
    return $text . $more;
}

/**
 * تحويل النص إلى HTML آمن
 */
function safeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * إزالة HTML الخطير
 */
function stripDangerousHtml($html) {
    $allowed_tags = '<p><br><strong><b><em><i><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
    return strip_tags($html, $allowed_tags);
}

/**
 * تحويل الروابط في النص إلى روابط قابلة للنقر
 */
function makeLinksClickable($text) {
    $pattern = '/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i';
    return preg_replace($pattern, '<a href="$0" target="_blank" rel="noopener">$0</a>', $text);
}

/**
 * تحويل الهاشتاغ إلى روابط
 */
function makeHashtagsClickable($text) {
    $pattern = '/#([a-zA-Z0-9_\x{0600}-\x{06FF}]+)/u';
    return preg_replace($pattern, '<a href="/search?tag=$1" class="hashtag">#$1</a>', $text);
}

/**
 * تحويل المنشن إلى روابط
 */
function makeMentionsClickable($text) {
    $pattern = '/@([a-zA-Z0-9_]+)/';
    return preg_replace($pattern, '<a href="/user/$1" class="mention">@$1</a>', $text);
}

/**
 * إنشاء breadcrumb
 */
function generateBreadcrumb($items) {
    $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    $count = count($items);
    foreach ($items as $index => $item) {
        if ($index === $count - 1) {
            $breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">' . safeHtml($item['title']) . '</li>';
        } else {
            $breadcrumb .= '<li class="breadcrumb-item"><a href="' . safeHtml($item['url']) . '">' . safeHtml($item['title']) . '</a></li>';
        }
    }
    
    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;
}

/**
 * إنشاء pagination
 */
function generatePagination($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<nav aria-label="ترقيم الصفحات"><ul class="pagination justify-content-center">';
    
    // رابط الصفحة السابقة
    if ($current_page > 1) {
        $prev_params = array_merge($params, ['page' => $current_page - 1]);
        $prev_url = $base_url . '?' . http_build_query($prev_params);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $prev_url . '">السابق</a></li>';
    }
    
    // أرقام الصفحات
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $page_params = array_merge($params, ['page' => $i]);
        $page_url = $base_url . '?' . http_build_query($page_params);
        $active = ($i === $current_page) ? ' active' : '';
        $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $page_url . '">' . $i . '</a></li>';
    }
    
    // رابط الصفحة التالية
    if ($current_page < $total_pages) {
        $next_params = array_merge($params, ['page' => $current_page + 1]);
        $next_url = $base_url . '?' . http_build_query($next_params);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $next_url . '">التالي</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    return $pagination;
}

/**
 * إرسال بريد إلكتروني
 */
function sendEmail($to, $subject, $message, $from = null) {
    if (!$from) {
        $from = SITE_EMAIL ?? 'noreply@' . $_SERVER['HTTP_HOST'];
    }
    
    $headers = [
        'From' => $from,
        'Reply-To' => $from,
        'Content-Type' => 'text/html; charset=UTF-8',
        'MIME-Version' => '1.0'
    ];
    
    $header_string = '';
    foreach ($headers as $key => $value) {
        $header_string .= $key . ': ' . $value . "\r\n";
    }
    
    return mail($to, $subject, $message, $header_string);
}

/**
 * إنشاء كود تفعيل
 */
function generateActivationCode() {
    return sprintf('%06d', mt_rand(100000, 999999));
}

/**
 * التحقق من انتهاء صلاحية الكود
 */
function isCodeExpired($created_at, $expiry_minutes = 15) {
    $expiry_time = strtotime($created_at) + ($expiry_minutes * 60);
    return time() > $expiry_time;
}

/**
 * تحويل التاريخ إلى التقويم الهجري
 */
function toHijriDate($date) {
    // تحويل بسيط - يمكن تحسينه باستخدام مكتبة متخصصة
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    $hijri_year = date('Y', $timestamp) - 579;
    return date('d/m/', $timestamp) . $hijri_year . ' هـ';
}

/**
 * تحويل الأرقام إلى العربية
 */
function toArabicNumbers($text) {
    $arabic_numbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english_numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    return str_replace($english_numbers, $arabic_numbers, $text);
}

/**
 * تحويل الأرقام إلى الإنجليزية
 */
function toEnglishNumbers($text) {
    $arabic_numbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english_numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    return str_replace($arabic_numbers, $english_numbers, $text);
}

/**
 * إنشاء QR Code
 */
function generateQRCode($text, $size = 200) {
    // استخدام خدمة خارجية لإنشاء QR Code
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($text);
}

/**
 * ضغط CSS
 */
function minifyCSS($css) {
    $css = preg_replace('/\/\*.*?\*\//s', '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    $css = str_replace(['; ', ' {', '{ ', ' }', '} ', ': '], [';', '{', '{', '}', '}', ':'], $css);
    return trim($css);
}

/**
 * ضغط JavaScript
 */
function minifyJS($js) {
    $js = preg_replace('/\/\*.*?\*\//s', '', $js);
    $js = preg_replace('/\/\/.*$/m', '', $js);
    $js = preg_replace('/\s+/', ' ', $js);
    return trim($js);
}

/**
 * إنشاء sitemap XML
 */
function generateSitemap($urls) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    foreach ($urls as $url) {
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
        if (isset($url['lastmod'])) {
            $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
        }
        if (isset($url['changefreq'])) {
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
        }
        if (isset($url['priority'])) {
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
        }
        $xml .= '  </url>' . "\n";
    }
    
    $xml .= '</urlset>';
    return $xml;
}

/**
 * تحديد موقع المستخدم من IP
 */
function getLocationFromIP($ip = null) {
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // استخدام خدمة مجانية للحصول على الموقع
    $response = @file_get_contents("http://ip-api.com/json/{$ip}");
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            return [
                'country' => $data['country'],
                'city' => $data['city'],
                'region' => $data['regionName'],
                'timezone' => $data['timezone']
            ];
        }
    }
    
    return null;
}

/**
 * تحويل العملة
 */
function convertCurrency($amount, $from, $to) {
    // يمكن استخدام API خارجي للحصول على أسعار الصرف
    $rates = [
        'USD' => 1,
        'EUR' => 0.85,
        'SAR' => 3.75,
        'AED' => 3.67,
        'EGP' => 30.9
    ];
    
    if (!isset($rates[$from]) || !isset($rates[$to])) {
        return false;
    }
    
    $usd_amount = $amount / $rates[$from];
    return $usd_amount * $rates[$to];
}

/**
 * إنشاء باركود
 */
function generateBarcode($text, $type = 'code128') {
    // استخدام خدمة خارجية لإنشاء الباركود
    return "https://barcode.tec-it.com/barcode.ashx?data=" . urlencode($text) . "&code=" . $type;
}

/**
 * تحسين الصورة للويب
 */
function optimizeImageForWeb($source, $destination, $quality = 85) {
    $image_info = getimagesize($source);
    if (!$image_info) {
        return false;
    }
    
    $type = $image_info[2];
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            return imagejpeg($image, $destination, $quality);
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            return imagepng($image, $destination, 9 - round(($quality / 100) * 9));
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            return imagegif($image, $destination);
        default:
            return false;
    }
}
?>

