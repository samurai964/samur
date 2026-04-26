SET FOREIGN_KEY_CHECKS = 0;

-- حذف الجداول بترتيب عكسي للتبعيات
DROP TABLE IF EXISTS `language_error_log`;
DROP TABLE IF EXISTS `translation_cache`;
DROP TABLE IF EXISTS `language_update_log`;
DROP TABLE IF EXISTS `language_usage_stats`;
DROP TABLE IF EXISTS `user_language_preferences`;
DROP TABLE IF EXISTS `language_packages`;
DROP TABLE IF EXISTS `translations`;
DROP TABLE IF EXISTS `translation_keys`;
DROP TABLE IF EXISTS `translation_groups`;
DROP TABLE IF EXISTS `language_settings`;
DROP TABLE IF EXISTS `languages`;

SET FOREIGN_KEY_CHECKS = 1;

-- جدول اللغات المتاحة
CREATE TABLE `languages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(10) NOT NULL UNIQUE,
    `name` varchar(100) NOT NULL,
    `native_name` varchar(100) NOT NULL,
    `flag_icon` varchar(50) DEFAULT NULL,
    `direction` enum('ltr','rtl') NOT NULL DEFAULT 'ltr',
    `is_active` tinyint(1) NOT NULL DEFAULT 0,
    `is_default` tinyint(1) NOT NULL DEFAULT 0,
    `is_installed` tinyint(1) NOT NULL DEFAULT 0,
    `version` varchar(20) DEFAULT '1.0.0',
    `author` varchar(100) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `completion_percentage` decimal(5,2) DEFAULT 0.00,
    `last_updated` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `metadata` json DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_code` (`code`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_is_default` (`is_default`),
    KEY `idx_is_installed` (`is_installed`),
    KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول مجموعات الترجمة (Translation Groups)
CREATE TABLE `translation_groups` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `group_key` varchar(100) NOT NULL UNIQUE,
    `name` varchar(150) NOT NULL,
    `description` text DEFAULT NULL,
    `module` varchar(50) DEFAULT NULL,
    `is_system` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_group_key` (`group_key`),
    KEY `idx_module` (`module`),
    KEY `idx_is_system` (`is_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول مفاتيح الترجمة
CREATE TABLE `translation_keys` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key_name` varchar(255) NOT NULL,
    `group_id` int(11) NOT NULL,
    `default_value` text DEFAULT NULL,
    `description` text DEFAULT NULL,
    `context` varchar(255) DEFAULT NULL,
    `is_system` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_key_group` (`key_name`, `group_id`),
    KEY `idx_key_name` (`key_name`),
    KEY `idx_group_id` (`group_id`),
    KEY `idx_is_system` (`is_system`),
    FOREIGN KEY (`group_id`) REFERENCES `translation_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الترجمات
CREATE TABLE `translations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key_id` int(11) NOT NULL,
    `language_id` int(11) NOT NULL,
    `translated_value` text NOT NULL,
    `is_approved` tinyint(1) NOT NULL DEFAULT 0,
    `translator_id` int(11) DEFAULT NULL,
    `approved_by` int(11) DEFAULT NULL,
    `approved_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `metadata` json DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_key_language` (`key_id`, `language_id`),
    KEY `idx_key_id` (`key_id`),
    KEY `idx_language_id` (`language_id`),
    KEY `idx_is_approved` (`is_approved`),
    KEY `idx_translator_id` (`translator_id`),
    FOREIGN KEY (`key_id`) REFERENCES `translation_keys` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`translator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول حزم اللغات
CREATE TABLE `language_packages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `package_name` varchar(100) NOT NULL UNIQUE,
    `language_code` varchar(10) NOT NULL,
    `version` varchar(20) NOT NULL DEFAULT '1.0.0',
    `author` varchar(100) DEFAULT NULL,
    `author_email` varchar(150) DEFAULT NULL,
    `website` varchar(255) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `file_path` varchar(500) DEFAULT NULL,
    `file_size` bigint(20) DEFAULT NULL,
    `file_hash` varchar(64) DEFAULT NULL,
    `download_url` varchar(500) DEFAULT NULL,
    `is_official` tinyint(1) NOT NULL DEFAULT 0,
    `is_installed` tinyint(1) NOT NULL DEFAULT 0,
    `install_date` timestamp NULL DEFAULT NULL,
    `last_check` timestamp NULL DEFAULT NULL,
    `status` enum('available','downloading','installing','installed','failed','outdated') NOT NULL DEFAULT 'available',
    `error_message` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `metadata` json DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_package_name` (`package_name`),
    KEY `idx_language_code` (`language_code`),
    KEY `idx_is_official` (`is_official`),
    KEY `idx_is_installed` (`is_installed`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تفضيلات اللغة للمستخدمين
CREATE TABLE `user_language_preferences` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `language_id` int(11) NOT NULL,
    `is_primary` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_primary` (`user_id`, `is_primary`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_language_id` (`language_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول إحصائيات استخدام اللغات
CREATE TABLE `language_usage_stats` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `language_id` int(11) NOT NULL,
    `date` date NOT NULL,
    `page_views` bigint(20) NOT NULL DEFAULT 0,
    `unique_users` int(11) NOT NULL DEFAULT 0,
    `session_count` int(11) NOT NULL DEFAULT 0,
    `avg_session_duration` int(11) NOT NULL DEFAULT 0,
    `bounce_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_language_date` (`language_id`, `date`),
    KEY `idx_language_id` (`language_id`),
    KEY `idx_date` (`date`),
    FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سجل تحديثات اللغات
CREATE TABLE `language_update_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `language_id` int(11) DEFAULT NULL,
    `package_id` int(11) DEFAULT NULL,
    `action` enum('install','uninstall','update','activate','deactivate','download') NOT NULL,
    `status` enum('started','completed','failed') NOT NULL DEFAULT 'started',
    `old_version` varchar(20) DEFAULT NULL,
    `new_version` varchar(20) DEFAULT NULL,
    `error_message` text DEFAULT NULL,
    `performed_by` int(11) DEFAULT NULL,
    `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` timestamp NULL DEFAULT NULL,
    `details` json DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_language_id` (`language_id`),
    KEY `idx_package_id` (`package_id`),
    KEY `idx_action` (`action`),
    KEY `idx_status` (`status`),
    KEY `idx_performed_by` (`performed_by`),
    KEY `idx_started_at` (`started_at`),
    FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`package_id`) REFERENCES `language_packages` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول التخزين المؤقت للترجمات
CREATE TABLE `translation_cache` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `language_code` varchar(10) NOT NULL,
    `group_key` varchar(100) NOT NULL,
    `cache_key` varchar(255) NOT NULL,
    `cached_data` longtext NOT NULL,
    `expires_at` timestamp NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_cache_key` (`cache_key`),
    KEY `idx_language_code` (`language_code`),
    KEY `idx_group_key` (`group_key`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول إعدادات نظام اللغات
CREATE TABLE `language_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL UNIQUE,
    `setting_value` text DEFAULT NULL,
    `setting_type` enum('string','integer','boolean','json','array') NOT NULL DEFAULT 'string',
    `description` text DEFAULT NULL,
    `is_system` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_setting_key` (`setting_key`),
    KEY `idx_is_system` (`is_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج اللغات الأساسية
INSERT INTO `languages` (`code`, `name`, `native_name`, `flag_icon`, `direction`, `is_active`, `is_default`, `is_installed`, `completion_percentage`, `sort_order`) VALUES
('ar', 'Arabic', 'العربية', 'flag-sa', 'rtl', 1, 1, 1, 100.00, 1),
('en', 'English', 'English', 'flag-us', 'ltr', 1, 0, 1, 100.00, 2),
('fr', 'French', 'Français', 'flag-fr', 'ltr', 0, 0, 0, 0.00, 3),
('es', 'Spanish', 'Español', 'flag-es', 'ltr', 0, 0, 0, 0.00, 4),
('de', 'German', 'Deutsch', 'flag-de', 'ltr', 0, 0, 0, 0.00, 5),
('it', 'Italian', 'Italiano', 'flag-it', 'ltr', 0, 0, 0, 0.00, 6),
('pt', 'Portuguese', 'Português', 'flag-pt', 'ltr', 0, 0, 0, 0.00, 7),
('ru', 'Russian', 'Русский', 'flag-ru', 'ltr', 0, 0, 0, 0.00, 8),
('zh', 'Chinese', '中文', 'flag-cn', 'ltr', 0, 0, 0, 0.00, 9),
('ja', 'Japanese', '日本語', 'flag-jp', 'ltr', 0, 0, 0, 0.00, 10),
('ko', 'Korean', '한국어', 'flag-kr', 'ltr', 0, 0, 0, 0.00, 11),
('hi', 'Hindi', 'हिन्दी', 'flag-in', 'ltr', 0, 0, 0, 0.00, 12),
('ur', 'Urdu', 'اردو', 'flag-pk', 'rtl', 0, 0, 0, 0.00, 13),
('fa', 'Persian', 'فارسی', 'flag-ir', 'rtl', 0, 0, 0, 0.00, 14),
('tr', 'Turkish', 'Türkçe', 'flag-tr', 'ltr', 0, 0, 0, 0.00, 15);

-- إدراج مجموعات الترجمة الأساسية
INSERT INTO `translation_groups` (`group_key`, `name`, `description`, `module`, `is_system`) VALUES
('common', 'Common Terms', 'Common terms used throughout the system', 'core', 1),
('navigation', 'Navigation', 'Navigation menu items and links', 'core', 1),
('forms', 'Forms', 'Form labels, buttons, and validation messages', 'core', 1),
('auth', 'Authentication', 'Login, registration, and authentication messages', 'auth', 1),
('user', 'User Management', 'User profile and management terms', 'user', 1),
('content', 'Content Management', 'Content creation and management terms', 'content', 1),
('admin', 'Administration', 'Admin panel terms and messages', 'admin', 1),
('errors', 'Error Messages', 'Error and validation messages', 'core', 1),
('success', 'Success Messages', 'Success and confirmation messages', 'core', 1),
('email', 'Email Templates', 'Email template content and subjects', 'email', 1),
('ads', 'Advertisements', 'Advertisement related terms', 'ads', 1),
('payments', 'Payments', 'Payment and billing related terms', 'payments', 1),
('directory', 'Directory', 'Website directory related terms', 'directory', 1),
('courses', 'Courses', 'Online courses related terms', 'courses', 1),
('freelance', 'Freelance', 'Freelance services related terms', 'freelance', 1),
('ad_campaigns', 'Ad Campaigns', 'Advertisement campaigns system terms', 'ad_campaigns', 1);

-- إدراج مفاتيح الترجمة الأساسية لمجموعة common
INSERT INTO `translation_keys` (`key_name`, `group_id`, `default_value`, `description`, `is_system`) VALUES
('home', 1, 'Home', 'Home page link', 1),
('about', 1, 'About', 'About page link', 1),
('contact', 1, 'Contact', 'Contact page link', 1),
('search', 1, 'Search', 'Search button/placeholder', 1),
('save', 1, 'Save', 'Save button', 1),
('cancel', 1, 'Cancel', 'Cancel button', 1),
('delete', 1, 'Delete', 'Delete button', 1),
('edit', 1, 'Edit', 'Edit button', 1),
('view', 1, 'View', 'View button', 1),
('back', 1, 'Back', 'Back button', 1),
('next', 1, 'Next', 'Next button', 1),
('previous', 1, 'Previous', 'Previous button', 1),
('loading', 1, 'Loading...', 'Loading message', 1),
('yes', 1, 'Yes', 'Yes option', 1),
('no', 1, 'No', 'No option', 1);

-- إدراج الترجمات العربية للمفاتيح الأساسية
INSERT INTO `translations` (`key_id`, `language_id`, `translated_value`, `is_approved`) VALUES
(1, 1, 'الرئيسية', 1),
(2, 1, 'حول الموقع', 1),
(3, 1, 'اتصل بنا', 1),
(4, 1, 'بحث', 1),
(5, 1, 'حفظ', 1),
(6, 1, 'إلغاء', 1),
(7, 1, 'حذف', 1),
(8, 1, 'تعديل', 1),
(9, 1, 'عرض', 1),
(10, 1, 'رجوع', 1),
(11, 1, 'التالي', 1),
(12, 1, 'السابق', 1),
(13, 1, 'جاري التحميل...', 1),
(14, 1, 'نعم', 1),
(15, 1, 'لا', 1);

-- إدراج إعدادات النظام الأساسية
INSERT INTO `language_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_system`) VALUES
('default_language', 'ar', 'string', 'Default system language code', 1),
('fallback_language', 'en', 'string', 'Fallback language when translation is missing', 1),
('auto_detect_language', '1', 'boolean', 'Auto detect user language from browser', 1),
('cache_translations', '1', 'boolean', 'Enable translation caching', 1),
('cache_duration', '3600', 'integer', 'Translation cache duration in seconds', 1),
('allow_user_language_switch', '1', 'boolean', 'Allow users to switch language', 1),
('show_language_selector', '1', 'boolean', 'Show language selector in frontend', 1),
('translation_api_enabled', '0', 'boolean', 'Enable automatic translation API', 1),
('translation_api_key', '', 'string', 'Translation API key', 1),
('package_repository_url', 'https://packages.finalmaxcms.com/languages/', 'string', 'Language packages repository URL', 1),
('auto_update_packages', '0', 'boolean', 'Automatically update language packages', 1),
('backup_before_update', '1', 'boolean', 'Create backup before updating language packages', 1);

-- إنشاء فهارس إضافية لتحسين الأداء
CREATE INDEX idx_translations_composite ON translations(language_id, key_id, is_approved);
CREATE INDEX idx_translation_keys_composite ON translation_keys(group_id, key_name);
CREATE INDEX idx_languages_active_default ON languages(is_active, is_default);
CREATE INDEX idx_packages_status_installed ON language_packages(status, is_installed);
CREATE INDEX idx_usage_stats_date_range ON language_usage_stats(date, language_id);

-- إنشاء views مفيدة للاستعلامات السريعة
CREATE OR REPLACE VIEW v_active_languages AS
SELECT 
    l.id,
    l.code,
    l.name,
    l.native_name,
    l.flag_icon,
    l.direction,
    l.is_default,
    l.completion_percentage,
    l.sort_order
FROM languages l
WHERE l.is_active = 1 AND l.is_installed = 1
ORDER BY l.sort_order;

CREATE OR REPLACE VIEW v_translation_progress AS
SELECT 
    l.id as language_id,
    l.code,
    l.name,
    l.native_name,
    COUNT(tk.id) as total_keys,
    COUNT(t.id) as translated_keys,
    COUNT(CASE WHEN t.is_approved = 1 THEN 1 END) as approved_translations,
    ROUND((COUNT(t.id) / COUNT(tk.id)) * 100, 2) as completion_percentage
FROM languages l
CROSS JOIN translation_keys tk
LEFT JOIN translations t ON tk.id = t.key_id AND l.id = t.language_id
WHERE l.is_installed = 1
GROUP BY l.id, l.code, l.name, l.native_name;

CREATE OR REPLACE VIEW v_language_usage_summary AS
SELECT 
    l.id as language_id,
    l.code,
    l.name,
    l.native_name,
    COUNT(DISTINCT up.user_id) as total_users,
    SUM(us.page_views) as total_page_views,
    AVG(us.avg_session_duration) as avg_session_duration,
    AVG(us.bounce_rate) as avg_bounce_rate
FROM languages l
LEFT JOIN user_language_preferences up ON l.id = up.language_id
LEFT JOIN language_usage_stats us ON l.id = us.language_id
WHERE l.is_active = 1
GROUP BY l.id, l.code, l.name, l.native_name;