<?php
/**
 * Temporary Super Admin Registration Tool
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true); // Bypass tenant check!

require_once __DIR__ . '/app/config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');

    if (empty($email) || empty($password) || empty($name)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO `super_admins` (`email`, `password_hash`, `name`, `status`)
                VALUES (:email, :hash, :name, 'active')
                ON DUPLICATE KEY UPDATE `password_hash` = :hash, `name` = :name, `status` = 'active'
            ");
            $stmt->execute([
                'email' => $email,
                'hash' => $hash,
                'name' => $name
            ]);
            $message = "Super Admin registered successfully! You can now log in at /superadmin/login.php. Remember to delete this file for security.";
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Temp Super Admin Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background-color: #1e293b; padding: 32px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { margin-top: 0; text-align: center; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 14px; margin-bottom: 6px; color: #94a3b8; }
        input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #475569; background-color: #334155; color: white; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #8b5cf6; border: none; border-radius: 6px; color: white; font-weight: 600; cursor: pointer; margin-top: 10px; }
        button:hover { background-color: #7c3aed; }
        .success { color: #4ade80; text-align: center; margin-bottom: 16px; font-size: 14px; }
        .error { color: #f87171; text-align: center; margin-bottom: 16px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Register Super Admin</h2>
        
        <?php if ($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" placeholder="John Doe" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="superadmin@saas.com" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit">Create Super Admin</button>
        </form>
    </div>
</body>
</html>
