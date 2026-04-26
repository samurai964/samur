-- Final Max CMS Database Schema
-- نظام إدارة المحتوى المتقدم
-- تاريخ الإنشاء: 2025
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
-- إعدادات قاعدة البيانات
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- تعطيل فحص المفاتيح الأجنبية مؤقتاً لتجنب أخطاء التكامل
SET FOREIGN_KEY_CHECKS = 0;

-- حذف الآراء (Views) أولاً
DROP VIEW IF EXISTS `user_stats`;
DROP VIEW IF EXISTS `category_stats`;

-- تعديل ترتيب حذف الجداول لحل مشكلة المفاتيح الأجنبية
-- نبدأ بحذف الجداول التي تحتوي على مفاتيح أجنبية أولاً
DROP TABLE IF EXISTS `directory_reviews`;
DROP TABLE IF EXISTS `directory_websites`;
DROP TABLE IF EXISTS `directory_categories`;
DROP TABLE IF EXISTS `activity_log`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `pinned_topics`;
DROP TABLE IF EXISTS `points`;
DROP TABLE IF EXISTS `internal_ads`;
DROP TABLE IF EXISTS `ads`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `portfolio_items`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `topics`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;

-- إعادة تفعيل فحص المفاتيح الأجنبية
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- جدول المستخدمين
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('user','moderator','admin','super_admin') DEFAULT 'user',
  `status` enum('active','inactive','banned','pending') DEFAULT 'pending',
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(100) DEFAULT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `phone` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `social_platform1` varchar(255) DEFAULT NULL,
  `social_platform2` varchar(255) DEFAULT NULL,
  `social_platform3` varchar(255) DEFAULT NULL,
  `social_platform4` varchar(255) DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_points` (`points`),
  KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول الأقسام
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  KEY `idx_categories_active` (`is_active`),
  KEY `idx_categories_sort` (`sort_order`),
  CONSTRAINT `fk_categories_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول المواضيع
CREATE TABLE `topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `status` enum('draft','published','archived','deleted') DEFAULT 'published',
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `dislikes` int(11) DEFAULT 0,
  `comments_count` int(11) DEFAULT 0,
  `tags` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `idx_topics_status` (`status`),
  KEY `idx_topics_pinned` (`is_pinned`),
  KEY `idx_topics_featured` (`is_featured`),
  KEY `idx_topics_views` (`views`),
  KEY `idx_topics_likes` (`likes`),
  KEY `idx_topics_published_at` (`published_at`),
  KEY `idx_topics_created_at` (`created_at`),
  FULLTEXT KEY `idx_topics_search` (`title`,`content`),
  CONSTRAINT `fk_topics_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_topics_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول التعليقات
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('approved','pending','spam','deleted') DEFAULT 'approved',
  `likes` int(11) DEFAULT 0,
  `dislikes` int(11) DEFAULT 0,
  `is_edited` tinyint(1) DEFAULT 0,
  `edited_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `user_id` (`user_id`),
  KEY `parent_id` (`parent_id`),
  KEY `idx_comments_status` (`status`),
  KEY `idx_comments_created_at` (`created_at`),
  CONSTRAINT `fk_comments_topic_id` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول الخدمات
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `delivery_time` int(11) NOT NULL COMMENT 'بالأيام',
  `revisions` int(11) DEFAULT 0,
  `status` enum('active','paused','deleted','pending') DEFAULT 'pending',
  `is_featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `orders_count` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `reviews_count` int(11) DEFAULT 0,
  `images` json DEFAULT NULL,
  `extras` json DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `idx_services_status` (`status`),
  KEY `idx_services_featured` (`is_featured`),
  KEY `idx_services_price` (`price`),
  KEY `idx_services_rating` (`rating`),
  KEY `idx_services_created_at` (`created_at`),
  FULLTEXT KEY `idx_services_search` (`title`,`description`),
  CONSTRAINT `fk_services_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_services_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول الدورات
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `instructor_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `duration` int(11) DEFAULT NULL COMMENT 'بالساعات',
  `lessons_count` int(11) DEFAULT 0,
  `language` varchar(10) DEFAULT 'ar',
  `status` enum('draft','published','archived','deleted') DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_free` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `enrollments_count` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `reviews_count` int(11) DEFAULT 0,
  `thumbnail` varchar(255) DEFAULT NULL,
  `preview_video` varchar(255) DEFAULT NULL,
  `what_you_learn` json DEFAULT NULL,
  `requirements` json DEFAULT NULL,
  `target_audience` json DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `instructor_id` (`instructor_id`),
  KEY `category_id` (`category_id`),
  KEY `idx_courses_status` (`status`),
  KEY `idx_courses_featured` (`is_featured`),
  KEY `idx_courses_free` (`is_free`),
  KEY `idx_courses_price` (`price`),
  KEY `idx_courses_rating` (`rating`),
  KEY `idx_courses_published_at` (`published_at`),
  FULLTEXT KEY `idx_courses_search` (`title`,`description`),
  CONSTRAINT `fk_courses_instructor_id` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_courses_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول الإعلانات المبوبة
CREATE TABLE `ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'SAR',
  `location` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_whatsapp` varchar(20) DEFAULT NULL,
  `status` enum('active','expired','sold','deleted','pending') DEFAULT 'pending',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_urgent` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `images` json DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `featured_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `idx_ads_status` (`status`),
  KEY `idx_ads_featured` (`is_featured`),
  KEY `idx_ads_location` (`location`),
  KEY `idx_ads_price` (`price`),
  KEY `idx_ads_expires_at` (`expires_at`),
  KEY `idx_ads_created_at` (`created_at`),
  FULLTEXT KEY `idx_ads_search` (`title`,`description`),
  CONSTRAINT `fk_ads_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ads_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول الإعلانات الداخلية
CREATE TABLE `internal_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `position` enum('header','sidebar','content_top','content_middle','content_bottom','footer','popup','banner') NOT NULL,
  `target_pages` json DEFAULT NULL COMMENT 'الصفحات المستهدفة',
  `status` enum('active','inactive','scheduled') DEFAULT 'active',
  `priority` int(11) DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `max_views` int(11) DEFAULT NULL,
  `max_clicks` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_internal_ads_status` (`status`),
  KEY `idx_internal_ads_position` (`position`),
  KEY `idx_internal_ads_priority` (`priority`),
  KEY `idx_internal_ads_dates` (`start_date`,`end_date`),
  CONSTRAINT `fk_internal_ads_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول النقاط
CREATE TABLE `points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `type` enum('earned','spent','bonus','penalty') DEFAULT 'earned',
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_points_type` (`type`),
  KEY `idx_points_action` (`action`),
  KEY `idx_points_reference` (`reference_type`,`reference_id`),
  KEY `idx_points_created_at` (`created_at`),
  CONSTRAINT `fk_points_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول المواضيع المثبتة بمقابل
CREATE TABLE `pinned_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_pinned_topics_status` (`status`),
  KEY `idx_pinned_topics_expires_at` (`expires_at`),
  CONSTRAINT `fk_pinned_topics_topic_id` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pinned_topics_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول المدفوعات
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'SAR',
  `type` enum('service_order','course_enrollment','ad_promotion','topic_pin','wallet_deposit','subscription') NOT NULL,
  `status` enum('pending','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `gateway_transaction_id` varchar(100) DEFAULT NULL,
  `gateway_response` json DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `fees` decimal(10,2) DEFAULT 0.00,
  `net_amount` decimal(10,2) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_type` (`type`),
  KEY `idx_payments_gateway_transaction` (`gateway_transaction_id`),
  KEY `idx_payments_reference` (`reference_type`,`reference_id`),
  KEY `idx_payments_created_at` (`created_at`),
  CONSTRAINT `fk_payments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول الإشعارات
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_notifications_read` (`is_read`),
  KEY `idx_notifications_type` (`type`),
  KEY `idx_notifications_created_at` (`created_at`),
  CONSTRAINT `fk_notifications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول الجلسات
CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_sessions_last_activity` (`last_activity`),
  CONSTRAINT `fk_sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول سجل الأنشطة
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_activity_log_action` (`action`),
  KEY `idx_activity_log_created_at` (`created_at`),
  CONSTRAINT `fk_activity_log_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول إعدادات النظام
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` longtext DEFAULT NULL,
  `type` enum('string','integer','boolean','json','text') DEFAULT 'string',
  `group` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `idx_settings_group` (`group`),
  KEY `idx_settings_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جداول دليل المواقع
CREATE TABLE `directory_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `directory_websites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `url` varchar(500) NOT NULL,
  `description` text NOT NULL,
  `screenshot` varchar(255) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `language` varchar(10) DEFAULT 'ar',
  `is_free` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `featured_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rating` decimal(3,1) DEFAULT 0.0,
  `reviews_count` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `idx_directory_websites_status` (`status`),
  KEY `idx_directory_websites_category` (`category_id`),
  KEY `idx_directory_websites_rating` (`rating`),
  KEY `idx_directory_websites_views` (`views`),
  KEY `idx_directory_websites_featured` (`is_featured`),
  KEY `idx_directory_websites_featured_at` (`featured_at`),
  FULLTEXT KEY `idx_directory_websites_search` (`title`,`description`,`tags`),
  CONSTRAINT `fk_directory_websites_category_id` FOREIGN KEY (`category_id`) REFERENCES `directory_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_directory_websites_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `directory_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_review` (`website_id`,`user_id`),
  KEY `website_id` (`website_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_directory_reviews_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_directory_reviews_website_id` FOREIGN KEY (`website_id`) REFERENCES `directory_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول أعمال المستخدمين (Portfolio)
CREATE TABLE `portfolio_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `project_url` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `images` json DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'published',
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_portfolio_items_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- جدول إعدادات المستخدمين الإضافية (لتحسين البروفايل)
CREATE TABLE `user_profiles` (
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `skills` json DEFAULT NULL,
  `education` json DEFAULT NULL,
  `experience` json DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `profile_views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_user_profiles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- إدراج البيانات الأساسية
-- إدراج المستخدم الإداري الافتراضي
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `role`, `status`, `email_verified`, `points`, `wallet_balance`) VALUES
('admin', 'admin@finalmax.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير', 'النظام', 'super_admin', 'active', 1, 1000, 0.00);

-- إدراج الأقسام الأساسية
INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `color`, `sort_order`) VALUES
('عام', 'general', 'المواضيع العامة والنقاشات المتنوعة', '💬', '#007bff', 1),
('التقنية', 'technology', 'مواضيع التقنية والبرمجة والتطوير', '💻', '#28a745', 2),
('التصميم', 'design', 'التصميم الجرافيكي وتصميم المواقع', '🎨', '#dc3545', 3),
('التسويق', 'marketing', 'التسويق الرقمي والإعلانات', '📈', '#ffc107', 4),
('الكتابة', 'writing', 'الكتابة والترجمة والمحتوى', '✍️', '#6f42c1', 5),
('الأعمال', 'business', 'ريادة الأعمال والاستشارات', '💼', '#fd7e14', 6);

-- إدراج الإعدادات الأساسية
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `description`, `is_public`) VALUES
('site_name', 'Final Max CMS', 'string', 'general', 'اسم الموقع', 1),
('site_description', 'نظام إدارة محتوى متطور', 'text', 'general', 'وصف الموقع', 1),
('site_keywords', 'cms, منتدى, خدمات, دورات', 'text', 'seo', 'الكلمات المفتاحية', 1),
('admin_email', 'admin@finalmax.com', 'string', 'general', 'بريد المدير', 0),
('registration_enabled', '1', 'boolean', 'users', 'تفعيل التسجيل', 1),
('email_verification_required', '1', 'boolean', 'users', 'تأكيد البريد الإلكتروني مطلوب', 1),
('points_per_topic', '10', 'integer', 'points', 'نقاط إنشاء موضوع', 1),
('points_per_comment', '5', 'integer', 'points', 'نقاط التعليق', 1),
('points_per_like', '1', 'integer', 'points', 'نقاط الإعجاب', 1),
('max_login_attempts', '5', 'integer', 'security', 'محاولات تسجيل الدخول القصوى', 0),
('lockout_duration', '30', 'integer', 'security', 'مدة القفل بالدقائق', 0);

-- بيانات أساسية لفئات دليل المواقع
INSERT INTO `directory_categories` (`name`, `slug`, `description`, `icon`, `sort_order`) VALUES
('تقنية ومعلوماتية', 'technology', 'مواقع التقنية والبرمجة والذكاء الاصطناعي', '💻', 1),
('تعليم وتدريب', 'education', 'مواقع التعليم والدورات والجامعات', '🎓', 2),
('أخبار وإعلام', 'news', 'مواقع الأخبار والصحافة والإعلام', '📰', 3),
('تجارة إلكترونية', 'ecommerce', 'متاجر إلكترونية ومواقع التسوق', '🛒', 4),
('صحة وطب', 'health', 'مواقع الصحة والطب والعلاج', '🏥', 5),
('رياضة', 'sports', 'مواقع الرياضة والألعاب', '⚽', 6),
('ترفيه', 'entertainment', 'مواقع الترفيه والألعاب والأفلام', '🎬', 7),
('سفر وسياحة', 'travel', 'مواقع السفر والسياحة والفنادق', '✈️', 8),
('طعام وشراب', 'food', 'مواقع الطعام والمطاعم والوصفات', '🍽️', 9),
('موضة وجمال', 'fashion', 'مواقع الموضة والأزياء والجمال', '👗', 10),
('عقارات', 'real-estate', 'مواقع العقارات والإسكان', '🏠', 11),
('سيارات', 'automotive', 'مواقع السيارات والمركبات', '🚗', 12),
('مال وأعمال', 'business', 'مواقع الأعمال والاستثمار والمال', '💼', 13),
('حكومية', 'government', 'مواقع حكومية ورسمية', '🏛️', 14),
('منوعات', 'miscellaneous', 'مواقع متنوعة أخرى', '📂', 15);

-- مواقع تجريبية
INSERT INTO `directory_websites` (`user_id`, `category_id`, `title`, `slug`, `url`, `description`, `language`, `is_free`, `status`, `rating`, `reviews_count`, `views`) VALUES
(1, 1, 'موقع تقني متقدم', 'tech-advanced-site', 'https://example-tech.com', 'موقع متخصص في أحدث التقنيات والبرمجة والذكاء الاصطناعي مع مقالات ودروس متعدة', 'ar', 1, 'approved', 4.5, 12, 1250),
(1, 2, 'أكاديمية التعلم الرقمي', 'digital-learning-academy', 'https://example-edu.com', 'منصة تعليمية شاملة تقدم دورات في مختلف المجالات مع شهادات معتمدة', 'ar', 0, 'approved', 4.8, 25, 2100),
(1, 3, 'صحيفة الأخبار العربية', 'arab-news-paper', 'https://example-news.com', 'موقع إخباري شامل يغطي آخر الأخبار المحلية والعالمية بمصداقية عالية', 'ar', 1, 'approved', 4.2, 8, 890),
(1, 4, 'متجر الإلكترونيات الذكية', 'smart-electronics-store', 'https://example-shop.com', 'متجر إلكتروني متخصص في بيع الأجهزة الذكية والإلكترونيات بأفضل الأسعار', 'ar', 1, 'approved', 4.6, 18, 1680),
(1, 5, 'عيادة الصحة الرقمية', 'digital-health-clinic', 'https://example-health.com', 'منصة طبية تقدم استشارات صحية عن بُعد مع أطباء متخصصين', 'ar', 0, 'approved', 4.7, 15, 950);

-- إنشاء الفهارس الإضافية للأداء
CREATE INDEX `idx_users_email_verified` ON `users` (`email_verified`);
CREATE INDEX `idx_users_last_login` ON `users` (`last_login`);
CREATE INDEX `idx_topics_user_category` ON `topics` (`user_id`, `category_id`);
CREATE INDEX `idx_comments_topic_user` ON `comments` (`topic_id`, `user_id`);
CREATE INDEX `idx_services_user_category` ON `services` (`user_id`, `category_id`);
CREATE INDEX `idx_courses_instructor_category` ON `courses` (`instructor_id`, `category_id`);

COMMIT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;