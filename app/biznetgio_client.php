<?php
declare(strict_types=1);

if (!function_exists('bz_api_get')) {
    function bz_api_get(string $url, string $token): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["x-token: {$token}"],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) throw new RuntimeException("cURL error: $errno");
        if ($http < 200 || $http >= 300) throw new RuntimeException("HTTP $http: $body");
        $json = json_decode($body, true);
        return is_array($json) ? $json : [];
    }
}

if (!function_exists('bz_to_list')) {
    function bz_to_list(array $res): array {
        if (isset($res['data']) && is_array($res['data'])) return $res['data'];
        if ($res && array_is_list($res)) return $res;
        return array_values(array_filter($res, 'is_array'));
    }
}

if (!function_exists('bz_val')) {
    function bz_val(array $a, array $keys, $default = null) {
        foreach ($keys as $k) {
            if (array_key_exists($k, $a) && $a[$k] !== '' && $a[$k] !== null) return $a[$k];
        }
        return $default;
    }
}

/** Cari key di level dalam (rekursif) */
if (!function_exists('bz_deep_find')) {
    function bz_deep_find($data, array $keys) {
        if (!is_array($data)) return null;
        $hit = bz_val($data, $keys, null);
        if ($hit !== null && $hit !== '') return $hit;
        foreach ($data as $v) {
            if (is_array($v)) {
                $found = bz_deep_find($v, $keys);
                if ($found !== null && $found !== '') return $found;
            }
        }
        return null;
    }
}

/** Ambil IPv4 publik/pertama dari struktur nested */
if (!function_exists('bz_find_first_ipv4')) {
    function bz_find_first_ipv4($data): ?string {
        $best = null;
        $scan = function($val) use (&$best, &$scan) {
            if (is_string($val)) {
                if (preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $val, $m)) {
                    $ip = $m[0];
                    if ($ip !== '0.0.0.0' && !preg_match('/^(127\.|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $ip)) {
                        $best = $best ?? $ip;
                    } else {
                        $best = $best ?? $ip;
                    }
                }
            } elseif (is_array($val)) {
                if (isset($val['type']) && stripos((string)$val['type'], 'public') !== false) {
                    foreach ($val as $vv) $scan($vv);
                } else {
                    foreach ($val as $vv) $scan($vv);
                }
            }
        };
        $scan($data);
        return $best;
    }
}

/** Keypair map */
if (!function_exists('bz_build_keypair_map')) {
    function bz_build_keypair_map(array $list): array {
        $map = [];
        foreach (bz_to_list($list) as $kp) {
            $idInt = (int)bz_val($kp, ['id', 'keypair_id'], 0);
            $idStr = (string)bz_val($kp, ['id', 'keypair_id'], '');
            $name  = bz_val($kp, ['name', 'keypair_name', 'ssh_key_name']);
            if ($idInt && $name) $map[$idInt] = $name;
            if ($idStr !== '' && $name) $map[$idStr] = $name;
        }
        return $map;
    }
}

/** Parser tanggal & sisa hari dari next_due */
if (!function_exists('bz_parse_datetime')) {
    function bz_parse_datetime($v, string $tz='Asia/Jakarta'): ?DateTimeImmutable {
        if ($v === null || $v === '') return null;
        try {
            if (is_numeric($v)) {
                $ts = (int)$v;
                if ($ts > 20000000000) $ts = intdiv($ts, 1000); // ms → s
                return (new DateTimeImmutable('@'.$ts))->setTimezone(new DateTimeZone($tz));
            }
            $dt = new DateTimeImmutable((string)$v);
            return $dt->setTimezone(new DateTimeZone($tz));
        } catch (Throwable $e) { return null; }
    }
}
if (!function_exists('bz_days_left')) {
    function bz_days_left($dateLike, string $tz='Asia/Jakarta'): ?int {
        $dt = bz_parse_datetime($dateLike, $tz);
        if (!$dt) return null;
        $now  = new DateTimeImmutable('now', new DateTimeZone($tz));
        $diff = $now->diff($dt);
        return (int)$diff->format('%r%a');
    }
}

if (!function_exists('bz_get_all_vms')) {
    function bz_get_all_vms(string $endpoint, string $token, bool $onlyActive = true): array {
        $qs   = $onlyActive ? '?status=Active' : '';
        $base = rtrim($endpoint,'/');

        $liteAcc   = bz_api_get("$base/v1/neolites/accounts{$qs}",  $token);
        $metalAcc  = bz_api_get("$base/v1/baremetals/accounts{$qs}", $token);
        $liteKP    = bz_api_get("$base/v1/neolites/keypairs/",  $token);
        $metalKP   = bz_api_get("$base/v1/baremetals/keypairs/", $token);
        $kpMap     = bz_build_keypair_map($liteKP) + bz_build_keypair_map($metalKP);

        // Tandai sumber data agar bisa fallback ke kategori
        $liteItems  = bz_to_list($liteAcc);
        foreach ($liteItems as &$x) { $x['__source'] = 'NEO Lite'; } unset($x);
        $metalItems = bz_to_list($metalAcc);
        foreach ($metalItems as &$x) { $x['__source'] = 'NEO Metal'; } unset($x);

        $items = array_merge($liteItems, $metalItems);
        $out   = [];

        foreach ($items as $it) {
            // NAME
            $name = bz_val($it, ['name','vm_name','label','hostname','display_name'], null);
            if ($name === null) $name = bz_deep_find($it, ['name','vm_name','label','hostname','display_name','server_name','resourceName']);
            $name = (string)($name ?? '-');

            // STATUS
            $status = bz_val($it, ['status','state'], null);
            if ($status === null) $status = bz_deep_find($it, ['status','state']);
            $status = (string)($status ?? '-');

            // CREATED / NEXT DUE
            $dateCreated = bz_val($it, ['date_created','created_at','create_date','created'], null);
            if ($dateCreated === null) $dateCreated = bz_deep_find($it, ['date_created','created_at','create_date','created']);

            $nextDue = bz_val($it, ['next_due','next_invoice_date','due_date','nextDue'], null);
            if ($nextDue === null) $nextDue = bz_deep_find($it, ['next_due','next_invoice_date','due_date','nextDue']);

            // AMOUNT
            $amount = bz_val($it, ['recurring_amount','amount','price','recurringAmount'], null);
            if ($amount === null) $amount = bz_deep_find($it, ['recurring_amount','amount','price','recurringAmount']);
            if (is_array($amount)) $amount = bz_val($amount, ['amount','value'], null);
            $amount = $amount !== null ? (float)$amount : null;

            // ADDRESS
            $address = bz_val($it, ['address','ip_address','ip','public_ip','ipv4','publicIp'], null);
            if ($address === null) $address = bz_deep_find($it, ['address','ip_address','ip','public_ip','ipv4','publicIp']);
            if ($address === null) $address = bz_find_first_ipv4($it);
            if (is_array($address)) $address = json_encode($address);
            $address = $address !== null ? (string)$address : null;

            // KEYPAIR
            $kpId   = bz_val($it, ['keypair_id','keypairId','kp_id'], null);
            if ($kpId === null) $kpId = bz_deep_find($it, ['keypair_id','keypairId','kp_id']);
            $kpName = null;
            if ($kpId !== null && $kpId !== '') {
                $kpName = $kpMap[$kpId] ?? $kpMap[(int)$kpId] ?? null;
            }
            if ($kpName === null) {
                $kpName = bz_val($it, ['keypair_name','ssh_key_name','ssh_keypair','ssh_key'], null);
                if ($kpName === null) $kpName = bz_deep_find($it, ['keypair_name','ssh_key_name','ssh_keypair','ssh_key']);
            }

            // CATEGORY NAME (plan/produk) + fallback ke sumber
            $category = bz_val($it, [
                'category_name','category','product_name','package_name','plan_name','plan',
                'service_name','service','type','vm_type','instance_type','flavor_name','flavor'
            ], null);
            if ($category === null) {
                $category = bz_deep_find($it, [
                    'category_name','category','product_name','package_name','plan_name','plan',
                    'service_name','service','type','vm_type','instance_type','flavor_name','flavor'
                ]);
            }
            if ($category === null) {
                $category = $it['__source'] ?? 'Unknown';
            }
            $category = (string)$category;

            // Sisa hari dari next_due
            $daysLeft = bz_days_left($nextDue, 'Asia/Jakarta');

            // Status billing/akun kedua
            $status2 = bz_val($it, ['billing_status','account_status','status2'], null);
            if ($status2 === null) $status2 = bz_deep_find($it, ['billing_status','account_status','status2']);
            $status2 = (string)($status2 ?? $status);

            $out[] = [
                'name'             => $name,
                'category_name'    => $category,   // << ditambahkan
                'status'           => $status,
                'date_created'     => $dateCreated,
                'next_due'         => $nextDue,
                'days_left'        => $daysLeft,
                'recurring_amount' => $amount,
                'address'          => $address,
                'keypair_name'     => $kpName,
                'status_last'      => $status2,
                '_raw'             => $it,
            ];
        }
        return $out;
    }
}
