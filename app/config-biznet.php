<?php
// config.php
return [
  'app' => [
    'timezone' => 'Asia/Jakarta',
  ],

  // Integrasi Name.com
  'namecom' => [
    'user'   => 'ISI_USERNAME_NAMECOM_KAMU',
    'token'  => 'ISI_API_TOKEN_NAMECOM_KAMU',
    // Jika > 0 maka pakai nilai ini & skip API kurs; jika 0 => auto ambil via exchangerate.host
    'exrate_usd_idr' => 0,
  ],

  // Portal Biznet Gio
  'biznetgio' => [
    'endpoint' => 'https://api.portal.biznetgio.com',
    'token'    => 'API',
  ],
];
