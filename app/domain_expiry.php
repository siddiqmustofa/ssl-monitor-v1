<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}
require 'db.php';

$domains = $pdo->query("SELECT * FROM domains ORDER BY url")->fetchAll();

function get_domain_expiry($domain) {
    $host = parse_url($domain, PHP_URL_HOST) ?? $domain;
    $result = shell_exec("whois " . escapeshellarg($host));

    if (!is_string($result)) {
        return null;
    }

    if (preg_match('/Expiry Date:\s?(.*?)\n/i', $result, $matches)) {
        return trim($matches[1]);
    } elseif (preg_match('/Registry Expiry Date:\s?(.*?)\n/i', $result, $matches)) {
        return trim($matches[1]);
    }

    return null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Domain Expiry - SSL Monitor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary px-3">
  <a class="navbar-brand d-flex align-items-center" href="#">
    <img src="https://umbrella.us.com/web/image/website/1/logo/Umbrella%20Corporation?unique=b3d9378" alt="Logo" height="28" class="me-2">
    SSL Monitor
  </a>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="domains.php">Kelola Domain</a></li>
      <li class="nav-item"><a class="nav-link active" href="domain_expiry.php">Cek Expired Domain</a></li>
      <li class="nav-item"><a class="nav-link" href="settings.php">Pengaturan</a></li>
      <li class="nav-item"><a class="nav-link" href="users.php">Pengguna</a></li>
      <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
    </ul>
  </div>
</nav>

<div class="container py-4">
  <h3 class="mb-4">📅 Cek Kedaluwarsa Domain</h3>
  <div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
      <thead class="table-light">
        <tr>
          <th>Domain</th>
          <th>Tanggal Kedaluwarsa</th>
          <th>Sisa Hari</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($domains as $d): ?>
          <?php
            $expDate = get_domain_expiry($d['url']);
            $days = '-';
            $status = '❓ Tidak Diketahui';
            if ($expDate) {
              $ts = strtotime($expDate);
              $now = time();
              $days = floor(($ts - $now) / 86400);
              if ($days > 30) $status = '✅ Aman';
              elseif ($days > 0) $status = '⚠️ Akan Expired';
              else $status = '❌ Expired';
            }
          ?>
          <tr>
            <td><?= htmlspecialchars($d['url']) ?></td>
            <td><?= $expDate ?? 'Tidak ditemukan' ?></td>
            <td><?= is_numeric($days) ? $days : '-' ?></td>
            <td><?= $status ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
