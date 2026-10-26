# Lunch Management V2 (Phase 1)

Modul Lunch Tracking kini menjadi modul **independen** — seluruh dependency ke
Vehicle/Passenger Tracking telah diputus karena perusahaan memakai GPS Tracker
vendor. Ground truth kehadiran makan siang berasal dari **QR Attendance**, bukan
lagi dari trip kendaraan.

## Arsitektur

```
Employee Default (employee_defaults + employee_aliases)
        │
        ▼
Forecast Engine (lunch_forecast_batches + lunch_forecasts)
        │   prioritas: Manual Override → Tomorrow Confirmation
        │              → Historical Pattern → Default Site
        ▼
Lunch Distribution (lunch_distributions, snapshot terkunci untuk vendor)
        │
        ▼
QR Attendance (publik, tanpa login: /lunch-attendance)
        │
        ▼
Lunch Transaction (lunch_transactions — ground truth)
        │
        ▼
Learning Engine (learning_profiles; alias learning otomatis)
        │
        └──────────────► Forecast H+1 (artisan lunch:generate-forecast,
                          terjadwal harian via routes/console.php)
```

## Identity Resolution Engine

`App\Services\Lunch\IdentityResolutionService`:

```
Raw Input → Normalize → Alias Lookup → Employee Lookup
  → Historical Transaction Lookup → Fuzzy Match
  → Confidence Score → Resolved Employee → Lunch Transaction
```

- **Primary data** transaksi = hasil lookup master (`employee_default_id` +
  snapshot nama/divisi/jabatan).
- **Raw input** (`raw_alias`, `raw_position`, `raw_division`) disimpan hanya
  sebagai audit trail — tidak pernah menjadi data utama.
- Metadata `confidence_score` + `resolution_method`
  (`alias_exact | name_exact | historical | fuzzy | manual | unresolved`).
- Input yang berhasil di-resolve dengan keyakinan tinggi dipelajari otomatis
  sebagai alias baru (`employee_aliases.source = learned`).

## QR Attendance (publik)

- QR per site (PMU / Patuha / BCMP) berisi URL `/lunch-attendance/site/{code}`;
  cetak dari menu **QR Lokasi**.
- Flow: buka kamera → scan QR → form attendance → cari nama (alias lookup)
  → submit (Ajax) → popup **"Besok makan dimana?"** → selesai.
- Tanpa login, tanpa sidebar; endpoint dibatasi `throttle` dan konfirmasi
  besok diamankan dengan `confirmation_token`.
- Jawaban popup tersimpan di `lunch_transactions.tomorrow_site_id` dan menjadi
  prioritas ke-2 Forecast H+1.

## Yang dihapus (dependency Vehicle Tracking)

- Tabel: `trips`, `trip_passengers`, `trip_booking_requests`,
  `driver_schedules`, `vehicle_specifications`, `pekerja_patuha`
  (datanya dimigrasikan ke `employee_defaults`).
- Kolom: `lunch_trackings.source_trip_id`; nilai `prediction_source='vehicle'`
  di-relabel `legacy_vehicle` (histori tetap ada, tidak diproduksi lagi).
- Controller/Model/View/Route/Seeder SIAGA trip serta menu sidebar
  **Tracking Kendaraan / Kalender Perjalanan / Jadwal Driver / Daftar
  Perjalanan / Spesifikasi Kendaraan** di semua role.
- CATATAN: `trip_validations` & `vehicle_trip_analyses` **tetap ada** — meski
  bernama "trip", keduanya milik modul analisis efisiensi BBM (berbasis
  transaction proof), bukan vehicle tracking.

## Menu (admin & superadmin)

Dashboard Lunch · Employee Default · Guest Reservation · Lunch Forecast ·
Lunch Distribution · Lunch Transactions · Analytics · QR Lokasi · Export Vendor.

## Operasional harian

1. `lunch:generate-forecast` berjalan otomatis tiap sore
   (`siaga.forecast_generate_at`, default 15:00) — membuat Forecast H+1 dan
   memperbarui distribusi.
2. Admin meninjau forecast, melakukan **Manual Override** bila perlu, lalu
   **Kunci Angka Vendor** di halaman Distribution.
3. Karyawan scan QR saat makan siang; Learning Engine memperbarui pola dan
   akurasi forecast terisi otomatis.
4. Export Excel vendor (3 sheet) dari menu **Export Vendor**.

---

## Revisi 2026-10 — Alur baru & guard unit

### 1. Hierarki Unit → Site

- **Unit** = satuan administratif (Patuha, Dieng, Pusat).
- **Site** = lokasi spesifik di bawah unit (Power Plant, Basecamp, Kantor,
  Reservoir, Sumur). `sites.type` menyimpan klasifikasi ini.
- `employee_defaults.unit_id` sekarang eksplisit; bila kosong, diturunkan dari
  `default_site_id`.

### 2. Siapa yang melihat semua unit?

| Aktor | Scope data |
|---|---|
| `unit_id` kosong (transisi) | global |
| role `developers` | global |
| role `superadmin` **dan** unit `is_central` (Pusat) | global |
| role lain di unit Pusat (mis. GA Pusat) | hanya site unit Pusat |
| role lain dengan unit | hanya site unitnya |

Implementasi terpusat: `App\Services\UnitScope` + `User::hasGlobalUnitScope()`.

### 3. Alur scan pegawai

1. Scan QR site (cetak dari menu QR Lokasi).
2. Cari nama. Jika sudah ada di master → check-in.
3. Jika belum ada / belum punya akun → isi nama, divisi, unit.
   Sistem membuat `employee_defaults` (status `pending`) + akun `User`
   nonaktif (`registration_source = scan`). Superadmin unit membuat
   username/password final lalu memberikannya offline.
4. Check-in tercatat. Popup **"Besok makan di mana?"** hanya menampilkan site
   di unit pegawai. Jawaban langsung meng-*upsert* forecast H+1
   (`confirmation`); jika dilewati, forecast diisi `default` site.
5. Site QR = lokasi makan **hari ini**; tidak ada lagi modal terpisah untuk
   mengubah site hari ini.

### 4. Menu admin GA (4 item)

1. **Dashboard** — check-in hari ini, belum dikenali, tamu perlu konfirmasi,
   pintasan.
2. **Data Forecast** — ringkasan per site, override inline, tamu, distribusi,
   kunci vendor, export, QR, booking mandiri, dan master karyawan.
3. **Data Pengeluaran & Pembayaran Vendor** — vendor + harga/porsi, generate
   invoice dari porsi distribusi, koreksi nominal, approve, pembayaran
   bertahap (proof upload), status draft/partial/paid.
4. **Custom Purchase** — pembelian manual per tanggal + site dengan rincian
   item. Jika `replaces_catering` aktif, porsi katering pada tanggal + site
   tersebut di-nol-kan agar vendor tidak menagih dua kali.

### 5. Kredensial akun hasil scan

- Password login tetap hash satu arah.
- Salinan terenkripsi (`users.credentials_plain`, APP_KEY) dapat dibuka
  superadmin unit lewat Manajemen User; setiap pembukaan dicatat di
  `user_credential_reveals` dan `credentials_reveal_count`.
- Endpoint: `POST /users/{id}/credentials` dan
  `GET /users/{id}/credentials/reveal` (permission `users.update`, dibatasi
  unit pelaku).

