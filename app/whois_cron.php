<?php
date_default_timezone_set('Asia/Jakarta');
require 'db.php';
require 'vendor/autoload.php';

use Iodev\Whois\Factory;

$whoisClient = Factory::get()->createWhois();

$domains = $pdo->query("SELECT id, url FROM domains")->fetchAll();

foreach ($domains as $d) {
    $id = $d['id'];
    $domain = $d['url'];

    // Ambil hanya host dari URL jika mengandung skema
    $host = parse_url($domain, PHP_URL_HOST) ?? $domain;

    try {
        $info = $whoisClient->loadDomainInfo($host);

        if ($info && $info->getExpirationDate()) {
            $ts = $info->getExpirationDate();
            $days_left = floor(($ts - time()) / 86400);

            $stmt = $pdo->prepare("
                REPLACE INTO domain_whois (domain_id, expiry_date, days_left, checked_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $id,
                date('Y-m-d H:i:s', $ts),
                $days_left
            ]);

            echo "✔ {$host} expires in {$days_left} days (" . date('Y-m-d', $ts) . ")\n";
        } else {
            echo "⚠️ WHOIS data not found for: {$host}\n";
        }

    } catch (Exception $e) {
        echo "❌ Error checking {$host}: " . $e->getMessage() . "\n";
    }
}

echo "WHOIS check completed at " . date('Y-m-d H:i:s') . PHP_EOL;
