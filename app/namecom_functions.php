<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

if (!function_exists('get_usd_idr_rate')) {
    function get_usd_idr_rate(): array {
        // 1) ENV override
        $env = getenv('EXRATE_USD_IDR');
        if ($env && is_numeric($env) && (float)$env > 0) {
            return [ (float)$env, 'ENV' ];
        }

        // 2) API exchangerate.host
        $url = 'https://api.exchangerate.host/latest?base=USD&symbols=IDR';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body && $http === 200) {
            $json = json_decode($body, true);
            if (isset($json['rates']['IDR']) && is_numeric($json['rates']['IDR'])) {
                return [ (float)$json['rates']['IDR'], 'API' ];
            }
        }

        // 3) Fallback
        return [ 15500.0, 'FALLBACK' ];
    }
}

if (!function_exists('api_get_namecom')) {
    function api_get_namecom(string $url, string $user, string $token): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $user . ':' . $token, // Basic Auth
            CURLOPT_TIMEOUT        => 20,
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new RuntimeException("cURL error: $errno");
        }
        if ($http !== 200) {
            $msg = $body ?: 'Unknown error';
            throw new RuntimeException("HTTP $http: $msg");
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new RuntimeException("Invalid JSON");
        }
        return $json;
    }
}

if (!function_exists('fetch_all_domains_namecom')) {
    function fetch_all_domains_namecom(string $base, string $user, string $token, int $perPage = 1000): array {
        $all  = [];
        $page = 0;
        while (true) {
            $url = sprintf('%s/domains?perPage=%d&page=%d', rtrim($base, '/'), $perPage, $page);
            $res = api_get_namecom($url, $user, $token);
            $chunk = $res['domains'] ?? [];
            if (!is_array($chunk) || count($chunk) === 0) break;
            $all = array_merge($all, $chunk);
            if (count($chunk) < $perPage) break; // last page
            $page++;
            if ($page > 999) break; // safety
        }
        return $all;
    }
}

if (!function_exists('days_left_local')) {
    function days_left_local(?string $expireIso, string $tz = 'Asia/Jakarta'): ?int {
        if (!$expireIso) return null;
        try {
            $expireUtc   = new DateTime($expireIso, new DateTimeZone('UTC'));
            $expireLocal = clone $expireUtc;
            $expireLocal->setTimezone(new DateTimeZone($tz));
            $now = new DateTime('now', new DateTimeZone($tz));
            $diff = $now->diff($expireLocal);
            return (int)$diff->format('%r%a'); // bisa negatif
        } catch (Throwable $e) {
            return null;
        }
    }
}
