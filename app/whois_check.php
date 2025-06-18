<?php
function check_whois($domain) {
    $output = shell_exec("whois " . escapeshellarg($domain));
    $expiry = null;

    if (preg_match('/Expiry Date:\s*(.+)/i', $output, $match)) {
        $expiry = date('Y-m-d H:i:s', strtotime($match[1]));
    } elseif (preg_match('/Registry Expiry Date:\s*(.+)/i', $output, $match)) {
        $expiry = date('Y-m-d H:i:s', strtotime($match[1]));
    }

    $days_left = $expiry ? (int)((strtotime($expiry) - time()) / 86400) : null;
    return [
        'expiry' => $expiry,
        'days_left' => $days_left
    ];
}
