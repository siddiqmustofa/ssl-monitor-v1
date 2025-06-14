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
  <title>Pengguna - SSL Monitor</title>
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
  <a href="settings.php"><i class="bi bi-gear me-2"></i>Pengaturan</a>
  <a href="users.php" class="active"><i class="bi bi-people me-2"></i>Pengguna</a>
  <a href="logs.php"><i class="bi bi-clock-history me-2"></i>Log</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<div class="main">
  <h2 class="mb-4">👥 Pengguna</h2>

  <?php if (isset($_GET['changed'])): ?>
    <div class="alert alert-success">Password berhasil diganti.</div>
  <?php endif; ?>
  <?php if (isset($_GET['reset'])): ?>
    <div class="alert alert-warning">Password user berhasil direset ke default.</div>
  <?php endif; ?>

  <div class="row mb-4">
    <div class="col-md-6">
      <div class="card p-3">
        <h5>Tambah User Baru</h5>
        <form method="post">
          <input type="text" name="new_user" placeholder="Username" required class="form-control mb-2">
          <input type="password" name="new_pass" placeholder="Password" required class="form-control mb-2">
          <select name="role" class="form-select mb-2" required>
            <option value="user">User</option>
            <option value="admin">Admin</option>
          </select>
          <button type="submit" class="btn btn-primary">Tambah</button>
        </form>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-3">
        <h5>Ganti Password Anda</h5>
        <form method="post">
          <input type="password" name="new_self_pass" placeholder="Password baru" required class="form-control mb-2">
          <button type="submit" name="change_pass" class="btn btn-secondary">Ganti Password</button>
        </form>
      </div>
    </div>
  </div>

  <h5>Daftar User</h5>
  <div class="table-responsive">
    <table class="table table-hover align-middle text-center bg-white shadow-sm">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Role</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['id']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td>
              <?php if ($user['id'] != $_SESSION['user_id']): ?>
                <form method="post" class="d-inline">
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
</div>
</body>
</html>
