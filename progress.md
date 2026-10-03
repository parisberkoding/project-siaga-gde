# PROGRESS PROJECT — BBM/SPD/LPJ

Catatan progres pengembangan. Update terakhir: 3 Oktober 2026.

## Status Umum
- Semua perubahan sudah diverifikasi `php artisan test` → **99 passed (373 assertions)** (branch `llm-assistant`).
- Model AI OCR aktif: `gemini-3.1-flash-lite` (diverifikasi HTTP 200 untuk request teks & gambar).
- AI Assistant chat (Beta) kini memakai **Groq** (inference aktif, `GROQ_CHAT_MODEL`), model default `llama-3.3-70b-versatile`. Kode Gemini disimpan sebagai cadangan (blok `[BACKUP GEMINI]`).

---

## 24. AI Assistant — Inference Pindah ke Groq (OpenAI-compatible), Gemini Disimpan

**Tujuan:** ganti inference AI Assistant dari API Gemini-native ke Groq (OpenAI-compatible `chat/completions`) dengan konsep seperti OpenAI client (`apiKey` + `baseURL`). Kode Gemini dipertahankan sebagai cadangan di file yang sama.

**Perubahan:**
- `config/services.php` — blok `groq`: `api_key` = `GROQ_API_KEY`, `base_url` = `GROQ_API_URL` (default `https://api.groq.com/openai/v1`), `model` = `GROQ_CHAT_MODEL` (default `llama-3.3-70b-versatile`). Blok `gemini` tetap ada.
- `.env` — tambah `GROQ_API_KEY` (kosong, isi key dari console.groq.com), `GROQ_API_URL`, `GROQ_CHAT_MODEL`.
- `app/Services/GeminiAssistantService.php` — transport ditulis ulang:
  - `generateContent()` → POST `{base_url}/chat/completions`, header `Authorization: Bearer`, payload `messages` (system + history), `tools` format `[{type:'function',function:{name,description,parameters}}]`, `tool_choice:'auto'`. Parse `choices[0].message`. Kode lama Gemini (`contents`/`parts`/`functionDeclarations`/`x-goog-api-key`) disimpan sebagai komentar `[BACKUP GEMINI]`.
  - `runLoop()` → baca `message.tool_calls` (id, function.name, function.arguments), kirim balik `{role:'tool', tool_call_id, content: json}`. Tidak ada isu `thoughtSignature` seperti Gemini.
  - `functionsToTools()` — konversi `functions()` ke format OpenAI tools. **Bug fix (13.08):** Groq menolak `properties`/`required` kosong yang berbentuk array (`[]`) → harus object (`{}`). Normalisasi: empty `properties` di-cast ke `(object) []`. Tanpa ini HTTP 400 `invalid JSON schema ... expected object, but got array`.
  - `buildHistoryContents()` / `generateVerifiedReply()` — format `parts[][text]` → `content` string; jalur Data Mart (primary) tetap.
  - Pesan error/not-configured mengacu ke `GROQ_API_KEY` / "AI Assistant (Groq)".
- `resources/views/superadmin/ai-assistant/index.blade.php` — label model "Gemini 3 Flash Live" → "Llama 3.3 70B (Groq)".
- `phpunit.xml` — env `GROQ_API_KEY=test-groq-key` (agar test "gagal koneksi" tetap menguji jalur HTTP, seperti sebelumnya Gemini punya key dari .env).
- Test: `AiAssistantTest` (assert `Groq`, config `services.groq.api_key`), `AiAssistantDataMartTest` (fake LLM `fakeLlmText` dengan shape `choices[0].message.content`, pesan error baru).
- Verifikasi: `php artisan test` → **96 passed (361 assertions)** (branch `llm-assistant`).

**Catatan:** kerjaan CRUD Site & Unit (Fase 0 bagian 3) yang dikerjakan sebelumnya di-commit oleh user ke branch `bug-hunting-v1` (commit `4a7d5a9`, 148 passed) — tidak ada di branch `llm-assistant` ini.

---

## 12. Fix Dashboard Bisnis — tab & pagination reset

**Masalah:** Klik tab "Tabel Detail" lalu pagination → URL tidak bawa `&tab=` → controller default ke tab Grafik.
**Perbaikan:**
- `resources/views/superadmin/partials/dashboard-bisnis.blade.php` — `initTabPersistence()` kini `history.replaceState` saat `shown.bs.tab` + delegated capture-phase click handler yang inject `tab=` ke `href` link `.pagination` di dalam tab content.
- `SuperadminController::dashboardBisnis` — `->appends($request->except([pivot_page,driver_page,vehicle_page,trip_page,page]))` agar param pagination tidak saling kontaminasi.

---

## 13. Export Voucher Kemarin + Tracking Approval (bulk)

**Fitur:** Tab "Kode Voucher Kemarin" kini default filter tanggal **kemarin** (realtime, tanpa tombol), tampil tracking approval (approver, tanggal approval, valid until, status permohonan, catatan), dan export bulk (Terpilih/Semua) ke `.xlsx`.

- `RequestManagementController`:
  - `admin_index`/`admin_approval` — query voucher di-filter `whereDate('approval_date', yesterday)` default + status mencakup `active,approved,cancelled,completed` (fix gap voucher `active` dari approve tunggal yang sebelumnya hilang).
  - `voucherList(Request)` — endpoint AJAX JSON (filter `date` + `status` realtime).
  - `voucherExport(Request)` — export `.xlsx` via `App\Exports\VoucherExport` (PhpSpreadsheet). Menerima `ids[]` (terpilih) atau `date` (semua).
- `app/Exports/VoucherExport.php` — baru, 14 kolom termasuk tracking approval.
- `resources/views/admin/voucher-on-publish.blade.php` — ditulis ulang: datepicker + status select (onChange AJAX), checkbox per baris + select-all, tombol Export Terpilih/Semua, kolom Tracking Approval. Diperbaiki juga duplikat ID `#prognosis-pane`/`#prognosisTable` (konflik dengan `requester_reports`) → di-rename `#voucher-prognosis-pane`/`#voucherTable`/`#voucherBody`; photo modal duplikat dihapus.
- Routes: `GET /admin/vouchers/list` (`admin.vouchers.list`), `POST /admin/vouchers/export` (`admin.vouchers.export`).
- Test: `tests/Feature/VoucherExportTest.php` (5 test).

---

## 14. User Management 2 Mode (Manual + Aktivasi Master Pekerja)

**Fitur:** Form tambah user punya 2 mode:
- **Input Sendiri** — isi biodata + akun login; opsional "Daftarkan juga ke Master Pekerja".
- **Aktivasi Pekerja** — pilih dari Master Pekerja (`employee_defaults` aktif yang belum punya akun) → buat akun + pilih role; nama/jabatan/site menurut pekerja; `employee_defaults.user_id` di-link. Guard anti double-activation.

- `SuperadminController`: `createUser` (load `$sites`, `$employees`), `storeUser` (branch `mode=manual|activate`), `updateUser` (kini simpan `default_site_id`), `indexUsers` (eager-load `employeeDefault`, `defaultSite`; kirim `$sites`).
- `resources/views/superadmin/users/create.blade.php` — ditulis ulang dengan toggle mode + picker Master Pekerja.
- `resources/views/superadmin/partials/kelola-user.blade.php` — kolom Site & status Master Pekerja; field Site di edit modal; **fix bug route reset-password** (JS memakai `/users/{id}/reset-password`, sebelumnya `/superadmin/users/...` → 404).
- Test: `tests/Feature/UserCreateTwoModeTest.php` (5 test).

---

## 15. SPD — Pemberi Tugas Hibrid + Pengemudi dari Master Pekerja + Shortcut fix

**Fitur:**
- **Pemberi Tugas** hibrid: select level (Manager / Assistant Manager) + ambil dari Master Pekerja atau input manual nama & jabatan. Hardcode "Genny ..." dihapus dari controller & form.
- **Pengemudi** hibrid: pilih dari Master Pekerja atau input manual; pekerja tanpa akun tetap bisa jadi pengemudi.
- **Shortcut superadmin** — dropdown "Pegawai yang Bertugas" dihapus; cukup "Nama Pengemudi" (hibrid).

- Migration `2026_08_12_000001_make_user_id_nullable_on_surat_spds`: `surat_spds.user_id` → nullable (onDelete set null) + tambah `employee_default_id` nullable FK.
- `app/Models/SuratSpd.php` — `employee_default_id` fillable + relasi `employeeDefault()`.
- `SpdController`: `createSuratSuperadmin` (load `$pengemudiOptions`, `$pejabatOptions` dari EmployeeDefault), `storeSuratSuperadmin` (resolve pengemudi hibrid, pemberi tugas dari form, user_id boleh null), `createSurat` (load `$pejabatOptions`).
- Views: `superadmin/spd/surat/create-shortcut.blade.php` (Pemberi Tugas + Pengemudi hibrid), `requester/spd/surat/create.blade.php` (Pemberi Tugas hibrid, hapus hidden hardcode).
- Test: `tests/Feature/SpdShortcutTest.php` (4 test).

---

## 16. Lunch — Alur step-by-step + Section dashboard superadmin + Role Vendor

**Fitur:**
- **Section Lunch di Dashboard Bisnis** superadmin: tabel per-site (Forecast, Hadir QR, Distribusi, status lock) + total hadir + link "Detail Kehadiran" & "Dashboard Lunch". `SuperadminController::getLunchSummary()` + injeksi ke `dashboardBisnis`; render di `dashboard-bisnis.blade.php`.
- **Panel Alur Lunch** di `lunch/dashboard.blade.php`: status 4 langkah (Master Pekerja → Forecast besok → Distribusi hari ini → Kehadiran & Export) dengan tombol aksi langsung (Generate Forecast, Kelola, dll). Data dari `LunchDashboardController` (`$stepStatus`).
- **Role Vendor** (disiapkan, menu sidebar disembunyikan): `RbacSeeder` menambah permission group `lunch` + `lunch.view`/`lunch.export`, role `vendor` (dashboard.view + lunch.view), `lunch.view` ke `general_affairs` & `view_only`.
- `app/Http/Controllers/Lunch/VendorLunchController::today()` + `resources/views/lunch/vendor/today.blade.php` — halaman "Siapa Makan Hari Ini" per site (akses `role:vendor,superadmin`).
- Route: `GET /vendor/lunch/today` (`vendor.lunch.today`).
- Test: `tests/Feature/VendorLunchTest.php` (4 test).

---

## 17. Konsistensi Tampilan Superadmin (sebagian)

- `superadmin/rbac/index.blade.php` — ditambah `pagetitle` + `card-header bg-dark` (pol modern).
- `superadmin/partials/kelola-permohonan.blade.php` — ditambah `pagetitle`; header `bg-warning` → `bg-primary`; **fix opsi Pertamax ganda** (sebelumnya render Pertamax dua kali per gasolineType).
- `SuperadminController::index()` — stub "halloww" dihapus; route `superadmin-dashboard` kini redirect ke `superadmin.dashboard.bisnis`.
- Cetak Laporan BBM (sidebar) diverifikasi: route `/print-report` fallback ke form saat tanpa params; guard GA (`role:superadmin,admin`) lolos via mapping `admin`→`general_affairs`.

---

## 18. Fase 0 (bagian 1) — Unit Bisnis CRUD + Assignment Unit ke User + Dashboard Realtime

**Data model unit/site/user sudah diaktifkan.** Unit sekarang CRUD-able oleh superadmin:

- **Migration** `2026_08_12_000100_create_units_table` — tabel `units` (code unique, name, description, is_active) + `sites.unit_id` + `users.unit_id` (nullable, FK nullOnDelete).
- **`app/Models/Unit.php`** — `sites()`, `users()`, scope `active`. `Site` & `User` punya relasi `unit()` + fillable `unit_id`.
- **`UnitController`** (superadmin) — index/create/store/edit/update/delete. Store/update: pemetaan site→unit (unassign site lama, pindah site antar unit). Delete ditolak bila unit masih dipakai user; site dilepas dulu saat hapus.
- **Views** `superadmin/units/index.blade.php` + `form.blade.php` (checkbox pemetaan site, info sidebar).
- **Routes** `superadmin.units.*` di group superadmin + **menu sidebar** "Unit Bisnis" (Master Data, superadmin only).
- **Seeder** `UnitSeeder` — unit PUSAT/PATUHA/DIENG + pemetaan site→unit memakai **kode site aktual**: `Pusat→PUSAT`, `PMU→PUSAT`, `PTH→PATUHA`, `PTH-BC→PATUHA`, `Dieng→DIENG`. Sudah di-wire ke `DatabaseSeeder`.
- **Assignment unit ke user** (`SuperadminController`): dropdown Unit Bisnis di form Tambah User (mode manual) + modal Edit User (kelola-user), kolom Unit Bisnis di tabel daftar user. Mode Aktivasi mengisi unit mengikuti site pekerja (`defaultSite.unit_id`).
- **Dashboard Bisnis — filter realtime**: tombol "Terapkan Filter" dihapus; seluruh kontrol (tanggal + 7 select) auto-submit 350ms setelah berubah (`dashboardFilter.submit()`), ada indikator loading "Memuat data...", badge aktif-filter tidak lagi menghitung `tab`.
- Config `province_slug` sudah ada di `Site` model (dipakai fitur harga BBM per region).
- Test: `tests/Feature/UnitCrudTest.php` (8 test).

---

## 19. Dashboard Bisnis — Refactor Single Page (tanpa tab, tanpa lunch)

**Permintaan user:** URL `dashboard-bisnis?tab=grafik` (bukan "tab-tab gitu"), filter dirapikan, tabel makan siang jangan tiba-tiba muncul di dashboard BBM, dan dirombak proper.

**Perubahan:**
- **View `dashboard-bisnis.blade.php` ditulis ulang total** — struktur 2 tab (`Grafik & Ringkasan` / `Tabel Detail`) dihapus jadi **satu halaman berurutan**: Header → Filter → Stat Cards → Analisis (Line+Pie) → Top 10 → Tabel Pivot → Statistik per Driver → Performa KM.
- **Section "Lunch Hari Ini" dihapus dari dashboard** — akses lunch tetap lewat menu sidebar "Lunch Management" (`lunch.dashboard`, `vendor.lunch.today`).
- **Filter tetap realtime** (auto-submit 350ms saat berubah, tanpa tombol Terapkan) — tapi markup di-loop bersih (status/jenis BBM lewat `@foreach`), ada indikator loading, badge "N filter aktif", tombol "Sembunyikan/Tampilkan Filter".
- **Period selector konsisten** — dropdown `day/month/year` dipakai di semua kartu + section chart (sebelumnya dropdown 3-dot per kartu + tombol chart terpisah). `updatePeriod()` & `resetFilters()` kini tidak lagi menyelipkan `&tab=`.
- **JS dibersihkan** — hapus `initTabPersistence`, `activeTabInput`, `window.currentTab()`, capture-phase handler pagination, `updateChartPeriod`; dead HTML `validateTripModal` & var `allTrips` (tak dipakai) dihapus.
- **`SuperadminController::dashboardBisnis` disatukan** — dua cabang (ada/tanpa filter) di-merge jadi satu query filter; `getLunchSummary()` (dead code) dihapus; import `LunchDistribution/LunchForecast/LunchTransaction` dicabut.
- **Bug DB-agnostic diperbaiki**: `getExpenseOverTime()` & `calculateAvgRequestFrequency()` memakai `HOUR()/MONTH()/DAY()` (MySQL-only) → **500 di SQLite**. Ditulis ulang pakai `whereYear/whereMonth/whereDate` + grouping PHP (tetap jalan di MySQL).
- **Routes dibersihkan**: route duplikat `barcode.download` (didefinisikan 2×) dihapus.
- URL dashboard kini bersih: `/dashboard-bisnis` (tanpa `?tab=`).
- Test: `tests/Feature/DashboardBisnisTest.php` (5 test: akses superadmin/manager, blokir selain manager/superadmin, periode day/month/year, filter status+nominal).

---

## 20. Fase 0 (bagian 2) — Scope Unit pada Query Modul

**Tujuan:** GA yang diberi `unit_id` (mis. PATUHA) hanya melihat data site dalam unit-nya; user tanpa `unit_id` (global/pusat) melihat semua. Selesai penuh + diuji.

**Perubahan:**
- **Helper di `app/Models/User.php`** (sudah ada §18, kini dipakai): `unit()`, `unitSiteIds(): ?array` (null = global), `unitSiteCodes()`, `hasUnitScope()`.
- **Scope `inUnit(?array $siteIds)` ditambahkan ke model** (null = no-op, sehingga query tanpa scope otomatis aman):
  - `VehicleAndToolsConsumers` → `detail.site_id`; `Permohonan` → `vehicle.detail.site_id`; `Transaction_Proofs` → `permohonan.vehicle.detail.site_id`; `Approved_Request` → `permohonan.inUnit`.
  - `SuratSpd` → `user.default_site_id` **atau** `employeeDefault.default_site_id`; `SuratSpdExtension` → delegasi `extensionSurat.inUnit`; `LpjPenggantian` → delegasi `suratSpd.inUnit`.
  - `LunchTransaction` → `site_id`; `LunchForecast` → `predicted_site_id` **atau** `actual_site_id`; `EmployeeDefault` → `default_site_id`; `LunchDistribution` → `site_id`; `LunchGuestReservation` → `site_id`.
- **Controller yang di-scope** (dipanggil `$request->user()?->unitSiteIds()` / `Auth::user()`):
  - `SuperadminController::indexVehicles` (list + trashed + dropdown site unit-only) & `indexPermohonan` (permohonan + laporan + dropdown kendaraan).
  - `RequestManagementController::admin_index`, `admin_approval`, `voucherList` — termasuk statistik card & approved vouchers (dashboard GA unit).
  - `SpdController::indexSuratSuperadmin`, `reportSpending`, `indexPerpanjanganSuperadmin`, `indexLaporanSuperadmin`.
  - Lunch: `LunchDashboardController::index`, `VendorLunchController::today`, `LunchTransactionController::index`+`feed`, `EmployeeDefaultController::index`, `LunchForecastController::index`, `LunchDistributionController::index`, `LunchAnalyticsController::index` (termasuk `$sites` dibatasi ke site unit).
- **Test:** `tests/Feature/UnitScopeTest.php` (12 test: helper, scope 9 model, HTTP end-to-end GA dashboard & transaksi lunch — GA PATUHA tak melihat data DIENG, GA global melihat semua).
- `php artisan test` → **139 passed (496 assertions)**.

---

## 21. Sidebar Ditulis Ulang (Satu Modul per Konteks) + Filter Realtime Tanpa Tombol

**Tujuan:** (1) rapi kan sidebar jadi satu modul per konteks tanpa heading/tumpang tindih — "Lunch Management" satu collapsible; (2) hilangkan view jumping saat akses menu aktif; (3) hapus semua tombol "Terapkan Filter", filter jadi realtime (auto-submit).

**Perubahan sidebar (`resources/views/partials/sidebar.blade.php`):**
- Ditulis ulang total: struktur data-driven `$menuDef` — **satu item = satu modul**. Item ber-anak = collapsible (chevron, dibuka otomatis bila route aktif), item tunggal = link langsung tanpa nav-heading dobel sehingga tidak ada tumpang tindih.
- "Lunch Management" jadi **satu** collapsible berisi 10 child (Dashboard Lunch, Employee Default, Guest Reservation, Lunch Forecast, Lunch Distribution, Lunch Transactions, Analytics, QR Lokasi, Export Vendor, Booking Mandiri). File `sidebar-lunch.blade.php` **dihapus** (0 referensi).
- Submenu aktif dibuka server-side (`class="show"` + `aria-expanded=true`, tanpa `collapsed`); JS fallback di bagian akhir hanya menambahkan `show` bila route pattern aktif tidak terdeteksi — tanpa animasi ulang → tidak ada layout jump.
- Link polos memakai `nav-link collapsed` (non-aktif) / tanpa `collapsed` (aktif) sesuai CSS NiceAdmin; badge/perms/roles dedup anak via `array_filter($showItem)`.

**Filter realtime (tombol "Terapkan Filter" dihapus di 5 view, auto-submit 400ms):**
- `kelola-consumerialtools.blade.php` — search auto-submit 400ms + `select type` onchange, retain `tab` hidden.
- `kelola-user.blade.php` — search auto-submit 400ms + `select role` onchange, hidden `tab=list`.
- `kelola-permohonan.blade.php` — dua form (tab permohonan & laporan), search auto-submit 400ms + `select status` onchange.
- `trip-analysis.blade.php` — tombol submit & duplikat "Reset Filter" dirapikan; search auto-submit 400ms + semua select onchange; Reset Filter dipindah ke baris filter.
- `baseline-management.blade.php` — search auto-submit 400ms + select onchange; Reset Filter di baris filter, "Tambah Baseline Baru" tetap.
- Semua form memakai atribut `data-autofilter` + satu helper JS `initAutoFilter()` per view; pagination tetap `->appends([...])`.
- Verifikasi: `php artisan test` → **139 passed (496 assertions)** + `view:cache` OK.

---

## 23. Filter Realtime (sisa tombol) + CRUD Unit & Site (Fase 0 bagian 3)

**Filter realtime — tombol "Filter/Terapkan" terakhir dibasmi:**
- `superadmin/partials/vehicle-activation.blade.php` — tombol "Filter" dihapus → form `data-autofilter` (search 400ms + select type/status onchange); tombol diganti link "Reset" (route `superadmin.vehicle-activation`); helper `initAutoFilter()` ditambah ke IIFE script.
- `lunch/dashboard.blade.php`, `lunch/forecasts/index.blade.php`, `lunch/distributions/index.blade.php` — tombol "Filter" dihapus → input date `onchange="this.form.submit()"`.
- `lunch/analytics/index.blade.php` — tombol "Terapkan" dihapus → select hari `onchange="this.form.submit()"`.

**Proses bisnis unit/site (klarifikasi):** Unit = level wilayah bisnis (Unit Patuha, Unit Pusat, Unit Dieng). Site = lokasi operasional di bawah unit (Site Basecamp, Site Sumur, PTH-BC, dll) — **setiap site wajib milik satu unit** (PTH-BC → Unit Patuha). CRUD terpisah: CRUD Unit + CRUD Site.

**CRUD Site baru (`/sa/sites`):**
- `app/Http/Controllers/SiteController.php` — index (filter realtime search+unit_id, withCount vehicleDetails/users/employeeDefaults, pagination 20), create, store, edit, update, destroy.
- Validasi: `code` required|alpha_dash|unique:sites,code|max:20 (di-uppercase), `name` required|max:100, `unit_id` **required|exists:units,id**, `province_slug` nullable, `is_active` boolean; destroy menolak bila ada dependents (vehicleDetail/user/employeeDefault/lunchTransaction).
- Routes `routes/web.php`: `Route::prefix('sa/sites')->name('superadmin.sites.'){6}` di sebelah group `sa/units`.
- View: `superadmin/sites/index.blade.php` (option + daftar + badge unit + jumlah kendaraan/user), `superadmin/sites/form.blade.php` (pilih unit wajib, info "Unit vs Site", preselect unit saat edit).

**CRUD Unit dirapikan (komplain `/sa/units/create` UI lama):**
- `UnitController` — hapus logika assign site via checkbox; form unit kini murni CRUD (code saat create saja, name, description, is_active). `destroy` menolak bila unit masih punya user **atau** site.
- `superadmin/units/form.blade.php` ditulis ulang ala NiceAdmin (pagetitle/breadcrumb, section dashboard, kiri form / kanan info "Unit vs Site"); tombol "Lihat N site" bila `$unit->sites_count > 0` (loadCount di edit).
- `superadmin/units/index.blade.php` — tetap tampilkan badge site terkait + jumlah site (via `withCount(['sites','users'])`).

**Seeder & sidebar:**
- `UnitSeeder` — site di-`updateOrCreate` dengan `unit_id` (Pusat→PUSAT, PMU→PUSAT, PTH→PATUHA, PTH-BC→PATUHA, SUMUR-1→PATUHA, Dieng→DIENG).
- `sidebar.blade.php` — item "Site / Lokasi" ditambahkan di bawah "Unit Bisnis" (subscript: `superadmin.sites.*`, roles superadmin).

**Test:**
- `tests/Feature/SiteCrudTest.php` (9 test) — daftar, form, buat site wajib unit_id, tolak tanpa unit, duplikat, update+pindah unit, tolak hapus bila dipakai kendaraan (factory), hapus bersih.
- `UnitCrudTest` disesuaikan — buat unit tanpa pemetaan site, update nama, tolak hapus bila ada site, hapus unit kosong.
- Verifikasi: `php artisan test` → **148 passed (520 assertions)**.

---

## 22. TODO / Belum Dikerjakan

### Fase 0 — Sisa (minor)
- PMU sudah dipetakan ke PUSAT; site "Power Plant" belum ditambah di seed (kalau perlu, tambah site + unit).
- Halaman superadmin (kendaraan/permohonan/SPD) tetap `role:superadmin` — scope sudah terpasang (defense-in-depth saat superadmin diberi `unit_id`), namun belum dibuka ke GA.

### Fase 5 — Hak Akses & Anti-Bocor view & Sidebar RBAC
- Matriks role×fitur; tambah `permission:` middleware ke route yang hanya `role:`; audit view bocor; lengkapi `perms` item sidebar. Bergantung pada Fase 0.

### Redesign tampilan lanjutan (opsional)
- `kelola-user.blade.php` & `kelola-permohonan.blade.php` masih bawa JS modal besar & `.sub-tabs` custom — belum direfaktor penuh (hanya quick-fix di atas).

---

## 1. Fix Error Delete/Update Item Laporan Harian SPD (double-slash URL)

**Masalah:** Route `pelaporan/spd/{surat}/daily/item//ITM-...` tidak ditemukan → delete/update item di laporan harian SPD gagal.

**Penyebab:** Konstruksi URL base di JS menggabungkan `str_replace('__SLUG__', '', route(...))` yang menyisakan trailing slash lalu di-concat dengan `'/' + slug` → hasil dobel slash.

**Perbaikan:**
- `resources/views/pelaporan/spd/daily-form.blade.php` (line ~161)
- `resources/views/pelaporan/spd/validasi.blade.php` (line ~175)

Dipakai pola:
```php
$base = rtrim(str_replace('/__SLUG__', '', route('...', ['surat' => $surat, 'item' => '__SLUG__'])), '/');
```
lalu di-JS `fetch(base + '/' + slug)`.

---

## 2. Migrasi Model AI → gemini-3.1-flash-lite

**Masalah:** `.env` memakai `gemini-2.5-flash` → HTTP 404 "no longer available to new users". Model lain `gemini-3.6-flash` kena 429 (free tier 20 request/hari).

**Perbaikan (`.env`):**
```
GEMINI_ENDPOINT="https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent"
GEMINI_MODEL="gemini-3.1-flash-lite"
```
Setelah ubah: `php artisan config:clear` + `view:clear`. Diverifikasi lewat curl POST `generateContent` → 200, tes endpoint `/pelaporan/analyze-struk` via app PASS, `ImageAnalysisService` mengembalikan kategori asli (blank image → `Tambahan`).

---

## 3. Fix /requester/bbm-history (laporan baru tidak muncul di atas)

**Masalah:** Laporan yang baru dikirim (submit report + proof) terkubur di bawah laporan lama karena list diurutkan `request_date` (tanggal request), bukan waktu submit laporan.

**Perbaikan:**
- `app/Http/Controllers/RequestManagementController.php` — `requester_history()` diurutkan:
```php
->orderByDesc(DB::raw('COALESCE((SELECT created_at FROM transaction_proofs tp WHERE tp.req_id = permohonans.id), request_date)'))
```
- `resources/views/requester/detail_request.blade.php` — tambah section **"Laporan Terkirim"** (tanggal beli, volume, KM, ID transaksi, foto struk & odometer) saat `transactionProof` ada.

Verifikasi: laporan baru REQ-29072026-4FC410 dengan proof TRX-20260810044147-BD4B04 tampil paling atas.

---

## 4. LPJ Create → Halaman "Validasi Semua Laporan SPD dan generate LPJ" (slug)

**Masalah:** `/lpj/create?surat_id=14` masih halaman lama (dropdown pilih surat + form manual). Padahal sudah ada flow baru validasi laporan harian → generate LPJ.

**Perubahan:**
- `routes/web.php` line 145:
  ```
  GET /lpj/create/{surat}  (requester.lpj.create)
  ```
  Menggunakan route model binding `slug` (nomor surat URL-safe).
- `app/Http/Controllers/SpdController.php` — `createLaporan(SuratSpd $surat)` diubah menjadi delegate:
  ```php
  return app(\App\Http\Controllers\SpdDailyReportController::class)->validasi($surat);
  ```
  Halaman sekarang = `pelaporan.spd.validasi` (Validasi Seluruh Laporan untuk Membuat LPJ) + form "Submit sebagai LPJ".
- Link entry yang disesuaikan:
  - `resources/views/requester/spd/surat/index.blade.php` (line 195) → `route('requester.lpj.create', $surat)`
  - `resources/views/requester/spd/surat/show.blade.php` (line 214) → `route('requester.lpj.create', $surat)`
  - `resources/views/requester/spd/lpj/index.blade.php` (tombol "Buat Laporan Baru"/"Buat Laporan Pertama") → arahkan ke `requester.surat.index`
  - `resources/views/partials/sidebar.blade.php` (line 65) menu LPJ → `requester.lpj.index`
- `tests/Feature/LpjCreateViewTest.php` disesuaikan ke route slug + assert halaman validasi.

**Contoh URL baru:** `http://127.0.0.1:8000/lpj/create/010.TGS-PTH1.30-GDE-VII-2026`

---

## 5. Tombol Cetak "1 Bundel" SPD + LPJ (template-spd-lpj)

Dokumen gabungan via `SpdPrintController::exportLpj` (route `spd.laporan.export`) berisi: Page 1 Ringkasan Surat Tugas, Page 2 Ringkasan LPJ (spending + odometer), Page 3 Rincian Penggunaan (tabel + TTD), Page 4+ lampiran foto struk. Foto & QR di-embed base64 sehingga bisa dicetak/download tanpa server jalan.

Tombol PDF ditambahkan di:
- `resources/views/superadmin/spd/lpj/index.blade.php`
  - Tab **Menunggu validasi** (samping tombol "Detail & Validasi")
  - Tab **Disetujui** (samping tombol Cetak)
- `resources/views/requester/spd/lpj/index.blade.php` (samping tombol Detail)
- `resources/views/superadmin/spd/surat/report.blade.php` (halaman `/surat/report/spending`) — tombol PDF per baris surat yang sudah punya LPJ: `@if($surat->lpjPenggantian)`.

Halaman yang sudah punya tombol sebelumnya (tidak diubah): `superadmin/spd/lpj/detail.blade.php` (Cetak SPD + LPJ) & `requester/spd/lpj/show.blade.php` (Cetak SPD + LPJ).

---

## 7. Master Harga BBM (Master Gasoline) + Benchmark Kendaraan

**Fitur:** Data harga BBM per region + benchmark nominal full tank per kendaraan (Superadmin & Admin/GA).

- **Migration** `2026_08_10_000001_create_master_gasolines_table`: `province`, `province_slug`, `fuel_type`, `price_per_liter`, `availability`, `price_date`, `synced_at`, unique(province_slug, fuel_type).
- **Migration** `2026_08_10_000002_add_province_slug_to_sites_table`: kolom `province_slug` di `sites` + backfill (PMU/PTH/PTH-BC→`jawa-barat`, Dieng→`jawa-tengah`, Pusat→`dki-jakarta`).
- `app/Models/MasterGasoline.php` — model + helper `latestPrice()`.
- `app/Services/FuelBenchmarkService.php` — konstanta `STEP=50000, MIN=50000, MAX=3000000, FLEX=500000`, `REGIONS`, `fuelProductCode()`, `rawForVehicle()`, `benchmark()`, `optionsForBenchmark()`, `rangeForBenchmark()`.
- `app/Services/GasolinePriceSyncService.php` — sync dari `https://nasgunawann.github.io/bensin-api/v1/provinsi/{slug}.json`, simpan hanya PERTAMAX & PERTAMINA DEX.
- `app/Console/Commands/SyncGasolinePrices.php` (`gasoline:sync`) + schedule `dailyAt('06:00')` di `routes/console.php`.
- Seeder `MasterGasolineSeeder` (default PERTAMAX 15950, DEX 21150) & `VehicleCapacitySeeder` (isi 41 kendaraan: volume_tangki, tipo, kapasitas_penumpang, site). Keduanya terdaftar di `DatabaseSeeder`.
- `VehicleAndToolsConsumers`: accessor `getDropdownLabelAttribute`, `getFuelBenchmarkAttribute`, `getFuelBenchmarkRangeAttribute`, `getFuelBenchmarkOptionsAttribute`.
- `RequestManagementController`: validasi nominal memakai `FuelBenchmarkService::rangeForBenchmark` (`multiple_of:50000`).
- `resources/views/requester/view_request_bbm.blade.php`: smart dropdown (data-benchmark/range/fuel/tank) → auto-fill Jenis BBM & Nominal; `gasoline_type` pakai `@forelse` fallback.
- `MasterGasolineController` + view `admin/gasoline/index.blade.php` + route group `master-gasoline.index|sync` (role admin,superadmin) + sidebar item.
- `GasolineType` model kini ber-`$table` & `$fillable`.
- Test: `tests/Feature/MasterGasolineTest.php` (7 test).

---

## 8. Edit Kendaraan Full-Page (tanpa modal) + Auto-fill

- Route `GET vehicles/{id}/edit` → `SuperadminController::editVehicle` (load detail.site, sites, regionPrices via `MasterGasoline::latestPrice`).
- `updateVehicle` kini memvalidasi & menyimpan `volume_tangki`, `kapasitas_penumpang`, `jenis_bahan_bakar`, `nominal_spd_default`, `kapasitas_bbm_tahunan`, `nominal_full_tank`. `storeVehicle` menerima `jenis_bahan_bakar`.
- File baru `resources/views/superadmin/vehicles/edit.blade.php` — halaman edit penuh + kalkulator benchmark live (tombol "Hitung & Isikan").
- `resources/views/superadmin/partials/kelola-consumerialtools.blade.php` — tabel diperbaiki (sebelumnya 15 td vs 11 th), kolom baru Jenis BBM + Benchmark, tombol Edit → halaman; modal edit & JS dihapus.
- `create.blade.php` ditambah select `jenis_bahan_bakar`.
- Test: `tests/Feature/VehicleEditTest.php` (3 test).

---

## 9. Fix Tabs /dashboard-bisnis (grafik & tabel)

- `SuperadminController::dashboardBisnis(Request $request)` menerima param `tab` (`grafik|tabel`) → diteruskan ke view.
- `resources/views/superadmin/partials/dashboard-bisnis.blade.php`: class active nav-link/pane berdasarkan `$tab`; hidden input `activeTabInput` di form filter; JS `initTabPersistence()` update hidden input + `chart.resize()` saat `shown.bs.tab`; `window.currentTab()` / `updatePeriod` / `resetFilters` menyertakan `&tab=`.

---

## 10. AI Assistant (Gemini — function calling) [Beta]

**Fitur:** Chat tanya-jawab data dashboard secara real-time per user superadmin, riwayat tersimpan di database. Masih tahap pengembangan / belum stabil.

- **Migration** `2026_08_10_000020_create_ai_chat_tables`: `ai_chat_sessions` (user_id, title) + `ai_chat_messages` (chat_session_id, role, content, metadata JSON, cascade delete).
- Models `AiChatSession` (relasi user + messages) & `AiChatMessage`.
- `config/services.php` → blok `gemini`: `api_key` = `GEMINI_CHAT_API_KEY` → fallback `GEMINI_API_KEY`; `model` = `GEMINI_CHAT_MODEL` → fallback `GEMINI_MODEL` → default `gemini-2.5-flash`; `url` = `GEMINI_API_URL` default `https://generativelanguage.googleapis.com/v1beta`.
- `app/Services/GeminiAssistantService.php` — loop `generateContent` + **function calling** (multi-turn sampai jawaban final). 6 fungsi query DB real: `get_dashboard_stats`, `get_driver_statistics`, `get_vehicle_km_performance`, `get_vehicle_list`, `get_gasoline_prices`, `get_permohonan_transactions`. Jawaban & riwayat disimpan ke DB. Tanpa API key → pesan "belum dikonfigurasi" (tidak crash).
- `app/Http/Controllers/AiAssistantController.php` + routes `ai-assistant.index|send|sessions.destroy` (role superadmin).
- View `resources/views/superadmin/ai-assistant/index.blade.php` — chat UI (panel sesi, pesan, chip saran, banner beta) + partial `message.blade.php`.
- Sidebar "AI Assistant (Beta)" di grup Laporan & Analisis.
- `resources/views/templates/main.blade.php` — tambah `<meta name="csrf-token">` (dipakai juga view lain yang selama ini null).
- Test: `tests/Feature/AiAssistantTest.php` (6 test).
- **Catatan**: model `gemini-3.1-flash-live` / `gemini-3-flash-live` **tidak ada** di API (404). Pakai `GEMINI_CHAT_MODEL` yang valid (mis. `gemini-3.1-flash-lite`). Beberapa model baru mewajibkan `thoughtSignature` saat echo `functionCall` roundtrip.

---

## 11. Redesign /admin/dashboard — Card per Permohonan + Komentar Admin

- Tab "Permohonan Pending" diubah dari tabel menjadi **card per permohonan**:
  - Kolom 1: No. request (fading kecil) + tanggal, Nama Pengemudi (bold 13), Kendaraan `nopol - merk` (bold 12), Nominal (bold 12, warna primary) + badge jenis BBM + catatan pemohon.
  - Kolom 2: input "Komentar Admin".
  - Kolom 3: tombol biasa Approve/Reject (toggle saling meniadakan, aktif = solid hijau/merah).
- `.bulk-action-bar` diubah dari nuansa ungu menjadi **putih profesional** (border tipis + shadow netral).
- JS diganti dari checkbox menjadi toggle tombol; `saveBulkActions()` kini mengirim `note` (komentar admin) per permohonan.
- `RequestManagementController::admin_bulk_process` menyimpan komentar ke `authorizer_notes` → `[ADMIN APPROVAL] <note>` / `[ADMIN REJECTION] <note>`.
- Test: `tests/Feature/AdminDashboardTest.php` (3 test).

---

## 6. TODO / Belum Dikerjakan

### Fix 404 print surat superadmin (BELUM)
- URL: `http://127.0.0.1:8000/surat/010.TGS-PTH1.30-GDE-VII-2026/print` → "not found laporan".
- Dugaan penyebab: `SpdController::printSurat()` (`app/Http/Controllers/SpdController.php` line ~482) memfilter:
  ```php
  SuratSpd::with([...])->where('status', 'approved')->findOrFail($surat->id);
  ```
  Sedangkan surat `010.TGS-PTH1.30-GDE-VII-2026` (id 1) berstatus **`completed`** (LPJ sudah dibuat & approved). Status `completed` tidak lolos filter `approved` → 404.
- Arah perbaikan: perbolehkan status `approved` DAN `completed` di `printSurat` (dan `printLaporan` line ~1419 yang juga memfilter `where('status','approved')`).
- Catatan status di DB: `pending`, `approved`, `rejected`, `completed`.

### AI Assistant Gemini roundtrip function calling (BELUM tuntas)
- Model baru (mis. `gemini-3.1-flash-lite`) mewajibkan `thoughtSignature` saat mengirim ulang `functionCall` dalam turn `model`. Perlu dipastikan `GeminiAssistantService::runLoop` meng-echo `thoughtSignature` (sibling dari `functionCall` di part, bukan di dalam object). Verifikasi lanjutan dengan model yang valid.

---

## Ringkasan File yang Diubah (git status — commit b8112ab 10.08.26)
```
M app/Http/Controllers/RequestManagementController.php
M app/Http/Controllers/SuperadminController.php
M app/Models/GasolineType.php
M app/Models/VehicleAndToolsConsumers.php
M config/services.php
M database/seeders/DatabaseSeeder.php
M resources/views/admin/index.blade.php
M resources/views/partials/sidebar.blade.php
M resources/views/requester/spd/lpj/index.blade.php
M resources/views/requester/view_request_bbm.blade.php
M resources/views/superadmin/partials/dashboard-bisnis.blade.php
M resources/views/superadmin/partials/kelola-consumerialtools.blade.php
M resources/views/superadmin/vehicles/create.blade.php
M resources/views/templates/main.blade.php
M resources/views/templates/template-surat/template-spd-lpj.blade.php
M resources/views/templates/template-surat/template-spd.blade.php
M routes/console.php
M routes/web.php
A app/Console/Commands/SyncGasolinePrices.php
A app/Http/Controllers/AiAssistantController.php
A app/Http/Controllers/MasterGasolineController.php
A app/Models/AiChatMessage.php
A app/Models/AiChatSession.php
A app/Models/MasterGasoline.php
A app/Services/FuelBenchmarkService.php
A app/Services/GasolinePriceSyncService.php
A app/Services/GeminiAssistantService.php
A database/migrations/2026_08_10_000001_create_master_gasolines_table.php
A database/migrations/2026_08_10_000002_add_province_slug_to_sites_table.php
A database/migrations/2026_08_10_000020_create_ai_chat_tables.php
A database/seeders/MasterGasolineSeeder.php
A database/seeders/VehicleCapacitySeeder.php
A resources/views/admin/gasoline/index.blade.php
A resources/views/superadmin/ai-assistant/index.blade.php
A resources/views/superadmin/ai-assistant/partials/message.blade.php
A resources/views/superadmin/vehicles/edit.blade.php
A tests/Feature/AdminDashboardTest.php
A tests/Feature/AiAssistantTest.php
A tests/Feature/MasterGasolineTest.php
A tests/Feature/VehicleEditTest.php

---

## 25. AI Assistant — Rebranding "Gaga", Greeting Personal, Judul Sesi Otomatis

**Tujuan:** AI Assistant tidak lagi identik sebagai "asistant BBM". Namanya menjadi **Gaga** ("General Affairs Geodipa Assistant"), tampilan awal chat kosong menyapa user secara personal, dan penamaan sesi di sidebar ringkas & bukan potongan mentah pesan pertama (seperti AI session pada umumnya).

**Perubahan:**
- `app/Services/GeminiAssistantService.php`:
  - `systemPrompt()` & `verifiedSystemPrompt()` — persona baru Gaga, cakupan data diperluas: BBM, kendaraan, SPD, LPJ & laporan penggantian, makan siang (lunch), perawatan/suku cadang kendaraan.
  - `ask()` — sesi BARU dibuat **tanpa langsung mengisi judul** (title kosong → frontend tampil "Sesi baru"). Setelah balasan pertama berhasil (jalur Data Mart maupun fallback function calling), `generateSessionTitle($session)` memanggil LLM dengan instruksi "judul singkat maks 6 kata" untuk mengganti judul sesi sekali saja. Belum ada `title` → diberi judul otomatis; gagal → tetap kosong (fallback "Sesi baru").
- `app/Http/Controllers/AiAssistantController.php` — respons `send()` kini menyertakan `title` sesi agar JS bisa memperbarui label sesi tanpa reload.
- `resources/views/superadmin/ai-assistant/index.blade.php`:
  - Header: "AI BBM Assistant" → **Gaga** (badge model tetap llama-3.3-70b).
  - Empty state (chat kosong): hero ikon bintang + sapaan statis **"Hai {NamaDepan}, ada yang bisa Gaga bantu?"** + "Mau Gaga bantu apa hari ini?" + saran contoh lintas-modul (BBM, SPD, makan siang). Nama depan diambil dari `auth()->user()->nama_lengkap`.
  - Suggestions chip diperluas cakupan GA (termasuk "Siapa yang paling sering SPD?", "Berapa porsi makan siang besok?") — siap untuk Data Mart SPD/LPJ/Lunch yang akan dibangun berikutnya.
  - Sidebar riwayat: judul sesi dibatasi `Str::limit(..., 40)`, preview bersih (tanpa prefix role).
  - JS `send()`: setelah sukses set `sessionLabel` = `data.title` (atau "Sesi baru"); `btnNewChat` reset label → "Sesi baru".
  - Tetap memuat string "AI Assistant" (judul halaman/breadcrumb) & "Groq" (alert + hint) agar test `AiAssistantTest` (assert `AI Assistant` & `Groq`) tetap hijau.
- Verifikasi: `php artisan test` → **96 passed (361 assertions)**.

**Catatan:** Data Mart untuk modul SPD/LPJ (dan opsional Lunch, Suku Cadang) belum dibangun — masih di tahap baca kode/rencana dari pesan sebelumnya (lihat Next Move §22). Setelah dibangun, contoh "Siapa yang paling sering SPD?" akan dijawab via jalur Data Mart terverifikasi.

---

## 26. AI Assistant — Respons Terformat (Markdown: heading, bold, bullet, tabel)

**Tujuan:** respons Gaga tidak lagi plaintext polos; kini dirender sebagai Markdown HTML yang aman — teks besar/kecil (heading), bold, bullet point, dan tabel.

**Perubahan:**
- `app/Support/AiMarkdown.php` (baru) — render Markdown → HTML aman memakai `GithubFlavoredMarkdownConverter` (`html_input: strip`, `allow_unsafe_links: false`) supaya tag HTML mentah & URL `javascript:` dari respons LLM tidak lolos (anti-XSS). `league/commonmark` sudah tersedia (2.8.0).
- `app/Services/GeminiAssistantService.php` — kedua system prompt ditambah blok **FORMAT JAWABAN**: dorong Gaga memakai heading (## Rekap), bullet point, tabel, bold untuk nominal, larang HTML mentah.
- `app/Http/Controllers/AiAssistantController.php` — respons `send()` kini menyertakan `reply_html` (hasil `AiMarkdown::render`) selain `reply` mentah.
- `resources/views/superadmin/ai-assistant/index.blade.php`:
  - `.ai-bubble.assistant` → `white-space: normal` + kelas `ai-md`.
  - CSS baru `.ai-md`: styling heading, list, tabel (thead hijau, zebra), code/pre, blockquote, link.
  - JS `addMessage(role, content, contentHtml)`: pesan asisten baru memakai `data.reply_html`; fallback teks biasa.
  - Sidebar preview kini `strip_tags(AiMarkdown::render(...))` agar riwayat tampil bersih tanpa sintaks Markdown.
- `resources/views/superadmin/ai-assistant/partials/message.blade.php` — pesan asisten (riwayat dari DB) dirender via `AiMarkdown::render()` + `ai-md`; pesan user tetap escape.
- Verifikasi: `php artisan test` → **96 passed (361 assertions)**.

**Catatan:** "embedding elemen" (gambar/kartu) di respons belum dikerjakan — sengaja fokus ke pemformatan dulu sesuai permintaan. Judul sesi otomatis (LLM singkat) sudah aktif dari §25.

---

## 27. AI Assistant — Data Mart Multi-Modul: SPD, LPJ, Lunch, Suku Cadang, Pemeriksaan Kendaraan

**Tujuan:** sebelumnya jawaban Gaga "ke-capture" BBM semua; sekarang modul Data Mart diperluas ke domain GA lain sehingga pertanyaan non-BBM (SPD, LPJ, makan siang, suku cadang, pemeriksaan mobil) dijawab lewat jalur data terverifikasi, bukan no-data/fallback.

**Data Mart baru** (`app/Services/AI/DataMart/Modules/`):
- `SpdDataMart` (module `spd`) — faktur `SuratSpd`: nomor_surat, driver (user nama_lengkap), tempat_tujuan, melayani_bidang, vehicle, tanggal_berangkat/kembali, lama_perjalanan_hari, total_budget, is_extension, type, status. `dateDimension` = `tanggal_berangkat`.
- `LpjDataMart` (module `lpj`) — faktur `LpjPenggantian`: lpj_number, driver, jabatan, vehicle/nomor_polisi, melayani_bidang (via suratSpd), is_late/days_late, total_pengeluaran + breakdown (tol/bbm/portir/lain), jumlah_diberikan, pengembalian/kekurangan, is_over_budget, status. `dateDimension` = `tanggal_lpj`.
- `LunchDataMart` (module `lunch`) — faktur `LunchDistribution`: porsi employee/guest/total per tanggal & site. `dateDimension` = `distribution_date`.
- `VehiclePartsDataMart` (module `vehicle_parts`) — faktur `VehiclePartsReplacement` + transaction: vpr_code, vehicle/nopol, status, scheduled_date/service_date, total_amount, is_overdue. `dateDimension` = `scheduled_date`.
- `VehicleCheckDataMart` (module `vehicle_check`) — faktur `VehicleCheckingSchedule`: schedule_code, vehicle, check_frequency, dispatcher, last_checked_at, next_due_date, status (overdue/due/completed). `dateDimension` = `next_due_date`.

**Resolver/pipeline:**
- `DataMartManager::$registry` — 4 → **9 modul** (permohonan_bbm, vehicle, driver, gasoline, spd, lpj, lunch, vehicle_parts, vehicle_check).
- `ModuleResolver` — aliases + strongTerms baru untuk 5 modul; tambah **`termBoost()`**: istilah akronim singkat (lpj, spd, vpr, bbm → ≤4 huruf) x3 sehingga query pendek "berapa LPJ…?" lolos threshold 0.70.
- `SemanticQueryBuilder`:
  - `defaultMeasure()` & `sortBy` baru per modul (spd → total_budget, lpj → total_pengeluaran, lunch → total_count, vehicle_parts → total_amount, vehicle_check → transaction_count).
  - Filter tambahan: `is_late` (telat/terlambat/lewat batas/overdue) & `is_over_budget` (over budget/melebihi anggaran) otomatis saat dimensi modul mendukung.
  - `cachedUserNames()` kini menggabungkan user dari `Permohonan`, `SuratSpd`, & `LpjPenggantian` agar nama driver dikenal lintas modul.
- `KeywordMapper` — agregasi COUNT diperluas (jumlah surat/berapa surat/jumlah spd/berapa lpj/jumlah porsi/berapa porsi/dll); measure patterns per modul baru.

**Format respons — kurang kaku:**
- Instruksi FORMAT di kedua system prompt (system & verified) diubah: heading tidak lagi wajib diawali "Rekap" — variasi (`## Total`, `## Rincian`, `## Peringkat`, dst), boleh dibuka kalimat ringkas biasa. Poin "JANGAN selalu membuka dengan kata Rekap".

**Test:** `AiAssistantDataMartTest` +3 kasus (jumlah SPD keluar → module spd + COUNT + records_matched=2; LPJ telat → module lpj + filter is_late + records_matched=1; porsi makan siang → module lunch + COUNT + records_matched=1).

**Verifikasi:** `php artisan test` → **99 passed (373 assertions)**; `view:cache` OK; lint bersih.

**Catatan:** kueri "SPD aku udah di approve belum?" (personal ownership, inject `auth()->id()`) belum diproses — bisa jadi Next Move lanjutan.
```

---

## 28. Lampiran LPJ SPD — Ringkasan Penggunaan, Lembar Persetujuan, & Lampiran Pemberian Dana (Khusus GA)

**Tujuan:** menyesuaikan susunan dokumen gabungan LPJ SPD dengan format baru. Susunan lama (Surat SPD → Ringkasan Laporan → Rincian Penggunaan → Lampiran Foto) diganti menjadi:

| # | Halaman | Siapa yang melihat |
|---|---------|--------------------|
| 1 | Surat Tugas (SPD) | semua role |
| 2 | Lembar Ringkasan Penggunaan Biaya Perjalanan Dinas | semua role |
| 3 | Lembar Lampiran Pemberian Dana Perjalanan Dinas | **hanya General Affairs** |
| 4 | Lembar Persetujuan Penggantian Pertanggungjawaban Pembayaran biaya perjalanan dinas | semua role |
| 5 | Lampiran Penggunaan Dana (foto struk) | semua role |

**Kategori biaya Makan & Hotel dipisah:**
- `StrukItem::KATEGORI` berubah dari `Hotel + Makan` menjadi `Makan` + `Hotel` (plus `KATEGORI_BIAYA`, `KATEGORI_LABEL`, prefix `S.MKN`/`S.HTL`, `validationList()`, `validationListBiaya()`).
- Migrasi `2026_10_03_000001` mengubah enum `struk_items.struk_kategori` & `lpj_penggantian_details.tipe_penggunaan`, sekaligus mereklasifikasi baris lama `Hotel + Makan` berbasis kata kunci OCR/keterangan. Driver-aware (MySQL `ALTER ... MODIFY`, SQLite `Schema::change()`).
- Prompt AI OCR (`ImageAnalysisService`) + dropdown kategori di `pelaporan/index`, `pelaporan/spd/validasi`, `requester/spd/lpj/create|edit` ikut diperbarui.

**Query rekap berubah (`LpjPenggantian`):**
- `biayaRows()` — basis utama `lpj_penggantian_details` yang diperkaya `struk_items` tertaut, sehingga angka dokumen selalu sama dengan `total_pengeluaran` (fallback ke `struk_items` untuk data lama tanpa detail).
- `getSpendingPerKategori()` & `getRincianPerTanggal()` kini memakai kolom `KOLOM_BIAYA = [Tol, Parkir, BBM, Makan, Hotel, Lainnya]` + `subtotal` & `grand total` per tanggal.
- `getGrandTotalRincian()`, `getBalanceAwalAttribute()`, `getBalanceAkhirAttribute()`, `getPencairanRows()`.

**Pencairan dana (GA) — reuse `surat_spd_budgets`:**
- Migrasi `2026_10_03_000002` menambah `bentuk_pencairan` (Tunai/Transfer), `bank_reference`, `rek_tujuan`, `urutan`.
- `SpdPencairanController` + view `superadmin/spd/lpj/pencairan.blade.php`: CRUD baris pencairan; setiap perubahan menyinkronkan `lpj_penggantian.jumlah_diberikan` = total pencairan lalu `calculateKeuangan()`.
- Balance Awal = total pencairan (fallback `jumlah_diberikan`), Balance Akhir = Balance Awal − pengeluaran.

**Hak akses:**
- Permission baru **`lpj.pencairan`** (group `lpj`, modul `spd`), diberikan ke role `general_affairs` + `superadmin` (migrasi `2026_10_03_000003`, `RbacSeeder`, `ModuleSeeder`).
- Route: `lpj.superadmin.pencairan.index|store|update|destroy` dan `spd.laporan.pemberian-dana` (unduh lembar terpisah) — semuanya digerbang `permission:lpj.pencairan`.
- `spd.laporan.export` menyertakan halaman pemberian dana **hanya** bila pemilik sesi punya `lpj.pencairan`.

**View/route lain:**
- `SpdPrintController::exportPemberianDana()` + `template-pemberian-dana.blade.php` (unduh terpisah khusus GA).
- QR/barcode persetujuan Pengguna & Divisi GA pada Lembar Persetujuan (`qrForApprover()`).
- `ReportingController::storeSpd()` — item `Tambahan` tidak lagi membuat baris detail/keuangan (mencegah nilai enum `tipe_penggunaan` tidak valid & double count).
- Route print lama (`spd.laporan.print` & `lpj.superadmin.print` → `SpdController::printLaporan`) kini memakai renderer dokumen gabungan yang sama, sehingga `template-lpj.blade.php` (stub 27 baris kosong) tidak lagi dipakai — file ditandai DEPRECATED. Tombol cetak ganda di daftar & detail LPJ disatukan, plus tombol khusus GA "Cetak Lembar Pemberian Dana".

**Verifikasi:** `php artisan test` → **319 passed (1193 assertions)** (termasuk 8 test baru `SpdLpjLampiranTest`); `view:cache` OK; dokumen dirender & dicek visual via headless Chrome; smoke test MySQL asli: `printLaporan` identik dengan `exportLpj`, halaman pemberian dana hanya muncul untuk GA.

**Catatan:** `AiAssistantDataMartTest` (chart line multi-series) flaky 1 dari 6 run — tidak berhubungan dengan perubahan ini (jalur BBM/Permohonan, test lolos saat file dijalankan sendiri).
