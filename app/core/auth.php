<?php
/**
 * Authentication Core
 * PHP 7.1+
 * MULTI-TENANT & SECURE VERSION
 */

if (!defined('APP_INIT')) {
    die('Direct access not allowed');
}

// Ensure database connection is loaded first
require_once __DIR__ . '/../config/database.php';

/* =================================================
   SESSION INITIALIZATION (DO THIS ONCE, PROPERLY)
   ================================================= */

// Start session ONCE if not already active
if (session_status() === PHP_SESSION_NONE) {
    // Detect HTTPS
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? null) == 443
    );

    // Determine host-only session configuration
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = explode(':', $host)[0];

    // Configure session cookie params for host-only isolation
    session_set_cookie_params(
        0,          // lifetime (session)
        '/',        // path (ENTIRE DOMAIN)
        '',         // domain (empty string maps to exact host-only cookie)
        $isHttps,   // secure
        true        // httponly
    );

    // Resolve tenant to assign a unique session cookie name (isolates sessions across subdomains)
    $tenantId = current_tenant_id();
    $tenantSlug = 'global';
    if ($tenantId) {
        $tenant = current_tenant();
        if ($tenant) {
            $tenantSlug = $tenant['slug'];
        }
    }

    // Prefix the session name per tenant
    session_name('PHPSESSID_' . $tenantSlug);

    session_start();
}

/* =================================================
   AUTH FUNCTIONS
   ================================================= */

/**
 * Perform tenant-scoped user login (email + password)
 */
function auth_login($email, $password)
{
    global $pdo;

    $tenantId = current_tenant_id();
    if (!$tenantId) {
        return ['success' => false, 'error' => 'No tenant context resolved.'];
    }

    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.tenant_id,
            u.name,
            u.password_hash,
            u.status,
            r.role_name
        FROM users u
        INNER JOIN roles r ON r.role_id = u.role_id
        WHERE u.email = :email AND u.tenant_id = :tenant_id
        LIMIT 1
    ");
    $stmt->execute([
        'email' => $email,
        'tenant_id' => $tenantId
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid credentials'];
    }

    if ($user['status'] !== 'active') {
        return ['success' => false, 'error' => 'Account not active'];
    }

    // Prevent session fixation
    session_regenerate_id(true);

    // Store auth state (SINGLE SOURCE OF TRUTH)
    $_SESSION['auth'] = [
        'user_id'   => (int)$user['user_id'],
        'tenant_id' => (int)$user['tenant_id'],
        'role'      => $user['role_name'],
        'login_at'  => time()
    ];
    $_SESSION['user_name'] = $user['name'];

    // Update last login
    $upd = $pdo->prepare("
        UPDATE users
        SET last_login_ip = INET6_ATON(:ip),
            last_login_at = NOW()
        WHERE user_id = :uid AND tenant_id = :tenant_id
    ");
    $upd->execute([
        'ip'  => $_SERVER['REMOTE_ADDR'] ?? null,
        'uid' => $user['user_id'],
        'tenant_id' => $tenantId
    ]);

    return ['success' => true, 'role' => $user['role_name']];
}

/**
 * Perform global Super Admin login
 */
function auth_super_login($email, $password)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT id, name, password_hash, status 
        FROM super_admins 
        WHERE email = :email 
        LIMIT 1
    ");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid credentials'];
    }

    if ($admin['status'] !== 'active') {
        return ['success' => false, 'error' => 'Super admin account inactive'];
    }

    // Prevent session fixation
    session_regenerate_id(true);

    $_SESSION['super_auth'] = [
        'super_admin_id' => (int)$admin['id'],
        'role'           => 'super_admin',
        'name'           => $admin['name'],
        'login_at'       => time()
    ];

    return ['success' => true];
}

/**
 * Logout
 */
function auth_logout()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();
    }
}

/**
 * Is user logged in? (Enforces tenant isolation)
 */
function auth_check()
{
    if (defined('SUPER_ADMIN_CONTEXT') && SUPER_ADMIN_CONTEXT === true) {
        return isset($_SESSION['super_auth']['super_admin_id']);
    }

    if (!isset($_SESSION['auth']['user_id'])) {
        return false;
    }

    // Tenant isolation boundary: session tenant MUST match current resolved tenant
    if ((int)$_SESSION['auth']['tenant_id'] !== (int)current_tenant_id()) {
        return false;
    }

    return true;
}

/**
 * Get logged-in user ID
 */
function auth_user_id()
{
    if (defined('SUPER_ADMIN_CONTEXT') && SUPER_ADMIN_CONTEXT === true) {
        return isset($_SESSION['super_auth']['super_admin_id']) ? (int)$_SESSION['super_auth']['super_admin_id'] : null;
    }
    return auth_check() ? (int)$_SESSION['auth']['user_id'] : null;
}

/**
 * Get logged-in role
 */
function auth_role()
{
    if (defined('SUPER_ADMIN_CONTEXT') && SUPER_ADMIN_CONTEXT === true) {
        return 'super_admin';
    }
    return auth_check() ? $_SESSION['auth']['role'] : null;
}

/**
 * Require login
 */
function require_auth()
{
    if (defined('SUPER_ADMIN_CONTEXT') && SUPER_ADMIN_CONTEXT === true) {
        if (!isset($_SESSION['super_auth']['super_admin_id'])) {
            header('Location: /superadmin/login.php');
            exit;
        }
        return;
    }

    if (!auth_check()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Require specific role
 */
function require_role($role)
{
    if ($role === 'super_admin') {
        if (!isset($_SESSION['super_auth']['super_admin_id'])) {
            header('Location: /superadmin/login.php');
            exit;
        }
        return;
    }

    require_auth();

    if ($_SESSION['auth']['role'] !== $role) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Require any role from list
 */
function require_any_role(array $roles)
{
    if (in_array('super_admin', $roles, true) && isset($_SESSION['super_auth']['super_admin_id'])) {
        return;
    }

    require_auth();

    if (!in_array($_SESSION['auth']['role'], $roles, true)) {
        header('Location: /login.php');
        exit;
    }
}

// Automatically enforce active non-suspended tenant context for all pages including auth
require_tenant();

