# Dokumentasi Project BBM GDE (SIAGA)

**Sistem Informasi dan Aplikasi General Affairs (SIAGA)**
Perusahaan: **Geodipa Energy** (perusahaan geothermal)
Site: PMU, Patuha, BCMP (Basecamp)

---

## Daftar Isi

- [1. Ringkasan Project](#1-ringkasan-project)
- [2. Arsitektur & Teknologi](#2-arsitektur--teknologi)
- [3. Struktur Direktori](#3-struktur-direktori)
- [4. Modul & Fitur](#4-modul--fitur)
  - [4.1 Modul BBM (Fuel Request Management)](#41-modul-bbm-fuel-request-management)
  - [4.2 Modul SPD & LPJ (Business Trip Management)](#42-modul-spd--lpj-business-trip-management)
  - [4.3 Modul Vehicle Health Checking](#43-modul-vehicle-health-checking)
  - [4.4 Modul Lunch Management V2](#44-modul-lunch-management-v2)
  - [4.5 Modul Vehicle Fuel Efficiency Analysis](#45-modul-vehicle-fuel-efficiency-analysis)
  - [4.6 Modul RBAC Authorization Layer](#46-modul-rbac-authorization-layer)
  - [4.7 Modul Dispatcher](#47-modul-dispatcher)
  - [4.8 Modul Superadmin Dashboard](#48-modul-superadmin-dashboard)
  - [4.9 Modul Trip Analysis & Vehicle Tracking (Legacy)](#49-modul-trip-analysis--vehicle-tracking-legacy)
- [5. Basis Data (Database Schema)](#5-basis-data-database-schema)
- [6. Role & Authorization](#6-role--authorization)
- [7. Tracking Perubahan (Changelog)](#7-tracking-perubahan-changelog)
  - [7.1 Phase 1: Initial Project Setup (Nov 2025)](#71-phase-1-initial-project-setup-nov-2025)
  - [7.2 Phase 2: Authentication & Schema (Nov 2025)](#72-phase-2-authentication--schema-nov-2025)
  - [7.3 Phase 3: Approval Flow & Superadmin Backend (Nov 2025 - Jan 2026)](#73-phase-3-approval-flow--superadmin-backend-nov-2025---jan-2026)
  - [7.4 Phase 4: Major Deployment & SPD/LPJ Features (Jan 2026)](#74-phase-4-major-deployment--spdlpj-features-jan-2026)
  - [7.5 Phase 5: SPD Refinements & Superadmin Work (Feb 2026)](#75-phase-5-spd-refinements--superadmin-work-feb-2026)
  - [7.6 Phase 6: SIAGA Module Introduction (Feb 2026)](#76-phase-6-siaga-module-introduction-feb-2026)
  - [7.7 Phase 7: Catering, Lunch Tracking & Dispatcher Work (Feb - Mar 2026)](#77-phase-7-catering-lunch-tracking--dispatcher-work-feb---mar-2026)
  - [7.8 Phase 8: Trip History, Export Reports & PDF (Mar 2026)](#78-phase-8-trip-history-export-reports--pdf-mar-2026)
  - [7.9 Phase 9: Barcode Scanning & Vehicle Health (Mar 2026)](#79-phase-9-barcode-scanning--vehicle-health-mar-2026)
  - [7.10 Phase 10: Dashboard, User Management & View Standardization (Mar 2026)](#710-phase-10-dashboard-user-management--view-standardization-mar-2026)
  - [7.11 Phase 11: URL Slug Standardization & Dispatcher Templates (Mar 2026)](#711-phase-11-url-slug-standardization--dispatcher-templates-mar-2026)
  - [7.12 Phase 12: Vehicle Checkings & Merge Cleanup (Mar - Apr 2026)](#712-phase-12-vehicle-checkings--merge-cleanup-mar---apr-2026)
  - [7.13 Phase 13: Lunch Forecast & Distribution Redesign (Jul 2026)](#713-phase-13-lunch-forecast--distribution-redesign-jul-2026)
- [8. Branch & Merge History](#8-branch--merge-history)
- [9. Kontributor](#9-kontributor)
- [10. Commit Statistics](#10-commit-statistics)

---

## 1. Ringkasan Project

Aplikasi **SIAGA** adalah sistem internal Geodipa Energy untuk mengelola:

| Modul | Deskripsi |
|-------|-----------|
| **BBM** | Permohonan, approval, pembelian, dan pelaporan BBM kendaraan/alat |
| **SPD & LPJ** | Surat Perjalanan Dinas dan Laporan Pertanggungjawaban |
| **Vehicle Health** | Pengecekan rutin (harian/mingguan/bulanan/body) kendaraan |
| **Lunch Management** | Manajemen catering makan siang, prediksi, distribusi, QR attendance |
| **Dispatcher** | Penugasan PIC, penjadwalan pengecekan, ekspor checklist |
| **Fuel Analysis** | Analisis efisiensi BBM per trip kendaraan |
| **RBAC** | Role-based access control phase 2 |

---

## 2. Arsitektur & Teknologi

| Komponen | Teknologi |
|----------|-----------|
| **Framework** | Laravel 12 (PHP 8.2+) |
| **Frontend** | Blade templating, Bootstrap Icons |
| **Asset Bundler** | Vite |
| **Database** | MySQL/MariaDB (via Eloquent ORM) |
| **PDF** | barryvdh/laravel-dompdf |
| **Excel Export** | maatwebsite/excel |
| **QR Code** | endroid/qr-code |
| **Barcode** | picqer/php-barcode-generator |
| **Image** | intervention/image |
| **Testing** | Pest PHP |

---

## 3. Struktur Direktori

```
BBM_Project/
├── app/
│   ├── Console/Commands/
│   │   └── GenerateLunchForecast.php    # Scheduler: generate forecast makan siang
│   ├── Exports/
│   │   └── CateringExport.php           # Excel export vendor catering (3 sheets)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthentiactionController.php
│   │   │   ├── BodyCheckingController.php
│   │   │   ├── CateringGuestController.php
│   │   │   ├── DailyCheckingController.php
│   │   │   ├── DispatcherAdministrationController.php
│   │   │   ├── GasolineTypeController.php
│   │   │   ├── MonthlyCheckingController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── RequestManagementController.php
│   │   │   ├── RoutineCheckingController.php
│   │   │   ├── SpdController.php
│   │   │   ├── StorageFileController.php
│   │   │   ├── SuperadminController.php
│   │   │   ├── UserController.php
│   │   │   ├── VehicleCheckingSuperadminController.php
│   │   │   ├── VehicleHealthHistoryController.php
│   │   │   ├── VehicleRepairmentDriverController.php
│   │   │   ├── VehicleSparepartRequestRepairController.php
│   │   │   ├── VoucherController.php
│   │   │   ├── WeeklyCheckingController.php
│   │   │   └── Lunch/                  # 7 sub-controllers Lunch
│   │   ├── Controllers/Superadmin/
│   │   │   └── FinancialReportController.php
│   │   └── Middleware/
│   │       ├── CheckPermission.php     # RBAC phase 2
│   │       └── checkrole.php           # Legacy role middleware
│   ├── Models/                         # 40 model files
│   └── Services/Lunch/
│       ├── IdentityResolutionService.php
│       ├── LearningService.php
│       ├── LunchDistributionService.php
│       ├── LunchForecastService.php
│       └── NameNormalizer.php
├── config/
│   ├── app.php, auth.php, cache.php, database.php, dompdf.php
│   ├── filesystems.php, image.php, logging.php, mail.php
│   ├── queue.php, services.php, session.php
│   └── siaga.php                       # Konfigurasi Lunch Management
├── database/
│   ├── factories/
│   ├── migrations/                     # 57 file migration
│   └── seeders/                        # 10 file seeder
├── docs/
│   └── LUNCH_MANAGEMENT.md             # Dokumentasi arsitektur Lunch
├── resources/
│   ├── views/
│   │   ├── admin/                      # Dashboard, approval, reports, service-scheduling
│   │   ├── authentication/
│   │   ├── dispatcher/                 # Assign, barcode-scan, body-check, daily-check, history
│   │   ├── lunch/                      # Analytics, dashboard, distributions, forecasts, guests, qr-codes, transactions
│   │   ├── manager/
│   │   ├── partials/                   # navbar, sidebar, footer, sidebar-lunch
│   │   ├── profile/
│   │   ├── requester/                  # SPD forms, checking-acknowledgement, BBM requests
│   │   ├── superadmin/                 # Financial reports, users, vehicles, vehicle health, SPD validation
│   │   └── templates/                  # main.blade.php, public.blade.php, template-surat/
├── routes/
│   ├── console.php                     # Scheduler
│   └── web.php                         # 544 baris - semua routes utama
├── storage/
├── tests/
└── vendor/
```

---

## 4. Modul & Fitur

### 4.1 Modul BBM (Fuel Request Management)

**Deskripsi:** Manajemen permohonan BBM untuk kendaraan dan alat-alat konsumen BBM.

**Model:** `Permohonan`, `Approved_Request`, `Transaction_Proof`, `GasolineType`, `VehicleAndToolsConsumers`, `UserSlotAdjustment`

**Fitur:**
| Fitur | Controller | Route | Keterangan |
|-------|-----------|-------|------------|
| Submit permohonan BBM | RequestManagementController | `/requester/*` | Requester membuat request |
| Approval BBM | RequestManagementController | `/admin/approval/*` | Admin approve/reject |
| Bulk approval | RequestManagementController | `/admin/approval/*` | Approve multiple requests |
| Upload bukti transaksi | RequestManagementController | - | Upload foto receipt + odometer |
| Generate voucher | VoucherController | `/voucher/{id}/thermer/response` | Voucher untuk pembelian |
| Export laporan BBM | RequestManagementController | `/admin/export-report` | Laporan PDF |
| Slot management | UserSlotAdjustment | - | Max slot per user, penalty/bonus |
| Cetak laporan BBM | RequestManagementController | - | Report PDF dengan dompdf |

**Workflow:**
```
Requester submit -> Admin approve -> Generate voucher (expiry) -> Driver beli BBM -> Upload bukti -> Admin review -> Selesai
```

**Controller:** `app/Http/Controllers/RequestManagementController.php` (~1190 baris)

---

### 4.2 Modul SPD & LPJ (Business Trip Management)

**Deskripsi:** Manajemen Surat Perjalanan Dinas (SPD) dan Laporan Pertanggungjawaban (LPJ).

**Model:** `SuratSpd`, `SuratSpdDetail`, `SuratSpdExtension`, `SuratSpdBudget`, `LpjPenggantian`, `LpjPenggantianDetail`, `SpdBarcodeSignature`

**Fitur:**
| Fitur | Route | Keterangan |
|-------|-------|------------|
| CRUD Surat SPD | `/surat_requester/*` | Requester membuat surat SPD |
| Approval SPD | `/surat/*` | Superadmin approve/reject |
| Perpanjangan SPD | `/perpanjangan/*` | Extension surat (chain) |
| LPJ submission | `/lpj/*` | Expense report dengan details |
| Validasi LPJ | `/lpj/*` | Superadmin validasi |
| Cetak surat/laporan | `/spd/*` | PDF dengan dompdf |
| QR barcode signature | - | Tanda tangan digital pada surat |
| Budget per hari | - | Rp 1.400.000 per hari |

**Fitur Khusus:**
- Auto-generated letter numbering (format Roman bulan)
- Deadline LPJ: 3 hari setelah kembali
- Chain extensions (perpanjangan berantai)
- Passenger list

**Controller:** `app/Http/Controllers/SpdController.php` (~1248 baris)

---

### 4.3 Modul Vehicle Health Checking

**Deskripsi:** Pengecekan kesehatan kendaraan secara rutin.

**Model:** `VehicleChecking`, `VehicleCheckingDetail`, `VehicleCheckingItemResult`, `VehicleCheckingSchedule`, `VehiclePart`, `VehiclePartsReplacement`, `VehiclePartsReplacementDetail`, `VehiclePartsReplacementTransaction`, `VprtDetail`, `DamagePhoto`

**Fitur:**
| Fitur | Controller | Frekuensi | Status Type |
|-------|-----------|-----------|-------------|
| Daily Checking | `DailyCheckingController` | Harian | ok/ng |
| Weekly Checking | `WeeklyCheckingController` | Mingguan | ok/ng |
| Monthly Checking | `MonthlyCheckingController` | Bulanan | ok/ng |
| Body Inspection | `BodyCheckingController` | Periodik | baik/cukup/kurang/tidak |
| Sparepart Request | `VehicleSparepartRequestRepairController` | - | Workflow approval |
| Service Transaction | `VehicleRepairmentDriverController` | - | Real cost + photos |
| Vehicle Health Matrix | `VehicleCheckingSuperadminController` | - | Matrix kesehatan |
| Barcode Scan | `VehicleHealthHistoryController` | - | QR code history |

**Workflow Sparepart:**
```
Dispatcher flag perlu ganti part -> Admin approve -> Jadwalkan service -> Driver lakukan service (laporkan biaya + foto) -> Selesai
```

---

### 4.4 Modul Lunch Management V2

**Deskripsi:** Manajemen catering makan siang untuk 3 site (PMU, Patuha, BCMP). Modul independen dari vehicle tracking.

**Model:** `Site`, `EmployeeDefault`, `EmployeeAlias`, `LunchForecastBatch`, `LunchForecast`, `LunchDistribution`, `LunchTransaction`, `LunchGuestReservation`, `LearningProfile`

**Fitur:**
| Fitur | Controller | Route |
|-------|-----------|-------|
| Dashboard | `LunchDashboardController` | `/lunch` |
| Employee Master | `EmployeeDefaultController` | `/lunch/employee-defaults/*` |
| Forecast View/Generate | `LunchForecastController` | `/lunch/forecasts/*` |
| Distribution Lock | `LunchDistributionController` | `/lunch/distributions/*` |
| QR Code Generation | `LunchDistributionController` | `/lunch/qr-codes/*` |
| QR Attendance | `PublicAttendanceController` | `/lunch-attendance/*` (public, no auth) |
| Transaction View | `LunchTransactionController` | `/lunch/transactions/*` |
| Analytics | `LunchAnalyticsController` | `/lunch/analytics` |
| Guest Reservation | `CateringGuestController` | `/lunch/guests/*` |

**Forecast Engine Priority (H+1):**
1. Manual Override
2. Tomorrow Confirmation (popup "makan dimana besok?")
3. Historical Pattern (Learning Profiles)
4. Default Site

**Scheduled Task:** `lunch:generate-forecast` daily at 15:00

**Services:**
- `IdentityResolutionService.php` - Resolusi QR alias ke employee
- `LearningService.php` - Learning engine untuk prediksi
- `LunchDistributionService.php` - Build locked vendor counts
- `LunchForecastService.php` - Forecast engine H+1
- `NameNormalizer.php` - Normalisasi nama untuk fuzzy matching

---

### 4.5 Modul Vehicle Fuel Efficiency Analysis

**Deskripsi:** Analisis efisiensi BBM per trip kendaraan.

**Model:** `VehicleTripAnalysis`, `TripValidation`, `VehicleFuelBaseline`

**Fitur:**
| Fitur | Controller | Route |
|-------|-----------|-------|
| Trip Analysis Dashboard | `SuperadminController` | `/trip-analysis/*` |
| Recalculate Metrics | `SuperadminController` | `/trip-analysis/*` |
| Trip Validation | `SuperadminController` | `/trip-validations/*` |
| Bulk Validation | `SuperadminController` | `/trip-validations/*` |
| Baseline Override | `SuperadminController` | - |

**Logika Analisis:**
- Membandingkan transaksi BBM berurutan
- Menghitung selisih KM, konsumsi BBM, efisiensi (KM/L)
- Kategorisasi: normal / long_distance / suspicious
- Deteksi premature refueling (range usage < 60%)
- Alert levels: normal / warning / critical

---

### 4.6 Modul RBAC Authorization Layer

**Deskripsi:** Role-based access control phase 2 dengan sistem permission granular.

**Model:** `PermissionGroup`, `Permission`, `Role`, `user_roles` (pivot), `role_permissions` (pivot)

**Permission Groups:**
- Dashboard
- BBM
- SPD
- LPJ
- Vehicle
- Users
- RBAC (self)
- Settings

**System Roles:**
1. `superadmin` - Akses penuh
2. `general_affairs` - Admin GA
3. `dispatcher` - Petugas pengecekan
4. `requester` - Driver/pemohon
5. `manager` - Manajer (read + approve)
6. `view_only` - Staff non-GA (read only)

**Dual-write strategy:** Legacy `user.role` column co-exists dengan new `user_roles` table

**Middleware:**
- `CheckPermission.php` - Granular permission check (RBAC v2)
- `checkrole.php` - Legacy role check

---

### 4.7 Modul Dispatcher

**Deskripsi:** Dashboard dan workflow untuk role dispatcher.

**Controller:** `DispatcherAdministrationController`

**Fitur:**
| Fitur | Route |
|-------|-------|
| Dashboard (overview overdue/due/upcoming) | `/dispatcher/dashboard` |
| Generate checking period bulanan | `/dispatcher/generate-period` |
| Assign PIC ke kendaraan | `/dispatcher/assign/*` |
| Daily check CRUD | `/dispatcher/daily-check/*` |
| Weekly check CRUD | `/dispatcher/weekly-check/*` |
| Monthly check CRUD | `/dispatcher/monthly-check/*` |
| Body check CRUD | `/dispatcher/body-check/*` |
| Checking history | `/dispatcher/history` |
| Export PDF checklists | `/dispatcher/export/*` |
| Sparepart replacement request | `/dispatcher/replacement/*` |
| Scan barcode vehicle | `/dispatcher/scan-vehicle/*` |

---

### 4.8 Modul Superadmin Dashboard

**Deskripsi:** Dashboard utama untuk superadmin dengan statistik dan manajemen.

**Controller:** `SuperadminController` (~3329 baris - terbesar)

**Fitur:**
| Fitur | Route |
|-------|-------|
| Dashboard statistik + charts | `/sa/dashboard` |
| CRUD Users + reset password + restore | `/users/*` |
| CRUD Vehicles + approve/reject + activate/deactivate | `/vehicles/*` |
| Kelola Permohonan (update/cancel/delete) | `/kelola-permohonan/*` |
| Trip Analysis | `/trip-analysis/*` |
| Trip Validations | `/trip-validations/*` |
| Activity Log | `/activity-log` |
| Business Dashboard | `/dashboard-bisnis` |
| Vehicle Health Matrix | `/vehicle-checking/vehicle-health/*` |
| Financial Report + Export | `/vehicle-checking/financial-report/*` |
| Transaction Report | `/vehicle-checking/transaction-report` |
| Download Barcodes | `/vehicle-checking/barcode/*` |

**Superadmin Sub-controllers:**
- `VehicleCheckingSuperadminController` - Health matrix & financial reports
- `FinancialReportController` - Financial reports

---

### 4.9 Modul Trip Analysis & Vehicle Tracking (Legacy)

**Status:** Legacy / dihapus pada Lunch Management Refactor (Jul 2026)

**Tables yang di-drop:**
- `trips`
- `trip_passengers`
- `trip_booking_requests`
- `driver_schedules`
- `vehicle_specifications`
- `pekerja_patuha`

**Fitur yang sempat ada (sebelum di-refactor):**
- Booking trip kendaraan
- Penjadwalan driver
- Trip tracking dengan passenger list
- Tracking lokasi default

---

## 5. Basis Data (Database Schema)

### 5.1 Tabel Per Modul

**Core/System:**
| Tabel | Fungsi |
|-------|--------|
| `users` | User accounts |
| `sessions` | User sessions |
| `password_reset_tokens` | Reset password |
| `cache` | Cache |
| `jobs` | Queue jobs |

**BBM / Fuel:**
| Tabel | Fungsi |
|-------|--------|
| `permohonans` | Permohonan BBM (request) |
| `approved_requests` | Request yang sudah di-approve |
| `transaction_proofs` | Bukti transaksi (receipt, odometer) |
| `gasoline_types` | Jenis BBM (pertalite, solar, etc) |
| `vehicle_and_tools_consumers` | Kendaraan/alat konsumen BBM (VTC) |
| `user_slot_adjustments` | Penyesuaian slot per user |

**Fuel Analysis:**
| Tabel | Fungsi |
|-------|--------|
| `vehicle_trip_analyses` | Analisis trip per kendaraan |
| `trip_validations` | Validasi trip oleh superadmin |
| `vehicle_fuel_baselines` | Baseline efisiensi BBM |

**SPD (Business Trip):**
| Tabel | Fungsi |
|-------|--------|
| `surat_spds` | Surat Perjalanan Dinas |
| `spd_surat_details` | Detail surat (tujuan, dll) |
| `surat_spd_extensions` | Perpanjangan SPD |
| `surat_spd_budgets` | Budget SPD |
| `lpj_penggantians` | Laporan Pertanggungjawaban |
| `lpj_penggantian_details` | Detail LPJ |
| `spd_barcode_signatures` | Tanda tangan QR pada surat |

**Vehicle Checking:**
| Tabel | Fungsi |
|-------|--------|
| `vehicle_checkings` | Header pengecekan |
| `vehicle_checking_details` | Detail pengecekan per kendaraan |
| `vehicle_checking_item_results` | Hasil per item pengecekan |
| `vehicle_checking_schedules` | Jadwal pengecekan |
| `vehicle_parts` | Daftar part yang dicek |

**Vehicle Parts Replacement:**
| Tabel | Fungsi |
|-------|--------|
| `vehicle_parts_replacements` | Request penggantian part |
| `vehicle_parts_replacement_details` | Detail request |
| `vehicle_parts_replacement_transactions` | Transaksi penggantian |
| `vprt_details` | Detail transaksi |
| `damage_photos` | Foto kerusakan |

**Lunch Management:**
| Tabel | Fungsi |
|-------|--------|
| `sites` | Site/lokasi (PMU, Patuha, BCMP) |
| `employee_defaults` | Master data pegawai |
| `employee_aliases` | Alias nama untuk fuzzy matching |
| `lunch_forecast_batches` | Batch forecast |
| `lunch_forecasts` | Data forecast per employee per site |
| `lunch_distributions` | Snapshot distribusi ke vendor |
| `lunch_transactions` | Transaksi QR attendance |
| `lunch_guest_reservations` | Reservasi tamu |
| `learning_profiles` | Profile learning engine |

**RBAC Phase 2:**
| Tabel | Fungsi |
|-------|--------|
| `permission_groups` | Grup permission |
| `permissions` | Daftar permission |
| `roles` | Daftar role |
| `role_permissions` | Pivot role-permission |
| `user_roles` | Pivot user-role |

---

## 6. Role & Authorization

| Role | Level Akses | Middleware |
|------|-------------|-----------|
| **superadmin** | Full access | `checkrole` + `CheckPermission` |
| **general_affairs** | Admin GA: approval BBM, sparepart, scheduling | `checkrole` + `CheckPermission` |
| **dispatcher** | Pengecekan kendaraan, assign PIC, export checklist | `checkrole` + `CheckPermission` |
| **requester** | Driver: submit BBM request, SPD, LPJ, acknowledge checking | `checkrole` + `CheckPermission` |
| **manager** | View reports, approve tertentu | `checkrole` + `CheckPermission` |
| **view_only** | Staff non-GA: view only | `checkrole` + `CheckPermission` |

---

## 7. Tracking Perubahan (Changelog)

> Semua commit dalam urutan kronologis dari awal project hingga HEAD.

---

### 7.1 Phase 1: Initial Project Setup (Nov 2025)

| # | Hash | Tanggal | Author | Deskripsi | Files Changed |
|---|------|---------|--------|-----------|--------------|
| 1 | `6764a38` | 06-11-2025 | mg.faris | Inisiasi version control project BBM GDE | - |
| 2 | `177a6c5` | 06-11-2025 | mg.faris | Inisiasi Laravel boilerplate + vendor assets | 512 files (+645794/-13) |

**Branch:** `main`

---

### 7.2 Phase 2: Authentication & Schema (Nov 2025)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 3 | `484419a` | 11-11-2025 | mg.faris | [11.11.2025] Perancangan Schema Database, fitur authentikasi, seeding user 4 role | DB schema, auth, seeders |
| 4 | `e571a50` | 11-11-2025 | mg.faris | [kepencet] Minor fix login | 1 file |
| 5 | `e212f75` | 11-11-2025 | mg.faris | [11.11.2025.FARISDEV] Auth controller, navbar, sidebar, CheckRole middleware | 11 files |
| 6 | `a7b6fe1` | 11-11-2025 | mg.faris | [11.11.2025FARISDEV] Inisiasi routing | 6 files |
| 7 | `ed4b0bb` | 11-11-2025 | mg.faris | [11.11.2025FARISDEV] Update branch routing | - |
| 8 | **`760bab6`** | 11-11-2025 | mg.faris | **MERGE** branch `authentication` ke main | 16 files (+326/-57) |
| 9 | `bbfe376` | 11-11-2025 | mg.faris | [11.11.2025FARISDEV] Inisiasi view Request BBM, pending/approved slots | 5 files (+561/-17) |
| 10 | `3d44b2a` | 11-11-2025 | mg.faris | [11.11.2025] VTC model + seeder | 5 files (+83/-3) |

**Branch:** `authentication` (di-merge ke main via commit `760bab6`)

---

### 7.3 Phase 3: Approval Flow & Superadmin Backend (Nov 2025 - Jan 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 11 | `f376444` | 18-11-2025 | mg.faris | [18112025] Adaptasi flow approval, RequestManagementController | 27 files (+2156/-214) |
| 12 | `94d83a8` | 20-11-2025 | mg.faris | [20112025FARISDEV] Pengembangan Back-end Superadmin | 9 files (+1434/-14) |
| 13 | `010c698` | 20-11-2025 | mg.faris | Perubahan kode exports | 1 file (+3/-343) |
| 14 | `022e8d7` | 29-11-2025 | mg.faris | [29112025FARISDEV] Bulk approval, dashboard bisnis, CRUD Vehicle, User management, AJAX CRUD | 11 files (+2876/-689) |
| 15 | `108afef` | 08-12-2025 | mg.faris | [08122025FRSDEV] Trip analysis, vehicle health | 29 files (+3457/-887) |
| 16 | `ef848c1` | 31-12-2025 | mg.faris | Sebelum deploy hostinger: voucher, profile, repair | 35 files (+6925/-1462) |
| 17 | `c21e233` | 31-12-2025 | mg.faris | Ubah path langsung ke login page | 2 files |

**Branch:** `main` + `superadmin` (mulai divergen)

---

### 7.4 Phase 4: Major Deployment & SPD/LPJ Features (Jan 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 18 | `4a0b004` | 06-01-2026 | mg.faris | [06012026] Major Dev setelah deploy hostinger, perbaikan pagination, migrasi seeder dari CSV | 11 files (+2844/-1271) |
| 19 | `eb5748a` | 06-01-2026 | parisberkoding | [06012026] Major Dev after deployment (duplicate) | - |
| 20 | **`0feda2a`** | 06-01-2026 | parisberkoding | **MERGE** branch 'main' dari remote | Sync |
| 21 | `30b340a` | 08-01-2026 | parisberkoding | [08012026FRSDEV] Perbaikan superadmin trip analysis, fuel baseline | 12 files (+2898/-141) |
| 22 | `a260cdf` | 09-01-2026 | parisberkoding | Image Compressor | 7 files (+394/-290) |
| 23 | `b7d55a0` | 09-01-2026 | parisberkoding | Ignore public/storage | 2 files |
| 24 | `cf969e5` | 09-01-2026 | parisberkoding | Ignore public/storage fix | 1 file |
| 25 | `1b5ddfa` | 15-01-2026 | parisberkoding | [15012026FRSDEV] **Fitur baru SPD dan LPJ**, dompdf, SuratSpd | 41 files (+7502/-699) |
| 26 | `ecdaa7f` | 15-01-2026 | parisberkoding | Remove storage gitignore | - |

---

### 7.5 Phase 5: SPD Refinements & Superadmin Work (Feb 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 27 | `5fd336c` | 09-02-2026 | parisberkoding | **Major SPD overhaul**: budget, extensions, templates | 39 files (+6088/-2547) |
| 28 | **`7333673`** | 09-02-2026 | parisberkoding | **MERGE** branch 'main' into superadmin | 39 files (+6088/-2547) |
| 29 | `7672ad8` | 18-02-2026 | parisberkoding | **PEMBUATAN FLOW CEK KESEHATAN KENDARAAN & DISPATCHER ROLE** - 14 model baru | 66 files (+15394/-764) |

**Branch:** `superadmin` (aktif)

---

### 7.6 Phase 6: SIAGA Module Introduction (Feb 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 30 | `bde542a` | 19-02-2026 | **Claude** | **feat: tambah modul SIAGA** - DriverSchedule, Trip, TripPassenger, VehicleSpec, LunchTracking | 37 files (+3997/-76) |
| 31 | `7400e9a` | 19-02-2026 | parisberkoding | Refactor dispatcher/SPD | - |
| 32 | **`f87267d`** | 19-02-2026 | parisberkoding | **MERGE PR#2** - superadmin ke main | - |
| 33 | **`fd8877f`** | 19-02-2026 | parisberkoding | **MERGE** main ke vb/siaga | - |
| 34 | **`201ad69`** | 19-02-2026 | parisberkoding | **MERGE** SIAGA module dari Vibe Coding | - |
| 35 | `ab050df` | 19-02-2026 | **Claude** | fix: perbaiki composer.json, daftarkan route SIAGA | - |
| 36 | **`92cb574`** | 19-02-2026 | parisberkoding | **MERGE PR#3** - SIAGA fixes | - |
| 37 | `c65ca3a` | 19-02-2026 | **Claude** | **refactor: balik flow booking** - other user buat request, driver accept | - |
| 38 | **`55d01fa`** | 19-02-2026 | parisberkoding | **MERGE PR#4** - booking flow refactor | - |
| 39 | `bf01f56` | 19-02-2026 | parisberkoding | Update README dengan SIAGA project details | 1 file |
| 40 | `21236aa` | 20-02-2026 | parisberkoding | Revert "Refactor code siaga di GA" | - |
| 41 | **`4fb9925`** | 20-02-2026 | parisberkoding | **MERGE PR#5** - Revert | - |
| 42 | `db28424` | 19-02-2026 | **Claude** | fix: resolve admin view variable mismatches | - |
| 43 | **`59d0822`** | 20-02-2026 | parisberkoding | **MERGE PR#6** - Fixes | - |
| 44 | `8b42f6b` | 20-02-2026 | parisberkoding | Manual edits | - |
| 45 | `62b70ca` | 20-02-2026 | **Claude** | feat(siaga): refactor booking flow ke request-based model | - |
| 46 | **`a567c27`** | 20-02-2026 | parisberkoding | **MERGE PR#8** - Booking refactor | - |

**Branch:** `vb/siaga-new-module-o5btR`, `vb/debug-cook-feature-1JhgS`, `vb/refactor-booking-flow-Vkz2e`, `vb/refactor-booking-flow-j7MOE`

---

### 7.7 Phase 7: Catering, Lunch Tracking & Dispatcher Work (Feb - Mar 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 47 | `effca4b` | 20-02-2026 | parisberkoding | Perbaikan Flow Booking Driver, Query Lunch Identification | - |
| 48 | `d96c5e6` | 27-02-2026 | **Claude** | **feat(siaga): phase 2 catering** - pekerja_patuha, tamu guest calendar, export | - |
| 49 | **`e84625f`** | 27-02-2026 | parisberkoding | **MERGE PR#9** - Catering phase 2 | - |
| 50 | `bece4b7` | 27-02-2026 | parisberkoding | Perubahan Lunch Patuha | - |
| 51 | `490bdbd` | 27-02-2026 | **Claude** | refactor: tampilkan semua peserta makan siang per tanggal | - |
| 52 | **`9648364`** | 27-02-2026 | parisberkoding | **MERGE PR#10** - Lunch tracking | - |
| 53 | `e865498` | 27-02-2026 | **Claude** | **refactor: split RoutineCheckingController** ke Daily/Weekly/Monthly | - |
| 54 | **`8045151`** | 27-02-2026 | parisberkoding | **MERGE PR#11** - Dispatcher refactor | - |
| 55 | `3e464e3` | 27-02-2026 | parisberkoding | Lunch tracking direct commit | - |
| 56 | **`9d807b2`** | 27-02-2026 | parisberkoding | MERGE sync | - |
| 57 | `aa7cf29` | 27-02-2026 | **Claude** | feat: update sidebar & dashboard quick actions dispatcher | - |
| 58 | **`7180e94`** | 27-02-2026 | parisberkoding | **MERGE PR#12** - Dispatcher UI | - |
| 59 | **`e4a651f`** | 27-02-2026 | parisberkoding | MERGE sync | - |
| 60 | `7978e03` | 27-02-2026 | **Claude** | fix: perbaiki relasi model vehicle checking | - |
| 61 | **`5e9d1f0`** | 27-02-2026 | parisberkoding | **MERGE PR#13** - Vehicle fixes | - |
| 62 | `86d74cb` | 27-02-2026 | parisberkoding | Perbaikan minor | - |

**Branch:** `vb/default-user-location-CqudD`, `vb/lunch-tracking-catering-DW9Tc`, `vb/improve-dispatcher-flow-U46DT`, `vb/fix-vehicle-query-S832v`

---

### 7.8 Phase 8: Trip History, Export Reports & PDF (Mar 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 63 | **`50fcd66`** | 02-03-2026 | parisberkoding | MERGE sync | - |
| 64 | `8298aaa` | 02-03-2026 | parisberkoding | Perbaikan authentication controller | - |
| 65 | `0a85056` | 01-03-2026 | **Claude** | fix: perbaikan trip flow, self-assign, catering export | - |
| 66 | **`482a91c`** | 02-03-2026 | parisberkoding | **MERGE PR#14** - Trip flow fix | - |
| 67 | `34054bf` | 02-03-2026 | parisberkoding | Passenger edit | - |
| 68 | `ae2dcb5` | 01-03-2026 | **Claude** | feat(siaga): trip history, daftar perjalanan, booking terms | - |
| 69 | **`1d82737`** | 02-03-2026 | parisberkoding | MERGE sync | - |
| 70 | **`498f492`** | 02-03-2026 | parisberkoding | **MERGE PR#15** - Trip history | - |
| 71 | `2989641` | 02-03-2026 | **Claude** | refactor: paginasi grid transaksi BBM | - |
| 72 | **`58cb11a`** | 02-03-2026 | parisberkoding | **MERGE PR#16** - Pagination | - |
| 73 | `3e373c8` | 02-03-2026 | **Claude** | fix(report): foto gap, 4x3cm image, lossless compression | - |
| 74 | **`07f3475`** | 02-03-2026 | parisberkoding | **MERGE PR#17** - Photo fix | - |
| 75 | `6be2a33` | 02-03-2026 | **Claude** | fix: correct foreign key checkings() relationship | - |
| 76 | `d816074` | 02-03-2026 | parisberkoding | Perbaikan VTC model relasi checkings | - |
| 77 | `08e3ddb` | 02-03-2026 | **Claude** | fix(bbm-pdf): migrate to dompdf, redesign report layout | - |
| 78 | **`36e1c88`** | 02-03-2026 | parisberkoding | **MERGE PR#19** - dompdf fix | - |
| 79 | `4d8def7` | 02-03-2026 | **Claude** | fix: perbaiki PDF export laporan BBM dompdf-compatible | - |
| 80 | **`3516c9b`** | 02-03-2026 | parisberkoding | **MERGE PR#20** - PDF fix | - |
| 81 | `c618698` | 02-03-2026 | parisberkoding | Revert "Merge PR#20" | - |
| 82 | `7eadf75` | 02-03-2026 | **Claude** | feat(bbm-report): enhance card info layout, image settings | - |
| 83 | **`8ef0e36`** | 02-03-2026 | parisberkoding | **MERGE PR#21** - Card enhance | - |
| 84 | `3e2f8ac` | 02-03-2026 | parisberkoding | Resolve merge conflict | - |
| 85 | `e6f2f09` | 02-03-2026 | parisberkoding | Resolve ukuran export | - |
| 86 | `9286ead` | 02-03-2026 | parisberkoding | Adding data approved by di export report | - |
| 87 | `08ff002` | 02-03-2026 | **Claude** | feat: redesign BBM report card layout, PDF loading overlay | - |
| 88 | **`9cc2ff4`** | 02-03-2026 | parisberkoding | **MERGE PR#22** - Report template | - |
| 89 | **`bf392cf`** | 02-03-2026 | parisberkoding | MERGE sync | - |
| 90 | **`6bcda97`** | 02-03-2026 | parisberkoding | **MERGE PR#23** - Fuel report | - |
| 91 | `4c48b11` | 02-03-2026 | **Claude** | fix: restore Persetujuan section, col-photo column | - |
| 92 | **`edb6250`** | 02-03-2026 | parisberkoding | **MERGE PR#24** - Transaction card | - |
| 93 | **`b3daf3d`** | 02-03-2026 | parisberkoding | **MERGE PR#25** - Final fuel report | - |
| 94 | `30a6631` | 02-03-2026 | parisberkoding | Perbaikan metode kompresi (GD Imagic) | - |
| 95 | `f2a319e` | 02-03-2026 | **Claude** | fix: PDF margins, professional letterhead, loading indicator | - |
| 96 | **`da8af3c`** | 02-03-2026 | parisberkoding | **MERGE PR#26** - PDF margins | - |

**Branch:** `vb/remove-trip-flow-gRE9N`, `vb/add-trip-history-view-foKHq`, `vb/fuel-transaction-report-gjjMU`, `vb/fix-fuel-transaction-gap-H7xgs`, `vb/fix-bbm-pdf-export-aWaZo`, `vb/fix-pdf-export-report-lQwGj`, `vb/fuel-transaction-report-baVHA`, `vb/bbm-report-template-LdTsY`, `vb/export-report-transactions-card-biUt4`, `vb/fix-pdf-margins-loading-dRuAg`

---

### 7.9 Phase 9: Barcode Scanning & Vehicle Health (Mar 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 97 | `9c82ff0` | 02-03-2026 | parisberkoding | Perbaikan layout export laporan BBM | - |
| 98 | `3d57133` | 04-03-2026 | **Claude** | **feat: barcode scanning & vehicle health history** - VehicleHealthHistoryController baru | 15 files (+1014/-9) |
| 99 | `b14c151` | 05-03-2026 | parisberkoding | Fitur scan barcode | - |
| 100 | `9d41bdc` | 05-03-2026 | parisberkoding | Seeding vehicle barcode | 3 files (+195/-33) |
| 101 | `07233ca` | 05-03-2026 | **Claude** | refactor: ganti barcode scanner ke QR code (BaconQrCode) | - |
| 102 | **`d3d22f1`** | 05-03-2026 | parisberkoding | **MERGE PR#28** - QR scanner | - |
| 103 | `5999fdc` | 05-03-2026 | parisberkoding | Push perubahan QR scan | 2 files |

**Branch:** `vb/fix-qr-code-scanner-S9FzM`

---

### 7.10 Phase 10: Dashboard, User Management & View Standardization (Mar 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 104 | `26fd647` | 09-03-2026 | **Claude** | refactor: pisah dashboard bisnis jadi tab Grafik & Tabel Detail | 1 file (+1024/-1468) |
| 105 | **`7949582`** | 09-03-2026 | parisberkoding | **MERGE PR#29** - Dashboard refactor | - |
| 106 | `7c93937` | 09-03-2026 | **Claude** | **Major**: perbaikan laporan BBM, manajemen user & kendaraan, barcode, sidebar | 8 files (+1091/-255) |
| 107 | **`5a95783`** | 10-03-2026 | parisberkoding | **MERGE PR#30** - User management | - |
| 108 | `e04cc92` | 10-03-2026 | **Claude** | fix: handle null dates di export-report | - |
| 109 | **`dced767`** | 10-03-2026 | parisberkoding | **MERGE PR#31** - Null date fix | - |
| 110 | `e7e547d` | 10-03-2026 | parisberkoding | Perbaikan vehicle barcode seeder | - |
| 111 | **`88d1877`** | 10-03-2026 | parisberkoding | MERGE sync | - |
| 112 | `14d6cdc` | 10-03-2026 | **Claude** | fix: barcode download 404, image path, vehicle view | 3 files |
| 113 | **`04d18c7`** | 10-03-2026 | parisberkoding | **MERGE PR#32** - Barcode fix | - |
| 114 | `a03016c` | 10-03-2026 | parisberkoding | Perbaikan controller | - |
| 115 | `37a3952` | 10-03-2026 | **Claude** | Fix admin approval view - full-page layout | 2 files (+368/-5) |
| 116 | `78b56d6` | 10-03-2026 | parisberkoding | Direct commit main | - |
| 117 | **`b8e4939`** | 10-03-2026 | parisberkoding | **MERGE PR#33** - Admin approval | - |
| 118 | **`d9cbecd`** | 10-03-2026 | parisberkoding | MERGE sync | - |
| 119 | `dc23166` | 10-03-2026 | **Claude** | refactor: restructure sidebar jadi flat @if/@elseif/@endif chain | 1 file (+52/-12) |
| 120 | **`ed48482`** | 10-03-2026 | parisberkoding | **MERGE Vibe Coding** - Sidebar | - |
| 121 | `f3b9a5e` | 10-03-2026 | parisberkoding | Perbaikan dashboard admin, tab All Histories | - |
| 122 | **`1703c14`** | 10-03-2026 | parisberkoding | MERGE sync | - |
| 123 | `de8ca29` | 10-03-2026 | **Claude** | **Standardize stat card components & breadcrumbs** di 15 views | 15 files (+378/-328) |
| 124 | `d1e3709` | 10-03-2026 | parisberkoding | Perbaikan authorizer date di template export BBM | - |
| 125 | **`d88b852`** | 10-03-2026 | parisberkoding | **MERGE PR#35** - Standardization | - |

**Branch:** `vb/refactor-dashboard-layout-Ei8Cz`, `vb/fix-fuel-report-user-management-7Y3Pv`, `vb/fix-export-report-null-5wUsn`, `vb/fix-barcode-vehicle-view-BXIJW`, `vb/fix-admin-approval-view-czKqf`, `vb/standardize-view-components-XQbuj`

---

### 7.11 Phase 11: URL Slug Standardization & Dispatcher Templates (Mar 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 126 | `61d2135` | 12-03-2026 | **Claude** | feat: add slug fields ke 10 models & 4 migrations untuk URL rewriting | 14 files (+294) |
| 127 | `6ed556b` | 12-03-2026 | parisberkoding | Perbaikan ID on routes | - |
| 128 | `d6a8c53` | 16-03-2026 | **Claude** | fix: barcode download, BBM export loading, vehicle health | 8 files (+180/-14) |
| 129 | **`8fbc3a2`** | 16-03-2026 | parisberkoding | **MERGE PR#37** - Barcode fix | - |
| 130 | `aa91905` | 16-03-2026 | parisberkoding | Update sebagian view | 4 files |
| 131 | **`3277a28`** | 16-03-2026 | parisberkoding | MERGE sync | - |
| 132 | `7d12c17` | 24-03-2026 | parisberkoding | Redirecting Sidebar | 4 files |
| 133 | `2801bc6` | 28-03-2026 | parisberkoding | **Push perbaikan barcodes di signature surat SPD** | 12 files (+475/-30) |
| 134 | `e747c01` | 30-03-2026 | **Claude** | **Refactor SIAGA vehicle tracking**: nebeng concept, dynamic origin, new trip after completion | 15 files (+1370/-255) |
| 135 | **`16a548d`** | 30-03-2026 | parisberkoding | **MERGE PR#38** - Vehicle tracking | - |
| 136 | `d7e26f8` | 30-03-2026 | **Claude** | Gunakan trip_code sebagai route key (bukan ID) | 4 files |
| 137 | `69c38c4` | 30-03-2026 | parisberkoding | Perbaikan query trip booking | 6 files (+160/-53) |
| 138 | `2e68694` | 30-03-2026 | parisberkoding | **Penambahan template export dispatcher** body checking & routine checking | 5 files (+1848/-4) |
| 139 | `5941aee` | 31-03-2026 | **Claude** | feat: tambah filter approver di cetak laporan BBM | 3 files (+50/-4) |
| 140 | `9eafafe` | 31-03-2026 | **Claude** | Fix image compression timeout on PDF report export | 1 file (+49/-33) |
| 141 | **`d48bdf8`** | 31-03-2026 | parisberkoding | **MERGE PR#40** - Compression fix | - |
| 142 | **`687fc19`** | 31-03-2026 | parisberkoding | **MERGE PR#39** - Approver filter | - |
| 143 | `49f687a` | 31-03-2026 | parisberkoding | Push dispatcher updates | 10 files |
| 144 | **`3dcf0a5`** | 31-03-2026 | parisberkoding | MERGE sync | - |
| 145 | `c2c3c86` | 31-03-2026 | **Claude** | **Fix dispatcher flow**: checkbox validation, damage photos, exports - DamagePhoto model + export templates | 19 files (+829/-20) |
| 146 | **`336d876`** | 31-03-2026 | parisberkoding | MERGE sync | - |
| 147 | **`cf7ec66`** | 31-03-2026 | parisberkoding | **MERGE PR#41** - Tracking fix | - |

**Branch:** `vb/fix-barcode-download-VlSGW`, `vb/fix-vehicle-tracking-TWG2y`, `vb/add-approval-filter-bbm-dzwsM`, `vb/fix-image-compression-timeout-a8k7x`

---

### 7.12 Phase 12: Vehicle Checkings & Merge Cleanup (Mar - Apr 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 148 | **`9d5f9b4`** | 31-03-2026 | parisberkoding | **MERGE sync** - Major sync 90 files | 90 files (+8572/-3015) |
| 149 | **`bfa759b`** | 31-03-2026 | parisberkoding | **MERGE PR#18** - Vehicle checkings fix | - |
| 150 | `5e1f5b2` | 31-03-2026 | parisberkoding | Perbaikan Vehicle Checking Dispatcher debugs, report templates | 19 files (+832/-16) |
| 151 | **`64a185c`** | 31-03-2026 | parisberkoding | MERGE sync | - |
| 152 | `4f54c5a` | 31-03-2026 | **Claude** | Fix SPD barcode not showing in print template | 2 files (+64/-12) |
| 153 | **`4cfe15d`** | 31-03-2026 | parisberkoding | **MERGE PR#42** - SPD barcode | - |
| 154 | `625e1a5` | 02-04-2026 | parisberkoding | Perbaikan Retrieve data dari database | 6 files (+116/-79) |
| 155 | **`db4238d`** | 02-04-2026 | parisberkoding | MERGE sync | - |

**Branch:** `vb/fix-vehicle-checkings-column-CoaP4`, `vb/fix-spd-barcode-FPZVG`

---

### 7.13 Phase 13: Lunch Forecast & Distribution Redesign (Jul 2026)

| # | Hash | Tanggal | Author | Deskripsi | Files |
|---|------|---------|--------|-----------|-------|
| 156 | `c4b75a9` | 07-07-2026 | **Claude** | **Redesign Lunch Tracking jadi Lunch Forecast & Distribution System** - Site, LunchForecastBatch, LunchDistribution, LunchGuestReservation models baru | 26 files (+794/-554) |
| 157 | **`1603817`** | 07-07-2026 | parisberkoding | **MERGE PR#43** - Lunch redesign | - |
| 158 | `4f77dec` | 13-07-2026 | **Claude** | **Refactor Lunch Management jadi modul independen** tanpa Vehicle Tracking - EmployeeDefaults, EmployeeAliases, LunchTransaction, LearningProfile, LunchForecastService; drop 6 tabel vehicle | 89 files (+4084/-7048) |
| 159 | `c1eb28f` | 13-07-2026 | Faris I. | Perbaikan LPJ penggantian preprocessing data seeder | 3 files (+35/-100) |
| 160 | **`697000b`** | 13-07-2026 | parisberkoding | **MERGE PR#44** - Lunch management refactor (103 files) | 103 files (+4758/-7482) |
| 161 | `1c3ea73` | 13-07-2026 | Faris I. | Perbaikan susunan lunch tracking drop out | 1 file |
| 162 | `4b8801d` | 17-07-2026 | Faris I. | **Perbaikan SPD Controller: LPJ Data Creating report** + doc_data.json | 3 files (+2496/-7) |
| 163 | **`23df513`** | 17-07-2026 | Faris I. | **HEAD** Perbaikan SPD Controller: LPJ Data & Show Report | 3 files |

**Branch:** `vb/lunch-tracking-forecast-redesign-y29rvu`, `vb/lunch-management-refactor-wv96id`

---

## 8. Branch & Merge History

### 8.1 Branch Map (Sederhana)

```
main  ←─── authentication (merged 11 Nov 2025)
 │
 ├── superadmin (active Feb 2026, merged PR#2 19 Feb 2026)
 │
 ├── vb/siaga-new-module-o5btR (merged PR#3 19 Feb 2026)
 ├── vb/debug-cook-feature-1JhgS (merged PR#3 19 Feb 2026)
 ├── vb/refactor-booking-flow-Vkz2e (merged PR#4 19 Feb 2026)
 ├── vb/refactor-booking-flow-j7MOE (merged PR#8 20 Feb 2026)
 ├── vb/default-user-location-CqudD (merged PR#9 27 Feb 2026)
 ├── vb/lunch-tracking-catering-DW9Tc (merged PR#10 27 Feb 2026)
 ├── vb/improve-dispatcher-flow-U46DT (merged PR#11,12 27 Feb 2026)
 ├── vb/fix-vehicle-query-S832v (merged PR#13 27 Feb 2026)
 ├── vb/remove-trip-flow-gRE9N (merged PR#14 2 Mar 2026)
 ├── vb/add-trip-history-view-foKHq (merged PR#15 2 Mar 2026)
 ├── vb/fuel-transaction-report-gjjMU (merged PR#16 2 Mar 2026)
 ├── vb/fix-fuel-transaction-gap-H7xgs (merged PR#17 2 Mar 2026)
 ├── vb/fix-vehicle-checkings-column-CoaP4 (merged PR#18 31 Mar 2026)
 ├── vb/fix-bbm-pdf-export-aWaZo (merged PR#19 2 Mar 2026)
 ├── vb/fix-pdf-export-report-lQwGj (merged PR#20 2 Mar 2026) [reverted]
 ├── vb/fuel-transaction-report-baVHA (merged PR#21,23,25 2 Mar 2026)
 ├── vb/bbm-report-template-LdTsY (merged PR#22 2 Mar 2026)
 ├── vb/export-report-transactions-card-biUt4 (merged PR#24 2 Mar 2026)
 ├── vb/fix-pdf-margins-loading-dRuAg (merged PR#26 2 Mar 2026)
 ├── vb/fix-qr-code-scanner-S9FzM (merged PR#28 5 Mar 2026)
 ├── vb/refactor-dashboard-layout-Ei8Cz (merged PR#29 9 Mar 2026)
 ├── vb/fix-fuel-report-user-management-7Y3Pv (merged PR#30 10 Mar 2026)
 ├── vb/fix-export-report-null-5wUsn (merged PR#31 10 Mar 2026)
 ├── vb/fix-barcode-vehicle-view-BXIJW (merged PR#32 10 Mar 2026)
 ├── vb/fix-admin-approval-view-czKqf (merged PR#33 10 Mar 2026)
 ├── vb/standardize-view-components-XQbuj (merged PR#35 10 Mar 2026)
 ├── vb/fix-barcode-download-VlSGW (merged PR#37 16 Mar 2026)
 ├── vb/fix-vehicle-tracking-TWG2y (merged PR#38,41 30-31 Mar 2026)
 ├── vb/add-approval-filter-bbm-dzwsM (merged PR#39 31 Mar 2026)
 ├── vb/fix-image-compression-timeout-a8k7x (merged PR#40 31 Mar 2026)
 ├── vb/fix-spd-barcode-FPZVG (merged PR#42 31 Mar 2026)
 ├── vb/lunch-tracking-forecast-redesign-y29rvu (merged PR#43 7 Jul 2026)
 └── vb/lunch-management-refactor-wv96id (merged PR#44 13 Jul 2026)
```

### 8.2 Semua Pull Request yang Di-merge

| PR | Branch | Di-merge oleh | Tanggal |
|----|--------|--------------|---------|
| PR#1 | `authentication` | mg.faris | 11-11-2025 |
| PR#2 | `superadmin` | parisberkoding | 19-02-2026 |
| PR#3 | `debug-cook-feature-1JhgS` | parisberkoding | 19-02-2026 |
| PR#4 | `vb/refactor-booking-flow-Vkz2e` | parisberkoding | 19-02-2026 |
| PR#5 | Revert | parisberkoding | 20-02-2026 |
| PR#6 | `vb/refactor-booking-flow-Vkz2e` | parisberkoding | 20-02-2026 |
| PR#8 | `vb/refactor-booking-flow-j7MOE` | parisberkoding | 20-02-2026 |
| PR#9 | `vb/default-user-location-CqudD` | parisberkoding | 27-02-2026 |
| PR#10 | `vb/lunch-tracking-catering-DW9Tc` | parisberkoding | 27-02-2026 |
| PR#11 | `vb/improve-dispatcher-flow-U46DT` | parisberkoding | 27-02-2026 |
| PR#12 | `vb/improve-dispatcher-flow-U46DT` | parisberkoding | 27-02-2026 |
| PR#13 | `vb/fix-vehicle-query-S832v` | parisberkoding | 27-02-2026 |
| PR#14 | `vb/remove-trip-flow-gRE9N` | parisberkoding | 02-03-2026 |
| PR#15 | `vb/add-trip-history-view-foKHq` | parisberkoding | 02-03-2026 |
| PR#16 | `vb/fuel-transaction-report-gjjMU` | parisberkoding | 02-03-2026 |
| PR#17 | `vb/fix-fuel-transaction-gap-H7xgs` | parisberkoding | 02-03-2026 |
| PR#18 | `vb/fix-vehicle-checkings-column-CoaP4` | parisberkoding | 31-03-2026 |
| PR#19 | `vb/fix-bbm-pdf-export-aWaZo` | parisberkoding | 02-03-2026 |
| PR#20 | `vb/fix-pdf-export-report-lQwGj` | parisberkoding | 02-03-2026 (reverted) |
| PR#21 | `vb/fuel-transaction-report-baVHA` | parisberkoding | 02-03-2026 |
| PR#22 | `vb/bbm-report-template-LdTsY` | parisberkoding | 02-03-2026 |
| PR#23 | `vb/fuel-transaction-report-baVHA` | parisberkoding | 02-03-2026 |
| PR#24 | `vb/export-report-transactions-card-biUt4` | parisberkoding | 02-03-2026 |
| PR#25 | `vb/fuel-transaction-report-baVHA` | parisberkoding | 02-03-2026 |
| PR#26 | `vb/fix-pdf-margins-loading-dRuAg` | parisberkoding | 02-03-2026 |
| PR#28 | `vb/fix-qr-code-scanner-S9FzM` | parisberkoding | 05-03-2026 |
| PR#29 | `vb/refactor-dashboard-layout-Ei8Cz` | parisberkoding | 09-03-2026 |
| PR#30 | `vb/fix-fuel-report-user-management-7Y3Pv` | parisberkoding | 10-03-2026 |
| PR#31 | `vb/fix-export-report-null-5wUsn` | parisberkoding | 10-03-2026 |
| PR#32 | `vb/fix-barcode-vehicle-view-BXIJW` | parisberkoding | 10-03-2026 |
| PR#33 | `vb/fix-admin-approval-view-czKqf` | parisberkoding | 10-03-2026 |
| PR#35 | `vb/standardize-view-components-XQbuj` | parisberkoding | 10-03-2026 |
| PR#37 | `vb/fix-barcode-download-VlSGW` | parisberkoding | 16-03-2026 |
| PR#38 | `vb/fix-vehicle-tracking-TWG2y` | parisberkoding | 30-03-2026 |
| PR#39 | `vb/add-approval-filter-bbm-dzwsM` | parisberkoding | 31-03-2026 |
| PR#40 | `vb/fix-image-compression-timeout-a8k7x` | parisberkoding | 31-03-2026 |
| PR#41 | `vb/fix-vehicle-tracking-TWG2y` | parisberkoding | 31-03-2026 |
| PR#42 | `vb/fix-spd-barcode-FPZVG` | parisberkoding | 31-03-2026 |
| PR#43 | `vb/lunch-tracking-forecast-redesign-y29rvu` | parisberkoding | 07-07-2026 |
| PR#44 | `vb/lunch-management-refactor-wv96id` | parisberkoding | 13-07-2026 |


## 9. Commit Statistics

| Metrik | Nilai |
|--------|-------|
| Total commits | ~163 |
| Unique non-merge commits | ~100 |
| Merge commits | ~44 |
| Branches (saat ini) | 1 (`main`) |
| Rentang tanggal | 06-11-2025 s/d 17-07-2026 |
| HEAD | `23df513` (17-07-2026) |

---

*Dokumentasi ini dibuat pada 21 Juli 2026 berdasarkan analisis kode dan riwayat git project BBM GDE.*
