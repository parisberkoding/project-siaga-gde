# Klasifikasi Pekerjaan — SIAGA (BBM Project)

> Dokumen backlog & resep kerja. Dibuat sebagai hasil klasifikasi awal sebelum eksekusi.
> Prinsip umum: **setiap fitur yang belum ada dibangun LENGKAP dari nol** — kolom database,
> field input form, controller, model, migrasi — semuanya disesuaikan agar cocok dengan database.

---

## Keputusan yang sudah dikunci (hasil klarifikasi)

| # | Topik | Keputusan |
|---|-------|-----------|
| K1 | Isolasi data antar unit | `unit_id` dipakai sebagai **filter scope**. User dengan `unit_id = NULL` (global/pusat) otomatis melihat **SEMUA** unit. |
| K2 | "Pusat" sebagai unit | **Pusat adalah sebuah unit** (`code = Pusat`), bukan NULL. User yang di-scope ke Pusat hanya melihat data Pusat. |
| K3 | Dieng & Pusat | **Dieng = Unit**, **Pusat = Unit**. Site di bawahnya (Basecamp, Reservoir, Powerplant, dst.) adalah milik tiap unit. Seeder lama hanyalah placeholder. |
| K4 | Validasi "User" di flow SPD | Yang memvalidasi kebenaran perjalanan = **user yang berangkat / penumpang**. Perlu **permission baru** di RBAC. |
| K5 | AI/OCR kategorisasi | **Sudah ada**, tidak dibangun dari nol. Yang ditambah hanya **kalkulasi** agregat pemberian vs pemakaian. |
| K6 | Admin pusat vs admin unit | **Ditunda / skip dulu** (Cluster C). |

---

## Temuan Audit (status kode saat ini)

1. Kolom `unit_id` **sudah ada di migration** untuk `permohonans`, `approved_requests`,
   `surat_spds` — tetapi **belum masuk `$fillable`** di model `Permohonan`,
   `Approved_Request`, `SuratSpd`, dan **belum ada relasi `unit()`** di ketiganya.
   → Kolom ada tapi tidak pernah diisi & tidak dipakai query.

2. Scoping data saat ini **masih berbasis SITE**, bukan unit. Semua pakai
   `scopeInUnit($siteIds)` + `User::unitSiteIds()` (lihat `SpdController`,
   `RequestManagementController`, `SuperadminController`, `Lunch/*`). Ini **berbeda**
   dari permintaan (query langsung per `unit_id`).

3. Tabel turunan **belum punya `unit_id`**: `transaction_proofs`, `struk_items`,
   `lpj_penggantians`, `spd_daily_reports`, `surat_spd_details`, `surat_spd_extensions`,
   dan `vehicle_and_tools_consumers` (master BBM operasional).

4. Tabel `units` **belum punya `province` & `city`** (migration `2026_08_12_000100`
   hanya `code, name, description, is_active`), padahal model `Unit` sudah punya
   `province` / `city` di `$fillable`.

5. Nomor surat SPD **masih hardcode** `PTH1.30-GDE` di `SuratSpd::generateNomorSurat()`
   dan `tanggal_surat` default `'Patuha'`.

---

## Cluster A — Isolasi data antar unit (anti bocor)

Tujuan: **tidak ada data bocor antar unit**. Query mengikuti role + unit user yang login.

| # | Pekerjaan | Status | Aksi detail |
|---|-----------|--------|-------------|
| A1 | Kolom `unit_id` di 3 tabel utama | ✅ migration ada | Tambah `unit_id` ke `$fillable` + relasi `unit()` di `Permohonan`, `Approved_Request`, `SuratSpd`. |
| A2 | Query filter by `unit_id` | ⚠️ masih via site | Ubah scope dari `scopeInUnit($siteIds)` → filter `unit_id` langsung. Rule: `unit_id NULL` = global lihat semua; `unit_id = X` = hanya data unit X. |
| A3 | Auto-set `unit_id` saat create | ❌ belum | Isi `unit_id` dari `auth()->user()->unit_id` (fallback dari vehicle/site) saat create permohonan/SPD. |
| A4 | Tabel turunan BBM & SPD perlu `unit_id` | ⚠️ belum | Tambah `unit_id` + relasi + migrasi di: `transaction_proofs`, `struk_items`, `lpj_penggantians`, `spd_daily_reports`, `surat_spd_details`, `surat_spd_extensions`, `vehicle_and_tools_consumers`. |
| A5 | Uji anti-bocor | — | Verifikasi user unit Patuha tidak bisa lihat data Dieng, dst. |

### Model yang terdampak (Cluster A)

- `Permohonan` — tambah `unit_id` di fillable + relasi `unit()`, ubah `scopeInUnit`.
- `Approved_Request` — tambah `unit_id` di fillable + relasi `unit()`, ubah `scopeInUnit`.
- `SuratSpd` — tambah `unit_id` di fillable + relasi `unit()`, ubah `scopeInUnit`.
- `VehicleAndToolsConsumers` — tambah `unit_id` + relasi `unit()` (master BBM operasional).
- Tabel turunan (lihat A4) — tambah `unit_id` + relasi ke parent.

### Migration yang perlu dibuat

- `add_unit_id_to_vehicle_and_tools_consumers_table`
- `add_unit_id_to_transaction_proofs_table`
- `add_unit_id_to_struk_items_table`
- `add_unit_id_to_lpj_penggantians_table`
- `add_unit_id_to_spd_daily_reports_table`
- `add_unit_id_to_surat_spd_details_table`
- `add_unit_id_to_surat_spd_extensions_table`

---

## Cluster B — Paradigma Unit → Site + kota (untuk Surat SPD)

| # | Pekerjaan | Status | Aksi detail |
|---|-----------|--------|-------------|
| B1 | Tambah `province` + `city` di `units` | ❌ belum | Migration baru `add_province_city_to_units_table`. |
| B2 | Seeder unit aktual | ⚠️ salah tulis | Buat **Dieng** & **Pusat** sebagai `units`, lalu site-nya (Basecamp, Reservoir, Powerplant 1/2, dst.) ke `sites` dengan `unit_id` yang benar. |
| B3 | Nomor & tanggal surat SPD dinamis | ❌ hardcode | Refactor `SuratSpd::generateNomorSurat()` + `tanggal_surat` agar mengikuti kota/unit (mis. unit Patuha → kota Bandung). |

### Detail B2 — Struktur unit & site aktual

- **Pusat** (unit) → site-site kantor pusat.
- **Dieng** (unit) → site: Basecamp, Reservoir, Powerplant 1, Powerplant 2, dst.
- **Patuha** (unit) → site-site Patuha (sudah ada).
- Setiap site `belongsTo` satu unit (`site.unit_id`).

---

## Cluster C — Admin pusat vs admin unit

> **SKIP / ditunda.** Tidak dikerjakan pada iterasi ini.

---

## Cluster D — SPD, LPJ, Signature (paling crucial)

### D0 — Alur birokrasi SPD (komponen)

- **User** = orang yang berangkat SPD (penumpang).
- **Management** = yang memberi perintah berangkat (supervisor tertinggi).
- **Driver** = pengemudi.

### D1 — Flow pelaporan & validasi (diperpanjang)

```
Driver membuat laporan perjalanan dinas
  → Validasi oleh User (penumpang) — "mengetahui perjalanan ini benar" → approve/reject
      ├─ reject → revisi oleh Driver
      └─ approve → Validasi oleh Admin/GA
            ├─ reject → revisi
            └─ approve → Validasi oleh Management (supervisor tertinggi)
                  └─ approve → LPJ bisa dicetak oleh Admin & Driver
```

Aksi:
- Tambah **view baru**, **role baru**, **controller baru** untuk validasi oleh User.
- Tambah **permission baru** di RBAC untuk validasi kebenaran perjalanan oleh penumpang.

### D2 — Permission baru (RBAC)

- Kode permission baru, mis. `spd.validate_trip` atau setara — hak validasi kebenaran
  perjalanan oleh penumpang. Didaftarkan di `permissions` + `permission_groups`.

### D3 — Kode GL + Uang diberikan (saat persetujuan PENERBITAN SPD)

> Diletakkan di proses validasi/persetujuan **penerbitan surat SPD** (BUKAN saat
> validasi LPJ).

Field baru (database + form + controller):

| Field | Tipe | Keterangan |
|-------|------|-----------|
| `kode_gl` | string | Kode GL SAP |
| `uang_diberikan` | decimal | Nominal uang yang diberikan ke driver |

UI card di form validasi penerbitan:

```
{ Card : Kode GL dan Nominal Pemberian
  <row>
    cols-1: [Kode GL]  <kolom kode GL SAP>
    cols-2: [Uang yang diberikan]* <kolom uang>
  </row>
}
```

### D4 (2.a) — Penambahan uang di surat yang sudah disetujui

- Edit dilakukan pada **surat yang sudah disetujui**.
- Field: nominal penambahan, alasan, bukti transfer (foto **opsional**), kolom
  referensi transaksi bank.
- Form input: **modal**.
- List: berdasarkan surat yang sudah disetujui.
- Hanya tampil di view **admin & superadmin** (authorized roles via RBAC).

Field baru (tabel penambahan dana):

| Field | Tipe | Keterangan |
|-------|------|-----------|
| `surat_spd_id` | FK | Surat yang disetujui |
| `nominal_tambahan` | decimal | Jumlah penambahan |
| `alasan` | text | Alasan penambahan |
| `bukti_transfer_path` | string nullable | Foto bukti transfer (opsional) |
| `bank_reference` | string | Referensi transaksi bank |

### D5 (2.b) — Kalkulasi akhir LPJ (AI sudah ada)

- AI/OCR kategorisasi **sudah ada** (hotel, makan, BBM, dll.).
- Yang ditambah: kalkulasi agregat:
  - Total uang yang ditransfer.
  - Total pemakaian (dari kategori AI/OCR).
  - Selisih → **harus dikembalikan ke GA atau tidak**.

### D6 (2.c) — Format Ringkasan SPD

Di atas blok tanda tangan (user, driver, dst.) tambah agregat:

```
Diberikan      : [total yang diberikan by transfer]
Pengembalian   : [diberikan − pelaporan LPJ]
```

### D7 (3) — SPD dalam kota / luar kota / lainnya

- Dropdown di view create (baik shortcut create maupun biasa), pilihan:
  `["SPD Luar Kota", "SPD Dalam Kota", "Lainnya"]`.
- "Lainnya" → isi manual (mis. dinas internasional).
- Dropdown **bisa di-override oleh admin** saat proses validasi penerbitan surat
  (permohonan mendadak dari manajemen).
- **Bangun lengkap dari nol**: kolom database (enum/string type), field form di
  create, logika di controller, override di view validasi admin.

Field baru:

| Field | Tipe | Keterangan |
|-------|------|-----------|
| `jenis_spd` (atau `mode_spd`) | string | `dalam_kota` / `luar_kota` / `lainnya` |
| `jenis_spd_lainnya` | string nullable | Isi manual jika "Lainnya" |

---

## Ringkasan urutan eksekusi yang disarankan

1. **Cluster A** — fondasi isolasi data (migration + model + query).
2. **Cluster B** — unit/site + kota untuk nomor surat.
3. **Cluster D** — SPD/LPJ/Signature (bergantung pada A & B).
4. **Cluster C** — ditunda.

---

## Status dokumen

- [x] Klasifikasi dibuat.
- [x] Konfirmasi final dari user sebelum eksekusi.
- [x] Eksekusi Cluster A.
- [x] Eksekusi Cluster B.
- [x] Eksekusi Cluster D.
- [ ] Eksekusi Cluster C (ditunda).
