-- Full Database Schema Dump
-- Generated on 2026-06-17 13:11:08

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table structure for table `account_managers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `account_managers`;
CREATE TABLE `account_managers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `advertiser_invoices`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `advertiser_invoices`;
CREATE TABLE `advertiser_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `advertiser_id` bigint(20) unsigned NOT NULL,
  `invoice_id` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_id` (`invoice_id`),
  KEY `idx_advertiser` (`advertiser_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_advertiser_invoices` FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `advertiser_ip_activity`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `advertiser_ip_activity`;
CREATE TABLE `advertiser_ip_activity` (
  `activity_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `advertiser_id` bigint(20) unsigned NOT NULL,
  `ip_address` varbinary(16) NOT NULL,
  `action` varchar(100) NOT NULL,
  `meta` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`activity_id`),
  KEY `idx_advertiser_id` (`advertiser_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_advertiser_ip_activity` FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `advertiser_ip_whitelist`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `advertiser_ip_whitelist`;
CREATE TABLE `advertiser_ip_whitelist` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `advertiser_id` bigint(20) unsigned NOT NULL,
  `ip_address` varbinary(16) NOT NULL,
  `description` varchar(255) NOT NULL,
  `is_active` enum('active','inactive','rejected') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_adv_ip` (`advertiser_id`,`ip_address`),
  KEY `idx_tenant_ip_whitelist` (`tenant_id`,`advertiser_id`),
  CONSTRAINT `advertiser_ip_whitelist_ibfk_1` FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `advertiser_payment_methods`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `advertiser_payment_methods`;
CREATE TABLE `advertiser_payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `advertiser_id` bigint(20) unsigned NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `account_details` text NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_advertiser` (`advertiser_id`),
  KEY `idx_default` (`is_default`),
  KEY `idx_verified` (`is_verified`),
  CONSTRAINT `fk_advertiser_payment_methods` FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `advertiser_transactions`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `advertiser_transactions`;
CREATE TABLE `advertiser_transactions` (
  `transaction_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `advertiser_id` bigint(20) unsigned NOT NULL,
  `type` enum('deposit','withdrawal','adjustment') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `reference_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `idx_advertiser` (`advertiser_id`),
  KEY `idx_type_status` (`type`,`status`),
  CONSTRAINT `fk_transaction_advertiser` FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `affiliate_bank_details`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `affiliate_bank_details`;
CREATE TABLE `affiliate_bank_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `affiliate_id` bigint(20) unsigned NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_holder` varchar(150) NOT NULL,
  `account_number` varchar(30) NOT NULL,
  `ifsc_code` varchar(11) NOT NULL,
  `upi_id` varchar(150) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_affiliate` (`affiliate_id`),
  KEY `idx_tenant_bank_details` (`tenant_id`,`affiliate_id`),
  CONSTRAINT `fk_affiliate_bank_user` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `affiliate_bank_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `affiliate_bank_logs`;
CREATE TABLE `affiliate_bank_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `affiliate_id` bigint(20) unsigned NOT NULL,
  `action` enum('created','updated') NOT NULL,
  `ip_address` varbinary(16) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `affiliate_offer_approval`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `affiliate_offer_approval`;
CREATE TABLE `affiliate_offer_approval` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `affiliate_id` bigint(20) unsigned NOT NULL,
  `offer_id` bigint(20) unsigned NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `payout_type` enum('default','custom') NOT NULL DEFAULT 'default',
  `custom_payout` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_aff_offer` (`affiliate_id`,`offer_id`),
  UNIQUE KEY `uk_affiliate_offer` (`affiliate_id`,`offer_id`),
  KEY `status` (`status`),
  KEY `idx_affiliate` (`affiliate_id`),
  KEY `idx_offer` (`offer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_tenant_approval` (`tenant_id`,`affiliate_id`,`offer_id`),
  CONSTRAINT `affiliate_offer_approval_ibfk_1` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `affiliate_offer_approval_ibfk_2` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`offer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `affiliate_offer_postbacks`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `affiliate_offer_postbacks`;
CREATE TABLE `affiliate_offer_postbacks` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `affiliate_id` bigint(20) NOT NULL,
  `offer_id` bigint(20) NOT NULL,
  `event` enum('conversion') DEFAULT 'conversion',
  `fire_status` enum('approved','pending','rejected') DEFAULT 'approved',
  `postback_url` text NOT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_aff_offer` (`affiliate_id`,`offer_id`),
  KEY `idx_tenant_offer_postbacks` (`tenant_id`,`affiliate_id`,`offer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `affiliate_payout_requests`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `affiliate_payout_requests`;
CREATE TABLE `affiliate_payout_requests` (
  `payout_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `affiliate_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`payout_id`),
  KEY `idx_affiliate` (`affiliate_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_payout_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `affiliate_postback_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `affiliate_postback_logs`;
CREATE TABLE `affiliate_postback_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  `affiliate_id` int(11) DEFAULT NULL,
  `offer_id` int(11) DEFAULT NULL,
  `conversion_id` int(11) DEFAULT NULL,
  `postback_url` text DEFAULT NULL,
  `http_code` int(11) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_affiliate_postback_logs` (`tenant_id`,`affiliate_id`,`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `affiliate_postbacks`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `affiliate_postbacks`;
CREATE TABLE `affiliate_postbacks` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `affiliate_id` bigint(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `postback_type` enum('global','offer') NOT NULL DEFAULT 'global',
  `offer_id` bigint(20) unsigned DEFAULT NULL,
  `event` enum('conversion') DEFAULT 'conversion',
  `fire_status` enum('approved','pending','rejected') DEFAULT 'approved',
  `postback_url` text NOT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_affiliate_offer` (`affiliate_id`,`offer_id`),
  KEY `idx_tenant_postbacks` (`tenant_id`,`affiliate_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `api_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `api_logs`;
CREATE TABLE `api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `request_data` text DEFAULT NULL,
  `response_data` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_api_logs` (`tenant_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `clicks`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `clicks`;
CREATE TABLE `clicks` (
  `click_id` char(32) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  `offer_id` bigint(20) unsigned NOT NULL,
  `affiliate_id` bigint(20) unsigned NOT NULL,
  `sub1` varchar(100) DEFAULT NULL,
  `sub2` varchar(100) DEFAULT NULL,
  `sub3` varchar(100) DEFAULT NULL,
  `sub4` varchar(100) DEFAULT NULL,
  `sub5` varchar(100) DEFAULT NULL,
  `ip_address` varbinary(16) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `country` char(2) DEFAULT NULL,
  `device` enum('desktop','mobile','tablet','bot','unknown') DEFAULT 'unknown',
  `browser` varchar(100) DEFAULT NULL,
  `is_unique` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`click_id`),
  KEY `offer_id` (`offer_id`),
  KEY `affiliate_id` (`affiliate_id`),
  KEY `created_at` (`created_at`),
  KEY `ip_address` (`ip_address`),
  KEY `idx_clicks_offer` (`offer_id`),
  KEY `idx_clicks_sub1` (`sub1`),
  KEY `idx_clicks_affiliate` (`affiliate_id`),
  KEY `idx_clicks_created` (`created_at`),
  KEY `idx_clicks_ip` (`ip_address`),
  KEY `idx_clicks_click_id` (`click_id`),
  KEY `idx_tenant_clicks` (`tenant_id`,`offer_id`,`affiliate_id`,`click_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `conversions`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `conversions`;
CREATE TABLE `conversions` (
  `conversion_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  `click_id` char(32) NOT NULL,
  `offer_id` bigint(20) unsigned NOT NULL,
  `affiliate_id` bigint(20) unsigned NOT NULL,
  `advertiser_id` bigint(20) unsigned NOT NULL,
  `payout` decimal(10,2) NOT NULL,
  `revenue` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `source` enum('postback','manual','api') DEFAULT 'postback',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`conversion_id`),
  UNIQUE KEY `uniq_click` (`click_id`),
  KEY `affiliate_id` (`affiliate_id`),
  KEY `advertiser_id` (`advertiser_id`),
  KEY `offer_id` (`offer_id`),
  KEY `status` (`status`),
  KEY `idx_conv_offer` (`offer_id`),
  KEY `idx_conv_click` (`click_id`),
  KEY `idx_conv_status` (`status`),
  KEY `idx_conversions_click` (`click_id`),
  KEY `idx_conversions_status` (`status`),
  KEY `idx_conversions_created` (`created_at`),
  KEY `idx_tenant_conversions` (`tenant_id`,`offer_id`,`affiliate_id`,`advertiser_id`,`status`,`created_at`),
  CONSTRAINT `conversions_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`offer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `error_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `error_logs`;
CREATE TABLE `error_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error_code` int(11) DEFAULT NULL,
  `requested_url` varchar(255) DEFAULT NULL,
  `referrer_url` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `query_string` text DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_error_logs` (`tenant_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `offer_caps`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `offer_caps`;
CREATE TABLE `offer_caps` (
  `cap_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `offer_id` bigint(20) unsigned NOT NULL,
  `cap_type` enum('daily','total') NOT NULL,
  `cap_limit` int(10) unsigned NOT NULL,
  `current_count` int(10) unsigned DEFAULT 0,
  `last_reset` date DEFAULT NULL,
  PRIMARY KEY (`cap_id`),
  UNIQUE KEY `uniq_offer_cap` (`offer_id`,`cap_type`),
  CONSTRAINT `offer_caps_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`offer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `offer_links`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `offer_links`;
CREATE TABLE `offer_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `affiliate_id` bigint(20) unsigned NOT NULL,
  `offer_id` bigint(20) unsigned NOT NULL,
  `campaign` varchar(50) DEFAULT NULL,
  `sub1` varchar(255) DEFAULT NULL,
  `sub2` varchar(255) DEFAULT NULL,
  `sub3` varchar(255) DEFAULT NULL,
  `sub4` varchar(255) DEFAULT NULL,
  `sub5` varchar(255) DEFAULT NULL,
  `generated_url` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `affiliate_id` (`affiliate_id`),
  KEY `offer_id` (`offer_id`),
  KEY `idx_tenant_offer_links` (`tenant_id`,`offer_id`,`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `offers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `offers`;
CREATE TABLE `offers` (
  `offer_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  `advertiser_id` bigint(20) unsigned NOT NULL,
  `offer_name` varchar(255) NOT NULL,
  `offer_description` text DEFAULT NULL,
  `objective` enum('conversions','sale','app_install','leads','impressions','clicks') NOT NULL DEFAULT 'conversions',
  `kpi` varchar(255) DEFAULT NULL,
  `allowed_traffic` varchar(255) DEFAULT NULL,
  `offer_url` text NOT NULL,
  `postback_token` varchar(64) NOT NULL,
  `payout` decimal(10,2) NOT NULL,
  `payout_type` enum('cpa','cpl','cpi','revshare') NOT NULL DEFAULT 'cpa',
  `conversion_cap` int(10) unsigned DEFAULT NULL,
  `revenue` decimal(10,2) NOT NULL,
  `currency` char(3) DEFAULT 'USD',
  `status` enum('pending','approved','active','paused','rejected') NOT NULL DEFAULT 'pending',
  `visibility` enum('public','private') DEFAULT 'public',
  `daily_cap` int(10) unsigned DEFAULT NULL,
  `total_cap` int(10) unsigned DEFAULT NULL,
  `allowed_countries` varchar(255) DEFAULT NULL,
  `blocked_countries` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `category` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `device_type` enum('all','desktop','mobile','tablet') NOT NULL DEFAULT 'all',
  `preview_url` varchar(255) DEFAULT NULL,
  `campaign_url` text NOT NULL,
  `conversion_tracking` enum('postback','pixel') NOT NULL DEFAULT 'postback',
  `terms_required` tinyint(1) NOT NULL DEFAULT 0,
  `internal_note` text DEFAULT NULL,
  `geo` varchar(100) NOT NULL,
  `device_targeting` varchar(150) NOT NULL,
  `browser_targeting` varchar(150) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  KEY `advertiser_id` (`advertiser_id`),
  KEY `status` (`status`),
  KEY `idx_offers_advertiser` (`advertiser_id`),
  KEY `idx_tenant_offers` (`tenant_id`,`advertiser_id`,`status`),
  CONSTRAINT `offers_ibfk_1` FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `payouts`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `payouts`;
CREATE TABLE `payouts` (
  `payout_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `affiliate_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('requested','approved','paid','rejected') DEFAULT 'requested',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`payout_id`),
  KEY `affiliate_id` (`affiliate_id`),
  KEY `status` (`status`),
  CONSTRAINT `payouts_ibfk_1` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `postback_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `postback_logs`;
CREATE TABLE `postback_logs` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `raw_request` text NOT NULL,
  `ip_address` varbinary(16) DEFAULT NULL,
  `click_id` char(32) DEFAULT NULL,
  `status` enum('accepted','duplicate','invalid') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`log_id`),
  KEY `click_id` (`click_id`),
  KEY `status` (`status`),
  KEY `idx_postback_status` (`status`),
  KEY `idx_tenant_postback_logs` (`tenant_id`,`click_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `postback_logs_aff`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `postback_logs_aff`;
CREATE TABLE `postback_logs_aff` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `affiliate_id` bigint(20) DEFAULT NULL,
  `offer_id` bigint(20) DEFAULT NULL,
  `conversion_id` varchar(64) DEFAULT NULL,
  `payout` decimal(10,2) DEFAULT NULL,
  `fired_url` text DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fired_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_affiliate` (`affiliate_id`),
  KEY `idx_offer` (`offer_id`),
  KEY `idx_conversion` (`conversion_id`),
  KEY `idx_fired_at` (`fired_at`),
  KEY `idx_tenant_postback_logs_aff` (`tenant_id`,`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `roles`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `role_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `super_admins`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `super_admins`;
CREATE TABLE `super_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `system_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varbinary(16) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `tenant_domains`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tenant_domains`;
CREATE TABLE `tenant_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `domain` varchar(150) NOT NULL,
  `type` enum('subdomain','custom','root') NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `verification_status` enum('pending','verified','failed') DEFAULT 'verified',
  `ssl_status` enum('active','expired','none') DEFAULT 'none',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain` (`domain`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `tenant_domains_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `tenant_settings`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tenant_settings`;
CREATE TABLE `tenant_settings` (
  `tenant_id` int(11) NOT NULL,
  `site_name` varchar(100) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `favicon_path` varchar(255) DEFAULT NULL,
  `primary_color` varchar(10) DEFAULT '#2563eb',
  `support_email` varchar(150) DEFAULT NULL,
  `tracking_domain` varchar(150) DEFAULT NULL,
  `postback_domain` varchar(150) DEFAULT NULL,
  `smtp_host` varchar(150) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT 587,
  `smtp_username` varchar(150) DEFAULT NULL,
  `smtp_password` varchar(150) DEFAULT NULL,
  `smtp_encryption` varchar(10) DEFAULT 'tls',
  `smtp_from_email` varchar(150) DEFAULT NULL,
  `smtp_from_name` varchar(100) DEFAULT NULL,
  `min_payout` decimal(10,2) DEFAULT 50.00,
  `payout_frequency` varchar(50) DEFAULT 'weekly',
  `auto_approve_affiliates` tinyint(1) DEFAULT 0,
  `auto_approve_advertisers` tinyint(1) DEFAULT 0,
  `require_kyc_affiliate` tinyint(1) DEFAULT 0,
  `require_kyc_advertiser` tinyint(1) DEFAULT 0,
  `terms_url` varchar(255) DEFAULT NULL,
  `privacy_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`tenant_id`),
  CONSTRAINT `tenant_settings_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `tenants`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `owner_email` varchar(150) DEFAULT NULL,
  `status` enum('active','suspended','pending','deleted') DEFAULT 'active',
  `plan_name` varchar(50) DEFAULT 'Starter',
  `max_affiliates` int(11) DEFAULT 100,
  `max_advertisers` int(11) DEFAULT 20,
  `max_offers` int(11) DEFAULT 100,
  `timezone` varchar(50) DEFAULT 'UTC',
  `currency` varchar(10) DEFAULT 'USD',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  `role_id` tinyint(3) unsigned NOT NULL,
  `account_manager_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `telegram_id` varchar(100) DEFAULT NULL,
  `teams_id` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('pending','active','blocked') DEFAULT 'pending',
  `payout_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `kyc_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `company` varchar(150) DEFAULT NULL,
  `balance` decimal(12,2) DEFAULT 0.00,
  `last_login_ip` varbinary(16) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uniq_tenant_email` (`tenant_id`,`email`),
  KEY `role_id` (`role_id`),
  KEY `status` (`status`),
  KEY `fk_user_account_manager` (`account_manager_id`),
  KEY `idx_tenant_users_lookup` (`tenant_id`,`email`,`role_id`,`status`),
  CONSTRAINT `fk_user_account_manager` FOREIGN KEY (`account_manager_id`) REFERENCES `account_managers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------
-- Table structure for table `saas_plans`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `saas_plans`;
CREATE TABLE `saas_plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `price` VARCHAR(50) NOT NULL,
  `offers_limit` VARCHAR(50) NOT NULL,
  `publishers_limit` VARCHAR(50) NOT NULL,
  `advertisers_limit` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `color` VARCHAR(20) DEFAULT '#60a5fa',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default plans
INSERT INTO `saas_plans` (`name`, `price`, `offers_limit`, `publishers_limit`, `advertisers_limit`, `description`, `color`)
VALUES 
('Starter', '$99/mo', '100', '100', '20', 'Great for starting out or testing workflows.', '#60a5fa'),
('Professional', '$299/mo', '500', '500', '100', 'Designed for growing affiliate networks.', '#c084fc'),
('Enterprise', '$999/mo', 'Unlimited', 'Unlimited', 'Unlimited', 'Uncapped limits and VIP support for large operations.', '#34d399')
ON DUPLICATE KEY UPDATE `price` = VALUES(`price`);

SET FOREIGN_KEY_CHECKS = 1;
-- =====================================================
-- Phase 1: Seed SaaS Super Admin and Default Tenant
-- =====================================================

-- 1. Seed Global Super Admin
-- Password hash for 'admin123'
INSERT INTO `super_admins` (`email`, `password_hash`, `name`, `status`) 
VALUES ('superadmin@saas.com', '$2y$10$ljLXAKtAp1cq3TcR3N/Vo.O8R1UbdjgJxBpVq.dP22SarVw.opwTm', 'Global Super Admin', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 2. Create Default Tenant
-- ID will be 1
INSERT INTO `tenants` (`id`, `name`, `slug`, `company_name`, `owner_name`, `owner_email`, `status`, `plan_name`)
VALUES (1, 'Taskbazi', 'default', 'Taskbazi Inc.', 'Soumojit Saha', 'wbsoumo@gmail.com', 'active', 'Enterprise')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `slug` = VALUES(`slug`);

-- 3. Set up domains for Default Tenant
-- Map 'taskbazi.xyz' and 'localhost' so it resolves automatically
INSERT INTO `tenant_domains` (`tenant_id`, `domain`, `type`, `is_primary`, `verification_status`, `ssl_status`)
VALUES 
(1, 'taskbazi.xyz', 'root', 1, 'verified', 'none'),
(1, 'localhost', 'root', 0, 'verified', 'none'),
(1, 'default.localhost', 'subdomain', 0, 'verified', 'none')
ON DUPLICATE KEY UPDATE `tenant_id` = VALUES(`tenant_id`);

-- 4. Set up settings for Default Tenant
INSERT INTO `tenant_settings` (
    `tenant_id`, `site_name`, `logo_path`, `favicon_path`, `primary_color`, `support_email`, 
    `min_payout`, `payout_frequency`, `auto_approve_affiliates`, `auto_approve_advertisers`
) VALUES (
    1, 'Taskbazi', '/favicon.png', '/favicon.png', '#2563eb', 'wbsoumo@gmail.com',
    50.00, 'weekly', 0, 0
)
ON DUPLICATE KEY UPDATE `site_name` = VALUES(`site_name`);

-- 5. Ensure all existing users and campaigns are tied to tenant 1 (already handled by DEFAULT 1, but this double-checks)
UPDATE `users` SET `tenant_id` = 1 WHERE `tenant_id` = 0;
UPDATE `offers` SET `tenant_id` = 1 WHERE `tenant_id` = 0;
UPDATE `clicks` SET `tenant_id` = 1 WHERE `tenant_id` = 0;
UPDATE `conversions` SET `tenant_id` = 1 WHERE `tenant_id` = 0;
