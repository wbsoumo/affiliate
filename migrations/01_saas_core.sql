-- =====================================================
-- Phase 1: SaaS Core Tables & Schema Migrations
-- =====================================================

-- 1. Create Tenants/Networks Table
CREATE TABLE IF NOT EXISTS `tenants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `company_name` VARCHAR(150),
    `owner_name` VARCHAR(100),
    `owner_email` VARCHAR(150),
    `status` ENUM('active', 'suspended', 'pending', 'deleted') DEFAULT 'active',
    `plan_name` VARCHAR(50) DEFAULT 'Starter',
    `max_affiliates` INT DEFAULT 100,
    `max_advertisers` INT DEFAULT 20,
    `max_offers` INT DEFAULT 100,
    `timezone` VARCHAR(50) DEFAULT 'UTC',
    `currency` VARCHAR(10) DEFAULT 'USD',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create Tenant Domains Table
CREATE TABLE IF NOT EXISTS `tenant_domains` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `domain` VARCHAR(150) NOT NULL UNIQUE,
    `type` ENUM('subdomain', 'custom', 'root') NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 0,
    `verification_status` ENUM('pending', 'verified', 'failed') DEFAULT 'verified',
    `ssl_status` ENUM('active', 'expired', 'none') DEFAULT 'none',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create Tenant Settings Table for branding and configurations
CREATE TABLE IF NOT EXISTS `tenant_settings` (
    `tenant_id` INT PRIMARY KEY,
    `site_name` VARCHAR(100) NOT NULL,
    `logo_path` VARCHAR(255) DEFAULT NULL,
    `favicon_path` VARCHAR(255) DEFAULT NULL,
    `primary_color` VARCHAR(10) DEFAULT '#2563eb',
    `support_email` VARCHAR(150) DEFAULT NULL,
    `tracking_domain` VARCHAR(150) DEFAULT NULL,
    `postback_domain` VARCHAR(150) DEFAULT NULL,
    
    -- SMTP settings
    `smtp_host` VARCHAR(150) DEFAULT NULL,
    `smtp_port` INT DEFAULT 587,
    `smtp_username` VARCHAR(150) DEFAULT NULL,
    `smtp_password` VARCHAR(150) DEFAULT NULL,
    `smtp_encryption` VARCHAR(10) DEFAULT 'tls',
    `smtp_from_email` VARCHAR(150) DEFAULT NULL,
    `smtp_from_name` VARCHAR(100) DEFAULT NULL,
    
    -- Payment settings
    `min_payout` DECIMAL(10,2) DEFAULT 50.00,
    `payout_frequency` VARCHAR(50) DEFAULT 'weekly',
    
    -- Signup settings
    `auto_approve_affiliates` TINYINT(1) DEFAULT 0,
    `auto_approve_advertisers` TINYINT(1) DEFAULT 0,
    
    -- KYC settings
    `require_kyc_affiliate` TINYINT(1) DEFAULT 0,
    `require_kyc_advertiser` TINYINT(1) DEFAULT 0,
    
    -- Terms/Privacy URLs
    `terms_url` VARCHAR(255) DEFAULT NULL,
    `privacy_url` VARCHAR(255) DEFAULT NULL,
    
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create Global Super Admins Table
CREATE TABLE IF NOT EXISTS `super_admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure missing dependency tables are created before adding tenant_id
CREATE TABLE IF NOT EXISTS `api_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `endpoint` VARCHAR(255),
    `method` VARCHAR(10),
    `request_data` TEXT,
    `response_data` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `error_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `error_code` INT,
    `requested_url` VARCHAR(255),
    `referrer_url` VARCHAR(255),
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `user_id` INT,
    `user_role` VARCHAR(50),
    `request_method` VARCHAR(10),
    `query_string` TEXT,
    `logged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `affiliate_bank_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT NOT NULL,
    `bank_name` VARCHAR(150),
    `account_number` VARCHAR(50),
    `account_holder` VARCHAR(100),
    `ifsc_code` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `offer_links` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `offer_id` INT NOT NULL,
    `affiliate_id` INT NOT NULL,
    `link_url` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `postback_logs_aff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT,
    `offer_id` INT,
    `conversion_id` INT,
    `fired_url` TEXT,
    `response_code` INT,
    `response_body` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `affiliate_postback_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL DEFAULT 1,
    `affiliate_id` INT,
    `offer_id` INT,
    `conversion_id` INT,
    `postback_url` TEXT,
    `http_code` INT,
    `response` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX IF NOT EXISTS `idx_tenant_affiliate_postback_logs` ON `affiliate_postback_logs` (`tenant_id`, `affiliate_id`, `offer_id`);


-- 5. Add tenant_id to users and adjust constraints
ALTER TABLE `users` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1 AFTER `user_id`;

-- Drop the old unique index on email
ALTER TABLE `users` DROP INDEX `email`;

-- Create new compound unique index for multi-tenancy
ALTER TABLE `users` ADD UNIQUE KEY `uniq_tenant_email` (`tenant_id`, `email`);

-- Add search indexes on users table
CREATE INDEX `idx_tenant_users_lookup` ON `users` (`tenant_id`, `email`, `role_id`, `status`);

-- 6. Add tenant_id to other tenant-owned tables
ALTER TABLE `offers` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1 AFTER `offer_id`;
CREATE INDEX `idx_tenant_offers` ON `offers` (`tenant_id`, `advertiser_id`, `status`);

ALTER TABLE `clicks` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1 AFTER `click_id`;
CREATE INDEX `idx_tenant_clicks` ON `clicks` (`tenant_id`, `offer_id`, `affiliate_id`, `click_id`, `created_at`);

ALTER TABLE `conversions` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1 AFTER `conversion_id`;
CREATE INDEX `idx_tenant_conversions` ON `conversions` (`tenant_id`, `offer_id`, `affiliate_id`, `advertiser_id`, `status`, `created_at`);

ALTER TABLE `affiliate_offer_approval` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1;
CREATE INDEX `idx_tenant_approval` ON `affiliate_offer_approval` (`tenant_id`, `affiliate_id`, `offer_id`);

ALTER TABLE `affiliate_postbacks` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1;
CREATE INDEX `idx_tenant_postbacks` ON `affiliate_postbacks` (`tenant_id`, `affiliate_id`);

ALTER TABLE `affiliate_offer_postbacks` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1;
CREATE INDEX `idx_tenant_offer_postbacks` ON `affiliate_offer_postbacks` (`tenant_id`, `affiliate_id`, `offer_id`);

ALTER TABLE `postback_logs` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1;
CREATE INDEX `idx_tenant_postback_logs` ON `postback_logs` (`tenant_id`, `click_id`);

ALTER TABLE `affiliate_bank_details` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1;
CREATE INDEX `idx_tenant_bank_details` ON `affiliate_bank_details` (`tenant_id`, `affiliate_id`);

ALTER TABLE `advertiser_ip_whitelist` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1;
CREATE INDEX `idx_tenant_ip_whitelist` ON `advertiser_ip_whitelist` (`tenant_id`, `advertiser_id`);

ALTER TABLE `api_logs` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1;
CREATE INDEX `idx_tenant_api_logs` ON `api_logs` (`tenant_id`, `user_id`);

ALTER TABLE `error_logs` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1;
CREATE INDEX `idx_tenant_error_logs` ON `error_logs` (`tenant_id`, `user_id`);

ALTER TABLE `offer_links` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1;
CREATE INDEX `idx_tenant_offer_links` ON `offer_links` (`tenant_id`, `offer_id`, `affiliate_id`);

ALTER TABLE `postback_logs_aff` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1;
CREATE INDEX `idx_tenant_postback_logs_aff` ON `postback_logs_aff` (`tenant_id`, `affiliate_id`);
