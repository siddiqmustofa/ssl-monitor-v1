<?php
ob_start();
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}
require 'db.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

$sort = $_GET['sort'] ?? 'last_checked';
$allowed = ['url', 'valid_to', 'days_left', 'last_checked'];
$orderBy = in_array($sort, $allowed) ? $sort : 'last_checked';

$total = $pdo->query("SELECT COUNT(*) FROM domains")->fetchColumn();
$pages = ceil($total / $limit);

$stmt = $pdo->prepare("SELECT * FROM domains ORDER BY $orderBy DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$domains = $stmt->fetchAll();
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelola Domain - SSL Monitor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
    .badge-status { width: 16px; height: 16px; border-radius: 50%; display: inline-block; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="dashboard.php">🔐 SSL Monitor</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="nav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="domains.php">Domains</a></li>
        <li class="nav-item"><a class="nav-link" href="domain_expiry_notify.php">Whois</a></li>
        <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
        <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="logs.php">Logs</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-4">🌐 Manage Domains</h2>

  <!-- Form import file -->
  <form method="POST" action="import_domains.php" enctype="multipart/form-data" class="d-flex mb-3">
    <input type="file" name="import_file" class="form-control me-2" accept=".txt" required>
    <button class="btn btn-success">📥 Import dari File</button>
  </form>

  <!-- Form tambah domain -->
  <form method="POST" action="api.php" class="d-flex mb-4">
    <input type="text" name="url" class="form-control me-2" placeholder="example.com atau https://example.com" required>
    <button class="btn btn-primary">Tambah Domain</button>
  </form>

  <!-- Tabel domain -->
  <form method="POST" action="api.php">
    <div class="table-responsive">
      <table class="table table-bordered text-center bg-white">
        <thead class="table-light">
          <tr>
            <th><input type="checkbox" onclick="toggleSelectAll(this)"></th>
            <th><a href="?sort=url">Domain</a></th>
            <th>Status</th>
            <th><a href="?sort=valid_to">Valid Hingga</a></th>
            <th><a href="?sort=days_left">Sisa Hari</a></th>
            <th><a href="?sort=last_checked">Terakhir Dicek</a></th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($domains as $d): ?>
          <?php
            $color = 'bg-secondary';
            if (is_null($d['days_left'])) $color = 'bg-danger';
            elseif ($d['days_left'] <= 15) $color = 'bg-warning';
            else $color = 'bg-success';
          ?>
          <tr>
            <td><input type="checkbox" name="domain_ids[]" value="<?= $d['id'] ?>"></td>
            <td><?= htmlspecialchars($d['url']) ?></td>
            <td><span class="badge-status <?= $color ?>"></span></td>
            <td><?= $d['valid_to'] ?? '-' ?></td>
            <td><?= $d['days_left'] ?? '-' ?></td>
            <td><?= $d['last_checked'] ?? '-' ?></td>
            <td>
              <button class="btn btn-sm btn-primary" name="check_id" value="<?= $d['id'] ?>">🔁</button>
              <button class="btn btn-sm btn-warning" name="notify_id" value="<?= $d['id'] ?>">🔔</button>
              <button class="btn btn-sm btn-danger" name="delete_id" value="<?= $d['id'] ?>" onclick="return confirm('Hapus domain ini?')">🗑</button>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>

    <!-- Aksi massal dan pagination -->
    <div class="d-flex justify-content-between mt-3">
      <div>
        <button name="check_selected" value="1" class="btn btn-outline-primary btn-sm">🔁 Cek Terpilih</button>
        <button name="notify_selected" value="1" class="btn btn-outline-secondary btn-sm">🔔 Notif Terpilih</button>
        <button name="delete_selected" value="1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus domain terpilih?')">🗑 Hapus Terpilih</button>
      </div>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&sort=<?= urlencode($orderBy) ?>"><?= $i ?></a>
            </li>
          <?php endfor ?>
        </ul>
      </nav>
    </div>
  </form>
</div>

<script>
function toggleSelectAll(source) {
  document.querySelectorAll('input[name="domain_ids[]"]').forEach(cb => cb.checked = source.checked);
}
</script>
</body>
</html>
