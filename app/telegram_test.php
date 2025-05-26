<?php
require 'db.php';

$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();

$token = $settings['telegram_token'] ?? '';
$chat_id = $settings['telegram_chat_id'] ?? '';

$message = "🔧 Tes notifikasi dari SSL Monitor";
$response = '';

if ($token && $chat_id) {
    $text = urlencode($message);
    $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=$text&parse_mode=Markdown";
    $response = file_get_contents($url);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Test Telegram</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
  <h3>🔔 Pengujian Kirim Notifikasi Telegram</h3>
  <ul class="list-group mb-3">
    <li class="list-group-item"><strong>Token:</strong> <?= htmlspecialchars(substr($token, 0, 20)) ?>...</li>
    <li class="list-group-item"><strong>Chat ID:</strong> <?= htmlspecialchars($chat_id) ?></li>
  </ul>
  <?php if ($response): ?>
    <div class="alert alert-success">✅ Notifikasi terkirim! Respon API:</div>
    <pre><?= htmlspecialchars($response) ?></pre>
  <?php else: ?>
    <div class="alert alert-danger">❌ Gagal mengirim notifikasi. Periksa token & chat ID.</div>
  <?php endif ?>
</div>
</body>
</html>
