<?php
/**
 * Database Schema Importer
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true); // Bypass tenant check!

$configFile = __DIR__ . '/app/config/config.php';
if (!file_exists($configFile)) {
    die("Error: Config file not found at {$configFile}. Please create it first on your server with database credentials.");
}

require_once __DIR__ . '/app/config/database.php';

$message = '';
$error = '';

if (isset($_GET['run']) && $_GET['run'] === '1') {
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        $error = "Error: schema.sql file not found at {$schemaFile}.";
    } else {
        try {
            $sql = file_get_contents($schemaFile);
            
            // Set error mode to exception
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Execute the schema script
            $pdo->exec($sql);
            
            $message = "Database schema imported successfully! All tables have been created and seeded.";
        } catch (PDOException $e) {
            $error = "Database Import Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Schema Importer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background-color: #1e293b; padding: 40px; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); width: 100%; max-width: 480px; text-align: center; }
        h1 { margin-top: 0; font-size: 24px; color: white; }
        p { color: #94a3b8; font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .btn { display: inline-block; padding: 14px 28px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 15px; }
        .btn:hover { background-color: #2563eb; }
        .success { background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; padding: 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: left; }
        .error { background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; padding: 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: left; }
        .warning-box { background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; padding: 16px; border-radius: 8px; margin-top: 24px; font-size: 13px; text-align: left; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Database Importer</h1>
        <p>This utility will drop any existing tables and import the clean schema defined in <strong>schema.sql</strong> into your database.</p>
        
        <?php if ($message): ?>
            <div class="success">
                <strong>Success!</strong><br>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <a href="/superadmin/login.php" class="btn" style="background-color: #10b981;">Go to login portal</a>
        <?php elseif ($error): ?>
            <div class="error">
                <strong>Error!</strong><br>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="?run=1" class="btn">Try Again</a>
        <?php else: ?>
            <a href="?run=1" class="btn">Start Schema Import</a>
        <?php endif; ?>

        <div class="warning-box">
            <strong>⚠️ SECURITY WARNING:</strong><br>
            After the import completes, you <strong>MUST</strong> delete both <code>import_schema.php</code> and <code>schema.sql</code> from your server's root folder to prevent unauthorized database wipes.
        </div>
    </div>
</body>
</html>
