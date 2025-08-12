<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}

require 'db.php';

// Konfigurasi pagination
$limit = 25;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filter pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = '';
$params = [];

if ($search !== '') {
  $searchQuery = 'AND d.url LIKE ?';
  $params[] = '%' . $search . '%';
}

// Hitung total log
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM logs l JOIN domains d ON l.domain_id = d.id WHERE 1=1 $searchQuery");
$countStmt->execute($params);
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// Ambil data log
$stmt = $pdo->prepare("SELECT l.*, d.url FROM logs l JOIN domains d ON l.domain_id = d.id WHERE 1=1 $searchQuery ORDER BY l.sent_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Log Notifikasi - SSL Monitor</title>
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
  <a href="settings.php"><i class="bi bi-gear me-2"></i>Pengaturan</a>
  <a href="users.php"><i class="bi bi-people me-2"></i>Pengguna</a>
  <a href="logs.php" class="active"><i class="bi bi-clock-history me-2"></i>Log</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<div class="main">
  <h2 class="mb-4">📋 Log Notifikasi</h2>

  <form class="row g-2 mb-4" method="get">
    <div class="col-md-4">
      <input type="text" name="search" class="form-control" placeholder="Cari domain..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-2 d-grid">
      <button type="submit" class="btn btn-primary">🔍 Cari</button>
    </div>
  </form>

  <div class="card">
    <div class="card-header">Riwayat Notifikasi</div>
    <div class="table-responsive">
      <table class="table table-hover align-middle text-center mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Domain</th>
            <th>Pesan</th>
            <th>Jenis</th>
            <th>Terkirim</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="5" class="text-muted">Tidak ada log ditemukan.</td></tr>
          <?php else: ?>
            <?php foreach ($logs as $index => $log): ?>
              <tr>
                <td><?= $offset + $index + 1 ?></td>
                <td><?= htmlspecialchars($log['url']) ?></td>
                <td><?= htmlspecialchars($log['message']) ?></td>
                <td><?= htmlspecialchars($log['type']) ?></td>
                <td><?= date('Y-m-d H:i:s', strtotime($log['sent_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>
</body>
</html>
