<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}

// --- Kredensial Name.com dari ENV ---
$user  = 'gedexxx';   // contoh: akun name.com
$token = '251bdaebb2e26d82727fdffb86ff9c5ab6f6ebd1';  // contoh: API token
if (!$user || !$token) {
    http_response_code(500);
    echo "Harap set environment NAMECOM_USER dan NAMECOM_TOKEN.";
    exit;
}

// --- Config dasar ---
$base = 'https://api.name.com/core/v1';

// --- Load fungsi utilitas (aman, hanya sekali) ---
require_once __DIR__ . '/namecom_functions.php';

// --- Ambil data ---
try {
    [$usdIdr, $rateSrc] = get_usd_idr_rate();
    $domains = fetch_all_domains_namecom($base, $user, $token);
} catch (Throwable $e) {
    http_response_code(502);
    echo "Gagal mengambil data: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

// --- Hitung ringkasan ---
$totalDomain = count($domains);
$totalRupiah = 0;
foreach ($domains as $d) {
    $priceUsd = $d['renewalPrice'] ?? null;
    if ($priceUsd !== null && is_numeric($priceUsd)) {
        $totalRupiah += (int)round(((float)$priceUsd) * $usdIdr);
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Domain - Name.com</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f1f3f5; }
    .sidebar { height: 100vh; background-color: #343a40; color: white; position: fixed; width: 240px; top: 0; left: 0; padding: 1rem; }
    .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 0.5rem 0; }
    .sidebar a.active, .sidebar a:hover { color: #ffffff; background-color: #495057; border-radius: 5px; padding-left: 10px; }
    .main { margin-left: 240px; padding: 2rem; }
    .badge-status { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    td.domain-cell { text-align: left !important; font-weight: 500; }
  </style>
</head>
<body>
<div class="sidebar">
  <h5 class="text-white">🔐 SSL Monitor</h5>
  <hr class="border-light">
  <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
  <a href="domains.php"><i class="bi bi-globe2 me-2"></i>SSL</a>
  <a href="domain_expiry_notify.php"><i class="bi bi-calendar2-week me-2"></i>Cek Expired Domain</a>
  <!-- Menu baru NAME aktif di halaman ini -->
  <a href="name.php" class="active"><i class="bi bi-hdd-network me-2"></i>NAME</a>
  <a href="list_vm.php""><i class="bi bi-hdd-network me-2"></i>VM Biznet</a>

  <a href="settings.php"><i class="bi bi-gear me-2"></i>Pengaturan</a>
  <a href="users.php"><i class="bi bi-people me-2"></i>Pengguna</a>
  <a href="logs.php"><i class="bi bi-clock-history me-2"></i>Log</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<div class="main">
  <h2 class="mb-3">📡 Daftar Domain (Name.com)</h2>
  <div class="text-muted mb-3">
    Zona waktu: Asia/Jakarta • Diperbarui: <?= htmlspecialchars(date('Y-m-d H:i:s')) ?><br>
    Kurs USD→IDR: <strong><?= number_format($usdIdr, 0, ',', '.') ?></strong> (<?= htmlspecialchars($rateSrc) ?>)
  </div>

  <!-- Ringkasan -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <i class="bi bi-globe2 fs-3 text-primary me-3"></i>
          <div>
            <div class="text-muted small">Total Domain</div>
            <div class="fs-5 fw-semibold"><?= number_format($totalDomain) ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <i class="bi bi-cash-coin fs-3 text-success me-3"></i>
          <div>
            <div class="text-muted small">Total Rupiah</div>
            <div class="fs-5 fw-semibold">Rp <?= number_format($totalRupiah, 0, ',', '.') ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabel data -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Data Domain</span>
      <span class="text-muted small">Harga = renewalPrice (Name.com)</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-center">
          <tr>
            <th class="text-start">Domain</th>
            <th>Expire Date</th>
            <th>Sisa Hari</th>
            <th>Auto-renew</th>
            <th>Locked</th>
            <th>Renewal Price (USD)</th>
            <th>Renewal Price (IDR)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($domains as $d): ?>
            <?php
              $expire = $d['expireDate'] ?? null;
              $days   = days_left_local($expire);
              $badge  = 'bg-secondary';
              if ($days !== null) {
                if ($days <= 0)       $badge = 'bg-danger';
                elseif ($days <= 30)  $badge = 'bg-warning';
                else                  $badge = 'bg-success';
              }
              $priceUsd = $d['renewalPrice'] ?? null;
              $priceIdr = ($priceUsd !== null && is_numeric($priceUsd)) ? (int)round(((float)$priceUsd) * $usdIdr) : null;
            ?>
            <tr>
              <td class="domain-cell">
                <span class="badge-status <?= $badge ?>"></span><?= htmlspecialchars($d['domainName'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td class="text-center"><?= htmlspecialchars($expire ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-center"><?= $days ?? '-' ?></td>
              <td class="text-center"><?= !empty($d['autorenewEnabled']) ? 'ON' : 'OFF' ?></td>
              <td class="text-center"><?= !empty($d['locked']) ? 'Yes' : 'No' ?></td>
              <td class="text-end"><?= $priceUsd !== null ? number_format((float)$priceUsd, 2) : '-' ?></td>
              <td class="text-end"><?= $priceIdr !== null ? number_format($priceIdr, 0, ',', '.') : '-' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($domains)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada domain ditemukan.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
