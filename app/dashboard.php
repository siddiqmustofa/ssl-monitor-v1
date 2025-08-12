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
$latest = $pdo->query("SELECT * FROM domains ORDER BY last_checked DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - SSL Monitor</title>
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
    .card-summary {
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
  </style>
</head>
<body>
<div class="sidebar">
  <h5 class="text-white">🔐 SSL Monitor</h5>
  <hr class="border-light">
  <a href="dashboard.php" class="active"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
  <a href="domains.php"><i class="bi bi-globe2 me-2"></i>SSL</a>
  <a href="domain_expiry_notify.php"><i class="bi bi-calendar2-week me-2"></i>Cek Expired Domain</a>
  <a href="name.php"><i class="bi bi-gear me-2"></i>NAME</a>
  <a href="list_vm.php"><i class="bi bi-hdd-network me-2"></i>VM Biznet</a>

  <a href="settings.php"><i class="bi bi-gear me-2"></i>Pengaturan</a>
  <a href="users.php"><i class="bi bi-people me-2"></i>Pengguna</a>
  <a href="logs.php"><i class="bi bi-clock-history me-2"></i>Log</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<div class="main">
  <h2 class="mb-4">📊 Ringkasan SSL</h2>

  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card card-summary text-center p-3">
        <h6>Total</h6>
        <h2><?= $total ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-summary text-center p-3 text-success">
        <h6>Aman</h6>
        <h2><?= $aman ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-summary text-center p-3 text-warning">
        <h6>Segera Expired</h6>
        <h2><?= $expired ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-summary text-center p-3 text-danger">
        <h6>Error</h6>
        <h2><?= $error ?></h2>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-white fw-semibold">
      🔍 10 Domain Terakhir Dicek
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle text-center mb-0">
        <thead class="table-light">
          <tr>
            <th>Domain</th>
            <th>Valid Hingga</th>
            <th>Sisa Hari</th>
            <th>Terakhir Dicek</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($latest as $d): ?>
            <tr>
              <td><?= htmlspecialchars($d['url']) ?></td>
              <td><?= $d['valid_to'] ?? '-' ?></td>
              <td><?= $d['days_left'] ?? '-' ?></td>
              <td><?= $d['last_checked'] ?? '-' ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
