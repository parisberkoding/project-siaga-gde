# SIAGA Development Phase

**Sistem Informasi Administrasi General Affairs**
**Geodipa Energy** — PMU, Patuha, BCMP

---

> Dokumentasi ini dibuat untuk dikonversi ke Excel. Setiap sheet dipisahkan oleh `---`.

---

## SHEET 1: Overview Project

| Fitur | Deskripsi Fitur | Kebutuhan Data |
|-------|----------------|----------------|
| **1. BBM Request** | Permohonan BBM oleh driver/requester, approval oleh admin, upload bukti transaksi (struk + odometer), dan pelaporan | `users`, `permohonans`, `approved_requests`, `transaction_proofs`, `gasoline_types`, `vehicle_and_tools_consumers`, `user_slot_adjustments` |
| **2. SPD (Surat Perjalanan Dinas)** | Pembuatan surat tugas perjalanan dinas dengan detail peserta, kendaraan, budget, dan approval superadmin | `users`, `surat_spds`, `spd_surat_details`, `vehicle_and_tools_consumers`, `surat_spd_budgets`, `surat_spd_extensions`, `spd_barcode_signatures` |
| **3. LPJ (Laporan Pertanggungjawaban)** | Laporan pengeluaran setelah perjalanan dinas dengan breakdown biaya per kategori (BBM, Tol, Parkir, Lain-lain) + approval berjenjang | `surat_spds`, `lpj_penggantians`, `lpj_penggantian_details`, `users`, `vehicle_and_tools_consumers` |
| **4. Vehicle Daily Checking** | Pengecekan harian kondisi kendaraan (kesiapan, keselamatan, kelengkapan) dengan status ok/ng per part | `vehicle_checkings`, `vehicle_checking_details`, `vehicle_checking_item_results`, `vehicle_parts`, `vehicle_and_tools_consumers` |
| **5. Vehicle Weekly Checking** | Pengecekan mingguan kondisi kendaraan (sama struktur dengan daily) | Sama dengan Daily Checking |
| **6. Vehicle Monthly Checking** | Pengecekan bulanan kondisi kendaraan | Sama dengan Daily Checking |
| **7. Vehicle Body Inspection** | Pengecekan kondisi body kendaraan dengan status baik/cukup/kurang/tidak + foto kerusakan | `vehicle_checking_details`, `vehicle_checking_item_results`, `vehicle_parts`, `damage_photos` |
| **8. Vehicle Sparepart Replacement** | Request penggantian part dari dispatcher, approval admin, penjadwalan service, dan transaksi service oleh driver | `vehicle_parts_replacements`, `vehicle_parts_replacement_details`, `vehicle_parts_replacement_transactions`, `vprt_details`, `damage_photos` |
| **9. Vehicle Barcode/QR Scan** | Barcode QR untuk setiap kendaraan, scan untuk melihat riwayat pengecekan & kesehatan kendaraan | `vehicle_barcodes`, `vehicle_and_tools_consumers` |
| **10. Vehicle Health Matrix** | Matrix kesehatan kendaraan untuk superadmin (overview semua kendaraan) | Semua tabel Vehicle Checking |
| **11. Dispatcher Dashboard** | Dashboard untuk dispatcher: overview jadwal pengecekan (overdue/due/upcoming), assign PIC, generate period | `vehicle_checking_schedules`, `vehicle_checkings`, `users`, `vehicle_and_tools_consumers` |
| **12. Dispatcher Export PDF** | Export checklist pengecekan ke PDF (routine check, body check) | Semua tabel Vehicle Checking |
| **13. Fuel Trip Analysis** | Analisis efisiensi BBM per trip: perbandingan KM, konsumsi, efisiensi (KM/L), deteksi premature refueling | `vehicle_trip_analyses`, `trip_validations`, `vehicle_fuel_baselines`, `transaction_proofs`, `vehicle_and_tools_consumers` |
| **14. Lunch Employee Master** | Data master pegawai untuk lunch management, termasuk alias nama untuk fuzzy matching | `employee_defaults`, `employee_aliases`, `users`, `sites` |
| **15. Lunch Forecast Engine** | Prediksi jumlah makan siang H+1 per site berdasarkan: override manual, konfirmasi pengguna, historical pattern (learning profile), default site | `lunch_forecasts`, `lunch_forecast_batches`, `learning_profiles`, `employee_defaults`, `sites`, `lunch_transactions` |
| **16. Lunch Distribution** | Snapshot distribusi makan siang per site per hari yang di-lock untuk dikirim ke vendor catering | `lunch_distributions`, `sites`, `lunch_forecast_batches` |
| **17. Lunch QR Attendance** | Absensi makan siang via QR code (public, no auth): lookup nama, konfirmasi token, scan timestamp | `lunch_transactions`, `employee_defaults`, `employee_aliases`, `sites` |
| **18. Lunch Guest Reservation** | Reservasi tamu untuk makan siang di site tertentu, dengan konfirmasi H-2 | `lunch_guest_reservations`, `sites`, `users` |
| **19. Lunch Analytics** | Dashboard analitik lunch: tren, akurasi prediksi, distribusi per site | Semua tabel Lunch |
| **20. Catering Export** | Export Excel multi-sheet untuk vendor catering (3 sheets: data per site) | Semua tabel Lunch |
| **21. Superadmin Dashboard** | Dashboard utama superadmin: statistik, charts, ringkasan semua modul | Semua tabel |
| **22. User Management** | CRUD users, reset password, restore, role assignment | `users`, `user_roles`, `roles`, `role_permissions` |
| **23. Vehicle Management** | CRUD kendaraan/alat, approve/reject, activate/deactivate | `vehicle_and_tools_consumers` |
| **24. RBAC Phase 2** | Sistem permission granular: permission groups, permissions, roles, user-role assignment | `permission_groups`, `permissions`, `roles`, `role_permissions`, `user_roles` |
| **25. Financial Report** | Laporan keuangan: biaya BBM, biaya perawatan kendaraan, export Excel | Semua tabel transaksional |
| **26. Vehicle Tracking (Legacy — Removed)** | Booking armada kendaraan, trip scheduling, driver assignment, passenger list — **dihapus 13 Jul 2026** saat refactor Lunch Management | `trips`, `trip_passengers`, `trip_booking_requests`, `driver_schedules`, `vehicle_specifications`, `pekerja_patuha` (semua di-drop) |
| **27. Meeting Expense (Planned)** | Manajemen biaya meeting/rapat — **masih dalam perbincangan** | Belum ditentukan |

---

## SHEET 2: Detail

### Modul: BBM (Fuel Request Management)

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Permohonan BBM (CRUD)** | [11.11.2025] Inisiasi view Request BBM; [18.11.2025] Adaptasi flow approval; [29.11.2025] Bulk approval, AJAX CRUD; [02.03.2026] Paginasi grid transaksi BBM | **Done** | Migrasi dari flow sederhana ke approval flow + bulk process. Nama model: `Permohonan`. Route key: `request_number` | 02-03-2026 |
| **Approval BBM** | [18.11.2025] Flow approval; [29.11.2025] Bulk approval; [10.03.2026] Fix admin approval view full-page layout; [31.03.2026] Filter approver di halaman cetak | **Done** | Awalnya manual approve satu-satu, lalu ditambah bulk process. Ada perubahan layout view approval | 31-03-2026 |
| **Upload Bukti Transaksi** | [31.12.2025] Upload proof (struk + odometer); [02.03.2026] Foto gap fix, 4x3cm image size, lossless compression; [02.03.2026] Restore Persetujuan section + col-photo | **Done** | Ada perubahan metode kompresi (GD + Imagic). Ukuran gambar diubah ke 4x3cm untuk standarisasi PDF | 02-03-2026 |
| **Voucher Generation** | [31.12.2025] Generate voucher dengan expiry; [02.03.2026] Voucher Thermer response JSON | **Done** | Voucher untuk pembelian BBM di SPBU. Format: kode unik + valid until | 02-03-2026 |
| **Export Laporan BBM (PDF)** | [20.11.2025] Export; [02.03.2026] Migrasi ke dompdf, redesign report layout, card layout, loading overlay; [02.03.2026] Fix PDF margins, letterhead, compression timeout; [31.03.2026] Filter approver | **Done** | **Eksperimen**: Awalnya pake library bawaan, lalu migrasi total ke dompdf. Sempat revert PR#20. Ada 7+ PR khusus untuk PDF BBM. Kompresi gambar sempat timeout (diff 50+ baris) | 31-03-2026 |
| **Slot Management** | [11.11.2025] Pending/approved slots; [18.11.2025] User slot adjustments | **Done** | Sistem slot per user dengan penalty/bonus. Model: `UserSlotAdjustment` | 18-11-2025 |
| **OCR/VLM Receipt Scanning (Planned)** | [Belum dikerjakan] Terdokumentasi di `to_prompt.md` — integrasi OCR/VLM untuk ekstraksi struk otomatis + klasifikasi jenis struk + auto-fill LPJ | **Planned** | Masih diskusi: pilihan antara VLM API (Gemini/GPT-4o) vs Tesseract lokal vs hybrid. Perlu migrasi DB: tambah kolom OCR ke `transaction_proofs` | - |

---

### Modul: SPD & LPJ (Business Trip Management)

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Surat SPD (CRUD)** | [15.01.2026] Fitur baru SPD + dompdf; [09.02.2026] Perbaikan SPD request; [30.03.2026] trip_code sebagai route key | **Done** | **Eksperimen**: Route key dari ID → trip_code. Format nomor surat: auto-generated dengan format Roman bulan. Ada relasi parent-child untuk extension. Controller: `SpdController` (~1248 baris) | 30-03-2026 |
| **Approval SPD** | [15.01.2026] Approval flow superadmin; [09.02.2026] Approval dengan budget | **Done** | Approval oleh superadmin. Setelah approve: budget otomatis teralokasi. Status: pending → approved/rejected | 09-02-2026 |
| **Perpanjangan SPD** | [09.02.2026] Extension surat (chain); [31.03.2026] SPD barcode signature fix | **Done** | Chain extensions: surat asli → extension 1 → extension 2 (dst). Setiap extension update `tanggal_kembali` + `budget_tambahan`. Ada field `extension_count` di `surat_spds` | 31-03-2026 |
| **Budget Management** | [09.02.2026] Budget per day Rp 1.400.000; [02.04.2026] Retrieve data database fixes | **Done** | Budget: Rp 1.400.000/hari. Disimpan di `surat_spd_budgets`. Total budget = lama_perjalanan_hari x budget_per_hari | 02-04-2026 |
| **LPJ (CRUD + Approval)** | [15.01.2026] Fitur LPJ; [09.02.2026] Perbaikan LPJ; [13.07.2026] Preprocessing data seeder; [17.07.2026] LPJ Data Creating & Show Report + doc_data.json | **Done** | **Module terakhir di-HEAD (23df513, 17 Jul 2026)**. Approval berjenjang: pengguna → divisi GA. Deadline: 3 hari setelah kembali (`lpj_deadline`). Ada deteksi `is_late` + `days_late`. Model `LpjPenggantianDetail` punya `boot triggers` untuk recalculate totals otomatis | 17-07-2026 |
| **Cetak Surat/Laporan PDF** | [15.01.2026] dompdf untuk surat/laporan; [02.03.2026] dompdf compatibility; [31.03.2026] SPD barcode not showing fix | **Done** | **Eksperimen**: Barcode signature sempat tidak muncul di PDF print template. Fix: 64 baris di 2 file | 31-03-2026 |
| **QR Barcode Signature** | [28.03.2026] Push perbaikan barcodes di signature surat SPD; [31.03.2026] Fix SPD barcode | **Done** | QR code signature menggunakan `endroid/qr-code`. Disimpan di `spd_barcode_signatures.signature_path`. Tampil di template surat yang di-print PDF | 31-03-2026 |

---

### Modul: Vehicle Checking

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Daily Checking** | [18.02.2026] Pembuatan flow cek kesehatan kendaraan + dispatcher role; [27.02.2026] Split RoutineCheckingController ke Daily/Weekly/Monthly; [10.03.2026] Standardize view components | **Done** | **Eksperimen penting**: Awalnya semua routine checking digabung di `RoutineCheckingController`, lalu di-split menjadi 3 controller terpisah (Daily, Weekly, Monthly) untuk modularitas. Part status: binary (ok/ng) | 10-03-2026 |
| **Weekly Checking** | [18.02.2026] Sama dengan daily; [27.02.2026] Split controller; [30.03.2026] Export template weekly checking | **Done** | Sama struktur dengan daily checking. Part dengan `check_frequency = weekly` | 30-03-2026 |
| **Monthly Checking** | [18.02.2026] Sama dengan daily; [27.02.2026] Split controller; [10.03.2026] Standardize view | **Done** | Part dengan `check_frequency = monthly`. Ada `vehicle_checking_schedules` untuk tracking jadwal | 10-03-2026 |
| **Body Inspection** | [18.02.2026] Body checking flow; [31.03.2026] Fix checkbox validation + damage photos + Auto-Ajukan button (partial) | **Partial (90%)** | **Pending**: TODO.md — method `replacementAutoCreate()` di `DispatcherAdministrationController` belum diimplementasi. Part status: baik/cukup/kurang/tidak (scale). Ada foto kerusakan per zona (depan, belakang, kiri, kanan) | 31-03-2026 |
| **Checking Schedules** | [18.02.2026] Schedule system; [27.02.2026] Dispatcher flow; [31.03.2026] Sync major (90 files, 8572+/3015-) | **Done** | **Merge besar**: Commit `9d5f9b4` — sync 90 file dengan 8572 tambahan. Schedule status: pending/due/overdue/completed. Unique constraint: (vehicle_id, check_frequency) | 31-03-2026 |
| **Barcode/QR Scan** | [04.03.2026] Barcode scanning & vehicle health history (VehicleHealthHistoryController); [05.03.2026] Migrasi barcode ke QR code (BaconQrCode); [05.03.2026] Seeding vehicle barcode; [10.03.2026] Barcode download 404 fix; [16.03.2026] Barcode download + vehicle health | **Done** | **Eksperimen**: Awalnya pakai barcode biasa (`picqer/php-barcode-generator`), lalu migrasi total ke QR code (`endroid/qr-code`). Barcode download sempat 404 karena path image issue | 16-03-2026 |
| **Vehicle Health Matrix** | [08.12.2025] Trip analysis + vehicle health; [31.03.2026] Perbaikan Vehicle Checking debugs | **Done** | Matrix kesehatan untuk superadmin. Ada di `VehicleCheckingSuperadminController` | 31-03-2026 |

---

### Modul: Vehicle Sparepart Replacement

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Sparepart Request (Dispatcher)** | [18.02.2026] Flow replacement; [27.02.2026] Dispatcher flow; [31.03.2026] Fix dispatcher flow: checkbox validation, DamagePhoto model; [31.03.2026] Perbaikan Vehicle Checking debugs | **Done** | Dispatcher bisa flag `needs_replacement` = true di hasil checking. Auto-create VPR button (partial - lihat Body Inspection). Status flow: pending → approved → scheduled → in_service → completed | 31-03-2026 |
| **Sparepart Approval (Admin)** | [18.02.2026] Admin approval replacement; [31.03.2026] Perbaikan debug | **Done** | Admin approve/reject/schedule replacement. Di `VehicleSparepartRequestRepairController` | 31-03-2026 |
| **Service Transaction (Driver)** | [31.12.2025] Driver repairment; [18.02.2026] Service transaction flow; [31.03.2026] Fix dengan DamagePhoto | **Done** | Driver melaporkan biaya aktual + foto bukti. Model: `VehiclePartsReplacementTransaction` + `VprtDetail` (breakdown part, quantity, unit price) | 31-03-2026 |
| **Auto VPR Creation (Pending)** | [31.03.2026] Add Auto-Ajukan button di view body-check & routine-check show.blade.php; Method backend `replacementAutoCreate()` masih TODO | **Pending** | **TODO.md item**: `DispatcherAdministrationController::replacementAutoCreate($checkingDetailCode)` belum diimplementasi. Frontend button sudah ada, backend blm | - |

---

### Modul: Dispatcher

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Dispatcher Dashboard** | [18.02.2026] Dispatcher role + dashboard; [27.02.2026] Dashboard quick actions + sidebar | **Done** | Overview: count schedules by status (overdue/due/upcoming). Assign PIC ke kendaraan | 27-02-2026 |
| **Assign PIC** | [18.02.2026] Assign dispatcher ke kendaraan; [27.02.2026] Perbaikan flow | **Done** | Dispatcher ditugaskan ke schedule kendaraan via `vehicle_checking_schedules.assigned_dispatcher_id` | 27-02-2026 |
| **Period Generation** | [18.02.2026] Generate monthly checking period | **Done** | Generate period pengecekan bulanan otomatis. Button di dashboard dispatcher | 18-02-2026 |
| **Export Checklists PDF** | [30.03.2026] Template export body checking & routine checking (5 files, 1848+/4-); [31.03.2026] Damage photo export | **Done** | **Penambahan besar**: 1848 baris kode template dalam 5 file untuk export PDF checklist | 31-03-2026 |
| **History View** | [18.02.2026] Checking history | **Done** | Riwayat pengecekan per kendaraan untuk dispatcher | 18-02-2026 |

---

### Modul: Vehicle Tracking (Legacy — Removed)

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Trip Booking & Driver Scheduling** | [19.02.2026] Feat: tambah modul SIAGA — DriverSchedule, Trip, TripPassenger, VehicleSpec (37 files, +3997); [19.02.2026] Refactor: balik flow booking — other user buat request, driver accept; [20.02.2026] Refactor ke request-based model; [20.02.2026] Perbaikan Flow Booking Driver; [30.03.2026] Refactor: nebeng concept, dynamic origin, new trip after completion (15 files, +1370/-255) | **Removed** | **Eksperimen besar**: Booking flow di-refactor 3 kali (original → driver accept → request-based → nebeng concept). Ada konsep "nebeng" (numpang) di trip. Route key: trip_code (bukan ID). Semua tabel di-drop migration `2026_07_13_000007` | 30-03-2026 |
| **Passenger Management** | [19.02.2026] TripPassenger model; [02.03.2026] Passenger edit manual | **Removed** | Fitur nambah/hapus penumpang per trip. Column: nama penumpang, urutan. Ikut terhapus dengan drop tabel `trip_passengers` | 02-03-2026 |
| **Trip History & Daftar Perjalanan** | [01.03.2026] Feat: trip history, daftar perjalanan, booking terms; [02.03.2026] Fix trip flow + self-assign | **Removed** | Halaman riwayat perjalanan untuk requester/driver. Ikut terhapus | 02-03-2026 |
| **Vehicle Specifications** | [19.02.2026] VehicleSpec model (kapasitas, dll) | **Removed** | Data spesifikasi kendaraan terpisah dari `vehicle_and_tools_consumers`. Tabel di-drop | 19-02-2026 |
| **Pekerja Patuha** | [27.02.2026] Phase 2 catering: pekerja_patuha; [13.07.2026] Drop tabel, diganti EmployeeDefaults | **Removed** | Digantikan oleh `employee_defaults` yang jadi master employee multi-site | 13-07-2026 |
| **Integrasi Lunch dengan Trip** | [19.02.2026] Lunch tracking terintegrasi dengan trip; [07.07.2026] Redesign lepas dari trip; [13.07.2026] Refactor independen total (89 files, +4084/-7048) | **Removed** | **Refactor paling besar**: Awalnya lunch forecasting bergantung pada data trip/booking, lalu diputus total menjadi modul independen tanpa dependensi vehicle tracking | 13-07-2026 |

---

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Employee Master** | [13.07.2026] Refactor Lunch Management jadi modul independen — EmployeeDefaults, EmployeeAlias; drop 6 tabel vehicle tracking | **Done** | **Refactor besar**: 89 file, +4084/-7048. EmployeeDefaults jadi master employee, menggantikan `pekerja_patuha` dan user references. Relasi: employee_defaults → employee_aliases (1:N) untuk fuzzy name matching | 13-07-2026 |
| **Lunch Forecast Engine** | [19.02.2026] Lunch tracking awal (SIAGA); [07.07.2026] Redesign ke Lunch Forecast & Distribution System (Site, LunchForecastBatch, LunchForecast); [13.07.2026] Refactor independen: LunchForecastService, LearningService, IdentityResolutionService | **Done** | **Eksperimen besar**: Awalnya bagian dari SIAGA module (tergabung dengan vehicle tracking), lalu dijadikan modul independen. Forecast priority: 1) Manual Override > 2) Tomorrow Confirmation > 3) Historical Pattern > 4) Default Site. Algorithm versioning di `lunch_forecast_batches.algorithm_version` | 13-07-2026 |
| **Lunch Distribution** | [07.07.2026] LunchDistribution model; [13.07.2026] LunchDistributionService build locked vendor counts | **Done** | Snapshot per site per hari. Status: draft → finalized (is_locked). QR code generation untuk site checkout. Ada `CateringExport` (Excel multi-sheet) | 13-07-2026 |
| **QR Attendance** | [13.07.2026] PublicAttendanceController (public, no auth), LunchTransaction model, IdentityResolutionService, NameNormalizer | **Done** | **Fitur publik tanpa login**. Flow: scan QR → form lookup nama → AJAX lookup → resolve identity → submit → konfirmasi "makan dimana besok?" (tomorrow_site_id). Throttled routes untuk keamanan | 13-07-2026 |
| **Guest Reservation** | [27.02.2026] Catering guest calendar (phase 2); [07.07.2026] Redesign ke LunchGuestReservation; [13.07.2026] Refactor independen | **Done** | Reservasi tamu. Status: pending → confirmed (H-2). Relasi: N:1 ke sites. Column rename: `catering_guests` → `lunch_guest_reservations`, `instansi` → `institution`, `jumlah` → `guest_count` | 13-07-2026 |
| **Learning Engine** | [13.07.2026] LearningProfile model, LearningService | **Done** | Tracking per-employee-per-site transaction count untuk future predictions. Fields: `transaction_count`, `last_transaction_date`. Unique: (employee_default_id, site_id) | 13-07-2026 |
| **Lunch Analytics** | [13.07.2026] LunchAnalyticsController | **Done** | Dashboard analitik: tren harian, akurasi prediksi, distribusi per site | 13-07-2026 |
| **Scheduler: Generate Forecast** | [13.07.2026] `lunch:generate-forecast` command, daily at 15:00 via `routes/console.php` | **Done** | Scheduled task: `dailyAt(config('siaga.forecast_generate_at', '15:00'))` | 13-07-2026 |

---

### Modul: Fuel Trip Analysis

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Trip Analysis Dashboard** | [08.12.2025] Trip analysis + vehicle health (29 files, +3457/-887); [08.01.2026] Perbaikan trip analysis + fuel baseline (12 files, +2898/-141) | **Done** | Membandingkan transaksi BBM berurutan, hitung KM difference, fuel consumed, efisiensi (KM/L). Kategorisasi: normal/long_distance/suspicious | 08-01-2026 |
| **Trip Validation** | [08.01.2026] Validation flow; [29.11.2025] Bulk validation | **Done** | Superadmin validasi trip dengan notes. Deteksi premature refueling (range usage < 60%). Alert levels: normal/warning/critical | 08-01-2026 |
| **Baseline Management** | [08.01.2026] VehicleFuelBaseline; [31.03.2026] Baseline override + reason | **Done** | Admin bisa override expected efficiency per trip dengan reason. Unik per (vehicle_id, fuel_type) | 31-03-2026 |

---

### Modul: RBAC Phase 2

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Permission Groups** | [Tidak tercatat di commit] Migrasi RBAC, PermissionGroup model | **Done** | Groups: Dashboard, BBM, SPD, LPJ, Vehicle, Users, RBAC, Settings | Tidak tercatat |
| **Permission Management** | [Tidak tercatat di commit] Permission model, seeder | **Done** | Permission codes per module+action. Relasi N:N ke roles via `role_permissions` | Tidak tercatat |
| **Role Management** | [Tidak tercatat di commit] Role model, seeder (6 system roles) | **Done** | 6 roles: superadmin, general_affairs, dispatcher, requester, manager, view_only. Dual-write: legacy `user.role` + `user_roles` table | Tidak tercatat |
| **Permission Checking Middleware** | [Tidak tercatat di commit] CheckPermission middleware | **Done** | **Eksperimen**: Dual-write strategy — legacy `checkrole.php` masih jalan paralel dengan `CheckPermission.php`. Migrasi bertahap | Tidak tercatat |

---

### Modul: Superadmin

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Dashboard & Charts** | [20.11.2025] Pengembangan Back-end Superadmin; [29.11.2025] Dashboard bisnis; [09.03.2026] Pisah dashboard jadi tab Grafik & Tabel Detail (1 file, +1024/-1468); [10.03.2026] Admin dashboard tab All Histories | **Done** | **Eksperimen**: Dashboard di-refactor dari view monolithic ke tab-based (Grafik + Tabel Detail). Ini file dengan perubahan baris terbesar di fase 10 | 10-03-2026 |
| **User Management** | [29.11.2025] CRUD User, AJAX CRUD; [09.03.2026] Perbaikan manajemen user; [10.03.2026] Fix user management | **Done** | CRUD dengan soft delete, restore, reset password. Ada `user_code` (unique) ditambahkan via migration 12 Mar 2026 | 10-03-2026 |
| **Vehicle Management** | [29.11.2025] CRUD Vehicle; [09.03.2026] Perbaikan manajemen kendaraan | **Done** | CRUD kendaraan/alat, approve/reject, activate/deactivate. Route key: `consumerial_code` | 09-03-2026 |
| **Activity Log** | [Tidak tercatat di commit history] | **Done** | Log aktivitas untuk superadmin | Tidak tercatat |
| **Business Dashboard** | [29.11.2025] Dashboard bisnis; [09.03.2026] Refactor tab | **Done** | Dashboard untuk manager + superadmin. Ada 2 tab: Grafik + Tabel Detail | 09-03-2026 |
| **Financial Reports** | [08.12.2025] Financial report; [31.03.2026] Transaction report | **Done** | Laporan keuangan: biaya BBM, perawatan. Export Excel | 31-03-2026 |

---

### Modul: Meeting Expense (Planned)

| Modul Fitur Granular | Development Logs | Status Terakhir Development | Notes | Tanggal Terakhir Pengerjaan |
|---------------------|------------------|---------------------------|-------|---------------------------|
| **Meeting Expense Management** | Belum ada development | **Planned / Still Discussed** | Masih dalam tahap perbincangan. Belum ada desain, skema DB, atau kode sama sekali | - |
| *(sub-features TBD)* | - | **Planned** | Belum ditentukan | - |

---

## Summary Status Keseluruhan

| Status | Jumlah Fitur |
|--------|-------------|
| ✅ **Done** | 40 fitur |
| 🟡 **Partial (90%)** | 1 fitur (Body Inspection - auto VPR creation pending) |
| 🔵 **Planned** | 2 fitur (OCR/VLM Receipt, Meeting Expense) |
| ❌ **Removed (Legacy)** | 1 modul — Vehicle Tracking (6 tabel di-drop: `trips`, `trip_passengers`, `trip_booking_requests`, `driver_schedules`, `vehicle_specifications`, `pekerja_patuha`) |
| **Total** | **44 fitur** |

---

*Dokumentasi dibuat: 21 Juli 2026*
*Source: Analisis kode, git history, TODO.md, to_prompt.md, README.md*
