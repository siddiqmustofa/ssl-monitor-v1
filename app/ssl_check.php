<?php
function check_ssl($domain) {
    $orignal_parse = parse_url($domain);
    $host = $orignal_parse['host'] ?? $domain;

    $context = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
    $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    if (!$client) return ['valid' => false, 'error' => $errstr];

    $params = stream_context_get_params($client);
    $cert = openssl_x509_parse($params["options"]["ssl"]["peer_certificate"]);

    if (!isset($cert['validTo'])) {
        return ['valid' => false, 'error' => 'No validTo in certificate'];
    }

    $rawDate = $cert['validTo'];

    // Try common format first (YmdHisZ)
    $validTo = date_create_from_format('YmdHis\\Z', $rawDate);

    // Try alternative format (ymdHisZ - 2 digit year)
    if (!$validTo) {
        $validTo = date_create_from_format('ymdHis\\Z', $rawDate);
    }

    // Try timestamp fallback
    if (!$validTo) {
        $ts = strtotime($rawDate);
        if ($ts !== false) {
            $validTo = (new DateTime())->setTimestamp($ts);
        }
    }

    if (!$validTo) {
        return ['valid' => false, 'error' => "Invalid date format: $rawDate"];
    }

    return [
        'valid' => true,
        'valid_to' => $validTo->format('Y-m-d'),
        'days_left' => $validTo->diff(new DateTime())->days
    ];
}
