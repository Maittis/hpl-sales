<?php
require_once __DIR__ . '/config.php';

$steps = [];
$ok = false;
$fatal = null;
$adminDone = false;
$adminError = '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS);
    $connection->set_charset(DB_CHARSET);

    try {
        $connection->query('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET ' . DB_CHARSET . ' COLLATE ' . DB_CHARSET . '_unicode_ci');
        $steps[] = 'Database "' . DB_NAME . '" created / verified.';
    } catch (mysqli_sql_exception $e) {
        $steps[] = 'Using existing database "' . DB_NAME . '".';
    }

    $connection->select_db(DB_NAME);

    $connection->query('CREATE TABLE IF NOT EXISTS settings (key_name VARCHAR(80) PRIMARY KEY, value MEDIUMTEXT) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);
    $connection->query('CREATE TABLE IF NOT EXISTS admins (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(60) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);
    $connection->query('CREATE TABLE IF NOT EXISTS leads (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, phone VARCHAR(40) NOT NULL, knowledge TEXT NOT NULL, wants_to_learn VARCHAR(10) NOT NULL, submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

    $colCheck = $connection->query("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . $connection->real_escape_string(DB_NAME) . "' AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'status'");
    if ((int)$colCheck->fetch_assoc()['c'] === 0) {
        $connection->query("ALTER TABLE leads ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'new'");
        $connection->query("ALTER TABLE leads ADD COLUMN contacted_at TIMESTAMP NULL DEFAULT NULL");
    }

    $steps[] = 'Tables "settings", "admins" and "leads" created / verified.';

    $seed = $connection->prepare('INSERT IGNORE INTO settings (key_name, value) VALUES (?, ?)');
    foreach (hpl_defaults() as $key => $value) {
        $seed->bind_param('ss', $key, $value);
        $seed->execute();
    }
    $steps[] = 'Default page content seeded (' . count(hpl_defaults()) . ' fields).';
    $ok = true;

    $adminCount = (int)$connection->query('SELECT COUNT(*) AS c FROM admins')->fetch_assoc()['c'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if ($username === '' || strlen($password) < 6) {
            $adminError = 'Enter a username and a password of at least 6 characters.';
        } elseif ($password !== $confirm) {
            $adminError = 'Passwords do not match.';
        } else {
            $exists = $connection->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
            $exists->bind_param('s', $username);
            $exists->execute();
            if ($exists->get_result()->fetch_assoc()) {
                $adminError = 'That username already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $insert = $connection->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
                $insert->bind_param('ss', $username, $hash);
                $insert->execute();
                $adminDone = true;
                $adminCount = 1;
            }
        }
    }
} catch (mysqli_sql_exception $e) {
    $fatal = $e->getMessage();
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HPL Setup</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing:border-box; }
    body { margin:0; background:#0a0f22; color:#182038; font-family:'DM Sans',sans-serif; padding:40px 16px; }
    .card { background:#fff; border-radius:10px; margin:0 auto; max-width:560px; padding:34px; }
    h1 { font-family:'Space Grotesk',sans-serif; font-size:28px; margin:0 0 6px; }
    .sub { color:#687083; font-size:14px; margin:0 0 22px; }
    .step { background:#f0f7ef; border-left:3px solid #2e8b57; color:#23402f; font-size:14px; margin:4px 0; padding:9px 12px; }
    .error { background:#fbeeec; border-left:3px solid #c0392b; color:#7a2c25; font-size:14px; margin:8px 0; padding:9px 12px; }
    label { display:block; font-size:13px; font-weight:700; margin:14px 0 4px; }
    input[type=text], input[type=password] { border:1px solid #cfd4dc; border-radius:6px; font-size:15px; padding:11px 12px; width:100%; }
    .btn { background:#d4a52c; border:0; border-radius:6px; color:#111a38; cursor:pointer; display:inline-block; font-size:13px; font-weight:700; margin-top:20px; padding:13px 26px; text-transform:uppercase; }
    .btn:hover { background:#f4ca5b; }
    a.link { color:#2c3e6e; font-size:13px; text-decoration:underline; }
    .divider { border-top:1px solid #e3e6ea; margin:24px 0; }
  </style>
</head>
<body>
  <div class="card">
    <h1>HPL Sales — Setup</h1>
    <p class="sub">One-time installer for the landing page admin panel.</p>

    <?php if ($fatal !== null): ?>
      <div class="error">Database error: <?= h($fatal) ?>. Check DB_HOST / DB_USER / DB_PASS in <b>config.php</b> and confirm MySQL is running.</div>
    <?php else: ?>
      <?php foreach ($steps as $step): ?>
        <div class="step"><?= h($step) ?></div>
      <?php endforeach; ?>

      <div class="divider"></div>

      <?php if ($adminDone): ?>
        <div class="step">Admin account created. You can now log in.</div>
        <p style="font-size:14px;color:#687083">Admin login: <a class="link" href="admin/login.php">admin/login.php</a> &nbsp;·&nbsp; <a class="link" href="index.php">View site</a></p>
      <?php elseif ($adminCount > 0): ?>
        <p style="font-size:14px;color:#687083">An admin account already exists. Go to <a class="link" href="admin/login.php">admin/login.php</a>. <a class="link" href="index.php">View site</a></p>
      <?php else: ?>
        <p style="font-size:15px;font-weight:700">Create the admin account</p>
        <?php if ($adminError !== ''): ?><div class="error"><?= h($adminError) ?></div><?php endif; ?>
        <form method="post" autocomplete="off">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required>
          <label for="password">Password (min 6 characters)</label>
          <input type="password" id="password" name="password" required>
          <label for="confirm">Confirm password</label>
          <input type="password" id="confirm" name="confirm" required>
          <button class="btn" type="submit" name="create_admin" value="1">Create admin &amp; finish</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
