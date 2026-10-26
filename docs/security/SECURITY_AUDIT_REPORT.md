# LAPORAN AUDIT KEAMANAN LARAVEL

**Proyek**: SIAGA — Manajemen BBM/SPD/Vehicle (Laravel)
**Tanggal**: 2026-08-19
**Metodologi**: Route listing → klasifikasi public/closed → audit injection/XSS/validasi → audit session → rekomendasi.

---

## Ringkasan Eksekutif

- **Total route teridentifikasi**: 261 (Public: 18, Closed: 243)
- **Total temuan kritis**: 5
- **Total temuan sedang**: 7
- **Total temuan rendah**: 6

| Isu Terpenting | Dampak |
|---|---|
| Path traversal + eksposur file publik tanpa auth di `/files/storage/{path}` (**terkonfirmasi bocor `.env`**) | Eksfiltrasi APP_KEY, kredensial DB, API key — full compromise |
| `StorageFileController::serve()` tanpa otorisasi berpemilik | Seluruh struk/odo/barcode semua user terbaca siapa pun |
| `GET /my/lunch-plan` (public) membocorkan direktori karyawan | PII (nama, jabatan, divisi, site) tanpa auth |
| `POST /my/lunch-plan/store` (public, tanpa CSRF kupasan ownership) | Siapa pun bisa membuat booking self-service atas nama pegawai mana pun |
| Endpoint `/storage/{path}` PUT (upload, signed URL) terdaftar | Permukaan upload tambahan bila signing lemah |

Poin positif yang sudah ada: login memakai **RateLimiter (5x per 900s)** + **session `invalidate()` sebelum login ulang** (anti session-fixation), seluruh query pakai Eloquent/prepared statement (tidak ada SQLi terkonfirmasi), `same_site=lax`, `http_only=true`, output Blade mayoritas `{{ }}`, dan **Ziggy tidak dipakai** (tidak ada eksposur seluruh route ke JS).

---

## Daftar Route

- **PUBLIC_ROUTES.md**: 18 route publik (terlampir) → `docs/security/PUBLIC_ROUTES.md`
- **CLOSED_ROUTES.md**: 243 route terlindungi (terlampir) → `docs/security/CLOSED_ROUTES.md`

---

## Temuan Kritis (Harus Segera Diperbaiki)

| No | Route | Kerentanan | Lokasi | Rekomendasi |
|----|-------|------------|--------|-------------|
| 1 | `GET /files/storage/{path}` | **Path Traversal → bocor `.env`.** Sanitasi `str_replace('../')` bisa dilewati `....//` menjadi `../` (double-dot bypass). Terkonfirmasi `storage/app/public/../../../.env` terbaca via URL `....//....//....//.env` (HTTP 200, APP_KEY & kredensial terekspos). | `app/Http/Controllers/StorageFileController.php:12` ; `routes/web.php:39` | Ganti dengan Laravel `Storage::disk('public')->response()` + `realpath()` containment check, gunakan signed URL, dan wajibkan `auth`. Jangan sanitasi manual. |
| 2 | `GET /files/storage/{path}` | **IDOR / informasi pribadi tanpa otorisasi.** Menyajikan SEMUA file `storage/app/public`: struk BBM, foto odometer, barcode, QR surat, tanpa cek kepemilikan ataupun login. | `StorageFileController.php:15-34` | Enkripsi/Randomize nama file kebal-tebak, pindahkan ke disk privat, dan layani hanya untuk `Auth::user()` pemilik terkait. |
| 3 | `GET /my/lunch-plan` | **Kebocoran PII karyawan (public).** `EmployeeDefault::...->get()` mengembalikan nama, jabatan, divisi, site default, slug semua pegawai tanpa login. | `app/Http/Controllers/Lunch/SelfBookingController.php:29-60,107` ; `routes/web.php:705-708` | Tambahkan `auth` atau batasi lookup via token rahasia per pegawai (bukan slug publik). |
| 4 | `POST /my/lunch-plan/store` | **Otorisasi rusak + penyalahgunaan resources.** Tanpa auth, siapa pun bisa `updateOrCreate` `LunchForecast` untuk slug pegawai mana pun, termasuk menimpa booking; tanpa CSRF antar-request untuk pengguna anonim dan tanpa roller `created_by`. | `SelfBookingController.php:118-171` | Wajib `auth` + verifikasi `employee_default.user_id === Auth::id()`; tambah throttle. |
| 5 | `POST /pelaporan/analyze-struk` | **Tidak ada batasan kapasitas/waktu input gambar; `path` dari AI di-`store`.** Payload gambar 10MB x 10 file = 100MB per request ke memori/kompresi; tanpa capaian kerja bisa DoS. | `app/Http/Controllers/ReportingController.php:108-144` | Tambah throttle `:3,1`, batasan total bytes, pindah proses ke queue/antrian, validasi dimensi gambar (tidak hanya mime). |

---

## Temuan Sedang

| No | Route | Kerentanan | Lokasi | Rekomendasi |
|----|-------|------------|--------|-------------|
| 1 | Semua (global) | **CSP tidak aktif.** `spatie/laravel-csp` terpasang (`config/csp.php` `enabled=true`) tetapi middleware `AddCspHeaders` tidak didaftarkan di `bootstrap/app.php` maupun route mana pun → header `Content-Security-Policy` tidak pernah terkirim (terkonfirmasi via curl). | `bootstrap/app.php:14-19` ; `config/csp.php` | Daftarkan `Spatie\Csp\Middleware\AddCspHeaders` ke global middleware. |
| 2 | Semua (auth) | **Belum ada proteksi session hijacking lanjutan.** Tidak ada `AuthenticateSession` middleware (logout dari device lain), tidak ada device fingerprinting, tidak ada deteksi session jumping. Session ID hanya diregenerate saat login/logout. | `app/Http/Controllers/AuthentiactionController.php:77,131-133` | Aktifkan `Illuminate\Session\Middleware\AuthenticateSession` + middleware `AuthenticateSession` setelah login; tambah fingerprint (UA+IP) & invalidasi bila berubah drastis. |
| 3 | `POST /my/lunch-plan/store` ; session parkir | **CSRF exception `api/*`.</strong> `bootstrap/app.php:21-25` mengecualikan semua `api/*` dari CSRF; kombinasi dengan `api/thermer-print` memunculkan permukaan POST tanpa token. | `bootstrap/app.php:21-25` | Persempit daftar exception ke URI eksak; jangan `api/*` wildcard tanpa alasan. |
| 4 | `GET /storage/{path}` + `PUT /storage/{path}` | **Endpoint upload/serve terdaftar** karena `local` disk `serve=true`. PUT memungkinkan upload via signed URL; jika ts/token lemah atau signed URL bocor dari response storage, bisa dimanfaatkan. | `config/filesystems.php:33-36` | Set `'serve' => false` kecuali dibutuhkan; gunakan disk publik symlink standar; tinjau `temporary_url` generation. |
| 5 | `POST /pelaporan/analyze-struk`, `/spd` | **Penolakan file berbasis mime saja.** `mimes:jpeg,png,jpg,webp` hanya cek ekstensi/MIME header; konten sebenarnya tidak diverifikasi → poliglot/webp-bomb bisa diupload & disajikan kembali ke admin (`{!! $qrSvgContent !!}` di template-surat). | `ReportingController.php:110,226,356-358` ; `template-spd.blade.php:637` | Validasi `finfo`/`imagecreatefrom*` untuk benar-benar gambar; jangan render SVG/HTML dari konten tak terpercaya. |
| 6 | `GET /lunch-attendance/lookup` (public) | **Enum MIME/lookup autocomplete terbuka** — throttle 60/menit masih membuka enumerasi daftar pegawai (sync via `q=`). | `PublicAttendanceController.php:49-56` ; `IdentityResolutionService.php:36-71` | Kunci hasil per kombinasi IP+rate yang jauh lebih ketat; jangan kembalikan `slug`/`id` ke publik bila tak diperlukan halaman self-booking. |
| 7 | Semua (role) | **Tidak ada eksposur `password` di log/info (bagus), tetapi `APP_DEBUG=true` & `APP_ENV=local` di `.env`** — bila ter-deploy begitu, page error akan menampilkan stack & env (termasuk kredensial). | `.env` | Pastikan production `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`. |

---

## Temuan Rendah / Best Practice

| No | Route | Catatan | Rekomendasi |
|----|-------|---------|-------------|
| 1 | Semua | **Header security belum lengkap.** Tidak ada `X-Frame-Options`/`frame-ancestors`, `X-Content-Type-Options` hanya set di `StorageFileController`; `X-Powered-By: PHP/8.4.21` tetap muncul. | Tambah middleware (X-Frame-Options DENY, X-Content-Type-Options nosniff, Referrer-Policy, remove X-Powered-By). |
| 2 | Semua | **SRI (Subresource Integrity)** sebagian asset CDN tanpa `integrity` (mis. `sweetalert2@11`, `jspdf`, `Chart.js`, bootstrap via unpkg/cdnjs). | Tambahkan `integrity` + `crossorigin` untuk semua asset CDN eksternal. |
| 3 | Auth (login) | **Kebijakan password longgar pada login validation** `min:3` (walau `change-password` wajib `min:8`). | Seragamkan validasi login minimum (8) walau sekadar mencegah akun lemah. |
| 4 | `profile.change-password` | Tidak ada verifikasi password lama, tidak ada `AuthenticateSession` → jika session dibajak, password bisa diubah tanpa tahu yang lama. | Wajibkan `current_password` + regenerasi session setelah ubah password. |
| 5 | `voucher.thermer.response`, `requester-show`, `transaction.getPhoto` | **Tidak ada cek kepemilikan eksplisit** pada banyak endpoint `{id}` milik requester; bergantung pada pola `Auth::id()` di query. | Pasang `authorize()` / scope query per user secara seragam (mis. `->where('requester_id', Auth::id())`). |
| 6 | `ai-assistant` partial | `message.blade.php:19` `{!! $content !!}` **aman** karena `AiMarkdown::render()` memakai CommonMark `html_input=strip` + `allow_unsafe_links=false`, dan chart pakai `json_encode(JSON_HEX_APOS)`. Tetap pastikan data dari model AI tidak pernah masuk `{!! }` langsung di tempat lain. | Pertahankan pola sanitasi; tambahkan tes XSS untuk konten AI. |

---

## Checklist Perbaikan

- [x] **KRITIS**: Perbaiki path traversal + tambah `auth` di `StorageFileController` (`realpath()` containment + `Route::middleware(['auth'])` di `storage.serve`). Diverifikasi: traversal `.env` → 404; file tanpa login → redirect login.
- [~] **KRITIS**: Batasi `POST /my/lunch-plan/store` dengan throttle (`throttle:20,1`) + `GET /my/lunch-plan` hanya menampilkan direktori pegawai saat terautentikasi. (ownership penuh belum ditambahkan karena fitur self-booking memang public via QR).
- [x] **KRITIS**: Batasi upload struk di `/pelaporan/analyze-struk` dengan `throttle:10,1`.
- [x] Tambahkan header keamanan: CSP via `AddCspHeaders` (preset kustom `AppCspPreset`), `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`.
- [ ] Set cookie dengan HttpOnly (sudah) & Secure (`SESSION_SECURE_COOKIE=true` di production).
- [x] Hapus header X-Powered-By (middleware `SecurityHeaders`).
- [x] Persempit CSRF exception dari `api/*` wildcard → hanya `api/thermer-print` (semua route `api/*` adalah GET, tak butuh token).
- [ ] Hapus komentar/route disabled yang sensitif tersisa (mengurangi info bagi attacker).
- [ ] Tambahkan SRI pada asset CDN tanpa `integrity`.
- [ ] Aktifkan `AuthenticateSession` + fingerprinting untuk deteksi session jumping.
- [ ] Set `serve => false` pada disk `local` (route `storage.local` GET + `storage.local.upload` PUT framework dihapus).
- [ ] `ProfileController@changePassword` wajib `current_password` + regenerate session (diterapkan; validasi sudah).
- [ ] `APP_DEBUG=true` / `APP_ENV=local` → matikan saat production (dev-only saat ini).