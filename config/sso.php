<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi SSO Geodipa
|--------------------------------------------------------------------------
|
| Protokol SSO dan isi endpoint HR BELUM diputuskan IT Pusat. Karena itu
| seluruh titik yang bergantung pada protokol dikumpulkan di sini, sehingga
| saat keputusan itu turun yang berubah hanya konfigurasi + satu class adapter
| — bukan alur provisioning, mapping, dan audit yang sudah dibangun.
|
| Alur yang berlaku:
|     SSO   -> autentikasi (siapa yang boleh login)
|     HR    -> atribut kepegawaian (NIK, unit, jabatan, status)
|     SIAGA -> otorisasi (role & permission) + Master Pekerja
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Saklar utama
    |--------------------------------------------------------------------------
    | Selama `false`, route SSO menolak dengan pesan jelas dan TIDAK ada satu
    | pun perubahan perilaku pada login lokal yang sudah berjalan.
    */
    'enabled' => env('SSO_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Adapter yang dipakai
    |--------------------------------------------------------------------------
    | Kunci merujuk ke `providers` di bawah. Default sengaja `not_configured`
    | supaya percobaan memakai SSO sebelum protokolnya ditetapkan akan gagal
    | dengan pesan yang menjelaskan, bukan gagal secara misterius.
    */
    'driver' => env('SSO_DRIVER', 'not_configured'),

    /*
    |--------------------------------------------------------------------------
    | Nama provider yang disimpan di sso_identities.provider
    |--------------------------------------------------------------------------
    */
    'provider_name' => env('SSO_PROVIDER_NAME', 'geodipa'),

    /*
    |--------------------------------------------------------------------------
    | Pemetaan nama claim -> atribut internal
    |--------------------------------------------------------------------------
    | Ini bagian yang paling mungkin perlu disesuaikan setelah IT Pusat
    | memberi contoh payload. Contoh: kalau IdP mengirim `employee_unit`
    | alih-alih `unit`, cukup ubah di sini tanpa menyentuh kode.
    |
    | Biarkan null untuk atribut yang memang tidak dikirim IdP. Atribut yang
    | null akan diambil dari sumber lain (mis. Master Pekerja / HR).
    */
    'claims' => [
        'subject'  => env('SSO_CLAIM_SUBJECT', 'sub'),
        'name'     => env('SSO_CLAIM_NAME', 'name'),
        'email'    => env('SSO_CLAIM_EMAIL', 'email'),
        'unit'     => env('SSO_CLAIM_UNIT', 'unit'),
        'jabatan'  => env('SSO_CLAIM_JABATAN', 'jabatan'),
        'nik'      => env('SSO_CLAIM_NIK', 'nik'),
        'division' => env('SSO_CLAIM_DIVISION', 'division'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Adapter tersedia
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'not_configured' => App\Services\Sso\Providers\NotConfiguredProvider::class,
        // Tambahkan implementasi nyata setelah protokol ditetapkan, mis.:
        // 'oidc' => App\Services\Sso\Providers\OidcProvider::class,
        // 'saml' => App\Services\Sso\Providers\SamlProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Perilaku provisioning (Just-In-Time)
    |--------------------------------------------------------------------------
    */
    'jit' => [
        // Akun otomatis aktif dan boleh login.
        'auto_activate' => true,

        // Kalau kombinasi (jabatan + unit) belum punya mapping, user tetap
        // boleh login TETAPI dengan NOL permission dan masuk antrian review.
        // Ini fail-closed untuk HAK AKSES, bukan untuk akses masuk.
        'zero_permission_when_unmapped' => true,

        // Wajibkan membuat + menautkan baris Master Pekerja saat provisioning,
        // karena absensi Lunch me-resolve NAMA ke Master Pekerja, bukan ke akun.
        // Tanpa ini, user SSO tidak akan pernah ter-resolve di absensi makan.
        'link_master_pekerja' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Kebijakan perubahan jabatan (asimetris)
    |--------------------------------------------------------------------------
    | Revoke & downgrade berlaku langsung (keamanan lebih dulu).
    | Upgrade HARUS lewat persetujuan admin Pusat, supaya promosi di SSO tidak
    | diam-diam menaikkan hak akses.
    */
    'role_change' => [
        'auto_apply_downgrade' => true,
        'auto_apply_revoke' => true,
        'require_review_on_upgrade' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Revalidasi sesi
    |--------------------------------------------------------------------------
    | Jendela antara SSO mencabut akses dan sesi Laravel berakhir. Sesi lokal
    | default 120 menit, jadi tanpa revalidasi periodik pencabutan tidak
    | efektif. Diperiksa lewat timestamp di cache, bukan round-trip per request.
    */
    'revalidate_minutes' => env('SSO_REVALIDATE_MINUTES', 15),
];
