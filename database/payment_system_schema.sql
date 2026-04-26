SET FOREIGN_KEY_CHECKS = 0;

-- حذف الجداول بترتيب عكسي للتبعيات
DROP TABLE IF EXISTS `tax_settings`;
DROP TABLE IF EXISTS `financial_reports`;
DROP TABLE IF EXISTS `advertiser_subscriptions`;
DROP TABLE IF EXISTS `subscription_plans`;
DROP TABLE IF EXISTS `coupon_usage`;
DROP TABLE IF EXISTS `discount_coupons`;
DROP TABLE IF EXISTS `payment_gateways`;
DROP TABLE IF EXISTS `advertiser_balance`;
DROP TABLE IF EXISTS `payment_transactions`;
DROP TABLE IF EXISTS `invoice_items`;
DROP TABLE IF EXISTS `invoices`;

SET FOREIGN_KEY_CHECKS = 1;

-- جدول الفواتير
CREATE TABLE `invoices` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `invoice_number` varchar(50) NOT NULL UNIQUE,
    `advertiser_id` int(11) NOT NULL,
    `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `currency` varchar(3) NOT NULL DEFAULT 'USD',
    `description` text,
    `status` enum('pending','paid','cancelled','overdue','refunded') NOT NULL DEFAULT 'pending',
    `payment_method` varchar(50) DEFAULT NULL,
    `transaction_id` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `due_date` date NOT NULL,
    `paid_at` timestamp NULL DEFAULT NULL,
    `notes` text,
    `metadata` json DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_advertiser_id` (`advertiser_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_due_date` (`due_date`),
    KEY `idx_invoice_number` (`invoice_number`),
    FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول عناصر الفاتورة
CREATE TABLE `invoice_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `invoice_id` int(11) NOT NULL,
    `description` varchar(255) NOT NULL,
    `quantity` decimal(8,2) NOT NULL DEFAULT 1.00,
    `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
    `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
    `item_type` varchar(50) DEFAULT 'service',
    `campaign_id` int(11) DEFAULT NULL,
    `period_start` date DEFAULT NULL,
    `period_end` date DEFAULT NULL,
    `metadata` json DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_invoice_id` (`invoice_id`),
    KEY `idx_campaign_id` (`campaign_id`),
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`campaign_id`) REFERENCES `ad_campaigns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المعاملات المالية
CREATE TABLE `payment_transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `invoice_id` int(11) DEFAULT NULL,
    `advertiser_id` int(11) NOT NULL,
    `transaction_type` enum('payment','refund','chargeback','fee') NOT NULL DEFAULT 'payment',
    `gateway` varchar(50) NOT NULL,
    `gateway_transaction_id` varchar(100) DEFAULT NULL,
    `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `net_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `currency` varchar(3) NOT NULL DEFAULT 'USD',
    `status` enum('pending','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    `gateway_response` json DEFAULT NULL,
    `error_message` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `processed_at` timestamp NULL DEFAULT NULL,
    `reference_number` varchar(100) DEFAULT NULL,
    `notes` text,
    `metadata` json DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_invoice_id` (`invoice_id`),
    KEY `idx_advertiser_id` (`advertiser_id`),
    KEY `idx_gateway` (`gateway`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_gateway_transaction_id` (`gateway_transaction_id`),
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول رصيد المعلنين
CREATE TABLE `advertiser_balance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `advertiser_id` int(11) NOT NULL,
    `transaction_type` enum('credit','debit','adjustment','refund') NOT NULL DEFAULT 'credit',
    `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `balance_before` decimal(10,2) NOT NULL DEFAULT 0.00,
    `balance_after` decimal(10,2) NOT NULL DEFAULT 0.00,
    `currency` varchar(3) NOT NULL DEFAULT 'USD',
    `description` varchar(255) NOT NULL,
    `reference_type` enum('invoice','campaign','adjustment','refund','bonus') DEFAULT NULL,
    `reference_id` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int(11) DEFAULT NULL,
    `notes` text,
    `metadata` json DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_advertiser_id` (`advertiser_id`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_reference` (`reference_type`, `reference_id`),
    FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول إعدادات بوابات الدفع
CREATE TABLE `payment_gateways` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `gateway_name` varchar(50) NOT NULL UNIQUE,
    `display_name` varchar(100) NOT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 0,
    `is_test_mode` tinyint(1) NOT NULL DEFAULT 1,
    `supported_currencies` json DEFAULT NULL,
    `configuration` json DEFAULT NULL,
    `fee_percentage` decimal(5,4) NOT NULL DEFAULT 0.0000,
    `fee_fixed` decimal(10,2) NOT NULL DEFAULT 0.00,
    `min_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `max_amount` decimal(10,2) DEFAULT NULL,
    `processing_time` varchar(50) DEFAULT NULL,
    `description` text,
    `logo_url` varchar(255) DEFAULT NULL,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_gateway_name` (`gateway_name`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول كوبونات الخصم
CREATE TABLE `discount_coupons` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL UNIQUE,
    `name` varchar(100) NOT NULL,
    `description` text,
    `discount_type` enum('percentage','fixed','credit') NOT NULL DEFAULT 'percentage',
    `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
    `min_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `max_discount` decimal(10,2) DEFAULT NULL,
    `usage_limit` int(11) DEFAULT NULL,
    `used_count` int(11) NOT NULL DEFAULT 0,
    `user_limit` int(11) DEFAULT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `valid_from` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `valid_until` timestamp NULL DEFAULT NULL,
    `applicable_to` enum('all','new_users','existing_users','specific_users') NOT NULL DEFAULT 'all',
    `target_users` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_code` (`code`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_valid_dates` (`valid_from`, `valid_until`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول استخدام الكوبونات
CREATE TABLE `coupon_usage` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `coupon_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `invoice_id` int(11) DEFAULT NULL,
    `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `original_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `final_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `used_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_coupon_id` (`coupon_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_invoice_id` (`invoice_id`),
    KEY `idx_used_at` (`used_at`),
    FOREIGN KEY (`coupon_id`) REFERENCES `discount_coupons` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول خطط الاشتراك
CREATE TABLE `subscription_plans` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `price` decimal(10,2) NOT NULL DEFAULT 0.00,
    `currency` varchar(3) NOT NULL DEFAULT 'USD',
    `billing_cycle` enum('monthly','quarterly','yearly','one_time') NOT NULL DEFAULT 'monthly',
    `trial_days` int(11) NOT NULL DEFAULT 0,
    `features` json DEFAULT NULL,
    `limits` json DEFAULT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `is_popular` tinyint(1) NOT NULL DEFAULT 0,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول اشتراكات المعلنين
CREATE TABLE `advertiser_subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `advertiser_id` int(11) NOT NULL,
    `plan_id` int(11) NOT NULL,
    `status` enum('active','cancelled','expired','suspended','trial') NOT NULL DEFAULT 'trial',
    `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` timestamp NULL DEFAULT NULL,
    `trial_ends_at` timestamp NULL DEFAULT NULL,
    `cancelled_at` timestamp NULL DEFAULT NULL,
    `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
    `payment_method` varchar(50) DEFAULT NULL,
    `last_payment_at` timestamp NULL DEFAULT NULL,
    `next_payment_at` timestamp NULL DEFAULT NULL,
    `total_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
    `metadata` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_advertiser_id` (`advertiser_id`),
    KEY `idx_plan_id` (`plan_id`),
    KEY `idx_status` (`status`),
    KEY `idx_expires_at` (`expires_at`),
    FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول التقارير المالية
CREATE TABLE `financial_reports` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `report_type` enum('daily','weekly','monthly','quarterly','yearly','custom') NOT NULL,
    `period_start` date NOT NULL,
    `period_end` date NOT NULL,
    `total_revenue` decimal(12,2) NOT NULL DEFAULT 0.00,
    `total_fees` decimal(12,2) NOT NULL DEFAULT 0.00,
    `net_revenue` decimal(12,2) NOT NULL DEFAULT 0.00,
    `total_transactions` int(11) NOT NULL DEFAULT 0,
    `successful_transactions` int(11) NOT NULL DEFAULT 0,
    `failed_transactions` int(11) NOT NULL DEFAULT 0,
    `refunded_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
    `gateway_breakdown` json DEFAULT NULL,
    `currency_breakdown` json DEFAULT NULL,
    `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `generated_by` int(11) DEFAULT NULL,
    `file_path` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_report_type` (`report_type`),
    KEY `idx_period` (`period_start`, `period_end`),
    KEY `idx_generated_at` (`generated_at`),
    FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول إعدادات الضرائب
CREATE TABLE `tax_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `country_code` varchar(2) NOT NULL,
    `state_code` varchar(10) DEFAULT NULL,
    `tax_name` varchar(100) NOT NULL,
    `tax_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `applies_to` enum('all','businesses','individuals') NOT NULL DEFAULT 'all',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_country_state` (`country_code`, `state_code`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج بوابات الدفع الافتراضية
INSERT INTO `payment_gateways` (`gateway_name`, `display_name`, `is_active`, `supported_currencies`, `fee_percentage`, `fee_fixed`, `min_amount`, `description`, `sort_order`) VALUES
('stripe', 'Stripe', 1, '["USD", "EUR", "GBP", "SAR", "AED"]', 0.0290, 0.30, 0.50, 'بوابة دفع عالمية تدعم البطاقات الائتمانية والمحافظ الرقمية', 1),
('paypal', 'PayPal', 1, '["USD", "EUR", "GBP", "SAR"]', 0.0340, 0.00, 1.00, 'محفظة رقمية عالمية شائعة', 2),
('razorpay', 'Razorpay', 1, '["USD", "INR"]', 0.0200, 0.00, 1.00, 'بوابة دفع هندية تدعم طرق دفع متعددة', 3),
('paymob', 'PayMob', 1, '["EGP", "USD", "SAR", "AED"]', 0.0250, 0.00, 1.00, 'بوابة دفع شرق أوسطية', 4),
('tap', 'Tap Payments', 1, '["SAR", "AED", "KWD", "BHD"]', 0.0250, 0.00, 1.00, 'بوابة دفع خليجية', 5),
('mada', 'مدى', 1, '["SAR"]', 0.0150, 0.00, 1.00, 'نظام الدفع السعودي الوطني', 6),
('bank_transfer', 'تحويل بنكي', 1, '["USD", "EUR", "SAR", "AED"]', 0.0100, 0.00, 10.00, 'تحويل بنكي مباشر', 7);

-- إدراج خطط الاشتراك الافتراضية
INSERT INTO `subscription_plans` (`name`, `description`, `price`, `billing_cycle`, `features`, `limits`, `is_popular`, `sort_order`) VALUES
('الخطة الأساسية', 'مثالية للمعلنين الجدد والشركات الصغيرة', 29.99, 'monthly', '["حملات غير محدودة", "استهداف أساسي", "تقارير أساسية", "دعم عبر البريد الإلكتروني"]', '{"max_campaigns": 10, "max_ads_per_campaign": 5, "max_monthly_spend": 1000}', 0, 1),
('الخطة المتقدمة', 'للشركات المتوسطة التي تحتاج ميزات متقدمة', 79.99, 'monthly', '["حملات غير محدودة", "استهداف متقدم", "تقارير مفصلة", "اختبار A/B", "دعم هاتفي"]', '{"max_campaigns": 50, "max_ads_per_campaign": 20, "max_monthly_spend": 5000}', 1, 2),
('الخطة الاحترافية', 'للشركات الكبيرة والوكالات الإعلانية', 199.99, 'monthly', '["حملات غير محدودة", "استهداف ذكي", "تقارير متقدمة", "API مخصص", "مدير حساب مخصص"]', '{"max_campaigns": -1, "max_ads_per_campaign": -1, "max_monthly_spend": -1}', 0, 3),
('الخطة السنوية الأساسية', 'خصم 20% على الخطة الأساسية السنوية', 287.90, 'yearly', '["حملات غير محدودة", "استهداف أساسي", "تقارير أساسية", "دعم عبر البريد الإلكتروني"]', '{"max_campaigns": 10, "max_ads_per_campaign": 5, "max_monthly_spend": 1000}', 0, 4);

-- إدراج إعدادات الضرائب الافتراضية
INSERT INTO `tax_settings` (`country_code`, `tax_name`, `tax_rate`, `applies_to`) VALUES
('SA', 'ضريبة القيمة المضافة', 0.1500, 'all'),
('AE', 'ضريبة القيمة المضافة', 0.0500, 'all'),
('US', 'Sales Tax', 0.0875, 'all'),
('GB', 'VAT', 0.2000, 'all'),
('DE', 'Mehrwertsteuer', 0.1900, 'all'),
('FR', 'TVA', 0.2000, 'all'),
('EG', 'ضريبة القيمة المضافة', 0.1400, 'all');

-- إنشاء فهارس إضافية لتحسين الأداء
CREATE INDEX idx_invoices_advertiser_status ON invoices(advertiser_id, status);
CREATE INDEX idx_transactions_gateway_status ON payment_transactions(gateway, status);
CREATE INDEX idx_balance_advertiser_date ON advertiser_balance(advertiser_id, created_at);
CREATE INDEX idx_coupons_code_active ON discount_coupons(code, is_active);
CREATE INDEX idx_subscriptions_advertiser_status ON advertiser_subscriptions(advertiser_id, status);

-- إنشاء views مفيدة للتقارير
CREATE OR REPLACE VIEW v_advertiser_financial_summary AS
SELECT 
    u.id as advertiser_id,
    u.username,
    u.email,
    COALESCE(SUM(CASE WHEN ab.transaction_type = 'credit' THEN ab.amount ELSE -ab.amount END), 0) as current_balance,
    COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END), 0) as total_paid,
    COALESCE(SUM(CASE WHEN i.status = 'pending' THEN i.total_amount ELSE 0 END), 0) as pending_amount,
    COUNT(DISTINCT i.id) as total_invoices,
    COUNT(DISTINCT CASE WHEN i.status = 'paid' THEN i.id END) as paid_invoices
FROM users u
LEFT JOIN advertiser_balance ab ON u.id = ab.advertiser_id
LEFT JOIN invoices i ON u.id = i.advertiser_id
WHERE u.role = 'advertiser'
GROUP BY u.id, u.username, u.email;

CREATE OR REPLACE VIEW v_payment_gateway_performance AS
SELECT 
    pg.gateway_name,
    pg.display_name,
    COUNT(pt.id) as total_transactions,
    COUNT(CASE WHEN pt.status = 'completed' THEN 1 END) as successful_transactions,
    COUNT(CASE WHEN pt.status = 'failed' THEN 1 END) as failed_transactions,
    COALESCE(SUM(CASE WHEN pt.status = 'completed' THEN pt.amount ELSE 0 END), 0) as total_amount,
    COALESCE(SUM(CASE WHEN pt.status = 'completed' THEN pt.fee_amount ELSE 0 END), 0) as total_fees,
    COALESCE(AVG(CASE WHEN pt.status = 'completed' THEN pt.amount END), 0) as avg_transaction_amount
FROM payment_gateways pg
LEFT JOIN payment_transactions pt ON pg.gateway_name = pt.gateway
GROUP BY pg.gateway_name, pg.display_name;