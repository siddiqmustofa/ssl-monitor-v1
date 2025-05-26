<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}
require 'db.php';

$imported = 0;
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['domain_file'])) {
  $file = $_FILES['domain_file']['tmp_name'];
  if (is_uploaded_file($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      $url = trim($line);
      if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        $url = 'https://' . $url;
      }
      if (filter_var($url, FILTER_VALIDATE_URL)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM domains WHERE url = ?");
        $stmt->execute([$url]);
        if ($stmt->fetchColumn() == 0) {
          $stmt = $pdo->prepare("INSERT INTO domains (url) VALUES (?)");
          $stmt->execute([$url]);
          $imported++;
        }
      }
    }
  } else {
    $error = true;
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Import Domain</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container py-4">
    <h3 class="mb-4">📥 Import Daftar Domain dari File</h3>

    <?php if ($imported > 0): ?>
      <div class="alert alert-success">✅ Berhasil menambahkan <?= $imported ?> domain.</div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger">❌ Gagal memproses file.</div>
    <?php endif ?>

    <form method="POST" enctype="multipart/form-data" class="card p-4 shadow-sm">
      <div class="mb-3">
        <label for="domain_file" class="form-label">Unggah file teks (.txt) — 1 domain per baris</label>
        <input type="file" name="domain_file" class="form-control" required accept=".txt">
      </div>
      <button type="submit" class="btn btn-primary">Import Sekarang</button>
      <a href="domains.php" class="btn btn-secondary ms-2">Kembali</a>
    </form>
  </div>
</body>
</html>
