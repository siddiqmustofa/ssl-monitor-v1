<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['logged_in'])) { header('Location: login.php'); exit; }

$cfg = require __DIR__ . '/config-biznet.php';
date_default_timezone_set($cfg['app']['timezone'] ?? 'Asia/Jakarta');

$endpoint   = rtrim((string)($cfg['biznetgio']['endpoint'] ?? ''), '/');
$token      = (string)($cfg['biznetgio']['token'] ?? '');
$onlyActive = (bool)($cfg['biznetgio']['only_active'] ?? true);
if (!$endpoint || !$token) { http_response_code(500); echo "Harap isi endpoint/token Biznet Gio di config.php"; exit; }

require_once __DIR__ . '/biznetgio_client.php';
try { $rows = bz_get_all_vms($endpoint, $token, $onlyActive); }
catch (Throwable $e) { http_response_code(502); echo "Gagal mengambil data: ".htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8'); exit; }

$totalVM = count($rows);
$totalAmount = 0.0; foreach ($rows as $r) { if ($r['recurring_amount'] !== null) $totalAmount += (float)$r['recurring_amount']; }
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>List VM Biznet Gio - SSL Monitor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
  <a href="name.php"><i class="bi bi-gear me-2"></i>NAME</a>
  <a href="list_vm.php" class="active"><i class="bi bi-hdd-network me-2"></i>VM Biznet</a>
  <a href="settings.php"><i class="bi bi-gear me-2"></i>Pengaturan</a>
  <a href="users.php"><i class="bi bi-people me-2"></i>Pengguna</a>
  <a href="logs.php"><i class="bi bi-clock-history me-2"></i>Log</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<div class="main">
  <h2 class="mb-4">🖥️ List VM Biznet Gio</h2>

  <div class="row g-2 mb-3">
    <div class="col-auto">
      <span class="badge bg-primary">Total VM: <?= number_format($totalVM) ?></span>
    </div>
    <div class="col-auto">
      <span class="badge bg-success">Total Recurring: <?= $totalAmount > 0 ? number_format($totalAmount, 2) : '0.00' ?></span>
    </div>
    <div class="col-auto text-muted small align-self-center">
      Diperbarui: <?= htmlspecialchars(date('Y-m-d H:i:s')) ?><?= $onlyActive ? ' • Filter: Active' : '' ?><?= $debug ? ' • Debug: ON' : '' ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Daftar VM</span>
      <div class="d-flex gap-2">
        <a href="list_vm.php" class="btn btn-sm btn-outline-primary">🔁 Refresh</a>
        <a href="list_vm.php?debug=1" class="btn btn-sm btn-outline-secondary">🐞 Debug</a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-center">
          <tr>
            <th class="text-start">name</th>
            <th>category_name</th> <!-- kolom baru -->
            <th>status</th>
            <th>date_created</th>
            <th>next_due</th>
            <th>sisa_hari</th>
            <th>recurring_amount</th>
            <th>address</th>
            <th>keypair_name</th>
            <th>status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">Tidak ada data.</td></tr>
          <?php else: foreach ($rows as $it):
            $status = strtolower((string)$it['status']);
            $badge  = 'bg-secondary';
            if (in_array($status, ['active','running','on'])) $badge='bg-success';
            elseif (in_array($status, ['stopped','suspended','paused'])) $badge='bg-warning';
            elseif (in_array($status, ['terminated','failed','error','off'])) $badge='bg-danger';

            $days = $it['days_left'];
            if ($days === null) { $daysCls='secondary'; $daysText='?'; }
            elseif ($days <= 0) { $daysCls='danger';    $daysText=(string)$days; }
            elseif ($days <=30){ $daysCls='warning';    $daysText=(string)$days; }
            else               { $daysCls='success';    $daysText=(string)$days; }

            $amount = $it['recurring_amount'];
          ?>
            <tr>
              <td class="domain-cell">
                <span class="badge-status <?= $badge ?>"></span><?= htmlspecialchars((string)$it['name']) ?>
              </td>
              <td class="text-center"><?= htmlspecialchars((string)($it['category_name'] ?? '-')) ?></td>
              <td class="text-center"><?= htmlspecialchars((string)$it['status']) ?></td>
              <td class="text-center"><?= htmlspecialchars((string)($it['date_created'] ?? '-')) ?></td>
              <td class="text-center"><?= htmlspecialchars((string)($it['next_due'] ?? '-')) ?></td>
              <td class="text-center"><span class="badge rounded-pill bg-<?= $daysCls ?>"><?= $daysText ?></span></td>
              <td class="text-end"><?= $amount !== null ? number_format((float)$amount, 2) : '-' ?></td>
              <td class="text-center"><?= htmlspecialchars((string)($it['address'] ?? '-')) ?></td>
              <td class="text-center"><?= htmlspecialchars((string)($it['keypair_name'] ?? '-')) ?></td>
              <td class="text-center"><?= htmlspecialchars((string)($it['status_last'] ?? $it['status'])) ?></td>
            </tr>
            <?php if ($debug): ?>
              <tr class="table-active">
                <td colspan="10" class="small">
                  <details>
                    <summary>Raw item</summary>
                    <pre class="mb-0"><?= htmlspecialchars(json_encode($it['_raw'] ?? $it, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)) ?></pre>
                  </details>
                </td>
              </tr>
            <?php endif; ?>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
