<?php
session_start();
require 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
  header('Location: login.php');
  exit;
}

// Ganti password diri sendiri
if (isset($_POST['change_pass']) && !empty($_POST['new_self_pass'])) {
  $hashed = password_hash($_POST['new_self_pass'], PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
  $stmt->execute([$hashed, $_SESSION['user_id']]);
  header('Location: users.php?changed=1');
  exit;
}

// Reset password user lain ke default
if (isset($_POST['reset_user'])) {
  $default = password_hash('admin123', PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
  $stmt->execute([$default, (int)$_POST['reset_user']]);
  header('Location: users.php?reset=1');
  exit;
}

// Tambah user baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_user'])) {
  $new_user = trim($_POST['new_user']);
  $new_pass = trim($_POST['new_pass']);
  $role = $_POST['role'];

  $allowed_roles = ['admin', 'user'];
  if (!in_array($role, $allowed_roles)) {
    die('Invalid role.');
  }

  $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
  $stmt->execute([$new_user, $hashed, $role]);
  header('Location: users.php');
  exit;
}

// Hapus user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if ($id !== $_SESSION['user_id']) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
  }
  header('Location: users.php');
  exit;
}

// Ambil data semua user
$users = $pdo->query("SELECT * FROM users ORDER BY id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>User Management</title>
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
  <h2 class="mb-4">👥 User Management</h2>

  <?php if (isset($_GET['changed'])): ?>
    <div class="alert alert-success">Password berhasil diganti.</div>
  <?php endif; ?>
  <?php if (isset($_GET['reset'])): ?>
    <div class="alert alert-warning">Password user berhasil direset ke default.</div>
  <?php endif; ?>

  <div class="row mb-4">
    <div class="col-md-6">
      <h5>Tambah User Baru</h5>
      <form method="post">
        <input type="text" name="new_user" placeholder="Username" required class="form-control mb-2">
        <input type="password" name="new_pass" placeholder="Password" required class="form-control mb-2">
        <select name="role" class="form-control mb-2" required>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
        <button type="submit" class="btn btn-primary">Tambah</button>
      </form>
    </div>
    <div class="col-md-6">
      <h5>Ganti Password Anda</h5>
      <form method="post">
        <input type="password" name="new_self_pass" placeholder="Password baru" required class="form-control mb-2">
        <button type="submit" name="change_pass" class="btn btn-secondary">Ganti Password</button>
      </form>
    </div>
  </div>

  <h5>Daftar User</h5>
  <table class="table table-bordered">
    <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= htmlspecialchars($user['id']) ?></td>
          <td><?= htmlspecialchars($user['username']) ?></td>
          <td><?= htmlspecialchars($user['role']) ?></td>
          <td>
            <?php if ($user['id'] != $_SESSION['user_id']): ?>
              <form method="post" style="display:inline-block">
                <input type="hidden" name="reset_user" value="<?= $user['id'] ?>">
                <button class="btn btn-warning btn-sm" onclick="return confirm('Reset password user ini ke default?')">Reset</button>
              </form>
              <a href="?delete=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus user ini?')">Delete</a>
            <?php else: ?>
              <em>(Anda)</em>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
