<?php
require 'db.php';
require 'ssl_check.php';
require 'notify.php';

$stmt = $pdo->query("SELECT * FROM domains");
$domains = $stmt->fetchAll();

foreach ($domains as $domain) {
    $result = check_ssl($domain['url']);
    $stmtUpdate = $pdo->prepare("UPDATE domains SET valid_to = ?, days_left = ?, last_checked = NOW() WHERE id = ?");

    if ($result['valid']) {
        $stmtUpdate->execute([$result['valid_to'], $result['days_left'], $domain['id']]);
        if ($result['days_left'] <= $config['expiry_warning_days']) {
            send_notification($domain['url'], $result['valid_to']);
        }
    } else {
        echo "Gagal cek SSL untuk {$domain['url']}: {$result['error']}\n";
    }
}
