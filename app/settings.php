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
  <title>Pengaturan - SSL Monitor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f1f3f5;
    }
    .sidebar {
      height: 100vh;
      background-color: #343a40;
      color: white;
      position: fixed;
      width: 240px;
      top: 0;
      left: 0;
      padding: 1rem;
    }
    .sidebar a {
      color: #adb5bd;
      text-decoration: none;
      display: block;
      padding: 0.5rem 0;
    }
    .sidebar a.active, .sidebar a:hover {
      color: #ffffff;
      background-color: #495057;
      border-radius: 5px;
      padding-left: 10px;
    }
    .main {
      margin-left: 240px;
      padding: 2rem;
    }
  </style>
</head>
<body>
<div class="sidebar">
  <h5 class="text-white">🔐 SSL Monitor</h5>
  <hr class="border-light">
  <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
  <a href="domains.php"><i class="bi bi-globe2 me-2"></i>SSL</a>
  <a href="domain_expiry_notify.php"><i class="bi bi-calendar2-week me-2"></i>Cek Expired Domain</a>
  <a href="name.php" ><i class="bi bi-hdd-network me-2"></i>NAME</a>
  <a href="list_vm.php""><i class="bi bi-hdd-network me-2"></i>VM Biznet</a>
  <a href="settings.php" class="active"><i class="bi bi-gear me-2"></i>Pengaturan</a>
  <a href="users.php"><i class="bi bi-people me-2"></i>Pengguna</a>
  <a href="logs.php"><i class="bi bi-clock-history me-2"></i>Log</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<div class="main">
  <h2 class="mb-4">⚙️ Pengaturan</h2>
  <p>Atur token Telegram, chat ID, dan interval pengecekan otomatis di sini.</p>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Pengaturan berhasil disimpan.</div>
  <?php endif; ?>

  <form method="post" class="card p-4 bg-white shadow-sm">
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

    <div class="mb-4">
      <label for="check_interval" class="form-label">Interval Pengecekan Otomatis</label>
      <select name="check_interval" id="check_interval" class="form-select">
        <?php
        $selected = $settings['check_interval'] ?? 'daily';
        foreach (['daily' => 'Harian', 'weekly' => 'Mingguan', 'hourly' => 'Setiap Jam'] as $val => $label) {
          echo "<option value=\"$val\" " . ($selected === $val ? 'selected' : '') . ">$label</option>";
        }
        ?>
      </select>
    </div>

    <div class="d-grid">
      <button type="submit" class="btn btn-primary">💾 Simpan Pengaturan</button>
    </div>
  </form>
</div>
</body>
</html>
