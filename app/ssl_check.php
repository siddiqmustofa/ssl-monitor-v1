<?php
function check_ssl($url) {
    $original = normalize_url($url);
    $host = parse_url($original, PHP_URL_HOST);

    // 1. Coba domain original dulu
    $result = try_ssl($original);

    // 2. Jika gagal, coba versi www atau non-www
    if (!$result['valid']) {
        $alternate = (str_starts_with($host, 'www.'))
            ? 'https://' . str_replace('www.', '', $host)
            : 'https://www.' . $host;

        $result = try_ssl($alternate);
    }

    return $result;
}

function normalize_url($url) {
    $url = trim($url);
    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        $url = 'https://' . $url;
    }
    return $url;
}

function try_ssl($url) {
    $host = parse_url($url, PHP_URL_HOST);
    $port = 443;
    $timeout = 5;

    $context = stream_context_create(["ssl" => [
        "capture_peer_cert" => true,
        "verify_peer" => false,
        "verify_peer_name" => false,
    ]]);

    $client = @stream_socket_client(
        "ssl://$host:$port",
        $errno, $errstr, $timeout,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$client) {
        return ['valid' => false, 'error' => "Connection failed to $host"];
    }

    $params = stream_context_get_params($client);
    $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

    if (!$cert || !isset($cert['validTo_time_t'])) {
        return ['valid' => false, 'error' => 'Invalid certificate format'];
    }

    $validTo = date('Y-m-d H:i:s', $cert['validTo_time_t']);
    $daysLeft = floor(($cert['validTo_time_t'] - time()) / 86400);

    return [
        'valid' => true,
        'valid_to' => $validTo,
        'days_left' => $daysLeft
    ];
}
