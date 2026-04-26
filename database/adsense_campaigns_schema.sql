SET FOREIGN_KEY_CHECKS = 0;

-- حذف الجداول بترتيب عكسي للتبعيات
DROP TABLE IF EXISTS `ad_placement_ads`;
DROP TABLE IF EXISTS `ad_placements`;
DROP TABLE IF EXISTS `ad_transactions`;
DROP TABLE IF EXISTS `ad_impressions`;
DROP TABLE IF EXISTS `ad_clicks`;
DROP TABLE IF EXISTS `ad_ads`;
DROP TABLE IF EXISTS `ad_campaigns`;
DROP TABLE IF EXISTS `ad_advertisers`;
DROP TABLE IF EXISTS `ad_settings`;

SET FOREIGN_KEY_CHECKS = 1;

-- Table: ad_advertisers
CREATE TABLE `ad_advertisers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `company_name` VARCHAR(255) NOT NULL,
  `contact_person` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(50),
  `address` TEXT,
  `balance` DECIMAL(10, 2) DEFAULT 0.00,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ad_campaigns
CREATE TABLE `ad_campaigns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `advertiser_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `budget` DECIMAL(10, 2) NOT NULL,
  `budget_type` ENUM('daily', 'total') DEFAULT 'total',
  `cpc_rate` DECIMAL(10, 4) DEFAULT 0.00,
  `cpm_rate` DECIMAL(10, 4) DEFAULT 0.00,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `target_countries` VARCHAR(255),
  `target_languages` VARCHAR(255),
  `target_keywords` TEXT,
  `status` ENUM('active', 'paused', 'completed', 'pending', 'rejected') DEFAULT 'pending',
  `current_spend` DECIMAL(10, 2) DEFAULT 0.00,
  `total_clicks` INT DEFAULT 0,
  `total_impressions` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`advertiser_id`) REFERENCES `ad_advertisers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ad_ads
CREATE TABLE `ad_ads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT NOT NULL,
  `type` ENUM('text', 'image', 'html') NOT NULL,
  `title` VARCHAR(255),
  `description` TEXT,
  `image_url` VARCHAR(255),
  `html_content` TEXT,
  `destination_url` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'paused', 'pending', 'rejected') DEFAULT 'pending',
  `clicks` INT DEFAULT 0,
  `impressions` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`campaign_id`) REFERENCES `ad_campaigns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ad_clicks
CREATE TABLE `ad_clicks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_id` INT NOT NULL,
  `campaign_id` INT NOT NULL,
  `user_id` INT,
  `ip_address` VARCHAR(45) NOT NULL,
  `click_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `cost` DECIMAL(10, 4) NOT NULL,
  FOREIGN KEY (`ad_id`) REFERENCES `ad_ads`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`campaign_id`) REFERENCES `ad_campaigns`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ad_impressions
CREATE TABLE `ad_impressions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_id` INT NOT NULL,
  `campaign_id` INT NOT NULL,
  `user_id` INT,
  `ip_address` VARCHAR(45) NOT NULL,
  `impression_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `cost` DECIMAL(10, 4) NOT NULL,
  FOREIGN KEY (`ad_id`) REFERENCES `ad_ads`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`campaign_id`) REFERENCES `ad_campaigns`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ad_transactions
CREATE TABLE `ad_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `advertiser_id` INT NOT NULL,
  `type` ENUM('deposit', 'withdrawal', 'campaign_spend', 'refund') NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `description` TEXT,
  `transaction_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`advertiser_id`) REFERENCES `ad_advertisers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ad_settings
CREATE TABLE `ad_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cpc_min_bid` DECIMAL(10, 4) DEFAULT 0.01,
  `cpm_min_bid` DECIMAL(10, 4) DEFAULT 0.10,
  `commission_rate` DECIMAL(5, 2) DEFAULT 0.10,
  `min_deposit` DECIMAL(10, 2) DEFAULT 10.00,
  `min_withdrawal` DECIMAL(10, 2) DEFAULT 20.00,
  `ad_review_required` BOOLEAN DEFAULT TRUE,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ad_placements
CREATE TABLE `ad_placements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT,
  `code` VARCHAR(255) NOT NULL UNIQUE,
  `width` INT,
  `height` INT,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ad_placement_ads
CREATE TABLE `ad_placement_ads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `placement_id` INT NOT NULL,
  `ad_id` INT NOT NULL,
  `priority` INT DEFAULT 1,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`placement_id`) REFERENCES `ad_placements`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ad_id`) REFERENCES `ad_ads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial data for ad_settings
INSERT INTO `ad_settings` (`cpc_min_bid`, `cpm_min_bid`, `commission_rate`, `min_deposit`, `min_withdrawal`, `ad_review_required`) VALUES
(0.01, 0.10, 0.10, 10.00, 20.00, TRUE);

-- Initial data for ad_placements (examples)
INSERT INTO `ad_placements` (`name`, `description`, `code`, `width`, `height`) VALUES
('Header Banner', 'Banner ad in the website header', 'header_banner', 728, 90),
('Sidebar Top', 'Top ad in the sidebar', 'sidebar_top', 300, 250),
('Content Middle', 'Ad within article content', 'content_middle', 336, 280),
('Footer Banner', 'Banner ad in the website footer', 'footer_banner', 728, 90),
('Popup Ad', 'Full-screen popup ad', 'popup_ad', 600, 400);