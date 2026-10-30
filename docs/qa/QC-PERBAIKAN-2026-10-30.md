# QC Independen — Perbaikan Pasca QA SIAGA (BBM / SPD / LPJ)

> **Ronde 2** — dokumen ini diperbarui setelah hardening `3281138` → `7f7acf3`.
> Hasil ronde 1 (4 item PARTIAL, 4 PASS DENGAN CATATAN, temuan F-01..F-04) dipertahankan di bagian
> "Ronde 2" di bawah agar jejak audit tidak hilang.

- **Tanggal QC:** 30 Oktober 2026 (Asia/Jakarta) — ronde 1 10:00–10:45, ronde 2 10:38–11:05
- **Repo:** `BBM_Project` · branch `QA-Repair` · **HEAD `7f7acf3`**
- **Delta ronde 2:** `3281138..HEAD` → 11 file, +1.135 / −13 (termasuk 3 file test baru: 14 + 14 + 3 test)
- **Total vs baseline QA:** `550aba8..HEAD` → **64 file**, +4.069 / −360
- **Sumber kebenaran tugas:** `Todolist Perbaikan Pasca QA Siaga.xlsx` (36 item; P1 = 1–8, P2 = 9–16, P3 = 17–31, P4 = 32–36)
- **Sifat:** verifikasi independen. Klaim agent sebelumnya **tidak** dipercaya; setiap status dibuktikan dari implementasi + test + harness adversarial milik QC sendiri.
- **Batas audit:** tidak ada perubahan pada kode aplikasi, test, atau konfigurasi. Hanya dokumen ini (di-commit) dan file Excel laporan QC (di luar repo).

## 1. Ringkasan eksekutif (akhir ronde 2)

| Status | P1 | P2 | P3 | P4 | Total |
|---|---|---|---|---|---|
| PASS | 8 | 8 | 13 | 4 | **33** |
| PASS DENGAN CATATAN | 0 | 0 | 2 | 1 | **3** |
| PARTIAL | 0 | 0 | 0 | 0 | **0** |
| FAIL | 0 | 0 | 0 | 0 | **0** |
| TIDAK TERVERIFIKASI | 0 | 0 | 0 | 0 | **0** |
| **Total** | **8** | **8** | **15** | **5** | **36** |

**Perubahan status dari ronde 1:** No 2 (SEC-001), No 4 (BBM-004), No 5 (SPD-002), No 6 (BBM-005) berubah **PARTIAL → PASS**; No 30 (SEC-004) berubah **PASS DENGAN CATATAN → PASS**. Tiga item tetap PDC: No 21 (VPR, `actual_cost` opsional), No 29 (SEC-002, key disimpan sebelum validasi), No 33 (LPJ-002, data lama tidak di-backfill).

**Hasil test:** full suite `./vendor/bin/pest --no-coverage` → **540 passed, 0 failed, 2.306 assertions, 51,26 s** (ronde 1: 509/0/2.195). Kenaikan **+31 test** = 14 `QaHardeningP5Test` + 14 `QaHardeningP5Ronde2Test` + 3 `DebugGuardTest`.

**Uji adversarial independen (bukan test buatan agent):** harness `/tmp/qc_r2.php` → **39 kasus, 0 gagal**; `/tmp/qc_debug.php` → guard produksi bekerja; `/tmp/qc_r2_extra.php` → 3 kasus; regresi SEC-003 19 kasus + migrasi 11/11.

**Integritas:** ronde 2 hanya **menambah** 3 file test; **0 file test lama disentuh**, 0 test dihapus, `git diff --check` bersih, 0 dependency baru, 0 artefak terlarang (`.env.example` sengaja diubah karena berisi `APP_DEBUG`).

## 2. Ronde 2 — verifikasi penutupan temuan

| ID | Temuan ronde 1 | Perbaikan (commit) | Verifikasi independen | Status |
|---|---|---|---|---|
| **F-01** | Detail LPJ lintas unit terbuka (GA/Manager/pegawai cukup menebak ID) | `12a2873` + `7f7acf3` — `LpjPenggantian::isVisibleToUser()` (`:466`), gate di `showLaporanSuperadmin` (`:1731`), `showValidasiPengguna` (`:1870`), lampiran dana (`SpdPrintController:159`), + scope unit pada 6 aksi tulis LPJ | 15 kasus harness: detail LPJ Dieng oleh GA Patuha **404**, Manager Dieng **200**, pemilik **200**, penumpang lintas unit **200**, superadmin global **200**; approve-all/approve-ga/management/reject/updateKeuangan **403**; approve-ga oleh GA unit sendiri **302 + tanda tangan tersimpan** | **Sudah Ditangani** |
| **F-02** | `bulk-process` menyetujui unit lain + voucher; `print-report` PDF semua unit; CRUD permohonan/laporan tanpa scope | `12a2873` — `inUnit()` pada bulk-process (`SuperadminController:2978`), print-report (`:514`), approver form (`:445`), update/cancel/delete permohonan (`:2722,2793,2833`), update/delete laporan (`:2877,3070`) | 12 kasus harness: bulk-process unit lain → status **tetap pending**, **voucher tidak dibuat**; update/cancel/delete → **404 (ModelNotFound)** dan data utuh; print-report rentang berisi data unit lain → **"Tidak ada data transaksi..."** bukan PDF; dropdown approver hanya 1 (unit sendiri); kontrol positif unit sendiri → **approved** | **Sudah Ditangani** |
| **F-03** | `fund-additions` & `save-mendadak-budget` menulis ke SPD unit lain; pencairan tanpa scope | `12a2873` + `7f7acf3` — `ensureUnit` (`SpdController:771,1966`), `SpdPencairanController:59-78` (baca 404 / tulis 403) | 8 kasus harness: fund-additions **403** & baris dana **tidak tertulis**; save-mendadak **403** & budget **tidak tertulis**; pencairan index **404**, store **403**; kontrol positif GA unit sendiri → 3/3 berhasil (1 baris dana, 1 baris pencairan, tanda tangan GA) | **Sudah Ditangani** |
| **F-04** | `APP_DEBUG=true` membocorkan stack trace/SQL | `0114f5a` — `.env.example:6` default `false` + guard `AppServiceProvider:43-45` | Verifikasi independen dengan `APP_ENV=production APP_DEBUG=true`: `environment=production`, **`config('app.debug')=false`**; alur lokal tidak diubah; `DebugGuardTest` (3 test) menguji ketiga sisi | **Sudah Ditangani** |

### Catatan kejujuran tentang harness ronde 2

Percobaan pertama harness saya memberi 4 "FAIL" pada jalur BBM. Penyebabnya **bug harness**, bukan bug aplikasi: `Request::create()` dibuat ulang di dalam closure sehingga tidak membawa *user resolver*, dan `UnitScope::for(null)` = global. Setelah request yang sama (ber-user) diteruskan ke controller, hasilnya **39/39 sesuai harapan**. Saya mencatatnya di sheet "Integritas Diff" agar tidak terbaca sebagai klaim bersih tanpa proses.

## 3. Temuan yang masih terbuka / diterima (non-blokir)

| ID | Tingkat | Ringkasan | Status |
|---|---|---|---|
| **F-05** | Sedang | Media non-goal tetap publik: foto body-check, bukti service/replacement, bukti lunch, bukti transfer dana SPD, foto transaksi service (`asset('storage/...')`). **Di luar teks BUG-SEC-003** — dinyatakan eksplisit, bukan PASS diam-diam. | Diterima |
| **F-06** | Sedang | Scan statis `ExceptionSanitizationTest` hanya per-baris; pola `$msg = $e->getMessage(); ... with('error',$msg)` lolos. Manual grep: 0 pelanggaran saat ini. | Diterima |
| **F-07** | Rendah | **(baru ronde 2)** `indexLaporanPengguna` menampilkan LPJ seluruh unit, tetapi `showValidasiPengguna` kini 404 untuk pegawai yang bukan pemilik/penumpang → tautan mati. Tidak ada kebocoran (justru lebih ketat), hanya UX menyesatkan. | Terbuka |
| **F-08** | Rendah | LPJ lama `jumlah_diberikan=0` tidak di-backfill; DB berubah hanya setelah GA menyimpan. | Diterima |
| **F-09** | Rendah | Idempotency key disimpan sebelum validasi slot → kirim ulang dengan key sama dalam 10 menit ditolak walau tidak ada data dibuat. | Terbuka |
| **F-10** | Rendah | Satu akun Manager memegang `lpj.validate` + `lpj.validate_management` → dapat mengisi tahap 2 & 3 (4-eyes melemah; tahap 1 tetap wajib). | Diterima |
| **F-11** | Info | **(baru ronde 2)** Guard debug hanya aktif bila `APP_ENV=production`; salah set `APP_ENV=local` mengembalikan risiko. Mitigasi: `.env.example` default false + checklist. | Diterima |
| **F-12** | Info | Storage dev sudah termigrasi (±2.150 file struk lama di `storage/app/private`); sebagian file baru adalah artefak test yang menulis ke disk asli. | Diterima |
| **F-13** | Info | Modal "Setujui LPJ" (approve-all) tanpa pemicu di view — dead UI, bukan bypass. | Diterima |
| **F-14** | Info | **(baru ronde 2)** Batas audit: isolasi unit modul master kendaraan/VPR/lunch/checking tidak diaudit menyeluruh. | Diterima |

## 4. Wajib saat deploy

1. **`php artisan migrate --force`** — migrasi backfill `2026_10_30_000001` memindahkan struk/odometer/QR lama dari `storage/app/public` ke `storage/app/private`. Tanpa ini file lama masih dapat diunduh tanpa login lewat symlink `/storage`. (Reversibel; tidak menimpa file yang sudah ada di tujuan — diverifikasi.)
2. **`APP_ENV=production`** dan `APP_DEBUG=false`. Sejak ronde 2 `.env.example` default `false` **dan** `AppServiceProvider` memaksa debug mati bila `APP_ENV=production` — guard tidak aktif bila `APP_ENV` salah (F-11).
3. **`php artisan db:seed --class=RbacSeeder`** (idempoten) agar `lpj.export` untuk `general_affairs` & `manager` tersinkron.
4. Pastikan symlink `public/storage` ada (`php artisan storage:link`) untuk media non-sensitif.
5. Verifikasi pasca-migrate: `storage/app/public/transaction_proofs` kosong dan struk lama tetap tampil.
6. Scheduler aktif (`php artisan schedule:run` tiap menit) untuk `bbm-expire-vouchers`.
7. **Smoke test lintas unit di staging** (baru): GA unit A membuka `/lpj/{id}`, `/print-report`, `/surat/{id}/fund-additions` milik unit B → harus 404/403/"Tidak ada data".
8. Disarankan: backup `storage/app/public` sebelum migrate; health-check `APP_ENV`/`config('app.debug')`.

## 5. Verdict

**Layak dirilis.** Seluruh 36 item sesuai teks Excel, dan tiga celah isolasi unit yang saya temukan sendiri di ronde 1 (F-01 detail LPJ, F-02 jalur BBM, F-03 keuangan SPD) sudah ditutup serta dikunci test — diverifikasi ulang dengan harness independen (39 kasus, 0 gagal), bukan sekadar menjalankan test buatan agent.

Syarat rilis tetap: (1) `migrate --force`; (2) `APP_ENV=production`; (3) `db:seed RbacSeeder`; (4) pastikan folder publik kosong; (5) smoke test lintas unit di staging. Sisa risiko bersifat non-blokir (F-05, F-07, F-09) dan tidak mengulang dampak yang dilarang teks Excel.

---

### Lampiran — status akhir per item

| No | Bug ID | Prioritas | Status | Bukti ringkas |
|---|---|---|---|---|
| 1 | BUG-SEC-003 | P1 | PASS | `MediaStorage.php:31,138`; `StorageFileController.php:43,126`; migrasi `2026_10_30_000001`; regresi 19 kasus |
| 2 | BUG-SEC-001 | P1 | **PASS** (dari PARTIAL) | `LpjPenggantian.php:466`; `SpdController.php:1731,1761,1850`; `SpdPrintController.php:159` |
| 3 | BUG-SPD-003 | P1 | PASS | `SuratSpd.php:58,199-240` |
| 4 | BUG-BBM-004 | P1 | **PASS** (dari PARTIAL) | `RequestManagementController.php:1183,1278`; `SuperadminController.php:2722,2793,2833,2978` |
| 5 | BUG-SPD-002 | P1 | **PASS** (dari PARTIAL) | `SpdController.php:276,767,1964`; `SpdPencairanController.php:59-78` |
| 6 | BUG-BBM-005 | P1 | **PASS** (dari PARTIAL) | `SuperadminController.php:445,514,2877,3070` |
| 7 | BUG-RBAC-001 | P1 | PASS | `SuperadminController.php:637`; `dashboard-bisnis.blade.php:115,468` |
| 8 | BUG-BBM-001 | P1 | PASS | `RequestManagementController.php:634,829`; `VoucherExpiryService.php:44` |
| 9 | BUG-CHK-002 | P2 | PASS | 0 penanda konflik di seluruh view |
| 10 | BUG-BBM-002 | P2 | PASS | `pelaporan/index.blade.php:417,444,576` |
| 11 | BUG-SPD-001 | P2 | PASS | `SuratSpd.php:897,923,927` |
| 12 | BUG-LPJ-001 | P2 | PASS | `LpjPenggantian.php:285` |
| 13 | BUG-LPJ-003 | P2 | PASS | `LpjPenggantian.php:513-540`; `SpdController.php:1783,1943` |
| 14 | BUG-CHK-003 | P2 | PASS | `BodyCheckingController.php:148,278-316` |
| 15 | BUG-EXP-001 | P2 | PASS | `RequestManagementController.php:1141-1160` |
| 16 | BUG-EXP-002 | P2 | PASS | `SpdController.php:628-632` |
| 17 | BUG-RBAC-002 | P3 | PASS | `RbacSeeder.php:24-41,319` |
| 18 | BUG-RBAC-003 | P3 | PASS | `SpdPrintController.php:74-84`; `routes/web.php:141-149` |
| 19 | BUG-CHK-001 | P3 | PASS | `DailyCheckingController.php:198-220` |
| 20 | BUG-CHK-005 | P3 | PASS | `DailyCheckingController.php:62-70` |
| 21 | BUG-VPR-001 | P3 | PASS DENGAN CATATAN | `VPRController:272-303`; `actual_cost` opsional |
| 22 | BUG-LNC-001 | P3 | PASS | `EmployeeDefaultController.php:93,114,332` |
| 23 | BUG-LNC-002 | P3 | PASS | `ModuleContext.php:70,152` |
| 24 | BUG-EFF-001 | P3 | PASS | `SuperadminController.php:3397-3437` |
| 25 | BUG-BBM-006 | P3 | PASS | `SuperadminController.php:236-350` |
| 26 | BUG-BBM-007 | P3 | PASS | `SuperadminController.php:222-227` |
| 27 | BUG-BBM-008 | P3 | PASS | `dashboard-bisnis.blade.php:9-21` |
| 28 | BUG-BBM-003 | P3 | PASS | `RequestManagementController.php:1219,1336`; `SuperadminController.php:3003` |
| 29 | BUG-SEC-002 | P3 | PASS DENGAN CATATAN | lock + idempotency `:200-217`; key disimpan sebelum validasi (F-09) |
| 30 | BUG-SEC-004 | P3 | **PASS** (dari PDC) | `helpers.php:30-41`; `AppServiceProvider.php:43-45`; `.env.example:6`; `DebugGuardTest` |
| 31 | BUG-ENV-001 | P3 | PASS | `docker-compose.yml:62`; `Dockerfile:22`; `composer.json:9` |
| 32 | BUG-CHK-004 | P4 | PASS | `barcode-scan/_checking-card.blade.php:43,49` |
| 33 | BUG-LPJ-002 | P4 | PASS DENGAN CATATAN | `SpdDailyReportController.php:786-812`; `LpjPenggantian.php:876-895` |
| 34 | BUG-UI-001 | P4 | PASS | `dashboard-bisnis.blade.php:115`; `routes/web.php:515` |
| 35 | BUG-UI-002 | P4 | PASS | `main.js:35,44,199,285` |
| 36 | BUG-BBM-009 | P4 | PASS | `ReportingController.php:727-747` |

Detail lengkap (bukti per baris, test pengunci, F-01..F-14, bukti test, integritas diff, catatan proses):
`../Laporan QC Perbaikan Pasca QA Siaga.xlsx` (5 sheet: Ringkasan, Detail 36 Item, Temuan & Risiko, Bukti Test, Integritas Diff).
