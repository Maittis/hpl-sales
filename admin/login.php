<?php

session_start();
require __DIR__ . '/../config.php';

$error = '';

if (!empty($_SESSION['hpl_admin'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $connection = db();
    if (!$connection) {
        $error = 'Database unavailable. Make sure MySQL is running and config.php points to the right server.';
    } else {
        $stmt = $connection->prepare('SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['hpl_admin'] = true;
            $_SESSION['hpl_admin_id'] = (int)$row['id'];
            header('Location: index.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login | HPL</title>
  <link rel="icon" type="image/png" sizes="16x16" href="../img/favicon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon-32.png">
  <link rel="icon" type="image/png" sizes="192x192" href="../img/favicon-192.png">
  <link rel="apple-touch-icon" href="../img/favicon-180.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing:border-box; }
    body { margin:0; background:#0a0f22; color:#182038; font-family:'DM Sans',sans-serif; display:flex; min-height:100vh; align-items:center; justify-content:center; padding:16px; }
    .card { background:#fff; border-radius:10px; max-width:400px; padding:34px; width:100%; }
    .brand { font-family:'Space Grotesk',sans-serif; font-size:20px; font-weight:700; color:#111a38; }
    h1 { font-family:'Space Grotesk',sans-serif; font-size:24px; margin:8px 0 4px; }
    .sub { color:#687083; font-size:14px; margin:0 0 20px; }
    .error { background:#fbeeec; border-left:3px solid #c0392b; color:#7a2c25; font-size:14px; margin-bottom:14px; padding:9px 12px; }
    label { display:block; font-size:13px; font-weight:700; margin:12px 0 4px; }
    input[type=text], input[type=password] { border:1px solid #cfd4dc; border-radius:6px; font-size:15px; padding:11px 12px; width:100%; }
    .btn { background:#d4a52c; border:0; border-radius:6px; color:#111a38; cursor:pointer; font-size:13px; font-weight:700; margin-top:20px; padding:13px 26px; text-transform:uppercase; width:100%; }
    .btn:hover { background:#f4ca5b; }
    .back { color:#687083; display:block; font-size:13px; margin-top:16px; text-align:center; text-decoration:underline; }
  </style>
</head>
<body>
  <div class="card">
    <div class="brand">HPL GOLD</div>
    <h1>Admin login</h1>
    <p class="sub">Sign in to edit the landing page.</p>
    <?php if ($error !== ''): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autofocus>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <button class="btn" type="submit">Log in</button>
    </form>
    <a class="back" href="../index.php">Back to site</a>
  </div>
</body>
</html>