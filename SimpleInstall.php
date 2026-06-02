<?php
/**
 * Simple installer for lite13-prepacked.
 * - Collects DB host/user/pass (plus DB name) and detects hostname.
 * - Verifies MySQL connectivity.
 * - Imports lite13.sql into the selected database.
 * - Updates casino/.env (APP_URL, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).
 * - Surfaces notes about CDN whitelisting, default users, and web server config.
 */

$envPath = __DIR__ . '/casino/.env';
$envDefaults = [
    'DB_HOST' => 'localhost',
    'DB_DATABASE' => 'testcas',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '123456',
    'APP_URL' => 'https://testcas.test',
];

if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES) as $line) {
        if (strpos($line, '=') !== false) {
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            $k = trim($k);
            if (isset($envDefaults[$k])) {
                $envDefaults[$k] = trim($v);
            }
        }
    }
}

$status = [
    'db' => null,
    'import' => null,
    'env' => null,
    'password' => null,
    'warnings' => [],
];

$hostname = $_SERVER['HTTP_HOST'] ?? gethostname() ?? 'localhost';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? $envDefaults['DB_HOST']);
    $dbUser = trim($_POST['db_user'] ?? $envDefaults['DB_USERNAME']);
    $dbPass = trim($_POST['db_pass'] ?? $envDefaults['DB_PASSWORD']);
    $dbName = trim($_POST['db_name'] ?? $envDefaults['DB_DATABASE']);
    $hostname = trim($_POST['hostname'] ?? $hostname);
    $basePassword = trim($_POST['base_password'] ?? '');
    $isLocalHost = in_array(strtolower($hostname), ['localhost', '127.0.0.1', '::1'], true) || preg_match('/(localhost|127\\.0\\.0\\.1|::1)$/i', $hostname);

    if ($isLocalHost) {
        $status['warnings'][] = 'Games CDN will not work if your IP is not reachable and authorized. Msg us on Discord to whitelist one IP.';
    }

    // Step 1: DB connection check
    $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_errno) {
        $status['db'] = '[FAIL] Failed to connect: ' . htmlspecialchars($mysqli->connect_error, ENT_QUOTES);
    } else {
        $status['db'] = '[OK] Connected to MySQL successfully.';

        // Step 2: Import SQL
        $sqlFile = __DIR__ . '/lite13.sql';
        if (!file_exists($sqlFile)) {
            $status['import'] = '[FAIL] SQL file not found at ' . basename($sqlFile);
        } else {
            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                $status['import'] = '[FAIL] Could not read SQL file.';
            } else {
                if ($mysqli->multi_query($sql) === false) {
                    $status['import'] = '[FAIL] Import failed: ' . htmlspecialchars($mysqli->error, ENT_QUOTES);
                } else {
                    do {
                        if ($result = $mysqli->store_result()) {
                            $result->free();
                        }
                    } while ($mysqli->more_results() && $mysqli->next_result());

                    if ($mysqli->errno) {
                        $status['import'] = '[WARN] Import completed with errors: ' . htmlspecialchars($mysqli->error, ENT_QUOTES);
                    } else {
                        $status['import'] = '[OK] SQL imported from lite13.sql.';
                    }
                }
            }
        }

        // Step 3: Update .env
        $appUrl = 'https://' . rtrim($hostname, '/');
        $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';
        $envContent = updateEnvValue($envContent, 'APP_URL', $appUrl);
        $envContent = updateEnvValue($envContent, 'DB_HOST', $dbHost);
        $envContent = updateEnvValue($envContent, 'DB_DATABASE', $dbName);
        $envContent = updateEnvValue($envContent, 'DB_USERNAME', $dbUser);
        $envContent = updateEnvValue($envContent, 'DB_PASSWORD', $dbPass);

        if (file_put_contents($envPath, $envContent) === false) {
            $status['env'] = '[FAIL] Failed to write casino/.env.';
        } else {
            $status['env'] = '[OK] casino/.env updated (APP_URL, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).';
        }

        // Step 4: Update base user passwords if provided
        if ($basePassword !== '') {
            $hashed = password_hash($basePassword, PASSWORD_BCRYPT);
            $stmt = $mysqli->prepare("UPDATE w_users SET `password` = ? WHERE `username` IN ('admin','new')");
            if ($stmt === false) {
                $status['password'] = '[FAIL] Could not prepare password update.';
            } else {
                $stmt->bind_param('s', $hashed);
                if ($stmt->execute()) {
                    $status['password'] = '[OK] Updated passwords for users admin and new.';
                } else {
                    $status['password'] = '[FAIL] Error updating passwords: ' . htmlspecialchars($stmt->error, ENT_QUOTES);
                }
                $stmt->close();
            }
        }
    }
}

/**
 * Replace or append a key=value pair inside .env content.
 */
function updateEnvValue(string $content, string $key, string $value): string
{
    $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
    $line = $key . '=' . $value;
    if (preg_match($pattern, $content)) {
        return preg_replace($pattern, $line, $content);
    }
    $trimmed = rtrim($content, "\r\n");
    return ($trimmed === '' ? $line : $trimmed . PHP_EOL . $line) . PHP_EOL;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Simple Install</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; padding: 0; display: flex; min-height: 100vh; }
        .wrapper { margin: auto; width: 90%; max-width: 800px; background: #111827; border: 1px solid #1f2937; border-radius: 10px; padding: 24px 28px 32px; box-shadow: 0 20px 50px rgba(0,0,0,0.35); }
        h1 { margin-top: 0; color: #38bdf8; }
        label { display: block; margin-top: 14px; color: #cbd5e1; }
        input { width: 100%; padding: 10px 12px; margin-top: 6px; border-radius: 6px; border: 1px solid #1f2937; background: #0b1220; color: #e2e8f0; }
        button { margin-top: 16px; width: 100%; padding: 12px; background: #22c55e; border: none; color: #0b1220; font-weight: bold; border-radius: 8px; cursor: pointer; }
        button:hover { background: #16a34a; }
        .status { margin-top: 16px; padding: 12px; border-radius: 8px; background: #0b1220; border: 1px solid #1f2937; }
        .warn { background: #312e81; border-color: #4338ca; color: #c4d4ff; margin-top: 12px; padding: 10px; border-radius: 6px; }
        .notes { margin-top: 18px; padding: 12px; border-radius: 8px; background: #0b1220; border: 1px solid #1f2937; line-height: 1.5; }
        a { color: #38bdf8; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px 16px; }
        .loading { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); color: #f8fafc; align-items: center; justify-content: center; flex-direction: column; z-index: 9999; text-align: center; }
        .spinner { width: 48px; height: 48px; border: 4px solid #334155; border-top-color: #38bdf8; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 12px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="wrapper">
        <h1>Simple Install</h1>
        <form method="post" id="install-form">
            <div class="grid">
                <div>
                    <label for="db_host">DB Host</label>
                    <input id="db_host" name="db_host" required value="<?php echo htmlspecialchars($_POST['db_host'] ?? $envDefaults['DB_HOST'], ENT_QUOTES); ?>">
                </div>
                <div>
                    <label for="db_user">DB User</label>
                    <input id="db_user" name="db_user" required value="<?php echo htmlspecialchars($_POST['db_user'] ?? $envDefaults['DB_USERNAME'], ENT_QUOTES); ?>">
                </div>
                <div>
                    <label for="db_pass">DB Password</label>
                    <input id="db_pass" name="db_pass" type="password" value="<?php echo htmlspecialchars($_POST['db_pass'] ?? $envDefaults['DB_PASSWORD'], ENT_QUOTES); ?>">
                </div>
                <div>
                    <label for="db_name">DB Name</label>
                    <input id="db_name" name="db_name" required value="<?php echo htmlspecialchars($_POST['db_name'] ?? $envDefaults['DB_DATABASE'], ENT_QUOTES); ?>">
                </div>
                <div>
                    <label for="hostname">Detected Hostname</label>
                    <input id="hostname" name="hostname" value="<?php echo htmlspecialchars($hostname, ENT_QUOTES); ?>">
                </div>
                <div>
                    <label for="base_password">New Password (admin & new)</label>
                    <input id="base_password" name="base_password" type="password" placeholder="Leave blank to skip">
                </div>
            </div>
            <button type="submit">Run Quick Install</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="status">
                <?php
                echo $status['db'] ? '<div>' . $status['db'] . '</div>' : '';
                echo $status['import'] ? '<div>' . $status['import'] . '</div>' : '';
                echo $status['env'] ? '<div>' . $status['env'] . '</div>' : '';
                echo $status['password'] ? '<div>' . $status['password'] . '</div>' : '';
                ?>
            </div>
            <?php if (!empty($status['warnings'])): ?>
                <?php foreach ($status['warnings'] as $warn): ?>
                    <div class="warn"><?php echo $warn; ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>

        <div class="notes">
            <strong>Defaults & Security:</strong> Base users <code>admin</code> and <code>new</code> have password <code>123456</code>. Change them: pick a new password, hash it with Laravel's <code>password_hash(..., PASSWORD_BCRYPT)</code>, and update <code>w_users</code>.
            <br><br>
            <strong>CDN / Hosting:</strong> If you self-host, point the CDN to your own games URL. Games CDN will not work if your IP is not reachable/authorized; ping us on Discord to whitelist one IP.
            <br><br>
            <strong>Web server:</strong> .htaccess is already set. For nginx, see <code>NOTES.md</code>.
        </div>
    </div>
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <div>Importing database... please wait</div>
    </div>
    <script>
        const form = document.getElementById('install-form');
        const loading = document.getElementById('loading');
        if (form && loading) {
            form.addEventListener('submit', () => {
                loading.style.display = 'flex';
            });
        }
    </script>
</body>
</html>
