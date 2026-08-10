# Progress Pengembangan BBM Project

> Terakhir diperbarui: 10 Agustus 2026

---

## 1. Sidebar Permission-Driven

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Sidebar Permission-Driven |
| **Controller/View/Migration yang diubah** | `resources/views/partials/sidebar.blade.php` |
| **Pekerjaan yang dilakukan (todolist)** | Sidebar diganti dari blok `@if role` menjadi data-driven menu dari `PermissionGroup` + `getPermissionCodes()` + RBAC role codes. Normalisasi legacy ke RBAC (`admin/admin_bbm/general_affairs` → `general_affairs`). Gating: `(roles ∩ roleCodes ≠ ∅) AND (perms ∩ userPerms ≠ ∅)`. Dashboard item per-role match. Role supplements (Pengecekan, Lunch, Barcode, Laporan) tetap gated by role. |
| **Status** | Selesai |
| **Notes untuk next step** | Verifikasi render per role superadmin/GA/view_only/requester/dispatcher di browser. |

---

## 2. generateTripAnalysis + BBM SPD

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Analisis Trip BBM SPD |
| **Controller/View/Migration yang diubah** | `app/Http/Controllers/SuperadminController.php` |
| **Pekerjaan yang dilakukan (todolist)** | Query `StrukItem::where('struk_kategori','BBM')` via `suratSpd→vehicle_id`, LPJ status approved. Estimasi liter via `estimateStrukLiters()` (metadata volume → harga_per_liter → avg bill/fuel_volume). SPD BBM dalam time window pair ditambah ke `fuel_consumed`. `system_notes` append "🧾 Termasuk BBM SPD...". Test: `PelaporanTest::menggabungkan BBM SPD ke analisis trip`. |
| **Status** | Selesai |
| **Notes untuk next step** | Test sudah passed. Tidak ada aksi lanjutan. |

---

## 3. Integrasi AI/OCR Gemini Flash

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | AI/OCR Multimodal via Gemini Flash |
| **Controller/View/Migration yang diubah** | `app/Services/ImageAnalysisService.php`, `config/imageanalysis.php` |
| **Pekerjaan yang dilakukan (todolist)** | Config reads `GEMINI_API_KEY`, `GEMINI_ENDPOINT`, `GEMINI_MODEL=gemini-2.0-flash`. Service mendukung 2 format: native Gemini (`generateContent`, auth `x-goog-api-key`, body `contents/parts/inline_data`) dan OpenAI-compat (`/chat/completions`, Bearer). Deteksi via `str_contains($baseUrl, 'generateContent')`. Helper `parseContent()` robust terhadap backticks. Verified real struk: BBM, Rp 1.200.000, 48.38L, Rp 24.800/L. |
| **Status** | Selesai |
| **Notes untuk next step** | `GEMINI_API_KEY` hanya terisi di dev — perlu setup di production. |

---

## 4. Bug Fix analyzeStruk Path (Storage Disk)

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Fix Penyimpanan File Lokal |
| **Controller/View/Migration yang diubah** | `app/Http/Controllers/ReportingController.php` |
| **Pekerjaan yang dilakukan (todolist)** | Disk `local` di Laravel 11 root-nya `storage/app/private/`, bukan `storage/app/`. Fix: controller `analyzeStruk` sekarang pakai `Storage::disk('local')->path($tmpPath)` bukan `storage_path('app/...')`. File storage lokal sekarang benar-benar tersimpan → AI results muncul. |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 5. Mode Persist URL `?mode=operational`

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Mode Persist Pelaporan |
| **Controller/View/Migration yang diubah** | `resources/views/pelaporan/index.blade.php` |
| **Pekerjaan yang dilakukan (todolist)** | Mode card click → `history.replaceState`. Page load baca `?mode=` dari URL. Mode persist via URL `?mode=operational` atau `?mode=spd` (survival refresh). |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 6. BBM Operasional Simplified (Auto-Analyze)

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Pelaporan BBM Operasional |
| **Controller/View/Migration yang diubah** | `resources/views/pelaporan/index.blade.php`, `app/Http/Controllers/ReportingController.php` |
| **Pekerjaan yang dilakukan (todolist)** | Dropdown permohonan + upload foto struk → auto-analyze on file change (tanpa tombol "Analisis AI"). Panel hasil AI: "Jenis Struk" badge (Struk BBM/Tol/Parkir/Lainnya), Nominal, Volume, Harga/Liter, Tanggal, OCR text collapsible. Hidden fields: `purchase_date`, `volume_bbm`, `nominal`. Submit tanpa manual editing. |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 7. SPD Items Simplified (Auto-Analyze)

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Item Pengeluaran SPD |
| **Controller/View/Migration yang diubah** | `resources/views/pelaporan/index.blade.php` |
| **Pekerjaan yang dilakukan (todolist)** | Item card simplified — Tanggal + Foto Struk `[Choose File]` only. Hidden fields: `struk_kategori`, `nominal`, `kategori_source`, `metadata_json`, `keterangan`. Auto-analyze on upload → badge + nominal read-only. `calculateSpd()` fixed (`.item-subtotal` guard). |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 8. LPJ Create Simplified

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Halaman Buat LPJ |
| **Controller/View/Migration yang diubah** | `resources/views/requester/spd/lpj/create.blade.php` |
| **Pekerjaan yang dilakukan (todolist)** | Auto-analyze on file upload, read-only AI result display, `Simpan Laporan` button. `kategoriLabel()` helper ("Struk BBM" etc). |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 9. Surat Detail Budgeting Di-skip

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Detail Surat SPD (Approve Tanpa Budget) |
| **Controller/View/Migration yang diubah** | `resources/views/superadmin/spd/surat/detail.blade.php`, `app/Http/Controllers/SpdController.php` |
| **Pekerjaan yang dilakukan (todolist)** | Form budget per hari (Quick Fill + rows) dihapus dari `superadmin/spd/surat/detail`. Approve button langsung submit tanpa budget. `approveSurat` controller: tidak lagi mewajibkan `budgets`. Budget validation dihapus. |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 10. Master Data Kendaraan Lengkap

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Master Data Kendaraan |
| **Controller/View/Migration yang diubah** | `resources/views/superadmin/vehicles/create.blade.php`, `resources/views/superadmin/vehicles/detail-form.blade.php`, `resources/views/superadmin/partials/kelola-consumerialtools.blade.php`, `app/Http/Controllers/SuperadminController.php`, `app/Models/VehicleDetail.php`, `database/migrations/2026_08_07_000004_add_nominal_spd_default_to_vehicle_details.php` |
| **Pekerjaan yang dilakukan (todolist)** | Create form — 6 field (Site, Kapasitas Penumpang, Kapasitas Tangki, Default Nominal Operasional, Default Nominal SPD). `kapasitas_bbm_tahunan` dihapus dari form & controller. Detail form — tambah `nominal_spd_default`. List view — kolom: Site, Penumpang, Tangki(L), Budget Ops.(Rp), Budget SPD.(Rp). Model fillable: `nominal_spd_default` ditambah. Migration tambah kolom `nominal_spd_default`. |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 11. Autentikasi Diperbaiki

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Keamanan Autentikasi |
| **Controller/View/Migration yang diubah** | `app/Http/Controllers/AuthentiactionController.php`, `routes/web.php`, `resources/views/partials/navbar.blade.php` |
| **Pekerjaan yang dilakukan (todolist)** | (1) Route logout `GET\|POST` + tanpa role restriction. (2) `redirect()->intended($fallback)` + `session()->invalidate()` di login (anti session-fixation). (3) `defaultRouteForRole()` normalisasi legacy roles ke RBAC codes + RBAC pivot fallback. (4) Logout: `invalidate()` + `regenerateToken()` + `withoutCookie()`. |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 12. Audit Keamanan

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Audit Keamanan |
| **Controller/View/Migration yang diubah** | Tidak ada file spesifik (audit seluruh codebase) |
| **Pekerjaan yang dilakukan (todolist)** | SQL injection AMAN (50 raw queries semua hardcoded/binding). XSS AMAN (`{!!!!}` hanya csrf/json_encode/file_get_contents). Session fixation AMAN (invalidate+regenerate). Brute force AMAN (5 attempt/15 menit lockout). CSRF AMAN (@csrf semua form POST). |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 13. Fix Validasi KM Akhir & Foto Odometer

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Validasi BBM Operasional |
| **Controller/View/Migration yang diubah** | `app/Http/Controllers/ReportingController.php` |
| **Pekerjaan yang dilakukan (todolist)** | Bug: back-end `storeOperational` mensyaratkan `km_akhir` + `foto_km` untuk kendaraan (`consumerial_type === 'KENDARAAN'`), tapi form-nya field odometer tersembunyi (`display:none`) tanpa JS untuk menampilkannya. Fix: ubah `km_akhir` dan `foto_km` dari `required` menjadi `nullable`. |
| **Status** | Selesai |
| **Notes untuk next step** | Lanjut ke multi-file upload (todo #14). |

---

## 14. Multi-File Upload + Kategori "Tambahan"

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Multi-File Upload & Kategori Tambahan (Odometer/Dashboard) |
| **Controller/View/Migration yang diubah** | `app/Services/ImageAnalysisService.php`, `app/Models/StrukItem.php`, `app/Http/Controllers/ReportingController.php`, `resources/views/pelaporan/index.blade.php`, `resources/views/requester/spd/lpj/create.blade.php`, `database/migrations/2026_08_07_122530_make_spd_id_nullable_and_add_tambahan_to_struk_items.php`, `tests/Feature/PelaporanTest.php` |
| **Pekerjaan yang dilakukan (todolist)** | (1) `ImageAnalysisService`: SYSTEM_PROMPT tambah aturan deteksi foto odometer/dashboard → kategori "Tambahan" (bukan struk). (2) `StrukItem` model: tambah "Tambahan" ke `KATEGORI`, `KATEGORI_PREFIX` (S.TBH), `normalizeKategori()`, `getKategoriBadgeClass()` (bg-warning text-dark). (3) `ReportingController::analyzeStruk()`: dukung multi-file via `images[]` — loop tiap file, kirim ke AI, kembalikan array results. (4) `ReportingController::storeOperational()`: terima `foto_struk` (single) atau `foto_files[]` (multi). Simpan struk utama ke `transaction_proofs`. Simpan semua foto ke `struk_items` (dengan kategori, nominal, metadata per file). (5) `pelaporan/index.blade.php`: input file `multiple`, JS kirim batch ke `analyzeStruk`, render card preview per file (badge + nominal + OCR), hidden fields `struk_kategoris[]`, `nominals[]`, `keterangan_s[]`, `metadata_s[]`. Badge "Tambahan" = `bg-warning text-dark`. Summary SPD tambah row "Tambahan" (count foto). (6) LPJ create: tambah "Tambahan" ke `kategoriLabels`. (7) Migration: buat `spd_id` nullable di `struk_items`, tambah enum "Tambahan". (8) Test: update test BBM operasional kirim field multi-file baru. |
| **Status** | Selesai |
| **Notes untuk next step** | (1) Jalankan migration ke production. (2) Verifikasi upload multi-file di browser: upload struk BBM + foto odometer, pastikan AI mengkategorikan "Tambahan" untuk odometer. (3) Cek `generateTripAnalysis` apakah struk_items dengan kategori "Tambahan" perlu di-exclude dari perhitungan BBM. |

---

## 15. Refactor Alur SPD — Budget Di-decouple

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Alur SPD tanpa wajib budget |
| **Controller/View/Migration yang diubah** | `app/Http/Controllers/SpdController.php`, `app/Http/Controllers/ReportingController.php`, `resources/views/superadmin/spd/surat/create-shortcut.blade.php` |
| **Pekerjaan yang dilakukan (todolist)** | (1) Validasi `totalBudget <= 0` untuk SPD tipe `biasa` di `storeSuratSuperadmin` dihapus — budget tidak lagi syarat membuat surat. (2) `printSurat`: `$needsBudget = false` — budget tidak lagi menjadi syarat cetak (modul budgeting terpisah). (3) Input budget nominal di `create-shortcut.blade.php` tidak lagi `required`. (4) `is_over_budget` di `storeLaporan`, `updateLaporanRequester` (SpdController) & `storeSpd` (ReportingController) hanya dihitung bila budget diisi (`$totalBudgetFromTable > 0`), supaya LPJ tanpa budget tidak salah ter-flag over budget. (5) Persist field baru `odo_before`, `odo_after`, `kebutuhan_bbm_liter` di ketiga method penyimpanan LPJ. |
| **Status** | Selesai |
| **Notes untuk next step** | Flow baru: Request → Review Admin → Cetak Surat → Laporan Driver → Review LPJ → Cetak LPJ → Selesai. Modul budgeting tetap ada tapi terpisah & tidak wajib. |

---

## 16. Cetak Dokumen Gabungan SPD + LPJ

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Ekspor/Cetak Gabungan SPD & LPJ |
| **Controller/View/Migration yang diubah** | `app/Http/Controllers/SpdPrintController.php` (baru), `resources/views/templates/template-surat/template-spd-lpj.blade.php` (baru), `routes/web.php`, `resources/views/requester/spd/lpj/show.blade.php`, `resources/views/superadmin/spd/lpj/detail.blade.php` |
| **Pekerjaan yang dilakukan (todolist)** | (1) Controller `SpdPrintController@exportLpj($id)` — render 1 dokumen A4 (margin 3 cm semua sisi) berisi 4 bagian: Page 1 Ringkasan Surat Tugas (kop surat, nomor surat, pengemudi, kendaraan, bidang dilayani, daftar nama), Page 2 Ringkasan Laporan (kebutuhan BBM liter, spending BBM/Parkir/Tol/Lainnya, odometer sebelum/sesudah, total jarak tempuh), Page 3 Rincian Penggunaan (tabel pivot Tanggal\|Tol\|BBM\|Parkir\|Lainnya + Total) + 3 kotak TTD (User / General Affairs / Manager), Page 4+ Lampiran foto-foto (masonry grid 2 kolom, 6 foto/halaman, wrapper `<div class="items">` berisi credential + gambar base64, pemisah per kategori Tol→BBM→Parkir→Lainnya→Tambahan). (2) Foto struk di-embed base64 (disk public) & logo & QR signature di-cache (`SpdBarcodeSignature`) supaya tampil saat cetak tanpa server jalan. (3) Route `GET spd/laporan/{id}/export` → name `spd.laporan.export`, middleware role `requester,admin,superadmin,manager,dispatcher` (siapa pun dengan hak lihat LPJ bisa cetak). (4) Tombol "Cetak SPD + LPJ" di `requester/spd/lpj/show` & `superadmin/spd/lpj/detail` (di superadmin link cetak lama tetap dipertahankan). |
| **Status** | Selesai |
| **Notes untuk next step** | Verifikasi visual print di browser: kop surat, tabel rincian, TTD & grid foto 6/halaman. Lebar konten layar 800–1340px. |

---

## 17. Field Odometer & Kebutuhan BBM (Migration + Model Helpers)

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Dokumentasi Tempuh SPD |
| **Controller/View/Migration yang diubah** | `database/migrations/2026_08_10_000001_add_spd_lpj_trip_fields_to_lpj_penggantians.php` (baru), `app/Models/LpjPenggantian.php` |
| **Pekerjaan yang dilakukan (todolist)** | (1) Migration menambah 3 kolom nullable decimal(15,2) di `lpj_penggantians`: `odo_before`, `odo_after`, `kebutuhan_bbm_liter`. (2) Model: kolom ditambah ke `$fillable` & casts `decimal:2`. (3) Helper baru: `getTotalJarakTempuh()` (selisih odo, null jika belum lengkap), `getKendaraanLabelAttribute()` (plat + merk/model dari relasi vehicle/detail), `getSpendingPerKategori()` (total nominal per Tol/BBM/Parkir/Lainnya dari `strukItems`), `getRincianPerTanggal()` (pivot per tanggal untuk tabel cetak, di-ksort), `getStrukItemsOrderedForPhotos()` (urut kategori Tol→BBM→Parkir→Lainnya→Tambahan untuk lampiran). Migration sudah jalan di MySQL asli (`php artisan migrate`). |
| **Status** | Selesai |
| **Notes untuk next step** | Kolom bersifat opsional; tidak wajib diisi di form. |

---

## 18. Form LPJ Odometer (Pelaporan & Requester)

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Input Odometer & Kebutuhan BBM di Form LPJ |
| **Controller/View/Migration yang diubah** | `resources/views/pelaporan/index.blade.php`, `resources/views/requester/spd/lpj/create.blade.php`, `resources/views/requester/spd/lpj/edit.blade.php` |
| **Pekerjaan yang dilakukan (todolist)** | (1) Form LPJ (mode SPD di pelaporan & halaman create/edit requester) ditambah input `odo_before`, `odo_after`, `kebutuhan_bbm_liter` (numeric, opsional). (2) JS hitung total jarak tempuh live (odo_after − odo_before) ditampilkan saat kedua field terisi. (3) Nilai dikirim & dipersist lewat `storeLaporan`, `updateLaporanRequester`, `storeSpd`. |
| **Status** | Selesai |
| **Notes untuk next step** | Verifikasi tampilan & hitungan live di browser. |

---

## 19. Fix Migration SQLite Compatibility (Test Suite)

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Test Suite Berjalan di SQLite |
| **Controller/View/Migration yang diubah** | `database/migrations/2026_07_27_072221_add_completed_to_surat_spds_status.php` |
| **Pekerjaan yang dilakukan (todolist)** | Migration lama pakai `DB::statement("ALTER TABLE ... MODIFY COLUMN ... ENUM")` yang MySQL-only → 7 test gagal di SQLite in-memory (pra-ada, terbukti dengan `git stash`). Fix: ganti ke schema builder `$table->enum('status', [...])->default('pending')->change()` (up: +`completed`, down: tanpa `completed`) — kompatibel MySQL & SQLite. Setelah fix, seluruh test suite lulus. |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 20. Bug BBM Operasional: struk_bbm_path null

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Pelaporan BBM Operasional |
| **Controller/View/Migration yang diubah** | `app/Http/Controllers/ReportingController.php` (`storeOperational`) |
| **Pekerjaan yang dilakukan (todolist)** | Form operasional kirim via `foto_files[]` tanpa `foto_struk` → `struk_bbm_path` null → MySQL error. Fix: semua file (foto_struk + foto_files[]) dikumpulkan, disimpan sekali ke `struk_items`, file pertama dijadikan `struk_bbm_path`. Tambah test regresi "menyimpan laporan BBM operasional via foto_files[] tanpa foto_struk". |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 21. Laporan Harian SPD (Pre-Fixed Report) → Validasi → LPJ Final

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Alur Laporan Harian SPD |
| **Controller/View/Migration yang diubah** | `app/Http/Controllers/SpdDailyReportController.php` (baru), `app/Http/Controllers/ReportingController.php` (`index`), `app/Models/SpdDailyReport.php` (baru), `app/Models/StrukItem.php`, `app/Models/SuratSpd.php`, `app/Http/Controllers/SpdController.php` (`updateKeuanganLaporan`), `routes/web.php`, migration `2026_08_10_000002_create_spd_daily_reports_table.php`; view `pelaporan/index.blade.php` (panel SPD jadi list surat), `pelaporan/spd/daily-index.blade.php`, `daily-form.blade.php`, `validasi.blade.php`, `superadmin/spd/lpj/detail.blade.php` (form Data Keuangan) |
| **Pekerjaan yang dilakukan (todolist)** | Tabel `spd_daily_reports` + kolom `spd_daily_report_id` di `struk_items`. Driver buat laporan harian: item auto-upload sebagai **draft server-side** (refresh aman, draft dibuat otomatis saat upload pertama), lalu submit per hari. Setelah `tanggal_kembali` lewat muncul tombol "Validasi Seluruh Laporan untuk Membuat LPJ": halaman validasi menampilkan SEMUA file yang dikirim (bisa hapus/ubah nominal/kategori inline, total live), + field odometer (odo_before, odo_after, kebutuhan_bbm_liter) → "Submit sebagai LPJ" → `LpjPenggantian` pending + `LpjPenggantianDetail` per item (kategori `Tambahan` hanya jadi lampiran). Info keuangan (uang_dari/jumlah_diberikan) disembunyikan dari driver; admin GA mengisi lewat form Data Keuangan di detail LPJ superadmin (route `PUT lpj/{id}/keuangan`). |
| **Status** | Selesai |
| **Notes untuk next step** | Uji manual di browser: `/pelaporan?mode=spd` → upload item → refresh (data tetap) → submit → validasi → submit LPJ → review GA + isi data keuangan. |

---

## 22. Fix Totalisasi Parkir vs Portir

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | Total LPJ Penggantian |
| **Controller/View/Migration yang diubah** | `app/Models/LpjPenggantian.php` (`calculateTotals`), `app/Models/LpjPenggantianDetail.php` (`getTipeBadgeClass`) |
| **Pekerjaan yang dilakukan (todolist)** | Detail LPJ menyimpan enum `Parkir`, tapi totalisasi query `'Portir'` → nominal Parkir tidak terhitung (test finalize: 450.000 vs 470.000). Fix: `whereIn('tipe_penggunaan', ['Parkir','Portir'])` + badge mendukung kedua nilai. |
| **Status** | Selesai |
| **Notes untuk next step** | Tidak ada aksi lanjutan. |

---

## 16 Tests Passed (59 Assertions)

| Test | Status |
|------|--------|
| `PelaporanTest::it melayani GET /pelaporan untuk user dengan permission` | Pass |
| `PelaporanTest::it menyimpan LPJ SPD per item struk + struk_items` | Pass |
| `PelaporanTest::it menyimpan laporan BBM operasional ke transaction_proofs` | Pass |
| `PelaporanTest::it menyimpan laporan BBM operasional via foto_files[] tanpa foto_struk` | Pass |
| `PelaporanTest::it mengembalikan kategorisasi AI pada /pelaporan/analyze-struk (fallback)` | Pass |
| `PelaporanTest::it menggabungkan BBM SPD (struk_items LPJ approved) ke analisis trip` | Pass |
| `LpjCreateViewTest::it merender LPJ create dengan item upload-sederhana + Simpan Lapora…` | Pass |
| `LpjCreateViewTest::it menyembunyikan budgeting per hari di detail surat (approve tanpa…` | Pass |
| `SpdDailyReportTest::it membuat draft server-side saat upload item (fix refresh hilang)` | Pass |
| `SpdDailyReportTest::it mengirim laporan harian (status submitted)` | Pass |
| `SpdDailyReportTest::it menampilkan halaman validasi dengan semua file yang dikirim driver` | Pass |
| `SpdDailyReportTest::it membuat LPJ final dari laporan harian saat finalize` | Pass |
| `SpdDailyReportTest::it memblokir finalize sebelum tanggal selesai SPD` | Pass |
| `SpdDailyReportTest::it membutuhkan minimal 1 item pengeluaran saat finalize` | Pass |
| `ExampleTest::the application returns a successful response` | Pass |
| `Unit\ExampleTest::that true is true` | Pass |

---

## 23. Slug Refactor — Hapus ID Numerik dari Semua URL SPD

| Aspek | Detail |
|-------|--------|
| **Fitur (Mengacu pada modul)** | URL aman tanpa ID (slug/kode surat tugas) |
| **Controller/View/Migration yang diubah** | Migrations `2026_08_10_000003`–`000007` (`slug` di `surat_spds`, `lpj_penggantians`, `surat_spd_extensions`, `spd_daily_reports`, `struk_items`); Models `SuratSpd`, `LpjPenggantian`, `SuratSpdExtension`, `SpdDailyReport`, `StrukItem`; `routes/web.php`; `SpdController`, `SpdPrintController`, `SpdDailyReportController`; views `pelaporan/index`, `pelaporan/spd/daily-form|daily-index|validasi`, `requester/spd/surat/show`, `requester/spd/lpj/index|show|edit`, `requester/spd-form-perpanjangan`, `superadmin/spd/surat/index|detail|report`, `superadmin/spd/lpj/index|detail`, `superadmin/spd/perpanjangan/index|detail`, `templates/template-surat/template-spd`; tests `SpdDailyReportTest`, `LpjCreateViewTest`. |
| **Pekerjaan yang dilakukan (todolist)** | Ganti semua route `{id}` → route model binding (`{surat}`, `{laporan}`, `{perpanjangan}`, `{daily}`, `{item}`). `nomor_surat`/`lpj_number` mengandung slash sehingga tidak aman di URL → tambah kolom `slug` unique di 5 tabel; backfill `str_replace('/', '-', kode)` untuk surat/lpj/extension, slug acak `LHP-*`/`ITM-*` untuk daily report & struk item. Models: `getRouteKeyName()` = `slug` + boot `creating` auto-generate. URL lama `/surat/14/print` → `/spd/surat/010.TGS-PTH1.30-GDE-VII-2026/print`. Fix bug blade `@json()` yang terpotong saat nested parens di `daily-form`, `validasi`, dan `edit` LPJ requester (pindah ekspresi kompleks ke blok `@php`). Perbaiki test route yang kurang param `$surat`. |
| **Status** | Selesai |
| **Notes untuk next step** | Full suite 16 passed (59 assertions). Konfirmasi manual di browser: buka detail surat, validasi, upload struk, cetak SPD/LPJ; pastikan slug `-` aman untuk segment URL dan tak ada 404 karena route cache lama (`php artisan route:clear` sudah dijalankan). |
