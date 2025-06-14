<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';
require 'ssl_check.php';
require 'notify.php';

if (isset($_POST['url'])) {
    $url = trim($_POST['url']);
    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        $url = 'https://' . $url;
    }

// Tambahan: coba akses www jika domain utama gagal nanti
$parsed = parse_url($url, PHP_URL_HOST);
if (substr($parsed, 0, 4) !== 'www.') {
    $www_version = 'https://www.' . $parsed;
} else {
    $non_www = 'https://' . str_replace('www.', '', $parsed);
}




    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $stmt = $pdo->prepare("INSERT INTO domains (url) VALUES (?)");
        $stmt->execute([$url]);
    }

    header('Location: domains.php');
    exit;
}

if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM domains WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: domains.php');
    exit;
}

if (isset($_POST['check_id'])) {
    $id = (int)$_POST['check_id'];
    $stmt = $pdo->prepare("SELECT * FROM domains WHERE id = ?");
    $stmt->execute([$id]);
    $domain = $stmt->fetch();

    if ($domain) {
        $result = check_ssl($domain['url']);
        $stmtUpdate = $pdo->prepare("UPDATE domains SET valid_to = ?, days_left = ?, last_checked = NOW() WHERE id = ?");
        if ($result['valid']) {
            $stmtUpdate->execute([$result['valid_to'], $result['days_left'], $domain['id']]);
            if ($result['days_left'] <= 15) {
                $msg = "🔔 SSL domain akan expired dalam {$result['days_left']} hari (hingga {$result['valid_to']})";
                send_notification($domain['url'], $msg);
                $stmt = $pdo->prepare("INSERT INTO logs (domain_id, type, message) VALUES (?, 'ssl', ?)");
                $stmt->execute([$domain['id'], $msg]);
            }
        } else {
            $stmtUpdate->execute([null, null, $domain['id']]);
        }
    }

    header('Location: domains.php');
    exit;
}

if (isset($_POST['notify_id'])) {
    $id = (int)$_POST['notify_id'];
    $stmt = $pdo->prepare("SELECT * FROM domains WHERE id = ?");
    $stmt->execute([$id]);
    $domain = $stmt->fetch();

    if ($domain && $domain['valid_to']) {
        $msg = "🔔 [PAKSA] SSL domain akan expired pada {$domain['valid_to']} (sisa {$domain['days_left']} hari)";
        send_notification($domain['url'], $msg);

        $stmt = $pdo->prepare("INSERT INTO logs (domain_id, type, message) VALUES (?, 'ssl', ?)");
        $stmt->execute([$domain['id'], $msg]);
    }

    header('Location: domains.php');
    exit;
}

if (isset($_POST['check_all'])) {
    $domains = $pdo->query("SELECT * FROM domains")->fetchAll();
    foreach ($domains as $domain) {
        $result = check_ssl($domain['url']);
        $stmtUpdate = $pdo->prepare("UPDATE domains SET valid_to = ?, days_left = ?, last_checked = NOW() WHERE id = ?");
        if ($result['valid']) {
            $stmtUpdate->execute([$result['valid_to'], $result['days_left'], $domain['id']]);
            if ($result['days_left'] <= 15) {
                $msg = "🔔 SSL domain akan expired dalam {$result['days_left']} hari (hingga {$result['valid_to']})";
                send_notification($domain['url'], $msg);
                $log = $pdo->prepare("INSERT INTO logs (domain_id, type, message) VALUES (?, 'ssl', ?)");
                $log->execute([$domain['id'], $msg]);
            }
        } else {
            $stmtUpdate->execute([null, null, $domain['id']]);
        }
    }
    header('Location: domains.php');
    exit;
}

if (isset($_POST['check_selected']) && !empty($_POST['domain_ids'])) {
    foreach ($_POST['domain_ids'] as $id) {
        $id = (int)$id;
        $stmt = $pdo->prepare("SELECT * FROM domains WHERE id = ?");
        $stmt->execute([$id]);
        $domain = $stmt->fetch();

        if ($domain) {
            $result = check_ssl($domain['url']);
            $stmtUpdate = $pdo->prepare("UPDATE domains SET valid_to = ?, days_left = ?, last_checked = NOW() WHERE id = ?");
            if ($result['valid']) {
                $stmtUpdate->execute([$result['valid_to'], $result['days_left'], $id]);
                if ($result['days_left'] <= 15) {
                    $msg = "🔔 SSL domain akan expired dalam {$result['days_left']} hari (hingga {$result['valid_to']})";
                    send_notification($domain['url'], $msg);
                    $log = $pdo->prepare("INSERT INTO logs (domain_id, type, message) VALUES (?, 'ssl', ?)");
                    $log->execute([$id, $msg]);
                }
            } else {
                $stmtUpdate->execute([null, null, $id]);
            }
        }
    }
    header('Location: domains.php');
    exit;
}

if (isset($_POST['delete_selected']) && !empty($_POST['domain_ids'])) {
    $placeholders = rtrim(str_repeat('?,', count($_POST['domain_ids'])), ',');
    $stmt = $pdo->prepare("DELETE FROM domains WHERE id IN ($placeholders)");
    $stmt->execute($_POST['domain_ids']);
    header('Location: domains.php');
    exit;
}
?>
