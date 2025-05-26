<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $stored_hash = $user['password'];

        // Login success if password matches bcrypt
        if (password_verify($password, $stored_hash)) {
            // Password valid (bcrypt)
            login_user($user);

        // Backward compatibility: SHA256 support
        } elseif (strlen($stored_hash) === 64 && hash('sha256', $password) === $stored_hash) {
            // Upgrade password to bcrypt
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user['id']]);

            login_user($user);

        } else {
            $error = "Username atau password salah.";
        }
    } else {
        $error = "Username atau password salah.";
    }
}

function login_user($user) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #f5f7fa; }
    .login-container { max-width: 400px; margin: auto; margin-top: 100px; padding: 2rem; background: white; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
  <div class="login-container">
    <h3 class="text-center mb-4">🔐 SSL Monitor Login</h3>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <input type="text" name="username" class="form-control" placeholder="Username" required autofocus>
      </div>
      <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="Password" required>
      </div>
      <button type="submit" class="btn btn-dark w-100">Login</button>
    </form>
  </div>
</body>
</html>
