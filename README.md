# Multi-Tenant SaaS Affiliate Tracking Platform Upgrade

This project transforms a legacy single-tenant procedural PHP affiliate system (Taskbazi) into a robust, multi-tenant SaaS tracking platform. Multiple independent network customers run their isolated dashboards (`/admin`, `/affiliate`, `/advertiser`) on separate subdomains or custom domains resolved dynamically via hostnames, while a global super admin controls all configurations from `/superadmin`.

---

## Technical Stack & Features

- **Procedural PHP 7.1+ / MySQL** using direct PDO.
- **Explicit Tenant Scoping**: Every database select, insert, update, and delete query on tenant-owned tables is explicitly filtered by `tenant_id` at the SQL string level. No automatic query rewriters are used to avoid runtime parser complexities.
- **Development Safety Net**: A custom `GuardPDO` database wrapper class inspects execution strings in development. Any unscoped queries against tenant tables will trigger a PHP warning and log details to `logs/sql_guard.log`.
- **Dynamic Domain Router**: Routes requests dynamically by resolving hostnames (`HTTP_HOST`) against mappings in the `tenant_domains` table.
- **Global Super Admin Panel**: Accessible under `/superadmin/` (authentication via `super_admins` table) to manage networks, price limits, custom domains, and platform telemetry logs.
- **Tenant Impersonation**: Allows super admins to securely sign into any tenant's admin dashboard for assistance.

---

## Directory Layout & Key Components

- [app/config/database.php](file:///Users/wbsoumo/Desktop/affiliate/app/config/database.php) - Loads Git-ignored local DB credentials and instantiates the `GuardPDO` wrapper.
- [app/core/tenant.php](file:///Users/wbsoumo/Desktop/affiliate/app/core/tenant.php) - Domain resolution core, global helper functions, and the SQL safety check guard.
- [app/core/auth.php](file:///Users/wbsoumo/Desktop/affiliate/app/core/auth.php) - Prepend tenant slugs to cookie names (`PHPSESSID_[slug]`) and manages session boundaries.
- [superadmin/](file:///Users/wbsoumo/Desktop/affiliate/superadmin) - The global platform management views (`login.php`, `dashboard.php`, `tenants.php`, `domains.php`, `audit_logs.php`, etc.).

---

## Installation & Setup

### 1. Database Migrations
Import the SaaS core migrations and seed details to your local MySQL database:
1. Run `migrations/01_saas_core.sql` to add control tables and tenant indexes.
2. Run `migrations/02_seed_demo_tenant.sql` to seed the default super admin credentials and default localhost routing.

### 2. Configuration Setup
Create `app/config/config.php` (Git-ignored) in the configuration directory:
```php
<?php
return [
    'db_host' => 'localhost',
    'db_name' => 'helnovexaa_affiliate',
    'db_user' => 'root',
    'db_pass' => '',
];
```

### 3. Verification & Testing
To execute the automated verification test suite:
```bash
/Applications/XAMPP/xamppfiles/bin/php verify_tenancy.php
```

---

## Tenancy Helper Functions reference

Use the following globally exposed helpers inside tenant views:
- `current_tenant()` - returns details of resolved tenant.
- `current_tenant_id()` - returns integer ID of resolved tenant.
- `require_tenant()` - asserts tenant is active; handles suspended blocks.
- `tenant_where($alias = null)` - returns SQL string `tenant_id = :tenant_id`.
- `tenant_params()` - returns array `['tenant_id' => current_tenant_id()]`.
- `assert_same_tenant($table, $id)` - enforces cross-tenant security checks.
