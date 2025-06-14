<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}
require 'db.php';

$total = $pdo->query("SELECT COUNT(*) FROM domains")->fetchColumn();
$aman = $pdo->query("SELECT COUNT(*) FROM domains WHERE days_left > 15")->fetchColumn();
$expired = $pdo->query("SELECT COUNT(*) FROM domains WHERE days_left <= 15 AND days_left IS NOT NULL")->fetchColumn();
$error = $pdo->query("SELECT COUNT(*) FROM domains WHERE days_left IS NULL")->fetchColumn();

$whois_stats = $pdo->query("SELECT
  COUNT(*) AS total,
  SUM(CASE WHEN days_left > 30 THEN 1 ELSE 0 END) AS safe,
  SUM(CASE WHEN days_left <= 30 AND days_left IS NOT NULL THEN 1 ELSE 0 END) AS expiring,
  SUM(CASE WHEN days_left IS NULL THEN 1 ELSE 0 END) AS error,
  MAX(checked_at) AS last_checked
FROM domain_whois")->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - SSL Monitor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f5f7fa;
    }
    .card {
      border: none;
      border-radius: 12px;
    }
    .nav-link.active {
      font-weight: bold;
      color: #fff !important;
    }
    .widget-box {
      border-radius: 16px;
      background: white;
      box-shadow: 0 8px 20px rgba(0,0,0,0.05);
      padding: 24px;
      transition: all 0.2s;
    }
    .widget-box:hover {
      box-shadow: 0 12px 24px rgba(0,0,0,0.08);
      transform: translateY(-2px);
    }
    .stat-value {
      font-size: 2rem;
      font-weight: 600;
    }
    .stat-label {
      font-size: 0.9rem;
      color: #6c757d;
    }
    .chart-container {
      width: 100%;
      max-width: 500px;
      margin: auto;
    }
    a.widget-link {
      text-decoration: none;
      color: inherit;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
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
  <div class="row g-4">
    <div class="col-md-3">
      <a href="domains.php" class="widget-link">
        <div class="widget-box text-center">
          <div class="stat-value text-primary"><?= $total ?></div>
          <div class="stat-label">Total Domain</div>
        </div>
      </a>
    </div>
    <div class="col-md-3">
      <a href="domains.php?status=safe" class="widget-link">
        <div class="widget-box text-center">
          <div class="stat-value text-success"><?= $aman ?></div>
          <div class="stat-label">SSL Aman</div>
        </div>
      </a>
    </div>
    <div class="col-md-3">
      <a href="domains.php?status=expiring" class="widget-link">
        <div class="widget-box text-center">
          <div class="stat-value text-warning"><?= $expired ?></div>
          <div class="stat-label">Hampir Expired</div>
        </div>
      </a>
    </div>
    <div class="col-md-3">
      <a href="domains.php?status=error" class="widget-link">
        <div class="widget-box text-center">
          <div class="stat-value text-danger"><?= $error ?></div>
          <div class="stat-label">Error</div>
        </div>
      </a>
    </div>
  </div>

  <div class="row g-4 mt-4">
    <div class="col-md-12">
      <div class="card p-4">
        <h5 class="mb-3">📆 Domain WHOIS Expiry Overview</h5>
        <div class="row text-center">
          <div class="col-md-3">
            <div class="stat-value text-primary"><?= $whois_stats['total'] ?? 0 ?></div>
            <div class="stat-label">Total Domain</div>
          </div>
          <div class="col-md-3">
            <div class="stat-value text-success"><?= $whois_stats['safe'] ?? 0 ?></div>
            <div class="stat-label">WHOIS Aman</div>
          </div>
          <div class="col-md-3">
            <div class="stat-value text-warning"><?= $whois_stats['expiring'] ?? 0 ?></div>
            <div class="stat-label">Segera Expired</div>
          </div>
          <div class="col-md-3">
            <div class="stat-value text-danger"><?= $whois_stats['error'] ?? 0 ?></div>
            <div class="stat-label">WHOIS Error</div>
          </div>
        </div>
        <p class="text-end text-muted mt-2 mb-0"><small>Terakhir dicek: <?= $whois_stats['last_checked'] ?? '-' ?></small></p>
      </div>
    </div>
  </div>

  <div class="card mt-5 p-4">
    <h5 class="mb-4">Grafik Status SSL</h5>
    <div class="chart-container">
      <canvas id="sslChart"></canvas>
    </div>
  </div>
</div>

<script>
  new Chart(document.getElementById('sslChart'), {
    type: 'doughnut',
    data: {
      labels: ['Aman', 'Segera Expired', 'Error'],
      datasets: [{
        data: [<?= $aman ?>, <?= $expired ?>, <?= $error ?>],
        backgroundColor: ['#198754', '#ffc107', '#dc3545']
      }]
    },
    options: {
      plugins: {
        legend: { position: 'bottom' },
        title: { display: true, text: 'Grafik Status SSL Saat Ini' }
      }
    }
  });
</script>
</body>
</html>
