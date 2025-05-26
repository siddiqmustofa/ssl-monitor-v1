<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';
require 'notify.php';

function get_domain_expiry($domain) {
    $host = parse_url($domain, PHP_URL_HOST) ?? $domain;
    $result = shell_exec("whois " . escapeshellarg($host));

    if (!is_string($result)) return null;

    if (preg_match('/Expiry Date:\s?(.*?)\n/i', $result, $matches) ||
        preg_match('/Registry Expiry Date:\s?(.*?)\n/i', $result, $matches)) {
        $expire = trim($matches[1]);
        $ts = strtotime($expire);
        $days = floor(($ts - time()) / 86400);
        return ['date' => $expire, 'days' => $days];
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_domains'], $_POST['action'])) {
    $ids = array_map('intval', $_POST['selected_domains']);
    $action = $_POST['action'];

    $placeholders = rtrim(str_repeat('?,', count($ids)), ',');
    $stmt = $pdo->prepare("SELECT * FROM domains WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $domains = $stmt->fetchAll();

    foreach ($domains as $d) {
        if ($action === 'notify') {
            $stmtCheck = $pdo->prepare("SELECT * FROM domain_whois WHERE domain_id = ?");
            $stmtCheck->execute([$d['id']]);
            $w = $stmtCheck->fetch();
            if ($w) {
                $msg = "🔔 [PAKSA] Domain akan expired pada {$w['expiry_date']} (sisa {$w['days_left']} hari)";
                send_notification($d['url'], $msg, 'domain');
                $log = $pdo->prepare("INSERT INTO logs (domain_id, type, message) VALUES (?, 'domain', ?)");
                $log->execute([$d['id'], $msg]);
            }
        } elseif ($action === 'check') {
            $result = get_domain_expiry($d['url']);
            if ($result) {
                $update = $pdo->prepare("REPLACE INTO domain_whois (domain_id, expiry_date, days_left, checked_at) VALUES (?, ?, ?, NOW())");
                $update->execute([$d['id'], $result['date'], $result['days']]);
            }
        }
    }
}
header('Location: domain_expiry_notify.php');
exit;
?>
