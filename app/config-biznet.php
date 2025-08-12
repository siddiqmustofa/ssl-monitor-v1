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
    'token'    => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJjbGllbnRfaWQiOjYzNTgsImVtYWlsIjoicnlhbkBwZW50YWNvZGUuaWQifQ.Rwc3DAXOpsb1_-Bw0-JQx_stRW-iZCKmQ_o0O4vIiuEhJcjCVSPqzzKFtSL7nKO6T2i38RzEHsddYPwddL598jqHI60ncqiY3kfdYKU1jkusmN7cZANGk44FFukq2RA9d5V1Oz17X6vrqFbY6lY69_F8z1AdIhE75Oy07jOx3xOWPKibPfgmlzQeVyl12MFZ30q71bG0_Dd1QPoGw-NuOz-HXh-5aC0oSEYNQVhnDLwBlRKnuDLh7oAgd44tJd0xX29EF-B14C91YviRl1ZDqPeJyb3s_ZLafVprma41u2XqFpdJfvM7lq4yJWhtLw0qs1R7N97Yl_zAblP6v-KRXFeGwj6l00Xjsup7hJX1IW19SfCgsgKEK8refTbFQfrOcfdF99eMeTP1kSTElB7R9XC5tfblR7dL_I3h3YLMZOMBrUXVxz6HVBFOXyJ6ywPuwrhT9r1FcgVB9BZ61-RJtE9fEaBNNT-SmdHLzHOEa9KY6UOEjYZUFq_lC0_HJv_2x8PzteOnCzmk_hHmnMHp2Ss_SRR1EJHiZPiFEVuQKaAc0dby5uRl9p33L5hZSHmyWbiPThd6R5vQH9FHKfmBn07uDCFiWMEt1j8BlmSgov6srwXnT930Q21armd0pw4C7WLi8yUjOBvX3yk2sEHWuuguOKRyXXfZtBfJQCa1suc',
  ],
];
