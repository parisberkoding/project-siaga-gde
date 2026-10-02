# Rencana Teknis — Gate Modul per Unit & Integrasi SSO

**Status:** Draf untuk direview · **Belum ada kode yang ditulis**
**Konteks:** Revisi IT Pusat — aplikasi berbasis di Pusat untuk seluruh unit
**Ruang lingkup:** Dua fitur yang saling bergantung
1. **Gate Modul per Unit** — "Modul mana yang di-enable untuk unit mana"
2. **Integrasi SSO Geodipa** — login terpusat + linking identitas

> Keduanya bertemu di satu titik: **`users.unit_id`**. Dan hasil audit data menunjukkan kolom itu
> **100% kosong** saat ini, sehingga urutan pengerjaannya tidak bisa dibalik.

---

## 1. Ringkasan Eksekutif

| # | Kesimpulan | Dampak |
|---|---|---|
| 1 | `users.unit_id` kosong **60 dari 60** user | Backfill wajib **diturunkan dari `default_site_id`**, bukan ditebak ke PUSAT |
| 2 | Rencana awal "backfill NULL → PUSAT" **salah fatal** | 54 user Patuha akan jadi `is_central` → bypass seluruh gate modul & batas data |
| 3 | `users.jabatan` kosong **59 dari 60** | Tidak bisa dipakai sebagai sumber mapping apa pun |
| 4 | Jabatan SSO (3 nilai) lebih kasar dari lokal (7 nilai) | Mapping jabatan **tidak boleh** jadi penentu tunggal — wajib ada override lokal |
| 5 | Jembatan Master Pekerja ↔ Master User **belum pernah dipakai** (0 dari 10 terisi) | Integrasi SSO bersifat *greenfield*, bukan migrasi |
| 6 | Route `sa/rbac` **kehilangan middleware `auth`** | Tidak dapat dieksploitasi hari ini, tetapi merupakan bug otorisasi laten |
| 7 | Belum ada infrastruktur SSO sama sekali | Desain harus *provider-agnostic* sampai IT Pusat memutuskan protokol |
| 8 | Tabel `users` **tidak punya kolom `is_active`** | Tanpa ini, revoke SSO tidak punya arti apa pun |

---

## 2. Kondisi Eksisting (hasil audit kode & data)

### 2.1 Basis data (data aktual saat audit)

| Entitas | Jumlah | Catatan |
|---|---|---|
| `users` aktif | 60 | `unit_id` **0 terisi**, `jabatan` 59 NULL |
| User punya `default_site_id` | 59 | 1 user tanpa site |
| `employee_defaults` | 10 | `user_id` **0 terisi** |
| `employee_aliases` | 14 | — |
| `units` | 3 | PUSAT, PATUHA, DIENG |
| `sites` | 10 | Semua sudah ter-map ke unit |
| Distict `employee_defaults.position` | 7 | Operator, Staff Admin, Driver, Teknisi, Staff Keuangan, Manager, Supervisor |
| Distict `users.jabatan` | 1 | Hanya `MAGANG` |

Distribusi role saat ini: `requester` 42, `admin_bbm` 9, `superadmin` 5, `view_only` 1, kosong 3 — **semuanya tanpa unit**.

### 2.2 Kemampuan menurunkan unit dari data

```
users.default_site_id → sites.unit_id → units
  PATUHA : 54 user
  PUSAT  :  5 user
  tidak dapat diturunkan : 1 user
```

User yatim yang dimaksud:

| Kolom | Nilai |
|---|---|
| `id` | 68 |
| `user_code` | USR-0064 |
| `username` | `gde_preview` |
| `nama_lengkap` | GDE Preview |
| `role` / role RBAC | `superadmin` |
| `default_site_id` | (kosong) |
| `unit_id` | (kosong) |
| `email` | (kosong) |
| dibuat | 2026-09-29 |
| histori | 0 permohonan, 0 surat SPD |

> **Keputusan:** user ini **ditahan di antrian review**, dan constraint `NOT NULL` pada
> `users.unit_id` **belum dipasang** sampai statusnya diputuskan manual. Alasannya: memberi
> unit PUSAT berarti memberi bypass gate modul kepada akun yang unitnya tidak diketahui.
> Perlu dikonfirmasi apakah akun ini masih dipakai atau bisa dihapus.

### 2.3 Permukaan otorisasi

| Metrik | Nilai |
|---|---|
| Route terdaftar | 273 |
| Punya `auth` | 251 |
| Punya `permission:` | 238 |
| Punya `role:` | 15 |
| Punya **keduanya** | 0 |
| `auth` tanpa gate | 4 |
| Tanpa `auth` | 22 |

Dari 22 route tanpa `auth`: **16 memang publik** (login, QR attendance, panduan), dan
**6 adalah `sa/rbac`** yang seharusnya terlindungi — lihat §10.

Deklarasi middleware di `routes/web.php`: **66 pemanggilan `permission:`** yang karena
pengelompokan berlaku ke **238 route**. Semua route ter-gate melewati satu pintu:
`CheckPermission` → `User::hasPermission()` → `User::getPermissionCodes()`.

Di luar route, otorisasi juga dipakai di:
- `Gate::define('permission')` di `AppServiceProvider`
- `resources/views/partials/sidebar.blade.php` (`$pcodes` + `$showItem`)
- 3 pengecekan `hasPermission` langsung di `resources/views/superadmin/spd/lpj/detail.blade.php`

### 2.4 Lapisan data scoping unit

Sudah ada dan berfungsi: `scopeInUnit()` di **14 model**, dengan **2 varian signature**:

| Varian | Model | Jumlah |
|---|---|---|
| `?int $unitId` | Permohonan, Approved_Request, SuratSpd, SuratSpdExtension, LpjPenggantian, Transaction_Proofs, VehicleAndToolsConsumers, VehiclePartsReplacement, VehicleCheckingSchedule | 9 |
| `?array $siteIds` | EmployeeDefault, LunchForecast, LunchTransaction, LunchDistribution, LunchGuestReservation | 5 |

**96 call site** `inUnit()`, tetapi hanya **19 titik resolusi**:

| Titik resolusi | Jumlah | Cara menutup central bypass |
|---|---|---|
| `$siteIds = user()->unitSiteIds()` | 9 | 1 edit di `unitSiteIds()` menutup ~45 call site |
| `$unitId = user()->unit_id` | 9 | ganti ke resolver baru |
| inline `inUnit($request->user()?->unit_id)` | 5 | ganti ke resolver baru |
| DataMart scope builder | 1 | ganti ke resolver baru |

Keduanya sudah memperlakukan `null` sebagai "tanpa scope", sehingga **bypass central cukup
dengan mengembalikan `null`** dari resolver — bukan membongkar 96 call site.

### 2.5 RBAC

- `permission_groups` **11 grup**: dashboard, bbm, spd, lpj, pelaporan, vehicle, users, rbac, lunch, settings, ai
- `permissions` ±57 baris, format `module.action`
- `roles` 8: superadmin, general_affairs, dispatcher, requester, manager, view_only, vendor, pengguna_spd
- Pivot: `role_permissions`, `user_roles` (`is_primary`)
- Kolom legacy `users.role` masih dual-write
- **Catatan penting:** `pelaporan` adalah satu-satunya grup yang menaungi **dua siklus bisnis** (BBM operasional + SPD)

### 2.6 Infrastruktur SSO

**Tidak ada.** Tidak ditemukan oauth/oidc/saml/jwt/socialite di `composer.json` maupun
kode. `config/services.php` hanya berisi Slack. Ini *greenfield*.

---

## 3. Keputusan yang Sudah Disepakati

### 3.1 Gate Modul per Unit

| Topik | Keputusan |
|---|---|
| Definisi modul | 1 **siklus utuh**: input → proses → report → dashboard |
| Daftar modul | **8 modul**, dashboard melekat pada siklusnya (bukan modul tersendiri) |
| `pelaporan` | **Dipecah** menjadi `pelaporan.bbm.*` dan `pelaporan.spd.*` |
| Modul dapat dimatikan | BBM, SPD & LPJ, Kendaraan & Alat, Lunch, AI, Manajemen Pengguna |
| Selalu aktif | RBAC, Pengaturan & Data Master |
| Bypass | flag `units.is_central` — **menempel ke unit, bukan role** |
| Yang boleh kelola matriks | hanya superadmin yang unitnya Pusat |
| Izin kelola matriks | permission baru `settings.modules` |
| Modul dimatikan | hilang total: menu disembunyikan + akses URL 403 + halaman penjelasan |
| Unit eksisting | **grandfather**: semua modul enabled, Pusat review manual |
| Unit baru | default **BBM + SPD** tercentang, tetap bisa diubah |
| Titik enforcement | **disatukan** di `hasPermission()`/`getPermissionCodes()` |
| Route | tetap memakai `permission:` — tanpa middleware modul baru |
| Konsep "modul" | **tabel `modules` terpisah**, bukan menyatu dengan `permission_groups` |

**Rumus:**

```
efektif  =  punya Permission (RBAC, global)  AND  modul enabled untuk unit user
bypass   =  user->unit->is_central = true
```

### 3.2 SSO

| Topik | Keputusan |
|---|---|
| Protokol | **belum pasti** → bangun lapisan *provider-agnostic* dulu |
| Kemungkinan protokol | OAuth/OIDC, dengan OTP per login (perlu diklarifikasi — lihat §11) |
| Directory | **seluruh karyawan Geodipa** |
| Provisioning | JIT, akun langsung aktif |
| Belum ter-map | akun aktif + **nol permission** + antrian review |
| Sumber role | `(jabatan + unit) → role`, **plus override per user yang menang** |
| Perubahan jabatan | **asimetris**: downgrade & revoke otomatis; upgrade perlu review |
| Revalidasi sesi | periodik 10–15 menit (timestamp di cache) |
| Deaktivasi lokal vs SSO | **tandai konflik**, Pusat yang memutuskan, tanpa perubahan otomatis |
| Akun lokal Pusat | dipertahankan sebagai **break-glass account** |

---

## 4. Arsitektur A — Gate Modul per Unit

### 4.1 Definisi 8 modul

| # | Modul | Siklus | Permission inti |
|---|---|---|---|
| 1 | BBM & Permohonan | permohonan → approval → bukti → cetak laporan | `bbm.*`, `pelaporan.bbm.*` |
| 2 | Perjalanan Dinas (SPD & LPJ) | surat tugas → validasi → SPD berjalan → LPJ → validasi berjenjang | `spd.*`, `lpj.*`, `pelaporan.spd.*` |
| 3 | Kendaraan & Alat | master → aktivasi → pengecekan → VPR/service → laporan | `vehicle.*` |
| 4 | Lunch Management | employee default → forecast → distribusi → transaksi | `lunch.*` |
| 5 | AI Assistant | chat / OCR | `ai.*` |
| 6 | Manajemen Pengguna | create → update → reset → nonaktif | `users.*` |
| — | RBAC *(selalu aktif)* | role → permission | `rbac.*` |
| — | Pengaturan & Data Master *(selalu aktif)* | harga BBM, unit, site | `settings.*` |

**Dashboard** tidak menjadi modul tersendiri. `dashboard.view` di-attribute ke modul 1–5,
sehingga user mendarat di dashboard modul pertama yang enabled. Kalau semua modul unit
mati → **halaman empty state**, bukan dashboard kosong.

### 4.2 Skema

**Tabel `modules`**

| Kolom | Keterangan |
|---|---|
| `code` | unik, mis. `bbm`, `spd`, `vehicle` |
| `name`, `icon`, `order` | tampilan |
| `is_gateable` | boleh dimatikan per unit? |
| `is_always_on` | RBAC & Pengaturan = true |
| `default_enabled` | dipakai saat create unit baru (BBM & SPD = true) |

**Tabel `module_permissions`** — pivot many-to-many `module_id` ↔ `permission_id`.
Many-to-many diperlukan karena beberapa permission dipakai lintas siklus:

| Permission | Pemilik |
|---|---|
| `pelaporan.view` / `pelaporan.analyze` | **kedua** modul (halaman `/pelaporan` satu, dua mode) |
| `dashboard.view` | modul 1–5 |
| sisanya | satu modul |

**Tabel `unit_modules`** — pivot `unit_id` ↔ `module_id` + `is_enabled`.
Inilah matriks yang dichecklist di halaman Pusat.

**Kolom `units.is_central`** — PUSAT = true. Menentukan bypass gate modul.

**Tabel audit `module_access_logs`** — `user_id` (pelaku), `unit_id`, `module_id`,
`action` (enable/disable), `created_at`. Wajib, karena belum ada infrastruktur audit sama sekali.

**Permission baru** `settings.modules`.

### 4.3 Titik enforcement

Semua otorisasi melewati `hasPermission()`. Menambahkan filter modul di sana
mengamankan **238 route + Gate + sidebar + 3 blade** sekaligus tanpa menyentuh
masing-masing.

Resolver modul memakai tabel `module_permissions` (bukan menurunkan dari prefix kode
permission), karena `pelaporan` membuktikan prefix tidak dapat diandalkan.

**Bypass central** diimplementasikan di dua tempat:

| Metode | Perubahan |
|---|---|
| `getPermissionCodes()` | kembalikan seluruh permission bila unit central |
| `unitSiteIds()` / resolver unit baru | kembalikan `null` (tanpa scope) bila unit central |

Dengan begitu **9 call site `$siteIds` tertutup oleh 1 edit**, dan hanya ~14 titik
resolusi `$unitId` yang perlu disesuaikan.

### 4.4 Perilaku saat modul dimatikan

| Aspek | Perilaku |
|---|---|
| Sidebar | grup & anak disembunyikan |
| URL langsung | 403 + halaman penjelasan |
| Badge count di sidebar | di-skip agar tidak query sia-sia |
| Data existing | **tidak dihapus**, hanya tidak dapat diakses |
| Query agregasi Pusat | **tetap lintas unit** — gate hanya di lapisan akses, bukan agregasi |
| Semua modul mati | empty state, bukan halaman kosong |

Poin "query agregasi Pusat tetap lintas unit" bersifat wajib: tanpa itu, mematikan modul
untuk sebuah unit akan membuat Pusat buta terhadap data unit tersebut.

---

## 5. Arsitektur B — SSO

### 5.1 Kepemilikan atribut (perlu disepakati IT Pusat)

| Atribut | Pemilik | Catatan |
|---|---|---|
| `sso_subject` | **SSO** | ID unik IdP, immutable, kunci linking |
| nama lengkap, email | **SSO** | sync ke `employee_defaults`; **alias lama dipertahankan** |
| jabatan | **SSO** (bila tersedia) | nilainya kasar — bukan penentu final |
| unit | **SSO** (bila tersedia) | harus di-map ke `units.code` lokal |
| `user_code` | **Lokal** | **immutable** — dia route key |
| role & permission | **Lokal** | dari mapping + override |
| `default_site_id` | **Lokal** | SSO tidak punya granularitas site |
| link `employee_defaults` | **Lokal** | jembatan ke Lunch |
| `is_active` | **Lokal** + sinyal revoke SSO | — |
| modul enabled per unit | **Lokal** | matriks §4 |

### 5.2 Skema

**Kolom baru di `users`**

| Kolom | Keterangan |
|---|---|
| `is_active` | **belum ada saat ini** — tanpa ini revoke tidak berarti |
| `deactivated_at`, `deactivation_reason` | jejak penonaktifan |
| `auth_provider` | `local` \| `sso` — menentukan jalur login |
| `sso_synced_at` | kapan terakhir disinkronkan |

**Tabel `sso_identities`** — dipisah dari `users` agar dapat menampung lebih dari satu provider:

| Kolom | Keterangan |
|---|---|
| `user_id` | relasi |
| `provider` | mis. `geodipa` |
| `subject` | ID unik IdP, unik per provider |
| `claims` | JSON — **raw claims** terakhir |
| `last_synced_at` | — |

**Tabel `sso_role_mappings`** — `jabatan`, `unit_id`, `role_id`.
Dipecah per kombinasi, **bukan per user** (lihat §5.4).

**Tabel `sso_sync_logs`** — `auth_provider`, `subject`, `raw_claims`, `hasil_mapping`,
`keputusan`, `created_at`. Raw claims disimpan karena sangat berguna saat troubleshooting.

**Adapter contract** — satu interface (`redirect()`, `callback()`, `claims()`) dengan
implementasi per protokol. Saat IT Pusat memutuskan, yang ditambah hanya satu implementasi.

### 5.3 Alur login

**Skema A — SSO mengembalikan atribut (OIDC/OAuth2)**

```
user buka SIAGA → redirect ke IdP (OTP di dalam IdP)
  → callback dengan token berisi claims (sub, nama, email, unit, jabatan)
  → cari user via sso_identities.subject
      ├─ ada    → sync atribut, evaluasi perubahan role
      └─ tidak  → JIT: buat user + employee_defaults, cari mapping (jabatan+unit)
                    ├─ mapping ada   → beri role
                    └─ mapping tidak → akun aktif, NOL permission, masuk antrian review
  → set device fingerprint SETELAH callback selesai
  → login
```

**Skema B — OTP hanya mengautentikasi (tanpa claims)**

```
user isi username di form SIAGA → OTP → verifikasi ke IdP
  → unit & jabatan dibaca dari Master Pekerja lokal (employee_defaults.defaultSite.unit_id)
  → cari mapping
  → login
```

Pada Skema B, matriks mapping menjadi **jabatan × SITE** (bukan jabatan × unit), karena
unit diturunkan dari `default_site_id`.

### 5.4 Antrian review per kombinasi, bukan per user

Karena directory berisi **seluruh karyawan Geodipa**, review per user tidak akan terkelola.
Tetapi tidak perlu — kuncinya per kombinasi:

```
kombinasi (jabatan × unit) yang belum ter-map  ≈  3 jabatan × 3–4 unit  =  maksimal 12 baris
```

Pusat mengisi 12 baris, dan **seluruh user pada kombinasi itu aktif sekaligus**. Bahkan
bila 1.000 karyawan login di minggu pertama, antrian tetap 12 baris.

### 5.5 Aturan perubahan jabatan (asimetris)

| Jenis perubahan | Perilaku |
|---|---|
| **Revoke** (SSO nonaktif) | langsung berlaku |
| **Downgrade** (role lebih rendah) | otomatis berlaku |
| **Upgrade** (role lebih tinggi) | masuk antrian **approval Pusat** |
| **Unit berpindah** | dicatat di audit, dan **memicu re-evaluasi gate modul** |
| **Konflik** (lokal nonaktif, SSO aktif) | **ditandai konflik**, Pusat memutuskan |

Alasan asimetri: promosi di SSO tidak boleh diam-diam menaikkan hak akses, tetapi
pencabutan hak harus instan agar tidak ada jendela akses berlebih.

### 5.6 Aturan JIT yang wajib

| Aturan | Alasan |
|---|---|
| JIT **tidak boleh** mengubah `is_active` menjadi `true` | kalau tidak, menonaktifkan user menjadi tidak ada gunanya |
| JIT **tidak boleh** menebak unit | menebak ke PUSAT = memberi bypass gate modul |
| JIT **wajib** membuat `employee_defaults` + link | tanpa itu user SSO tidak akan pernah ter-resolve di absensi Lunch (QR resolve berdasarkan nama, bukan akun) |
| Nama berubah → **alias lama dipertahankan** | justru alias lama yang menjaga QR attendance tetap resolve |
| Unit tidak dikenal → `needs_review`, **nol modul** | *fail-closed* |

### 5.7 Menutup jalur login lokal untuk user SSO

Tanpa ini, deprovisioning SSO tidak ada artinya: SSO mencabut akses, tetapi password lokal
masih hidup.

| Aspek | Keputusan |
|---|---|
| `users.password` | saat ini **NOT NULL** → perlu dibuat nullable atau diisi nilai acak tak tertebak |
| Form login lokal | **menolak** user ber-`auth_provider = 'sso'` dengan pesan jelas |
| Pesan | "Akun Anda menggunakan SSO Geodipa, silakan login melalui SSO" |

### 5.8 Break-glass account

`superadmin_pusat` adalah akun **lokal** (bukan SSO), sehingga otomatis menjadi satu-satunya
jalan masuk bila IdP mati.

| Ketentuan | Keterangan |
|---|---|
| Kredensial | disimpan **di luar repo**, di tempat terkontrol |
| Jangan dihapus | sekalipun SSO sudah berjalan penuh |
| Alert | beri notifikasi setiap kali akun ini dipakai |
| Password | **`superadmin` terlalu lemah** — ganti sebelum go-live |

> **Koreksi dari diskusi:** username `superadmin.pusat` **tidak dapat login**. Aturan
> validasi login memakai `alpha_dash` yang menolak titik dan spasi. Hasil uji:
> `superadmin.pusat` DITOLAK · `superadmin pusat` DITOLAK · `superadmin_pusat` LOLOS.
> Jadi username yang dipakai adalah **`superadmin_pusat`**.

---

## 6. Titik Temu — `users.unit_id`

Kedua fitur bergantung pada `unit_id`, dan datanya kosong. Urutannya **tidak boleh dibalik**:

1. Gate modul butuh `unit_id` untuk menentukan modul mana yang berlaku
2. SSO butuh `unit_id` untuk mencari mapping `(jabatan + unit) → role`
3. Karena itu **backfill `unit_id` harus selesai sebelum kedua fitur berjalan**

### 6.1 Backfill yang benar

| Sumber | Aksi |
|---|---|
| `default_site_id` ada → `sites.unit_id` | pakai unit hasil turunan (54 PATUHA, 5 PUSAT) |
| `default_site_id` kosong | **tahan di antrian review**, jangan di-NOT-NULL-kan |
| `sites.unit_id` kosong | turunkan dari konteks lain, atau tahan |

> **Peringatan keras:** **JANGAN** membackfill `unit_id` kosong ke PUSAT. Karena PUSAT
> adalah `is_central = true`, tindakan itu akan membuat 54 user Patuha mendapat bypass
> seluruh gate modul **dan** menembus batas data `inUnit()` yang sudah ada.

---

## 7. Rencana Migrasi (urutan aman)

Setiap langkah dirancang agar tidak ada kondisi antara yang merusak akses.

| # | Langkah | Risiko | Dapat dibalik? |
|---|---|---|---|
| 0 | **Backup database penuh** | — | — |
| 1 | Tambah `units.is_central`, set PUSAT = true | Rendah | Ya |
| 2 | Buat `modules`, `module_permissions`, `unit_modules`, `module_access_logs` | Rendah | Ya |
| 3 | Backfill `unit_modules` = **semua enabled** untuk PUSAT/PATUHA/DIENG (grandfather) | Rendah | Ya |
| 4 | Backfill `users.unit_id` dari `default_site_id` (54 PATUHA, 5 PUSAT) | **Tinggi** | Ya, tapi perlu backup |
| 5 | Ubah FK `units` → `restrictOnDelete` pada `users` & `sites` | Sedang | Ya |
| 6 | Tambah `is_active`, `deactivated_at`, `deactivation_reason`, `auth_provider`, `sso_synced_at` | Rendah | Ya |
| 7 | Buat `sso_identities`, `sso_role_mappings`, `sso_sync_logs` | Rendah | Ya |
| 8 | Permudah `users.password` menjadi nullable | Sedang | Ya |
| 9 | Pecah permission `pelaporan.*` → `.bbm.*` / `.spd.*`, update seeder role | **Sedang** | Sulit |
| 10 | Tambah permission `settings.modules` | Rendah | Ya |
| 11 | Seed `superadmin_pusat` (unit PUSAT) | Rendah | Ya |
| 12 | **Tahan** `users.unit_id` NOT NULL sampai user yatim diputuskan | — | — |
| 13 | Integrasi resolver modul di `hasPermission()` | Sedang | Ya |
| 14 | Guard Manajemen User (scoping + larangan set unit asing/kosong) | **Tinggi** | Ya |
| 15 | UI matriks modul per unit + audit | Sedang | Ya |
| 16 | Adapter SSO + bulk pre-sync + JIT | Sedang | Ya |

### 7.1 Catatan pada langkah-langkah kritis

**Langkah 4** — satu-satunya langkah yang mengubah data user secara massal. Wajib backup
sebelum dijalankan, dan wajib diverifikasi sesudahnya: distribusi unit harus **54 PATUHA /
5 PUSAT / 1 tanpa unit**.

**Langkah 5** — FK saat ini memakai `nullOnDelete`, yang kontradiktif dengan `NOT NULL`.
Kabar baiknya `UnitController::destroy` **sudah** memblokir penghapusan unit yang masih
punya user atau site, jadi guard-nya sudah ada di lapisan aplikasi.

**Langkah 9** — `pelaporan.*` dipakai oleh role `requester`, `manager`, dan `view_only` di
`RbacSeeder`. Pemecahan menyentuh `routes/web.php`, seeder, `ReportingController`, dan view
`pelaporan.index`. Perlu diingat `analyze-struk` (OCR) **dipakai kedua mode**, sehingga
permission-nya masuk ke dua modul.

**Langkah 14** — menutup lubang keamanan yang ditemukan saat audit:

| Lokasi | Masalah |
|---|---|
| `indexUsers` | `User::query()` **tanpa** `inUnit()` → unit superadmin melihat user semua unit |
| `storeUser` | `unit_id` hanya divalidasi `nullable`, tidak ada guard unit |
| `updateUser` | idem — bisa memindahkan user ke unit lain atau mengosongkan `unit_id` |
| Form user | masih menawarkan "Kosongkan untuk akses global (pusat)" |

Bandingkan `indexVehicles` yang **sudah** memakai `.inUnit()` — polanya sudah ada, hanya
belum diterapkan di Manajemen User.

---

## 8. Risiko & Mitigasi

| # | Risiko | Mitigasi |
|---|---|---|
| R1 | Jabatan SSO (3 nilai) tidak cukup menentukan role — dua "staff" di unit berbeda butuh permission berbeda | mapping `(jabatan + unit) → role` + **override per user yang menang** |
| R2 | Naik jabatan di SSO otomatis menaikkan hak akses | kebijakan **asimetris** — upgrade perlu approval Pusat |
| R3 | Tidak ada `is_active` → revoke SSO tidak berarti | tambah kolom + middleware penolak; **jangan** pakai soft-delete (menghilangkan histori) |
| R4 | User SSO masih bisa login lewat form lokal | `auth_provider` + penolakan eksplisit + password tidak terpakai |
| R5 | Unit dari SSO tidak dikenal; fallback ke PUSAT = eskalasi | **fail-closed**: `needs_review`, nol modul, tidak ditebak |
| R6 | Sesi lokal 120 menit hidup lebih lama dari sesi IdP | revalidasi periodik 10–15 menit via cache timestamp |
| R7 | JIT menghidupkan ulang akun yang dinonaktifkan lokal | JIT **dilarang** menyentuh `is_active` |
| R8 | IdP outage = seluruh organisasi terkunci | **break-glass account** lokal |
| R9 | JIT auto-active + directory seluruh karyawan = ribuan akun nol-permission | review **per kombinasi** (maks 12 baris), bukan per user |
| R10 | Blast radius JIT saat pertama dibuka | **jangan langsung buka JIT** — lakukan bulk pre-sync dulu |
| R11 | Tidak ada audit trail untuk perubahan hak akses | `sso_sync_logs` + `module_access_logs`, simpan **raw claims** |
| R12 | `VerifyDeviceFingerprint` (UA + IP/24) memicu logout palsu saat redirect dari IdP | set fingerprint **setelah** callback selesai, bukan sebelum |
| R13 | Nama berubah di SSO memutus identity resolution Lunch | pertahankan alias lama; sync `employee_defaults.employee_name` |
| R14 | `user_code` berubah saat role/unit berubah → URL & bookmark putus | `user_code` **immutable** |
| R15 | Perubahan unit mengubah akses modul sepenuhnya, tanpa jejak | catat di audit dengan nilai sebelum & sesudah |

### 8.1 Urutan rollout SSO yang disarankan

```
1. Bangun lapisan lokal (auth_provider, is_active, sso_identities, mapping, audit, adapter)
2. Bulk pre-sync dari IdP — tarik semua karyawan, TAPI belum bisa login
3. Pusat isi matriks 12 baris + review daftar user dalam keadaan tenang
4. BARU buka JIT sebagai jaring pengaman untuk karyawan baru
```

Membuka JIT sejak awal akan menghasilkan ribuan akun nol-permission berbarengan dengan
antrian yang belum punya UI-nya.

---

## 9. Rencana Fase Pengerjaan

| Fase | Isi | Prasyarat | Risiko |
|---|---|---|---|
| **0** | Inventaris jalur otorisasi; perbaiki bug `sa/rbac` | — | Rendah |
| **1** | Skema modul + migrasi + grandfather + backfill `unit_id` | Backup | **Tinggi** |
| **2** | Resolver modul di `hasPermission()` + bypass central | Fase 1 | Sedang |
| **3** | Pemecahan permission `pelaporan` | Fase 2 | Sedang |
| **4** | Guard Manajemen User + hapus konsep `unit_id` kosong | Fase 1 | **Tinggi** |
| **5** | UI matriks modul + `settings.modules` + audit | Fase 3 | Sedang |
| **6** | Sidebar/empty state + seeder `superadmin_pusat` + test matrix | Fase 5 | Rendah |
| **7** | Lapisan lokal SSO (`auth_provider`, `is_active`, `sso_identities`, mapping, audit, adapter) | Fase 1 | Sedang |
| **8** | Adapter IdP sesuai keputusan IT Pusat + bulk pre-sync | Fase 7 + **jawaban IT Pusat** | Sedang |
| **9** | JIT + antrian review + kebijakan asimetris | Fase 8 | Sedang |

**Fase 0 tidak bergantung pada apa pun dan tidak mengubah data** — dapat dikerjakan lebih dulu.

---

## 10. Temuan Bug & Utang Teknis

### 10.1 Route `sa/rbac` kehilangan middleware `auth` — prioritas tinggi

`routes/web.php` baris 479:

```
Route::middleware(['auth'])->prefix('sa/rbac')->name('superadmin.rbac.')->middleware('permission:rbac.roles')->group(...)
```

Pemanggilan `->middleware()` **kedua menggantikan** yang pertama, bukan menambahkan.
Hasil `php artisan route:list`:

```
URI: sa/rbac
  middleware: ["web","permission:rbac.roles"]      ← 'auth' HILANG
```

**6 route terdampak:** seluruh grup manajemen Role & Permission.

| Aspek | Keterangan |
|---|---|
| Dapat dieksploitasi? | **Belum** — `CheckPermission` punya guard `Auth::check()` sendiri dan mengembalikan 403 |
| Mengapa tetap serius | Perlindungannya bergantung pada guard internal middleware lain. Bila `CheckPermission` direfaktor, grup ini menjadi **terbuka tanpa login** |
| Dampak sekunder | Route minim kebijakan muncul sebagai "publik" di `route:list`, sehingga menyesatkan review keamanan dan membuat `docs/security/CLOSED_ROUTES.md` tidak akurat |
| Status | **Satu-satunya** tempat di `routes/web.php` dengan dua pemanggilan `->middleware()` pada satu rantai |

### 10.2 `is_active` pada `users` tidak ada, tetapi sudah dirujuk

`AuthentiactionController` baris 64 memeriksa `isset($user->is_active) && !$user->is_active`.
Karena kolomnya tidak ada, `isset` selalu `false` → **seluruh blok adalah dead code**.
Artinya hari ini **tidak ada mekanisme menonaktifkan akun** selain soft-delete.

### 10.3 `UnitSeeder::backfillUnitIds()` tidak menyentuh tabel `users`

Method tersebut membackfill `Permohonan`, `Approved_Request`, `SuratSpd`, dan
`VehicleAndToolsConsumers` — tetapi **tidak** `users`. Itulah sebabnya `users.unit_id`
tetap 100% kosong meskipun site sudah ter-map.

### 10.4 `users.jabatan` tidak terpakai

59 dari 60 user bernilai NULL. Kolom ini tidak dapat dijadikan sumber mapping apa pun
tanpa pengisian data terlebih dahulu.

### 10.5 Jembatan Master Pekerja ↔ Master User belum pernah dipakai

`storeUser` memiliki mode `activate` yang menghubungkan `employee_defaults` ke akun, tetapi
**0 dari 10** baris `employee_defaults` memiliki `user_id`. Alur ini ada tetapi belum
pernah dijalankan.

### 10.6 Tidak ada infrastruktur audit

`SuperadminController::getActivityLog()` masih dikomentari seluruhnya. Tidak ada tabel
log aktivitas.

---

## 11. Pertanyaan Terbuka untuk IT Pusat

### 11.1 Blokir — harus dijawab sebelum Fase 8

| # | Pertanyaan | Mengapa penting |
|---|---|---|
| 1 | Protokol apa yang dipakai: OIDC/OAuth2, SAML, atau OTP saja? | Menentukan bentuk adapter |
| 2 | Apakah IdP **mengembalikan atribut `unit` dan `jabatan`**, atau hanya mengautentikasi? | Menentukan apakah unit/jabatan dibaca dari SSO atau dari Master Pekerja lokal |
| 3 | Format kode unit yang dikirim (mis. `PATUHA` vs `PTH`)? | Harus di-map ke `units.code` lokal |
| 4 | Nilai pasti vocabulary jabatan yang dikirim? | Menentukan isi matriks mapping |
| 5 | Apakah ada claim `division`? | Bisa memperhalus mapping `(jabatan + unit)` yang kasar |
| 6 | Bagaimana mekanisme revoke/deaktivasi karyawan? | Menentukan cara menutup jendela akses |
| 7 | Berapa umur token, dan apakah ada refresh token? | Menentukan interval revalidasi sesi |

> **Catatan klarifikasi:** "OTP tiap login" **bukan SSO**. Bila IdP hanya memverifikasi
> identitas lewat OTP tanpa mengembalikan atribut, maka `unit` dan `jabatan` **harus**
> dibaca dari Master Pekerja lokal, dan matriks mapping menjadi **jabatan × site**.
> Kedua kemungkinan sudah diakomodasi oleh desain *provider-agnostic* di §5.

### 11.2 Keputusan internal yang masih menggantung

| # | Pertanyaan | Konteks |
|---|---|---|
| 1 | Akun `gde_preview` (superadmin, tanpa site) — masih dipakai atau dihapus? | Menghalangi pemasangan `NOT NULL` pada `unit_id` |
| 2 | Apakah `employee_defaults` wajib dibuat untuk semua user SSO? | Tanpa itu, absensi Lunch tidak akan resolve |
| 3 | Kalau SSO tidak mengirim `unit`, siapa yang mengisi unit untuk karyawan baru? | Unit tidak boleh ditebak |
| 4 | Batas waktu toleransi untuk "upgrade role menunggu review"? | User bisa login dengan role lama sementara menunggu |
| 5 | Berapa lama retensi `sso_sync_logs` dan `module_access_logs`? | Ukuran tabel |

---

## 12. Lampiran

### 12.1 Ringkasan angka kunci

| Metrik | Nilai |
|---|---|
| Route terdaftar | 273 |
| Route ter-gate `permission:` | 238 (dari 66 deklarasi) |
| Route ter-gate `role:` | 15 |
| Route publik | 16 |
| Route `auth` tanpa gate | 4 |
| Route `sa/rbac` kehilangan `auth` | 6 |
| `scopeInUnit()` | 14 model, 2 varian signature |
| Call site `inUnit()` | 96 |
| Titik resolusi unit | 19 |
| User | 60 (59 punya `default_site_id`) |
| `unit_id` terisi | **0** |
| Distribusi turunan | 54 PATUHA · 5 PUSAT · 1 yatim |
| `employee_defaults` | 10 (0 ter-link) |
| `permission_groups` / `permissions` / `roles` | 11 / ±57 / 8 |

### 12.2 Berkas utama yang akan tersentuh

| Area | Berkas |
|---|---|
| Otorisasi | `app/Models/User.php`, `app/Http/Middleware/CheckPermission.php`, `app/Providers/AppServiceProvider.php` |
| Scoping unit | 14 model dengan `scopeInUnit`, `app/Http/Controllers/SuperadminController.php`, `SpdController.php`, `RequestManagementController.php`, `Lunch/*` |
| RBAC | `app/Http/Controllers/RbacController.php`, `database/seeders/RbacSeeder.php`, `resources/views/superadmin/rbac/*` |
| Manajemen User | `SuperadminController::indexUsers/storeUser/updateUser`, `resources/views/superadmin/users/*` |
| Pelaporan | `routes/web.php`, `app/Http/Controllers/ReportingController.php`, `resources/views/pelaporan/index.blade.php` |
| Autentikasi | `app/Http/Controllers/AuthentiactionController.php`, `app/Http/Middleware/VerifyDeviceFingerprint.php` |
| Sidebar | `resources/views/partials/sidebar.blade.php` |
| Audit | `SuperadminController::getActivityLog()` (masih dikomentari) |

---

## 13. Peringatan Sebelum Implementasi

1. **Backup database penuh wajib** sebelum Fase 1, khususnya sebelum backfill `unit_id`.
2. **Jangan** membackfill `unit_id` ke PUSAT — 54 user Patuha akan mendapat bypass gate modul.
3. **Jangan** memasang `NOT NULL` pada `unit_id` sebelum user yatim diputuskan.
4. **Password `superadmin` terlalu lemah** untuk akun ber-bypass penuh — ganti sebelum go-live.
5. **Bug `sa/rbac`** sebaiknya diperbaiki di Fase 0 karena sangat murah dan menghilangkan
   ketergantungan pada guard internal middleware lain.
6. Verifikasi `docs/security/CLOSED_ROUTES.md` perlu diperbarui setelah bug `sa/rbac` diperbaiki.
