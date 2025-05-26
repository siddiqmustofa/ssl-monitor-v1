<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}
require 'db.php';

// Ambil pengaturan saat ini
$stmt = $pdo->prepare("SELECT * FROM settings LIMIT 1");
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Simpan pengaturan jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = trim($_POST['telegram_token'] ?? '');
  $chat_id = trim($_POST['telegram_chat_id'] ?? '');
  $interval = trim($_POST['check_interval'] ?? 'daily');

  // Validasi interval yang diizinkan
  $valid_intervals = ['daily', 'weekly', 'hourly'];
  if (!in_array($interval, $valid_intervals)) {
    $interval = 'daily';
  }

  if ($settings) {
    $stmt = $pdo->prepare("UPDATE settings SET telegram_token = ?, telegram_chat_id = ?, check_interval = ?");
  } else {
    $stmt = $pdo->prepare("INSERT INTO settings (telegram_token, telegram_chat_id, check_interval) VALUES (?, ?, ?)");
  }
  $stmt->execute([$token, $chat_id, $interval]);
  header('Location: settings.php?success=1');
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengaturan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #f5f7fa; }
    .navbar-dark .navbar-nav .nav-link.active { font-weight: bold; color: #fff; }
    .container { max-width: 800px; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="#">🔐 SSL Monitor</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
       <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'domains.php' ? 'active' : '' ?>" href="domains.php">Domains</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'domain_expiry_notify.php' ? 'active' : '' ?>" href="domain_expiry_notify.php">Whois</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>" href="settings.php">Settings</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" href="users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : '' ?>" href="logs.php">Logs</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-5">
  <h2 class="mb-4">⚙️ Pengaturan</h2>
  <p>Atur token Telegram, chat ID, dan interval pengecekan otomatis di sini.</p>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Pengaturan berhasil disimpan.</div>
  <?php endif; ?>

  <form method="post" class="border p-4 bg-white shadow-sm rounded">
    <div class="mb-3">
      <label for="telegram_token" class="form-label">Telegram Bot Token</label>
      <input type="text" name="telegram_token" id="telegram_token" class="form-control"
             value="<?= htmlspecialchars($settings['telegram_token'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label for="telegram_chat_id" class="form-label">Telegram Chat ID</label>
      <input type="text" name="telegram_chat_id" id="telegram_chat_id" class="form-control"
             value="<?= htmlspecialchars($settings['telegram_chat_id'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label for="check_interval" class="form-label">Interval Pengecekan</label>
      <select name="check_interval" id="check_interval" class="form-select">
        <?php
        $selected = $settings['check_interval'] ?? 'daily';
        foreach (['daily' => 'Harian', 'weekly' => 'Mingguan', 'hourly' => 'Setiap Jam'] as $val => $label) {
          echo "<option value=\"$val\" " . ($selected === $val ? 'selected' : '') . ">$label</option>";
        }
        ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
