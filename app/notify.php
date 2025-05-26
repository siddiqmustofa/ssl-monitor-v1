<?php
require 'db.php';

// Kirim notifikasi Telegram dengan pembeda untuk SSL vs Domain WHOIS
function send_notification($domain, $message, $type = 'ssl') {
    global $pdo;

    $settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
    if (!$settings || empty($settings['telegram_token']) || empty($settings['telegram_chat_id'])) {
        file_put_contents('notif_debug.log', date('c') . " => ❌ Token/chat_id kosong\n", FILE_APPEND);
        return;
    }

    $token = $settings['telegram_token'];
    $chat_id = $settings['telegram_chat_id'];

    $prefix = $type === 'domain' ? "🌐 *Domain WHOIS Monitor*" : "🔐 *SSL Monitor*";
    $text = "$prefix\n*Domain:* `$domain`\n*Info:* $message";
    $text = urlencode($text);

    $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=$text&parse_mode=Markdown";
    $response = file_get_contents($url);

    $log = date('c') . " => Kirim ke $chat_id | type: $type | domain: $domain | msg: $message\nRespon: $response\n";
    file_put_contents('notif_debug.log', $log, FILE_APPEND);

    // Simpan ke log DB
    $stmt = $pdo->prepare("SELECT id FROM domains WHERE url = ?");
    $stmt->execute([$domain]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $pdo->prepare("INSERT INTO logs (domain_id, type, message) VALUES (?, ?, ?)");
        $stmt->execute([$row['id'], $type, $message]);
    }
}
