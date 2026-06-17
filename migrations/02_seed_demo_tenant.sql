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
