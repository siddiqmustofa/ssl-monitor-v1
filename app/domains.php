<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}
require 'db.php';

$domains = $pdo->query("SELECT * FROM domains ORDER BY last_checked DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelola Domain - SSL Monitor</title>
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
    .badge-status {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 8px;
    }
    td.domain-cell {
      text-align: left !important;
      font-weight: 500;
    }
  </style>
</head>
<body>
<div class="sidebar">
  <h5 class="text-white">🔐 SSL Monitor</h5>
  <hr class="border-light">
  <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
  <a href="domains.php" class="active"><i class="bi bi-globe2 me-2"></i>SSL</a>
  <a href="domain_expiry_notify.php"><i class="bi bi-calendar2-week me-2"></i>Cek Expired Domain</a>
  <a href="settings.php"><i class="bi bi-gear me-2"></i>Pengaturan</a>
  <a href="users.php"><i class="bi bi-people me-2"></i>Pengguna</a>
  <a href="logs.php"><i class="bi bi-clock-history me-2"></i>Log</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<div class="main">
  <h2 class="mb-4">🌐 Kelola Domain</h2>

  <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
    <div class="alert alert-success">✅ Domain berhasil ditambahkan.</div>
  <?php elseif (isset($_GET['error']) && $_GET['error'] === 'exists'): ?>
    <div class="alert alert-warning">⚠️ Domain sudah terdaftar.</div>
  <?php elseif (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
    <div class="alert alert-danger">❌ URL domain tidak valid.</div>
  <?php endif; ?>

  <form method="POST" action="api.php" class="row g-2 mb-4">
    <div class="col-md-10">
      <input type="text" name="url" class="form-control" placeholder="example.com atau https://example.com" required>
    </div>
    <div class="col-md-2 d-grid">
      <button type="submit" class="btn btn-primary">Tambah Domain</button>
    </div>
  </form>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Daftar Domain</span>
      <form method="POST" action="api.php" onsubmit="return confirm('Cek semua domain sekarang?')">
        <input type="hidden" name="check_all" value="1">
        <button class="btn btn-sm btn-outline-primary">🔁 Cek Semua</button>
      </form>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-center">
          <tr>
            <th class="text-start">Domain</th>
            <th>Valid Hingga</th>
            <th>Sisa Hari</th>
            <th>Terakhir Dicek</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($domains as $d): ?>
            <?php
              $badge = 'bg-secondary';
              if (is_null($d['days_left'])) $badge = 'bg-danger';
              elseif ($d['days_left'] <= 15) $badge = 'bg-warning';
              else $badge = 'bg-success';
            ?>
            <tr>
              <td class="domain-cell">
                <span class="badge-status <?= $badge ?>"></span><?= htmlspecialchars($d['url']) ?>
              </td>
              <td class="text-center"><?= $d['valid_to'] ?? '-' ?></td>
              <td class="text-center"><?= $d['days_left'] ?? '-' ?></td>
              <td class="text-center"><?= $d['last_checked'] ?? '-' ?></td>
              <td class="text-center">
                <form method="POST" action="api.php" class="d-inline">
                  <input type="hidden" name="check_id" value="<?= $d['id'] ?>">
                  <button class="btn btn-sm btn-primary" title="Cek SSL">🔁</button>
                </form>
                <form method="POST" action="api.php" class="d-inline">
                  <input type="hidden" name="notify_id" value="<?= $d['id'] ?>">
                  <button class="btn btn-sm btn-warning" title="Kirim Notif">🔔</button>
                </form>
                <form method="POST" action="api.php" class="d-inline" onsubmit="return confirm('Hapus domain ini?')">
                  <input type="hidden" name="delete_id" value="<?= $d['id'] ?>">
                  <button class="btn btn-sm btn-danger" title="Hapus">🗑</button>
                </form>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>

