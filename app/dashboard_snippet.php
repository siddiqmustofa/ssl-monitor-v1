<?php
$whois_stats = $pdo->query("SELECT
  COUNT(*) AS total,
  SUM(CASE WHEN days_left > 30 THEN 1 ELSE 0 END) AS safe,
  SUM(CASE WHEN days_left <= 30 AND days_left IS NOT NULL THEN 1 ELSE 0 END) AS expiring,
  SUM(CASE WHEN days_left IS NULL THEN 1 ELSE 0 END) AS error,
  MAX(checked_at) AS last_checked
FROM domain_whois")->fetch();
?>
<p>📅 WHOIS Last Checked: <?= $whois_stats['last_checked'] ?></p>
