<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}

require 'db.php';
require 'vendor/autoload.php';
use Iodev\Whois\Factory;

$message = null;

// Kirim notifikasi manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notify_now'])) {
  $id = (int)$_POST['notify_now'];

  $stmt = $pdo->prepare("SELECT d.url, w.expiry_date, w.days_left FROM domains d LEFT JOIN domain_whois w ON d.id = w.domain_id WHERE d.id = ?");
  $stmt->execute([$id]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  $set = $pdo->query("SELECT telegram_token, telegram_chat_id FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

  if ($data && $set && $set['telegram_token'] && $set['telegram_chat_id']) {
    $messageText = "🔔 *Notifikasi Domain Expired*\n\n" .
                   "🌐 *Domain:* `{$data['url']}`\n" .
                   "📆 *Expired:* " . ($data['expiry_date'] ?? '-') . "\n" .
                   "⏳ *Sisa Hari:* " . ($data['days_left'] ?? '-') . "\n" .
                   "🕒 Dikirim manual oleh admin.";

    $url = "https://api.telegram.org/bot{$set['telegram_token']}/sendMessage";
    $payload = [
      'chat_id' => $set['telegram_chat_id'],
      'text' => $messageText,
      'parse_mode' => 'Markdown',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POSTFIELDS => $payload,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $message = $err || !$res ? "❌ Gagal mengirim notifikasi ke Telegram." : "✅ Notifikasi berhasil dikirim.";
  } else {
    $message = "⚠️ Token atau Chat ID belum disetel.";
  }
}

// WHOIS manual semua domain
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_all'])) {
  $whoisClient = Factory::get()->createWhois();
  $success = 0; $failed = 0;

  $domainsToCheck = $pdo->query("SELECT id, url FROM domains")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($domainsToCheck as $d) {
    $id = $d['id'];
    $host = parse_url($d['url'], PHP_URL_HOST) ?? $d['url'];

    try {
      $info = $whoisClient->loadDomainInfo($host);
      if ($info && $info->getExpirationDate()) {
        $ts = $info->getExpirationDate();
        $days_left = floor(($ts - time()) / 86400);

        $stmt = $pdo->prepare("REPLACE INTO domain_whois (domain_id, expiry_date, days_left, checked_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$id, date('Y-m-d H:i:s', $ts), $days_left]);
        $success++;
      } else {
        $failed++;
      }
    } catch (Exception) {
      $failed++;
    }
  }

  $message = "✅ $success domain berhasil diperiksa. ❌ $failed gagal.";
}

// WHOIS manual per domain
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_now'])) {
  $id = (int)$_POST['check_now'];
  $stmt = $pdo->prepare("SELECT url FROM domains WHERE id = ?");
  $stmt->execute([$id]);
  $domain = $stmt->fetchColumn();

  if ($domain) {
    $whoisClient = Factory::get()->createWhois();
    $host = parse_url($domain, PHP_URL_HOST) ?? $domain;

    try {
      $info = $whoisClient->loadDomainInfo($host);
      if ($info && $info->getExpirationDate()) {
        $ts = $info->getExpirationDate();
        $days_left = floor(($ts - time()) / 86400);

        $stmt = $pdo->prepare("REPLACE INTO domain_whois (domain_id, expiry_date, days_left, checked_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$id, date('Y-m-d H:i:s', $ts), $days_left]);
        $message = "✅ WHOIS untuk $host berhasil diperbarui.";
      } else {
        $message = "⚠️ Tidak dapat mengambil data WHOIS untuk $host.";
      }
    } catch (Exception $e) {
      $message = "❌ Error WHOIS: " . $e->getMessage();
    }
  } else {
    $message = "❌ Domain tidak ditemukan.";
  }
}

// Ambil data domain + WHOIS
$stmt = $pdo->query("
  SELECT d.id, d.url, w.expiry_date, w.days_left, w.checked_at
  FROM domains d
  LEFT JOIN domain_whois w ON d.id = w.domain_id
  ORDER BY w.days_left ASC
");
$domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Notifikasi Expired Domain (WHOIS)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #f5f7fa; }
    .navbar-dark .navbar-nav .nav-link.active { font-weight: bold; color: #fff; }
    .container { max-width: 1200px; }
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
  <h2 class="mb-4">📅 Notifikasi Expired Domain (WHOIS)</h2>

  <?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Tombol Cek Semua -->
  <form method="post" class="mb-4">
    <input type="hidden" name="check_all" value="1">
    <button type="submit" class="btn btn-success">🔄 Cek Semua Sekarang</button>
  </form>

  <table class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Domain</th>
        <th>Kedaluwarsa</th>
        <th>Sisa Hari</th>
        <th>Diperiksa</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($domains as $i => $row): ?>
        <tr class="<?= ($row['days_left'] !== null && $row['days_left'] <= 7) ? 'table-danger' : '' ?>">
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($row['url']) ?></td>
          <td><?= $row['expiry_date'] ? date('Y-m-d', strtotime($row['expiry_date'])) : '-' ?></td>
          <td><?= $row['days_left'] !== null ? $row['days_left'] : '-' ?></td>
          <td><?= $row['checked_at'] ? date('Y-m-d H:i', strtotime($row['checked_at'])) : '-' ?></td>
          <td>
            <form method="post" class="d-inline">
              <input type="hidden" name="check_now" value="<?= $row['id'] ?>">
              <button type="submit" class="btn btn-sm btn-info">Cek WHOIS</button>
            </form>
            <form method="post" class="d-inline">
              <input type="hidden" name="notify_now" value="<?= $row['id'] ?>">
              <button type="submit" class="btn btn-sm btn-warning">Kirim Notif</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
