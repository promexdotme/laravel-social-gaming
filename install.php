<?php

// Web-Based Installer for Laravel Social Gaming
// Place in project root alongside database_backup.sql

$dumpFile = __DIR__ . '/database_backup.sql';
$envFile = __DIR__ . '/casino/.env';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/.env';
}

$message = '';
$messageType = '';
$isInstalled = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? '127.0.0.1';
    $dbPort = $_POST['db_port'] ?? '3306';
    $dbName = $_POST['db_name'] ?? 'laravel-social-gaming';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_pass'] ?? '';
    $appUrl = rtrim($_POST['app_url'] ?? (($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'], '/');

    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@admin.com');
    $adminPass = $_POST['admin_pass'] ?? 'admin123';

    try {
        // 1. Test Connection
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);

        // 2. Create Database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        // 3. Import SQL Dump File
        if (!file_exists($dumpFile)) {
            throw new Exception("Database dump file (database_backup.sql) not found in root!");
        }

        $sqlCommands = file_get_contents($dumpFile);
        $pdo->exec($sqlCommands);

        // 4. Update / Create Admin User
        $hashedPass = password_hash($adminPass, PASSWORD_BCRYPT);
        
        // Find existing admin or create new user
        $stmt = $pdo->prepare("SELECT id FROM `w_users` WHERE `role_id` = 1 OR `username` = 'admin' LIMIT 1");
        $stmt->execute();
        $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingAdmin) {
            $updateStmt = $pdo->prepare("UPDATE `w_users` SET `username` = ?, `email` = ?, `password` = ?, `status` = 'Active' WHERE `id` = ?");
            $updateStmt->execute([$adminUser, $adminEmail, $hashedPass, $existingAdmin['id']]);
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO `w_users` (`username`, `email`, `password`, `role_id`, `status`, `created_at`, `updated_at`) VALUES (?, ?, ?, 1, 'Active', NOW(), NOW())");
            $insertStmt->execute([$adminUser, $adminEmail, $hashedPass]);
        }

        // 5. Update .env File
        $envPaths = [__DIR__ . '/casino/.env', __DIR__ . '/.env'];
        foreach ($envPaths as $ePath) {
            if (file_exists($ePath) || file_exists(dirname($ePath))) {
                $envContent = file_exists($ePath) ? file_get_contents($ePath) : '';

                $replacements = [
                    'APP_URL' => $appUrl,
                    'DB_HOST' => $dbHost,
                    'DB_PORT' => $dbPort,
                    'DB_DATABASE' => $dbName,
                    'DB_USERNAME' => $dbUser,
                    'DB_PASSWORD' => $dbPass
                ];

                foreach ($replacements as $k => $v) {
                    if (preg_match("/^{$k}=.*/m", $envContent)) {
                        $envContent = preg_replace("/^{$k}=.*/m", "{$k}=\"{$v}\"", $envContent);
                    } else {
                        $envContent .= "\n{$k}=\"{$v}\"";
                    }
                }
                file_put_contents($ePath, trim($envContent) . "\n");
            }
        }

        $isInstalled = true;
        $message = "Installation Successful! Database dumped and Admin account configured.";
        $messageType = "success";

    } catch (Exception $e) {
        $message = "Installation Error: " . $e->getMessage();
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel Social Gaming - Fast Installer</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 40px 20px; display: flex; justify-content: center; }
        .installer-card { background: #1e293b; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5); width: 100%; max-width: 600px; padding: 32px; border: 1px solid #334155; }
        h1 { margin-top: 0; font-size: 24px; color: #38bdf8; text-align: center; }
        p.subtitle { text-align: center; color: #94a3b8; margin-bottom: 28px; }
        .section-title { font-size: 16px; font-weight: 600; color: #f1f5f9; margin-top: 24px; margin-bottom: 12px; border-bottom: 1px solid #334155; padding-bottom: 6px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 14px; color: #cbd5e1; margin-bottom: 6px; font-weight: 500; }
        input[type="text"], input[type="password"], input[type="email"] { width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid #475569; border-radius: 6px; color: #f8fafc; font-size: 14px; box-sizing: border-box; }
        input:focus { outline: none; border-color: #38bdf8; ring: 2px #38bdf8; }
        .btn-submit { width: 100%; padding: 12px; background: #0284c7; color: #ffffff; border: none; border-radius: 6px; font-weight: 600; font-size: 16px; cursor: pointer; margin-top: 20px; transition: background 0.2s; }
        .btn-submit:hover { background: #0369a1; }
        .alert { padding: 14px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #064e3b; color: #6ee7b7; border: 1px solid #047857; }
        .alert-error { background: #7f1d1d; color: #fca5a5; border: 1px solid #b91c1c; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    </style>
</head>
<body>

<div class="installer-card">
    <h1>🚀 Laravel Social Gaming Installer</h1>
    <p class="subtitle">Fast 1-Click Database Dump & Admin Setup</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($isInstalled): ?>
        <div style="text-align: center; margin-top: 20px;">
            <p style="color: #cbd5e1;">Your database and admin credentials have been configured successfully!</p>
            <a href="/" style="display: inline-block; padding: 12px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;">Launch Casino Dashboard &rarr;</a>
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <div class="section-title">1. Database Connection Credentials</div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>DB Host</label>
                    <input type="text" name="db_host" value="127.0.0.1" required>
                </div>
                <div class="form-group">
                    <label>DB Port</label>
                    <input type="text" name="db_port" value="3306" required>
                </div>
            </div>

            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" value="laravel-social-gaming" required>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>DB Username</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label>DB Password</label>
                    <input type="password" name="db_pass" value="">
                </div>
            </div>

            <div class="form-group">
                <label>Application Base URL</label>
                <input type="text" name="app_url" value="<?= (($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>" required>
            </div>

            <div class="section-title">2. New Admin Account Settings</div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Admin Username</label>
                    <input type="text" name="admin_user" value="admin" required>
                </div>
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" value="admin@admin.com" required>
                </div>
            </div>

            <div class="form-group">
                <label>Admin Password</label>
                <input type="password" name="admin_pass" value="admin123" required>
            </div>

            <button type="submit" class="btn-submit">Install & Import Database</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
