<?php
/**
 * ملف مساعد لنظام الحملات الإعلانية
 * يوفر دوال سهلة الاستخدام لعرض الإعلانات في جميع أنحاء الموقع
 */

require_once __DIR__ . '/../modules/ad_campaigns/AdDisplayManager.php';

// متغير عام لمدير العرض
global $ad_display_manager;
$ad_display_manager = null;

/**
 * تهيئة مدير عرض الإعلانات
 */
function initAdDisplayManager() {
    global $ad_display_manager, $database;
    
    if ($ad_display_manager === null && isset($database)) {
        try {
            $ad_display_manager = new AdDisplayManager($database->getConnection());
        } catch (Exception $e) {
            error_log("خطأ في تهيئة مدير عرض الإعلانات: " . $e->getMessage());
            $ad_display_manager = false;
        }
    }
    
    return $ad_display_manager;
}

/**
 * عرض إعلانات في الصفحة الرئيسية
 * @param int $limit عدد الإعلانات
 * @return string HTML للإعلانات
 */
function display_homepage_ads($limit = 3) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displayAds('homepage', [
        'limit' => $limit,
        'style' => 'grid',
        'class' => 'homepage-ads'
    ]);
}

/**
 * عرض إعلانات في الشريط الجانبي
 * @param int $limit عدد الإعلانات
 * @return string HTML للإعلانات
 */
function display_sidebar_ads($limit = 3) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displaySidebarAds('sidebar', [
        'limit' => $limit,
        'class' => 'sidebar-ads'
    ]);
}

/**
 * عرض شريط إعلانات في الهيدر
 * @return string HTML لشريط الإعلانات
 */
function display_header_banner() {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displayAdBanner('header', [
        'class' => 'header-banner-ad'
    ]);
}

/**
 * عرض شريط إعلانات في الفوتر
 * @return string HTML لشريط الإعلانات
 */
function display_footer_banner() {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displayAdBanner('footer', [
        'class' => 'footer-banner-ad'
    ]);
}

/**
 * عرض إعلانات في صفحة تفاصيل الموضوع
 * @param int $limit عدد الإعلانات
 * @return string HTML للإعلانات
 */
function display_topic_detail_ads($limit = 2) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displayAds('topic_detail', [
        'limit' => $limit,
        'style' => 'inline',
        'class' => 'topic-detail-ads'
    ]);
}

/**
 * عرض إعلانات في صفحة الفئات
 * @param int $limit عدد الإعلانات
 * @return string HTML للإعلانات
 */
function display_category_ads($limit = 2) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displayAds('category', [
        'limit' => $limit,
        'style' => 'grid',
        'class' => 'category-ads'
    ]);
}

/**
 * عرض إعلانات في نتائج البحث
 * @param int $limit عدد الإعلانات
 * @return string HTML للإعلانات
 */
function display_search_results_ads($limit = 2) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displayAds('search_results', [
        'limit' => $limit,
        'style' => 'list',
        'class' => 'search-results-ads'
    ]);
}

/**
 * عرض إعلانات في صفحة البروفايل
 * @param int $limit عدد الإعلانات
 * @return string HTML للإعلانات
 */
function display_profile_ads($limit = 2) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displayAds('profile', [
        'limit' => $limit,
        'style' => 'sidebar',
        'class' => 'profile-ads'
    ]);
}

/**
 * عرض إعلانات في صفحة الإعلانات المبوبة
 * @param int $limit عدد الإعلانات
 * @return string HTML للإعلانات
 */
function display_classified_ads_ads($limit = 3) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displayAds('classified_ads', [
        'limit' => $limit,
        'style' => 'grid',
        'class' => 'classified-ads-ads'
    ]);
}

/**
 * عرض إعلانات في دليل المواقع
 * @param int $limit عدد الإعلانات
 * @return string HTML للإعلانات
 */
function display_directory_ads($limit = 2) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displayAds('directory', [
        'limit' => $limit,
        'style' => 'list',
        'class' => 'directory-ads'
    ]);
}

/**
 * عرض إعلانات في صفحة الدورات
 * @param int $limit عدد الإعلانات
 * @return string HTML للإعلانات
 */
function display_courses_ads($limit = 2) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displayAds('courses', [
        'limit' => $limit,
        'style' => 'grid',
        'class' => 'courses-ads'
    ]);
}

/**
 * إدراج إعلانات مدمجة في المحتوى
 * @param string $content المحتوى الأصلي
 * @param string $placement موقع العرض
 * @return string المحتوى مع الإعلانات المدمجة
 */
function inject_inline_ads($content, $placement = 'inline') {
    $manager = initAdDisplayManager();
    if (!$manager) return $content;
    
    return $manager->injectInlineAds($content, $placement);
}

/**
 * عرض إعلان واحد فقط
 * @param string $placement موقع العرض
 * @param array $options خيارات إضافية
 * @return string HTML للإعلان
 */
function display_single_ad($placement, $options = []) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    return $manager->displaySingleAd($placement, $options);
}

/**
 * عرض إعلانات متجاوبة حسب حجم الشاشة
 * @param string $placement موقع العرض
 * @param array $responsive_limits حدود مختلفة للشاشات
 * @return string HTML للإعلانات
 */
function display_responsive_ads($placement, $responsive_limits = []) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    // تحديد حد العرض حسب حجم الشاشة (يمكن تطويره أكثر)
    $default_limits = [
        'mobile' => 1,
        'tablet' => 2,
        'desktop' => 3
    ];
    
    $limits = array_merge($default_limits, $responsive_limits);
    
    // استخدام JavaScript لتحديد حجم الشاشة (مبسط)
    $html = '<div class="responsive-ads-container" data-placement="' . $placement . '">';
    
    // عرض للشاشات الكبيرة
    $html .= '<div class="ads-desktop hidden-mobile hidden-tablet">';
    $html .= $manager->displayAds($placement, ['limit' => $limits['desktop']]);
    $html .= '</div>';
    
    // عرض للتابلت
    $html .= '<div class="ads-tablet hidden-mobile hidden-desktop">';
    $html .= $manager->displayAds($placement, ['limit' => $limits['tablet']]);
    $html .= '</div>';
    
    // عرض للموبايل
    $html .= '<div class="ads-mobile hidden-tablet hidden-desktop">';
    $html .= $manager->displayAds($placement, ['limit' => $limits['mobile']]);
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * عرض إعلانات مع تأخير زمني
 * @param string $placement موقع العرض
 * @param int $delay التأخير بالثواني
 * @param array $options خيارات إضافية
 * @return string HTML للإعلانات مع JavaScript للتأخير
 */
function display_delayed_ads($placement, $delay = 3, $options = []) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    $ads_html = $manager->displayAds($placement, $options);
    $container_id = 'delayed-ads-' . uniqid();
    
    $html = '<div id="' . $container_id . '" style="display: none;">';
    $html .= $ads_html;
    $html .= '</div>';
    
    $html .= '<script>';
    $html .= 'setTimeout(function() {';
    $html .= '  document.getElementById("' . $container_id . '").style.display = "block";';
    $html .= '}, ' . ($delay * 1000) . ');';
    $html .= '</script>';
    
    return $html;
}

/**
 * عرض إعلانات مع إمكانية الإغلاق
 * @param string $placement موقع العرض
 * @param array $options خيارات إضافية
 * @return string HTML للإعلانات مع زر الإغلاق
 */
function display_closeable_ads($placement, $options = []) {
    $manager = initAdDisplayManager();
    if (!$manager) return '';
    
    $ads_html = $manager->displayAds($placement, $options);
    $container_id = 'closeable-ads-' . uniqid();
    
    $html = '<div id="' . $container_id . '" class="closeable-ads-container">';
    $html .= '<button class="close-ads-btn" onclick="document.getElementById(\'' . $container_id . '\').style.display=\'none\'">';
    $html .= '<i class="fas fa-times"></i>';
    $html .= '</button>';
    $html .= $ads_html;
    $html .= '</div>';
    
    // إضافة CSS للزر
    $html .= '<style>';
    $html .= '.closeable-ads-container { position: relative; }';
    $html .= '.close-ads-btn {';
    $html .= '  position: absolute;';
    $html .= '  top: 5px;';
    $html .= '  right: 5px;';
    $html .= '  background: rgba(0,0,0,0.5);';
    $html .= '  color: white;';
    $html .= '  border: none;';
    $html .= '  border-radius: 50%;';
    $html .= '  width: 25px;';
    $html .= '  height: 25px;';
    $html .= '  cursor: pointer;';
    $html .= '  z-index: 1000;';
    $html .= '}';
    $html .= '.close-ads-btn:hover { background: rgba(0,0,0,0.7); }';
    $html .= '</style>';
    
    return $html;
}

/**
 * جلب إعلانات عبر AJAX
 * @param string $placement موقع العرض
 * @param int $limit عدد الإعلانات
 * @return string JavaScript لجلب الإعلانات
 */
function load_ads_via_ajax($placement, $limit = 3) {
    $container_id = 'ajax-ads-' . uniqid();
    
    $html = '<div id="' . $container_id . '" class="ajax-ads-container">';
    $html .= '<div class="ads-loading">جاري تحميل الإعلانات...</div>';
    $html .= '</div>';
    
    $html .= '<script>';
    $html .= 'fetch("/api/ad_campaigns/get_ads?placement=' . $placement . '&limit=' . $limit . '&format=html")';
    $html .= '.then(response => response.json())';
    $html .= '.then(data => {';
    $html .= '  if (data.success) {';
    $html .= '    document.getElementById("' . $container_id . '").innerHTML = data.data.html;';
    $html .= '  } else {';
    $html .= '    document.getElementById("' . $container_id . '").style.display = "none";';
    $html .= '  }';
    $html .= '})';
    $html .= '.catch(error => {';
    $html .= '  document.getElementById("' . $container_id . '").style.display = "none";';
    $html .= '});';
    $html .= '</script>';
    
    return $html;
}

/**
 * التحقق من إمكانية عرض الإعلانات
 * @param string $placement موقع العرض
 * @return bool
 */
function can_display_ads($placement = 'general') {
    $manager = initAdDisplayManager();
    if (!$manager) return false;
    
    // يمكن إضافة منطق إضافي هنا للتحقق من الصلاحيات أو الإعدادات
    return true;
}

/**
 * الحصول على إحصائيات الإعلانات للصفحة الحالية
 * @return array إحصائيات الإعلانات
 */
function get_page_ad_stats() {
    global $database;
    
    if (!$database) return [];
    
    try {
        $page_url = $_SERVER['REQUEST_URI'] ?? '';
        $db = $database->getConnection();
        
        $sql = "SELECT 
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    COUNT(DISTINCT ai.ad_id) as unique_ads
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id 
                    AND DATE(ai.created_at) = DATE(ac.created_at)
                WHERE ai.page_url = ? 
                AND DATE(ai.created_at) = CURDATE()";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$page_url]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $stats ?: [];
        
    } catch (Exception $e) {
        error_log("خطأ في جلب إحصائيات الإعلانات: " . $e->getMessage());
        return [];
    }
}

/**
 * تسجيل حدث مخصص للإعلان
 * @param int $ad_id معرف الإعلان
 * @param string $event_type نوع الحدث
 * @param array $event_data بيانات إضافية
 * @return bool
 */
function log_ad_event($ad_id, $event_type, $event_data = []) {
    global $database;
    
    if (!$database || !is_numeric($ad_id)) return false;
    
    try {
        $db = $database->getConnection();
        
        $sql = "INSERT INTO fmc_ad_events 
                (ad_id, event_type, event_data, user_id, ip_address, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $user_id = $_SESSION['user_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $event_data_json = json_encode($event_data);
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([$ad_id, $event_type, $event_data_json, $user_id, $ip_address]);
        
    } catch (Exception $e) {
        error_log("خطأ في تسجيل حدث الإعلان: " . $e->getMessage());
        return false;
    }
}

/**
 * إضافة CSS مخصص للإعلانات
 * @param string $css كود CSS
 */
function add_custom_ad_css($css) {
    static $custom_css = '';
    $custom_css .= $css;
    
    // إضافة CSS في نهاية الصفحة
    add_action('wp_footer', function() use ($custom_css) {
        if (!empty($custom_css)) {
            echo "<style>{$custom_css}</style>";
        }
    });
}

/**
 * إضافة JavaScript مخصص للإعلانات
 * @param string $js كود JavaScript
 */
function add_custom_ad_js($js) {
    static $custom_js = '';
    $custom_js .= $js;
    
    // إضافة JavaScript في نهاية الصفحة
    add_action('wp_footer', function() use ($custom_js) {
        if (!empty($custom_js)) {
            echo "<script>{$custom_js}</script>";
        }
    });
}

// تهيئة تلقائية عند تحميل الملف
if (function_exists('add_action')) {
    add_action('init', 'initAdDisplayManager');
}
?>

