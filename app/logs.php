<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}

require 'db.php';

// --- Konfigurasi pagination ---
$limit = 25;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// --- Filter pencarian domain ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = '';
$params = [];

if ($search !== '') {
  $searchQuery = 'AND d.url LIKE ?';
  $params[] = '%' . $search . '%';
}

// --- Hitung total log untuk pagination ---
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM logs l JOIN domains d ON l.domain_id = d.id WHERE 1=1 $searchQuery");
$countStmt->execute($params);
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// --- Ambil log untuk halaman ini ---
$stmt = $pdo->prepare("SELECT l.*, d.url FROM logs l JOIN domains d ON l.domain_id = d.id WHERE 1=1 $searchQuery ORDER BY l.sent_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Notification Logs</title>
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
  <h2 class="mb-4">📋 Notification Logs</h2>

  <form class="row mb-4" method="get">
    <div class="col-md-4">
      <input type="text" name="search" class="form-control" placeholder="Search domain..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary">Search</button>
    </div>
  </form>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Domain</th>
        <th>Message</th>
        <th>Type</th>
        <th>Sent At</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="5" class="text-center">No logs found.</td></tr>
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

  <!-- Pagination -->
  <nav>
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
