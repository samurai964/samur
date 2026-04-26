<?php
/**
 * محرك اللغات الأساسي لـ Final Max CMS
 * يتولى إدارة الترجمات والتبديل بين اللغات
 * 
 * @package FinalMaxCMS
 * @subpackage Core
 * @version 1.0.0
 */

class LanguageEngine {
    
    private $db;
    private $current_language;
    private $fallback_language;
    private $translations_cache = [];
    private $settings_cache = [];
    private $auto_detect_enabled;
    private $cache_enabled;
    private $cache_duration;
    
    /**
     * Constructor
     */
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->loadSettings();
        $this->initializeLanguage();
    }
    
    /**
     * تحميل إعدادات النظام
     */
    private function loadSettings() {
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value, setting_type FROM fmc_language_settings");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($settings as $setting) {
                $value = $this->convertSettingValue($setting['setting_value'], $setting['setting_type']);
                $this->settings_cache[$setting['setting_key']] = $value;
            }
            
            // تعيين الإعدادات الأساسية
            $this->fallback_language = $this->settings_cache['fallback_language'] ?? 'en';
            $this->auto_detect_enabled = $this->settings_cache['auto_detect_language'] ?? true;
            $this->cache_enabled = $this->settings_cache['cache_translations'] ?? true;
            $this->cache_duration = $this->settings_cache['cache_duration'] ?? 3600;
            
        } catch (Exception $e) {
            error_log("LanguageEngine: Error loading settings - " . $e->getMessage());
            $this->setDefaultSettings();
        }
    }
    
    /**
     * تحويل قيمة الإعداد حسب النوع
     */
    private function convertSettingValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            case 'array':
                return explode(',', $value);
            default:
                return $value;
        }
    }
    
    /**
     * تعيين الإعدادات الافتراضية
     */
    private function setDefaultSettings() {
        $this->fallback_language = 'en';
        $this->auto_detect_enabled = true;
        $this->cache_enabled = true;
        $this->cache_duration = 3600;
    }
    
    /**
     * تهيئة اللغة الحالية
     */
    private function initializeLanguage() {
        // 1. التحقق من اللغة المحددة في الجلسة
        if (isset($_SESSION['language']) && $this->isLanguageValid($_SESSION['language'])) {
            $this->current_language = $_SESSION['language'];
            return;
        }
        
        // 2. التحقق من اللغة المحددة في الكوكيز
        if (isset($_COOKIE['language']) && $this->isLanguageValid($_COOKIE['language'])) {
            $this->current_language = $_COOKIE['language'];
            $_SESSION['language'] = $this->current_language;
            return;
        }
        
        // 3. التحقق من تفضيلات المستخدم المسجل
        if (isset($_SESSION['user_id'])) {
            $user_language = $this->getUserPreferredLanguage($_SESSION['user_id']);
            if ($user_language && $this->isLanguageValid($user_language)) {
                $this->current_language = $user_language;
                $_SESSION['language'] = $this->current_language;
                return;
            }
        }
        
        // 4. الكشف التلقائي من المتصفح
        if ($this->auto_detect_enabled) {
            $detected_language = $this->detectBrowserLanguage();
            if ($detected_language && $this->isLanguageValid($detected_language)) {
                $this->current_language = $detected_language;
                $_SESSION['language'] = $this->current_language;
                return;
            }
        }
        
        // 5. استخدام اللغة الافتراضية
        $this->current_language = $this->settings_cache['default_language'] ?? 'ar';
        $_SESSION['language'] = $this->current_language;
    }
    
    /**
     * التحقق من صحة كود اللغة
     */
    private function isLanguageValid($language_code) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM fmc_languages WHERE code = ? AND is_active = 1 AND is_installed = 1");
            $stmt->execute([$language_code]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("LanguageEngine: Error validating language - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * الحصول على لغة المستخدم المفضلة
     */
    private function getUserPreferredLanguage($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT l.code 
                FROM fmc_user_language_preferences ulp
                JOIN fmc_languages l ON ulp.language_id = l.id
                WHERE ulp.user_id = ? AND ulp.is_primary = 1 AND l.is_active = 1
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['code'] : null;
        } catch (Exception $e) {
            error_log("LanguageEngine: Error getting user preferred language - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * كشف لغة المتصفح
     */
    private function detectBrowserLanguage() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }
        
        $languages = [];
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        
        // تحليل header اللغة
        preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)\s*(?:;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $accept_language, $matches);
        
        if (count($matches[1])) {
            $languages = array_combine($matches[1], $matches[2]);
            foreach ($languages as $lang => $val) {
                if ($val === '') $languages[$lang] = 1;
            }
            arsort($languages, SORT_NUMERIC);
        }
        
        // البحث عن أول لغة مدعومة
        foreach (array_keys($languages) as $lang) {
            $lang_code = substr($lang, 0, 2); // أخذ أول حرفين فقط
            if ($this->isLanguageValid($lang_code)) {
                return $lang_code;
            }
        }
        
        return null;
    }
    
    /**
     * تغيير اللغة الحالية
     */
    public function setLanguage($language_code) {
        if (!$this->isLanguageValid($language_code)) {
            throw new Exception("Invalid language code: " . $language_code);
        }
        
        $this->current_language = $language_code;
        $_SESSION['language'] = $language_code;
        
        // حفظ في الكوكيز لمدة 30 يوم
        setcookie('language', $language_code, time() + (30 * 24 * 60 * 60), '/');
        
        // تحديث تفضيلات المستخدم إذا كان مسجل دخول
        if (isset($_SESSION['user_id'])) {
            $this->updateUserLanguagePreference($_SESSION['user_id'], $language_code);
        }
        
        // مسح التخزين المؤقت للترجمات
        $this->clearTranslationCache($language_code);
        
        return true;
    }
    
    /**
     * تحديث تفضيلات لغة المستخدم
     */
    private function updateUserLanguagePreference($user_id, $language_code) {
        try {
            // الحصول على معرف اللغة
            $stmt = $this->db->prepare("SELECT id FROM fmc_languages WHERE code = ?");
            $stmt->execute([$language_code]);
            $language = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$language) return false;
            
            // حذف التفضيل السابق
            $stmt = $this->db->prepare("DELETE FROM fmc_user_language_preferences WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // إضافة التفضيل الجديد
            $stmt = $this->db->prepare("
                INSERT INTO fmc_user_language_preferences (user_id, language_id, is_primary) 
                VALUES (?, ?, 1)
            ");
            $stmt->execute([$user_id, $language['id']]);
            
            return true;
        } catch (Exception $e) {
            error_log("LanguageEngine: Error updating user language preference - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * الحصول على اللغة الحالية
     */
    public function getCurrentLanguage() {
        return $this->current_language;
    }
    
    /**
     * الحصول على معلومات اللغة الحالية
     */
    public function getCurrentLanguageInfo() {
        try {
            $stmt = $this->db->prepare("
                SELECT code, name, native_name, flag_icon, direction, completion_percentage
                FROM fmc_languages 
                WHERE code = ? AND is_active = 1
            ");
            $stmt->execute([$this->current_language]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("LanguageEngine: Error getting current language info - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * الحصول على جميع اللغات النشطة
     */
    public function getActiveLanguages() {
        try {
            $stmt = $this->db->prepare("
                SELECT code, name, native_name, flag_icon, direction, completion_percentage, sort_order
                FROM fmc_languages 
                WHERE is_active = 1 AND is_installed = 1
                ORDER BY sort_order ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("LanguageEngine: Error getting active languages - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ترجمة نص
     */
    public function translate($key, $group = 'common', $variables = [], $language_code = null) {
        $language_code = $language_code ?: $this->current_language;
        
        // التحقق من التخزين المؤقت أولاً
        $cache_key = $this->generateCacheKey($language_code, $group, $key);
        if ($this->cache_enabled && isset($this->translations_cache[$cache_key])) {
            $translation = $this->translations_cache[$cache_key];
        } else {
            $translation = $this->getTranslationFromDatabase($language_code, $key, $group);
            
            // حفظ في التخزين المؤقت
            if ($this->cache_enabled) {
                $this->translations_cache[$cache_key] = $translation;
            }
        }
        
        // إذا لم توجد الترجمة، جرب اللغة الاحتياطية
        if (!$translation && $language_code !== $this->fallback_language) {
            $translation = $this->getTranslationFromDatabase($this->fallback_language, $key, $group);
        }
        
        // إذا لم توجد الترجمة، استخدم المفتاح نفسه
        if (!$translation) {
            $translation = $key;
            $this->logMissingTranslation($language_code, $key, $group);
        }
        
        // تطبيق المتغيرات
        if (!empty($variables)) {
            $translation = $this->applyVariables($translation, $variables);
        }
        
        return $translation;
    }
    
    /**
     * الحصول على الترجمة من قاعدة البيانات
     */
    private function getTranslationFromDatabase($language_code, $key, $group) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.translated_value
                FROM fmc_translations t
                JOIN fmc_translation_keys tk ON t.key_id = tk.id
                JOIN fmc_translation_groups tg ON tk.group_id = tg.id
                JOIN fmc_languages l ON t.language_id = l.id
                WHERE l.code = ? AND tk.key_name = ? AND tg.group_key = ? AND t.is_approved = 1
                LIMIT 1
            ");
            $stmt->execute([$language_code, $key, $group]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['translated_value'] : null;
        } catch (Exception $e) {
            error_log("LanguageEngine: Error getting translation from database - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * توليد مفتاح التخزين المؤقت
     */
    private function generateCacheKey($language_code, $group, $key) {
        return md5($language_code . '_' . $group . '_' . $key);
    }
    
    /**
     * تطبيق المتغيرات على النص
     */
    private function applyVariables($text, $variables) {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }
    
    /**
     * تسجيل الترجمات المفقودة
     */
    private function logMissingTranslation($language_code, $key, $group) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fmc_language_error_log 
                (error_type, language_code, key_name, group_key, error_message, user_id, ip_address, user_agent, request_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $error_message = "Missing translation for key: {$key} in group: {$group}";
            $user_id = $_SESSION['user_id'] ?? null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $request_url = $_SERVER['REQUEST_URI'] ?? null;
            
            $stmt->execute([
                'missing_translation',
                $language_code,
                $key,
                $group,
                $error_message,
                $user_id,
                $ip_address,
                $user_agent,
                $request_url
            ]);
        } catch (Exception $e) {
            error_log("LanguageEngine: Error logging missing translation - " . $e->getMessage());
        }
    }
    
    /**
     * تحميل مجموعة ترجمات كاملة
     */
    public function loadTranslationGroup($group, $language_code = null) {
        $language_code = $language_code ?: $this->current_language;
        
        try {
            $stmt = $this->db->prepare("
                SELECT tk.key_name, t.translated_value
                FROM fmc_translations t
                JOIN fmc_translation_keys tk ON t.key_id = tk.id
                JOIN fmc_translation_groups tg ON tk.group_id = tg.id
                JOIN fmc_languages l ON t.language_id = l.id
                WHERE l.code = ? AND tg.group_key = ? AND t.is_approved = 1
            ");
            $stmt->execute([$language_code, $group]);
            $translations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // حفظ في التخزين المؤقت
            if ($this->cache_enabled) {
                foreach ($translations as $key => $value) {
                    $cache_key = $this->generateCacheKey($language_code, $group, $key);
                    $this->translations_cache[$cache_key] = $value;
                }
            }
            
            return $translations;
        } catch (Exception $e) {
            error_log("LanguageEngine: Error loading translation group - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * مسح التخزين المؤقت للترجمات
     */
    public function clearTranslationCache($language_code = null) {
        if ($language_code) {
            // مسح تخزين لغة محددة
            foreach ($this->translations_cache as $key => $value) {
                if (strpos($key, $language_code) === 0) {
                    unset($this->translations_cache[$key]);
                }
            }
            
            // مسح من قاعدة البيانات
            try {
                $stmt = $this->db->prepare("DELETE FROM fmc_translation_cache WHERE language_code = ?");
                $stmt->execute([$language_code]);
            } catch (Exception $e) {
                error_log("LanguageEngine: Error clearing translation cache - " . $e->getMessage());
            }
        } else {
            // مسح جميع الترجمات
            $this->translations_cache = [];
            
            try {
                $stmt = $this->db->prepare("DELETE FROM fmc_translation_cache");
                $stmt->execute();
            } catch (Exception $e) {
                error_log("LanguageEngine: Error clearing all translation cache - " . $e->getMessage());
            }
        }
    }
    
    /**
     * حفظ التخزين المؤقت في قاعدة البيانات
     */
    public function saveTranslationCache() {
        if (!$this->cache_enabled || empty($this->translations_cache)) {
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fmc_translation_cache 
                (language_code, group_key, cache_key, cached_data, expires_at)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                cached_data = VALUES(cached_data),
                expires_at = VALUES(expires_at),
                updated_at = NOW()
            ");
            
            $expires_at = date('Y-m-d H:i:s', time() + $this->cache_duration);
            
            foreach ($this->translations_cache as $cache_key => $translation) {
                // استخراج معلومات من مفتاح التخزين المؤقت
                $parts = explode('_', $cache_key, 3);
                if (count($parts) >= 2) {
                    $language_code = $parts[0];
                    $group_key = $parts[1] ?? 'common';
                    
                    $stmt->execute([
                        $language_code,
                        $group_key,
                        $cache_key,
                        json_encode($translation),
                        $expires_at
                    ]);
                }
            }
        } catch (Exception $e) {
            error_log("LanguageEngine: Error saving translation cache - " . $e->getMessage());
        }
    }
    
    /**
     * تحميل التخزين المؤقت من قاعدة البيانات
     */
    public function loadTranslationCache($language_code = null) {
        if (!$this->cache_enabled) {
            return;
        }
        
        try {
            $sql = "SELECT cache_key, cached_data FROM fmc_translation_cache WHERE expires_at > NOW()";
            $params = [];
            
            if ($language_code) {
                $sql .= " AND language_code = ?";
                $params[] = $language_code;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $cached_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($cached_items as $item) {
                $this->translations_cache[$item['cache_key']] = json_decode($item['cached_data'], true);
            }
        } catch (Exception $e) {
            error_log("LanguageEngine: Error loading translation cache - " . $e->getMessage());
        }
    }
    
    /**
     * تنظيف التخزين المؤقت المنتهي الصلاحية
     */
    public function cleanExpiredCache() {
        try {
            $stmt = $this->db->prepare("DELETE FROM fmc_translation_cache WHERE expires_at <= NOW()");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("LanguageEngine: Error cleaning expired cache - " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * الحصول على إحصائيات الترجمة
     */
    public function getTranslationStats($language_code = null) {
        $language_code = $language_code ?: $this->current_language;
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    l.code,
                    l.name,
                    l.native_name,
                    COUNT(tk.id) as total_keys,
                    COUNT(t.id) as translated_keys,
                    COUNT(CASE WHEN t.is_approved = 1 THEN 1 END) as approved_translations,
                    ROUND((COUNT(t.id) / COUNT(tk.id)) * 100, 2) as completion_percentage
                FROM fmc_languages l
                CROSS JOIN fmc_translation_keys tk
                LEFT JOIN fmc_translations t ON tk.id = t.key_id AND l.id = t.language_id
                WHERE l.code = ?
                GROUP BY l.id, l.code, l.name, l.native_name
            ");
            $stmt->execute([$language_code]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("LanguageEngine: Error getting translation stats - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * تحديث إحصائيات استخدام اللغة
     */
    public function updateLanguageUsageStats() {
        if (!$this->current_language) return;
        
        try {
            $language_id = $this->getLanguageId($this->current_language);
            if (!$language_id) return;
            
            $today = date('Y-m-d');
            
            $stmt = $this->db->prepare("
                INSERT INTO fmc_language_usage_stats 
                (language_id, date, page_views, unique_users, session_count)
                VALUES (?, ?, 1, 1, 1)
                ON DUPLICATE KEY UPDATE
                page_views = page_views + 1,
                unique_users = unique_users + 1,
                session_count = session_count + 1
            ");
            $stmt->execute([$language_id, $today]);
        } catch (Exception $e) {
            error_log("LanguageEngine: Error updating language usage stats - " . $e->getMessage());
        }
    }
    
    /**
     * الحصول على معرف اللغة
     */
    private function getLanguageId($language_code) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM fmc_languages WHERE code = ?");
            $stmt->execute([$language_code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id'] : null;
        } catch (Exception $e) {
            error_log("LanguageEngine: Error getting language ID - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * تصدير ترجمات لغة معينة
     */
    public function exportLanguageTranslations($language_code, $format = 'json') {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    tg.group_key,
                    tk.key_name,
                    t.translated_value,
                    tk.default_value,
                    tk.description,
                    tk.context
                FROM fmc_translations t
                JOIN fmc_translation_keys tk ON t.key_id = tk.id
                JOIN fmc_translation_groups tg ON tk.group_id = tg.id
                JOIN fmc_languages l ON t.language_id = l.id
                WHERE l.code = ? AND t.is_approved = 1
                ORDER BY tg.group_key, tk.key_name
            ");
            $stmt->execute([$language_code]);
            $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            switch ($format) {
                case 'json':
                    return $this->exportToJson($translations);
                case 'php':
                    return $this->exportToPhp($translations);
                case 'csv':
                    return $this->exportToCsv($translations);
                default:
                    return $this->exportToJson($translations);
            }
        } catch (Exception $e) {
            error_log("LanguageEngine: Error exporting translations - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * تصدير إلى JSON
     */
    private function exportToJson($translations) {
        $grouped = [];
        foreach ($translations as $translation) {
            $grouped[$translation['group_key']][$translation['key_name']] = $translation['translated_value'];
        }
        return json_encode($grouped, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * تصدير إلى PHP
     */
    private function exportToPhp($translations) {
        $grouped = [];
        foreach ($translations as $translation) {
            $grouped[$translation['group_key']][$translation['key_name']] = $translation['translated_value'];
        }
        return "<?php\nreturn " . var_export($grouped, true) . ";\n";
    }
    
    /**
     * تصدير إلى CSV
     */
    private function exportToCsv($translations) {
        $csv = "Group,Key,Translation,Default,Description,Context\n";
        foreach ($translations as $translation) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s"' . "\n",
                $translation['group_key'],
                $translation['key_name'],
                str_replace('"', '""', $translation['translated_value']),
                str_replace('"', '""', $translation['default_value']),
                str_replace('"', '""', $translation['description']),
                str_replace('"', '""', $translation['context'])
            );
        }
        return $csv;
    }
    
    /**
     * Destructor - حفظ التخزين المؤقت عند انتهاء الكلاس
     */
    public function __destruct() {
        $this->saveTranslationCache();
        $this->updateLanguageUsageStats();
    }
}

/**
 * دالة مساعدة للترجمة السريعة
 */
function __($key, $group = 'common', $variables = [], $language_code = null) {
    global $language_engine;
    
    if (!isset($language_engine)) {
        return $key; // إرجاع المفتاح إذا لم يكن المحرك متاح
    }
    
    return $language_engine->translate($key, $group, $variables, $language_code);
}

/**
 * دالة مساعدة للترجمة مع تشكيل HTML
 */
function _e($key, $group = 'common', $variables = [], $language_code = null) {
    echo htmlspecialchars(__($key, $group, $variables, $language_code), ENT_QUOTES, 'UTF-8');
}

/**
 * دالة مساعدة للحصول على اتجاه النص
 */
function get_text_direction() {
    global $language_engine;
    
    if (!isset($language_engine)) {
        return 'ltr';
    }
    
    $language_info = $language_engine->getCurrentLanguageInfo();
    return $language_info ? $language_info['direction'] : 'ltr';
}

/**
 * دالة مساعدة للحصول على كود اللغة الحالية
 */
function get_current_language() {
    global $language_engine;
    
    if (!isset($language_engine)) {
        return 'ar';
    }
    
    return $language_engine->getCurrentLanguage();
}

/**
 * دالة مساعدة للحصول على معلومات اللغة الحالية
 */
function get_current_language_info() {
    global $language_engine;
    
    if (!isset($language_engine)) {
        return null;
    }
    
    return $language_engine->getCurrentLanguageInfo();
}

/**
 * دالة مساعدة للحصول على جميع اللغات النشطة
 */
function get_active_languages() {
    global $language_engine;
    
    if (!isset($language_engine)) {
        return [];
    }
    
    return $language_engine->getActiveLanguages();
}
?>

