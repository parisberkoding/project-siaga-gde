# QC Independen — Perbaikan Pasca QA SIAGA (BBM / SPD / LPJ)

- **Tanggal QC:** 30 Oktober 2026 (Asia/Jakarta)
- **Repo:** `BBM_Project` · branch `QA-Repair` · HEAD `3281138`
- **Basis perbandingan:** `550aba8..HEAD` → 57 file, +2.934 / −347 baris
- **Sumber kebenaran tugas:** `Todolist Perbaikan Pasca QA Siaga.xlsx` (36 item; P1 = 1–8, P2 = 9–16, P3 = 17–31, P4 = 32–36)
- **Sifat:** verifikasi independen. Klaim laporan subagent (`reports/p1..p4.md`) dan komentar kode **tidak** dipercaya; setiap status dibuktikan dari implementasi + test + uji adversarial.
- **Batas audit:** tidak ada satu pun perubahan pada kode aplikasi, test, atau konfigurasi. Hanya dokumen ini (di-commit) dan file Excel laporan QC (di luar repo).

## 1. Ringkasan eksekutif

| Status | P1 | P2 | P3 | P4 | Total |
|---|---|---|---|---|---|
| PASS | 4 | 8 | 12 | 4 | **28** |
| PASS DENGAN CATATAN | 0 | 0 | 3 | 1 | **4** |
| PARTIAL | 4 | 0 | 0 | 0 | **4** |
| FAIL | 0 | 0 | 0 | 0 | **0** |
| TIDAK TERVERIFIKASI | 0 | 0 | 0 | 0 | **0** |
| **Total** | **8** | **8** | **15** | **5** | **36** |

**Hasil test:** full suite `./vendor/bin/pest --no-coverage` → **509 passed, 0 failed, 2.195 assertions, 60,30 s**. Baseline 457 test (456 lulus + 1 gagal pre-existing `AiAssistantDataMartTest`) tidak dijalankan ulang agar worktree tidak di-checkout; hitungan 457 + 52 test baru = 509 **cocok**.

**Integritas test:** tidak ada test yang dihapus (`git diff --diff-filter=D` kosong) dan tidak ada assertion yang dilonggarkan. Satu-satunya ekspektasi lama yang berubah — `ValidasiPerjalananUserTest` — justru **diperkuat** (GA tidak lagi final; ditambah tahap Manager + assert `approved_by_management`). `git diff --check` bersih, tidak ada `.env`/`vendor`/`storage`/`xlsx` ter-commit, tidak ada dependency baru.

**Kesimpulan:** semua aksi yang secara eksplisit disebut 36 item sudah dikerjakan dan dikunci test. Namun QC ini menemukan **tiga celah isolasi unit** yang tidak ada di to-do list dan membuat 4 item P1 berstatus PARTIAL — dampak yang dilarang teks Excel masih dapat dicapai lewat endpoint lain.

## 2. Lima temuan teratas

| ID | Tingkat | Inti | Bukti |
|---|---|---|---|
| **F-01** | Kritis | Halaman **detail LPJ** tidak memvalidasi unit/kepemilikan: `GET /lpj/{id}` (GA), `/lpj-management/{id}` (Manager), `/lpj-pengguna/{id}` (pegawai) memuat LPJ lintas unit hanya dengan menebak ID. | `SpdController.php:1728-1742`, `:1830-1842`; runtime GA Patuha → LPJ Dieng = **HTTP 200** |
| **F-02** | Kritis | Isolasi unit BBM bocor lewat dua jalur lain: `POST /kelola-permohonan/bulk-process` **menyetujui permohonan + membuat voucher unit lain**; `GET /print-report` (`permission:bbm.export`) mencetak **PDF seluruh unit** termasuk foto struk. Turunan: `update/cancel/deletePermohonan` & `update/deleteLaporan` tanpa scope (hapus permohonan unit lain berhasil). | `SuperadminController.php:2943-2998`, `:458-520`, `:2705-2820`, `:2852-2870`, `:3038-3050`; runtime: approved + voucher dibuat, delete berhasil, PDF 18 KB berisi data unit Dieng |
| **F-03** | Tinggi | `POST /surat/{id}/fund-additions` dan `/surat/{id}/save-mendadak-budget` tanpa validasi unit → GA unit lain dapat mengubah keuangan SPD unit lain. | `SpdController.php:767-800`, `:1930-1945`; runtime: 1 baris dana + 1 baris budget tertulis di SPD unit lain |
| **F-04** | Tinggi | `.env` dan `.env.example` memakai `APP_DEBUG=true` → exception yang tidak tertangkap tetap membocorkan stack trace + SQL, sehingga sanitasi `safe_exception_message()` hanya efektif pada jalur yang di-catch. | `.env:4`, `.env.example:4` |
| **F-05** | Sedang | Non-goal yang **tetap publik**: foto body-check, bukti service/replacement, bukti pembayaran lunch, bukti transfer dana SPD, foto transaksi service — semuanya masih `asset('storage/...')`. Dinyatakan eksplisit di luar cakupan BUG-SEC-003 (teks Excel hanya struk & odometer), bukan PASS diam-diam. | `body-check/show.blade.php:423`, `replacement/show.blade.php:406`, `superadmin/transaction-report/index.blade.php:239`, `spd/surat/detail.blade.php:461` |

Temuan lengkap F-01..F-12 ada di sheet **Temuan & Risiko** pada file Excel.

## 3. Butir yang berstatus PARTIAL (jelas dan terukur)

1. **BUG-SEC-001 (No 2)** — cetak SPD, cetak LPJ, dan export LPJ **sudah** tertutup (`isVisibleToUser` + gate `lpj.export|pemilik`, 404 untuk yang tidak berhak; teruji). Yang belum: halaman detail LPJ (F-01).
2. **BUG-BBM-004 (No 4)** — `admin_process_request` & `admin_bulk_process` **sudah** unit-scoped dan teruji. Yang belum: jalur approval ketiga `superadmin.permohonan.bulk-process` (F-02).
3. **BUG-SPD-002 (No 5)** — `showSuratSuperadmin`, `approveSurat`, `rejectSurat` **sudah** memakai `UnitScope::ensureUnit` (runtime 403). Yang belum: `fund-additions` & `save-mendadak-budget` (F-03).
4. **BUG-BBM-005 (No 6)** — `dashboardBisnis` + seluruh dataset/opsi filter **sudah** unit-scoped dan teruji. Yang belum: `/print-report` (F-02).

## 4. Wajib saat deploy

1. **`php artisan migrate --force`** — menjalankan migrasi backfill `2026_10_30_000001` yang memindahkan struk/odometer/QR lama dari `storage/app/public` ke `storage/app/private`. Tanpa ini file lama **masih dapat diunduh tanpa login** lewat symlink `/storage`. (Migrasi reversibel; `down()` mengembalikan file; file yang sudah ada di tujuan tidak ditimpa — diverifikasi.)
2. **`APP_ENV=production` + `APP_DEBUG=false`** — tanpa ini F-04 membuat seluruh sanitasi exception tidak berarti.
3. **`php artisan db:seed --class=RbacSeeder`** — agar `lpj.export` untuk `general_affairs` & `manager` tersinkron (seeder idempoten, aman diulang).
4. **Pastikan symlink `public/storage` ada** (`php artisan storage:link`) untuk media non-sensitif; media sensitif tidak lagi melewati symlink.
5. **Verifikasi pasca-migrate:** `storage/app/public/transaction_proofs` kosong, dan struk lama tetap tampil di aplikasi (fallback baca disk privat → publik).
6. **Scheduler aktif** (`php artisan schedule:run` tiap menit) agar `bbm-expire-vouchers` berjalan tiap 10 menit.
7. Disarankan: backup `storage/app/public` sebelum migrate, dan uji cetak/export LPJ untuk role Manager & GA di staging.

## 5. Verdict

**Layak dirilis dengan syarat.** Perbaikan yang diminta 36 item selesai, teruji, dan tidak ada test yang "dihijaukan" dengan cara curang. Namun rilis sebaiknya menyertakan tiga hal berikut, karena QC ini menemukan jalur lintas unit yang belum ditutup:

- (a) jalankan migrasi backfill + matikan `APP_DEBUG` (wajib, tanpa syarat);
- (b) jadwalkan patch lanjutan untuk F-01, F-02, F-03 paling lambat satu sprint;
- (c) sampai patch itu ada, batasi role Admin GA ke satu unit (atau audit manual aktivitas `superadmin.permohonan.*` dan `surat.superadmin.fund-additions`).

Tanpa (a) dan (b), rilis masih meninggalkan akses struk lama via symlink dan akses data lintas unit — keduanya adalah dampak yang justru ingin dihilangkan oleh P1.

---

### Lampiran — status per item

| No | Bug ID | Prioritas | Status | Bukti ringkas |
|---|---|---|---|---|
| 1 | BUG-SEC-003 | P1 | PASS | `MediaStorage.php:31,138` + `StorageFileController.php:43,126` + migrasi `2026_10_30_000001` |
| 2 | BUG-SEC-001 | P1 | PARTIAL | `SpdPrintController.php:63-84`, `SpdController.php:626-637,1991-2005`; detail LPJ bocor (F-01) |
| 3 | BUG-SPD-003 | P1 | PASS | `SuratSpd.php:58,199-240` (+ `SpdNumberingTest`) |
| 4 | BUG-BBM-004 | P1 | PARTIAL | `RequestManagementController.php:1183,1278`; bulk-process bocor (F-02) |
| 5 | BUG-SPD-002 | P1 | PARTIAL | `SpdController.php:276,305,390`; fund-additions bocor (F-03) |
| 6 | BUG-BBM-005 | P1 | PARTIAL | `SuperadminController.php:655-738`; print-report bocor (F-02) |
| 7 | BUG-RBAC-001 | P1 | PASS | `SuperadminController.php:637-646`, `dashboard-bisnis.blade.php:115,468`, `DriverDashboardController.php:43` |
| 8 | BUG-BBM-001 | P1 | PASS | `RequestManagementController.php:634,829`, `ReportingController.php:435`, `VoucherExpiryService.php:44` |
| 9 | BUG-CHK-002 | P2 | PASS | `body-check/create.blade.php` (0 penanda konflik di seluruh repo) |
| 10 | BUG-BBM-002 | P2 | PASS | `pelaporan/index.blade.php:417,444,576` + `ReportingController.php:505` |
| 11 | BUG-SPD-001 | P2 | PASS | `SuratSpd.php:897,923,927` |
| 12 | BUG-LPJ-001 | P2 | PASS | `LpjPenggantian.php:284` (+ `LpjOverBudgetFormulaTest`) |
| 13 | BUG-LPJ-003 | P2 | PASS | `LpjPenggantian.php:513-525`, `SpdController.php:1755,1894` |
| 14 | BUG-CHK-003 | P2 | PASS | `BodyCheckingController.php:148,278-316` |
| 15 | BUG-EXP-001 | P2 | PASS | `RequestManagementController.php:1141-1160` |
| 16 | BUG-EXP-002 | P2 | PASS | `SpdController.php:628-632` |
| 17 | BUG-RBAC-002 | P3 | PASS | `RbacSeeder.php:24-41,319` |
| 18 | BUG-RBAC-003 | P3 | PASS | `SpdPrintController.php:74-84`, `routes/web.php:141-149`, `RbacSeeder.php:194,229` |
| 19 | BUG-CHK-001 | P3 | PASS | `DailyCheckingController.php:198-220` (+ 3 controller lain) |
| 20 | BUG-CHK-005 | P3 | PASS | `DailyCheckingController.php:62-70` (+ pola sama) |
| 21 | BUG-VPR-001 | P3 | PASS DENGAN CATATAN | `VehicleSparepartRequestRepairController.php:272-303`, `routes/web.php:376`; `actual_cost` opsional |
| 22 | BUG-LNC-001 | P3 | PASS | `Lunch/EmployeeDefaultController.php:93,114,332` |
| 23 | BUG-LNC-002 | P3 | PASS | `ModuleContext.php:70,152` |
| 24 | BUG-EFF-001 | P3 | PASS | `SuperadminController.php:3397-3437` |
| 25 | BUG-BBM-006 | P3 | PASS | `SuperadminController.php:236-350` |
| 26 | BUG-BBM-007 | P3 | PASS | `SuperadminController.php:222-227` |
| 27 | BUG-BBM-008 | P3 | PASS | `dashboard-bisnis.blade.php:9-21` |
| 28 | BUG-BBM-003 | P3 | PASS | `RequestManagementController.php:1219,1336`, `SuperadminController.php:3003` |
| 29 | BUG-SEC-002 | P3 | PASS DENGAN CATATAN | `RequestManagementController.php:200-217`, `view_request_bbm.blade.php:151,495`; key disimpan sebelum validasi (F-09) |
| 30 | BUG-SEC-004 | P3 | PASS DENGAN CATATAN | `helpers.php:30-41` + ±60 titik di 16 controller; `APP_DEBUG=true` (F-04) |
| 31 | BUG-ENV-001 | P3 | PASS | `docker-compose.yml:62`, `Dockerfile:22`, `composer.json:9` |
| 32 | BUG-CHK-004 | P4 | PASS | `barcode-scan/_checking-card.blade.php:43,49` |
| 33 | BUG-LPJ-002 | P4 | PASS DENGAN CATATAN | `SpdDailyReportController.php:786-812`, `LpjPenggantian.php:819-838`; data lama tidak di-backfill (F-08) |
| 34 | BUG-UI-001 | P4 | PASS | `dashboard-bisnis.blade.php:115`, `routes/web.php:515` |
| 35 | BUG-UI-002 | P4 | PASS | `main.js:35,44,199,285` (+ test Node) |
| 36 | BUG-BBM-009 | P4 | PASS | `ReportingController.php:727-747`, `ImageCompressor.php:21-58` |

Detail lengkap (bukti per baris, test pengunci, catatan/risiko, temuan F-01..F-12, bukti test, dan integritas diff) ada di:
`../Laporan QC Perbaikan Pasca QA Siaga.xlsx` (5 sheet: Ringkasan, Detail 36 Item, Temuan & Risiko, Bukti Test, Integritas Diff).
