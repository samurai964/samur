<?php

// وظائف عامة

/**
 * تنظيف المدخلات من المستخدم
 * @param string $data
 * @return string
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * إعادة توجيه المستخدم إلى صفحة معينة
 * @param string $location
 */
function redirect($location) {
    header("Location: " . $location);
    exit();
}

/**
 * عرض رسائل النظام (نجاح/خطأ)
 */
function display_message() {
    if (isset($_SESSION["message"])) {
        $message_type = $_SESSION["message_type"];
        $message_text = $_SESSION["message"];
        echo "<div class=\"alert alert-" . $message_type . "\">" . $message_text . "</div>";
        unset($_SESSION["message"]);
        unset($_SESSION["message_type"]);
    }
}

/**
 * تعيين رسالة للنظام
 * @param string $type (success|danger)
 * @param string $text
 */
function set_message($type, $text) {
    $_SESSION["message_type"] = $type;
    $_SESSION["message"] = $text;
}

/**
 * التحقق مما إذا كان المستخدم مسجلاً للدخول
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION["user_id"]);
}

/**
 * التحقق مما إذا كان المستخدم مسؤولاً
 * @return bool
 */
function is_admin() {
    return (is_logged_in() && isset($_SESSION["role"]) && $_SESSION["role"] === "admin");
}

/**
 * الحصول على بيانات المستخدم الحالي
 * @param string $key
 * @return mixed|null
 */
function get_user_data($key) {
    if (isset($_SESSION[$key])) {
        return $_SESSION[$key];
    }
    return null;
}

/**
 * بناء رابط URL كامل
 * @param string $path
 * @return string
 */
function url($path) {
    return SITE_URL . $path;
}

// وظائف المستخدمين

/**
 * إنشاء مستخدم جديد
 * @param string $username
 * @param string $email
 * @param string $password_hash
 * @return bool
 */
function create_user($username, $email, $password_hash) {
    global $db;
    $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, $email, $password_hash, 'user']);
}

/**
 * الحصول على مستخدم بواسطة اسم المستخدم
 * @param string $username
 * @return array|false
 */
function get_user_by_username($username) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * الحصول على مستخدم بواسطة البريد الإلكتروني
 * @param string $email
 * @return array|false
 */
function get_user_by_email($email) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * الحصول على مستخدم بواسطة ID
 * @param int $user_id
 * @return array|false
 */
function get_user_data_by_id($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * تحديث بيانات المستخدم
 * @param int $user_id
 * @param array $data
 * @return bool
 */
function update_user_data($user_id, $data) {
    global $db;
    $set_clause = [];
    $params = [];
    foreach ($data as $key => $value) {
        $set_clause[] = "`{$key}` = ?";
        $params[] = $value;
    }
    $params[] = $user_id;
    $sql = "UPDATE users SET " . implode(', ', $set_clause) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

/**
 * الحصول على عدد مشاركات المستخدم
 * @param int $user_id
 * @return int
 */
function get_user_posts_count($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * الحصول على عدد تعليقات المستخدم
 * @param int $user_id
 * @return int
 */
function get_user_comments_count($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * الحصول على نقاط المستخدم
 * @param int $user_id
 * @return int
 */
function get_user_points($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * الحصول على آخر أنشطة المستخدم
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function get_user_recent_activities($user_id, $limit = 5) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * تحويل التاريخ إلى صيغة "منذ كذا وقت"
 * @param string $datetime
 * @return string
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'سنة',
        'm' => 'شهر',
        'w' => 'أسبوع',
        'd' => 'يوم',
        'h' => 'ساعة',
        'i' => 'دقيقة',
        's' => 'ثانية',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' مضت' : 'الآن';
}

/**
 * الحصول على صورة الأفاتار للمستخدم
 * @return string
 */
function get_user_avatar() {
    $avatar = get_user_data('avatar');
    if ($avatar && file_exists(__DIR__ . '/../assets/uploads/avatars/' . $avatar)) {
        return url('assets/uploads/avatars/' . $avatar);
    }
    return url('assets/images/default-avatar.png');
}

// وظائف Portfolio

/**
 * إضافة عمل جديد إلى Portfolio
 * @param int $user_id
 * @param string $title
 * @param string $description
 * @param string $image
 * @param string $url
 * @return bool
 */
function add_portfolio_item($user_id, $title, $description, $image, $url = null) {
    global $db;
    $stmt = $db->prepare("INSERT INTO portfolio_items (user_id, title, description, image, url) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $description, $image, $url]);
}

/**
 * الحصول على أعمال المستخدم
 * @param int $user_id
 * @return array
 */
function get_user_portfolio_items($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM portfolio_items WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على عمل Portfolio بواسطة ID
 * @param int $item_id
 * @return array|false
 */
function get_portfolio_item_by_id($item_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM portfolio_items WHERE id = ?");
    $stmt->execute([$item_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * تحديث عمل Portfolio
 * @param int $item_id
 * @param array $data
 * @return bool
 */
function update_portfolio_item($item_id, $data) {
    global $db;
    $set_clause = [];
    $params = [];
    foreach ($data as $key => $value) {
        $set_clause[] = "`{$key}` = ?";
        $params[] = $value;
    }
    $params[] = $item_id;
    $sql = "UPDATE portfolio_items SET " . implode(', ', $set_clause) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

/**
 * حذف عمل Portfolio
 * @param int $item_id
 * @return bool
 */
function delete_portfolio_item($item_id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM portfolio_items WHERE id = ?");
    return $stmt->execute([$item_id]);
}

// وظائف الإدارة (Admin)

/**
 * الحصول على جميع المستخدمين
 * @return array
 */
function get_all_users() {
    global $db;
    $stmt = $db->query("SELECT * FROM users");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * حظر مستخدم
 * @param int $user_id
 * @return bool
 */
function ban_user($user_id) {
    global $db;
    $stmt = $db->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
    return $stmt->execute([$user_id]);
}

/**
 * إلغاء حظر مستخدم
 * @param int $user_id
 * @return bool
 */
function unban_user($user_id) {
    global $db;
    $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    return $stmt->execute([$user_id]);
}

/**
 * الحصول على جميع الفئات
 * @return array
 */
function get_all_categories() {
    global $db;
    $stmt = $db->query("SELECT * FROM categories");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * إضافة فئة جديدة
 * @param string $name
 * @param string $description
 * @return bool
 */
function add_category($name, $description) {
    global $db;
    $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    return $stmt->execute([$name, $description]);
}

/**
 * تحديث فئة
 * @param int $category_id
 * @param string $name
 * @param string $description
 * @return bool
 */
function update_category($category_id, $name, $description) {
    global $db;
    $stmt = $db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
    return $stmt->execute([$name, $description, $category_id]);
}

/**
 * حذف فئة
 * @param int $category_id
 * @return bool
 */
function delete_category($category_id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    return $stmt->execute([$category_id]);
}

/**
 * الحصول على جميع المشاركات
 * @return array
 */
function get_all_posts() {
    global $db;
    $stmt = $db->query("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على جميع التعليقات
 * @return array
 */
function get_all_comments() {
    global $db;
    $stmt = $db->query("SELECT c.*, u.username, p.title as post_title FROM comments c JOIN users u ON c.user_id = u.id JOIN posts p ON c.post_id = p.id ORDER BY c.created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إعدادات النظام
 * @return array|false
 */
function get_settings() {
    global $db;
    $stmt = $db->query("SELECT * FROM settings WHERE id = 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * تحديث إعدادات النظام
 * @param array $data
 * @return bool
 */
function update_settings($data) {
    global $db;
    $set_clause = [];
    $params = [];
    foreach ($data as $key => $value) {
        $set_clause[] = "`{$key}` = ?";
        $params[] = $value;
    }
    $sql = "UPDATE settings SET " . implode(', ', $set_clause) . " WHERE id = 1";
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

// وظائف API

/**
 * معالجة طلبات API
 * @param string $method
 * @param string $endpoint
 * @param array $data
 * @return array
 */
function handle_api_request($method, $endpoint, $data) {
    // هذه دالة وهمية. يجب استبدالها بمنطق API حقيقي.
    // بناءً على $endpoint و $method، قم بمعالجة الطلب وإرجاع استجابة JSON.
    return [
        'success' => true,
        'message' => 'API request processed successfully',
        'data' => [
            'method' => $method,
            'endpoint' => $endpoint,
            'received_data' => $data
        ]
    ];
}

?>



/**
 * الحصول على اسم الفئة بواسطة ID
 * @param int $category_id
 * @return string|null
 */
function get_category_name_by_id($category_id) {
    global $db;
    $stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['name'] : null;
}


