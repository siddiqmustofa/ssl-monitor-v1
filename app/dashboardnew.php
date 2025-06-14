<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}
require 'db.php';

$domains = $pdo->query("SELECT * FROM domains ORDER BY last_checked DESC LIMIT 10")->fetchAll();
$total = $pdo->query("SELECT COUNT(*) FROM domains")->fetchColumn();
$aman = $pdo->query("SELECT COUNT(*) FROM domains WHERE days_left > 15")->fetchColumn();
$expired = $pdo->query("SELECT COUNT(*) FROM domains WHERE days_left <= 15 AND days_left IS NOT NULL")->fetchColumn();
$error = $pdo->query("SELECT COUNT(*) FROM domains WHERE days_left IS NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - SSL Monitor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background: #0f172a;
      color: #e2e8f0;
      font-family: 'Inter', sans-serif;
    }
    .card {
      background-color: #1e293b;
      border: none;
      border-radius: 12px;
    }
    .card-header, .table thead {
      background-color: #334155;
      color: #e2e8f0;
    }
    .nav-link, .navbar-brand {
      color: #cbd5e1 !important;
    }
    .nav-link.active {
      font-weight: bold;
      color: #ffffff !important;
    }
    .table {
      color: #f1f5f9;
    }
    .form-control, .form-control:focus {
      background-color: #1e293b;
      border: 1px solid #475569;
      color: #e2e8f0;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
  <a class="navbar-brand" href="#">🔐 SSL Monitor</a>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="domains.php">Domains</a></li>
      <li class="nav-item"><a class="nav-link" href="domain_expiry_notify.php">Whois</a></li>
      <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
      <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
      <li class="nav-item"><a class="nav-link" href="logs.php">Logs</a></li>
      <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
    </ul>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-4 text-white">📊 SSL Monitor Overview</h2>

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card p-3 text-center"><h6>Total Domain</h6><h2><?= $total ?></h2></div></div>
    <div class="col-md-3"><div class="card p-3 text-center text-success"><h6>Aman</h6><h2><?= $aman ?></h2></div></div>
    <div class="col-md-3"><div class="card p-3 text-center text-warning"><h6>Segera Expired</h6><h2><?= $expired ?></h2></div></div>
    <div class="col-md-3"><div class="card p-3 text-center text-danger"><h6>Error</h6><h2><?= $error ?></h2></div></div>
  </div>

  <div class="card mb-4">
    <div class="card-header">Grafik Status SSL</div>
    <div class="card-body">
      <canvas id="sslChart" style="max-width: 100%;"></canvas>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>📅 Terakhir Dicek</span>
          <input type="text" id="searchDomain" class="form-control form-control-sm w-50" placeholder="Cari domain...">
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-borderless mb-0">
            <thead><tr><th>Domain</th><th>Sisa Hari</th><th>Tanggal</th></tr></thead>
            <tbody>
              <?php foreach ($domains as $d): ?>
                <tr>
                  <td><?= htmlspecialchars($d['url']) ?></td>
                  <td><?= $d['days_left'] ?? '-' ?></td>
                  <td><?= $d['last_checked'] ?? '-' ?></td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header">ℹ️ Info Sistem</div>
        <div class="card-body">
          <p>🔔 Notifikasi otomatis akan dikirim ke Telegram jika SSL domain akan segera expired (≤15 hari).</p>
          <p>⚙️ Jadwal pengecekan dapat disesuaikan pada halaman <a href="settings.php" class="text-info">Pengaturan</a>.</p>
          <p>📈 Cek domain, pengelolaan, dan laporan terintegrasi dalam satu panel.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('searchDomain').addEventListener('input', function () {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('.table tbody tr').forEach(row => {
      const text = row.innerText.toLowerCase();
      row.style.display = text.includes(keyword) ? '' : 'none';
    });
  });

  new Chart(document.getElementById('sslChart'), {
    type: 'pie',
    data: {
      labels: ['Aman', 'Segera Expired', 'Error'],
      datasets: [{
        data: [<?= $aman ?>, <?= $expired ?>, <?= $error ?>],
        backgroundColor: ['#22c55e', '#facc15', '#ef4444']
      }]
    },
    options: {
      plugins: {
        legend: { position: 'bottom', labels: { color: '#e2e8f0' }},
        title: { display: true, text: 'Status SSL Saat Ini', color: '#f1f5f9' }
      }
    }
  });
</script>

</body>
</html>
