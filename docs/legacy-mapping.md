# Legacy Mapping — Google Sheets → SIAGA Database

> Dokumen ini adalah hasil analisis pemetaan antara skema data legacy (dari Google Sheets API) dengan skema database SIAGA yang sudah ada. **BELUM ada baris kode transform yang ditulis. Review manusia diperlukan sebelum melanjutkan.**

---

## Ringkasan Temuan

| Aspek | Temuan |
|---|---|
| **Jumlah resource legacy** | 6 (gasoline_type, users, vehicles, permohonan, transaction_proof, vehicle_health) |
| **Tabel produksi yang relevan** | `gasoline_types`, `users`, `vehicle_and_tools_consumers`, `vehicle_details`, `permohonans`, `approved_requests`, `transaction_proofs` |
| **Tabel tanpa padanan** | `vehicle_health` — tidak ada tabel produksi yang cocok |
| **Kolom wajib (NOT NULL) tanpa padanan** | `permohonans.requester_id`, `permohonans.bill_amounts`, `transaction_proofs.requester_id`, `transaction_proofs.req_id`, dll. |
| **Konflik primary key** | Legacy users/vehicles pakai ID integer; sistem baru pakai auto-increment |
| **Relasi tak ter-resolve** | `driver_id` legacy → tidak ada jaminan exist di `users`; `vehicle_id` legacy → tidak ada jaminan exist di `vehicle_and_tools_consumers` |
| **Konsep unit/site** | Legacy tidak punya; sistem baru mewajibkan `site_id` pada vehicle |
| **Gasoline type name mismatch** | Legacy pakai nama display (e.g. "Pertamax"); sistem baru `gasoline_types.gasoline_code` pakai kode (e.g. "GASRON92") |

---

## Pemetaan per Resource

### 1. gasoline_type → `gasoline_types`

| Kolom Sumber | Tabel.Tujuan | Transformasi | Catatan/Risiko |
|---|---|---|---|
| `gasoline_type_id` | `gasoline_types.gasoline_code` | langsung | `gasoline_code` adalah string unique, cocok |
| `gasoline_name` | `gasoline_types.gasoline_name` | langsung | |
| `price_per_liter_rp` | `gasoline_types.price_per_liter` | langsung | |
| — | `gasoline_types.notes` | null | Tidak ada padanan di sumber |
| `_row` | — | source_row | Disimpan di staging untuk audit |

**Risiko:** Tidak ada. Mapping 1:1 bersih.

---

### 2. users → `users`

| Kolom Sumber | Tabel.Tujuan | Transformasi | Catatan/Risiko |
|---|---|---|---|
| `id` (integer) | `users.id` | **KONFLIK AUTO-INCREMENT** | Sistem baru punya data users existing dengan ID sendiri. Legacy ID tidak bisa langsung dipakai. **Butuh keputusan:** append sebagai user baru? Mapping ID? |
| `username` | `users.username` | langsung | Harus unique |
| `fullname` | `users.nama_lengkap` | rename field | |
| `role` | `users.role` | langsung | |
| `email` | `users.email` | langsung, nullable | Sumber boleh null |
| — | `users.password` | **WAJIB DIISI** | Legacy TIDAK mengirim password. Password harus diisi dummy (e.g. bcrypt dari username + salt) atau dibuat random. **Keputusan bisnis diperlukan.** |
| — | `users.user_code` | auto-generated oleh model boot | Aman |
| — | `users.jabatan` | null | Tidak ada padanan |
| — | `users.default_site_id` | null | Legacy tidak punya konsep site |
| — | `users.unit_id` | null | Legacy tidak punya konsep unit |
| — | `users.added_at` | `now()` | Default |
| `_row` | — | source_row | Disimpan di staging |

**Risiko TINGGI:**
- `id` bentrok dengan auto-increment → **tidak bisa diupsert berdasarkan id**
- `password` NOT NULL tapi tidak tersedia
- `username` unique — potensi duplikat dengan data existing
- Tidak ada `default_site_id` / `unit_id` — sistem baru mewajibkan user terkait site

---

### 3. vehicles → `vehicle_and_tools_consumers` + `vehicle_details`

| Kolom Sumber | Tabel.Tujuan | Transformasi | Catatan/Risiko |
|---|---|---|---|
| `id` | `vehicle_and_tools_consumers.id` | **KONFLIK AUTO-INCREMENT** | Sama seperti users |
| `plat_nomer` | `vehicle_and_tools_consumers.consumerial_code` | langsung | consumerial_code harus unique. Plat nomer ideal untuk ini |
| `merk` | `vehicle_details.merk` | pindah ke tabel detail | |
| `jenis` | `vehicle_and_tools_consumers.consumerial_type` | langsung | |
| — | `vehicle_and_tools_consumers.consumerial_name` | **WAJIB DIISI** | Sumber tidak punya. Bisa pakai plat_nomer sebagai fallback |
| — | `vehicle_and_tools_consumers.created_by` | **WAJIB DIISI** | Siapa yang create? Bisa user superadmin default |
| — | `vehicle_and_tools_consumers.is_approved` | default `false` | |
| — | `vehicle_and_tools_consumers.is_active` | default `true` | |
| — | `vehicle_details.site_id` | **WAJIB, TIDAK ADA PADANAN** | Legacy tidak punya konsep site. **Butuh keputusan bisnis** — default site apa? |
| `_row` | — | source_row | |

**Risiko TINGGI:**
- `id` bentrok auto-increment
- `consumerial_name` NOT NULL — tidak ada sumber
- `created_by` NOT NULL — tidak ada sumber
- `site_id` NOT NULL (di business logic) — tidak ada padanan
- `vehicle_and_tools_consumers` punya SoftDeletes, perlu diperhatikan

---

### 4. permohonan → `permohonans` + `approved_requests`

| Kolom Sumber | Tabel.Tujuan | Transformasi | Catatan/Risiko |
|---|---|---|---|
| `request_id` | `permohonans.request_number` | langsung | Unique |
| `driver_id` | `permohonans.requester_id` | perlu resolve ke users.id | **Bisa gagal** kalau driver_id tidak ada di users |
| `driver_name` | — | denormalisasi, simpan di staging saja | Tidak ada kolom di produksi untuk ini |
| `vehicle_id` | `permohonans.consumerial_tools_id` | perlu resolve ke vehicles.id | **Bisa gagal** kalau vehicle_id tidak ada di tabel produksi |
| `km_terakhir` | — | ada di `transaction_proofs.km_terakhir` | Mungkin disimpan di proof, bukan permohonan |
| `gasoline_type` (string nama) | `permohonans.gasoline_type` | **PERLU RESOLVE** | Tujuan kolom ini adalah string, bukan FK ke gasoline_types. Bisa langsung dipakai. TAPI sistem baru pakai gasoline_code. |
| `bill_payments` | `permohonans.bill_amounts` | rename, cast ke decimal | |
| `request_at` | `permohonans.request_date` | langsung | |
| `status` | `permohonans.status` | langsung | Periksa status match: completed, approved, pending, rejected |
| `approve_at` | `permohonans.authorization_date` | rename | |
| `authorizer_id` | `permohonans.authorizer_id` | resolve ke users.id | Bisa gagal |
| `authorizer_comment` | `permohonans.authorizer_notes` | rename | |
| `voucher_code` | `approved_requests.voucher_code` | **TABEL BERBEDA** | Voucher harus INSERT ke `approved_requests` |
| `requester_note` | `permohonans.requester_notes` | rename | |

**Risiko TINGGI:**
- Relasi `driver_id → users.id` dan `vehicle_id → vehicles.id` tidak terjamin — bisa orphan
- `voucher_code` tinggal di `permohonan` legacy → harus dipindah ke `approved_requests`
- `permohonans.bill_amounts` decimal(15,2) — `bill_payments` integer, aman
- `permohonans.requester_id` NOT NULL — harus resolve atau fallback
- `consumerial_tools_id` nullable di skema, jadi aman kalau vehicle tidak ter-resolve

---

### 5. transaction_proof → `transaction_proofs`

| Kolom Sumber | Tabel.Tujuan | Transformasi | Catatan/Risiko |
|---|---|---|---|
| `proof_id` | `transaction_proofs.transaction_id` | langsung | |
| `request_id` | `transaction_proofs.req_id` | perlu resolve ke permohonans.id | **Wajib resolve dari request_number ke id** |
| `voucher_code` | `transaction_proofs.voucher_number` | rename | |
| `km_terakhir` | `transaction_proofs.km_terakhir` | langsung, nullable | |
| `foto_km_terakhir` | `transaction_proofs.odometer_photo_path` | simpan mentah (URL) | **Gambar, ditunda prosesnya** |
| `foto_struk_bbm_pertamina` | `transaction_proofs.struk_bbm_path` | simpan mentah (URL) | **Gambar, ditunda prosesnya** |
| `created_at` | `transaction_proofs.purchase_datetime` | rename | |
| — | `transaction_proofs.requester_id` | **WAJIB, TIDAK ADA** | Perlu resolve dari request → requester |
| — | `transaction_proofs.fuel_volume` | **WAJIB, TIDAK ADA** | Legacy tidak punya volume BBM. Hanya bill_payments. |

**Risiko TINGGI:**
- `requester_id` NOT NULL — tidak ada di sumber, harus resolve dari relasi permohonan
- `fuel_volume` NOT NULL — tidak ada di sumber sama sekali. **Butuh default (0?)**
- `req_id` harus resolve dari `request_id` string ke `permohonans.id`

---

### 6. vehicle_health → **TIDAK ADA TABEL PRODUKSI**

Tidak ada tabel `vehicle_health` atau padanannya di sistem SIAGA. Tabel `vehicle_checkings` punya struktur berbeda (checking_code, period_start/end, revision, dll).

**Rekomendasi:** Data ini akan tetap di staging (`legacy_vehicle_healths`) untuk referensi, tapi TIDAK bisa di-migrate ke tabel produksi tanpa desain tabel baru.

---

## Format Gambar yang Ditemukan

> **BELUM bisa diisi — data belum ditarik.** Setelah `sheet:pull` pertama dijalankan, periksa nilai di kolom:
> - `legacy_transaction_proofs.foto_km_terakhir`
> - `legacy_transaction_proofs.foto_struk_bbm_pertamina`
> - `legacy_vehicle_healths.service_invoice_picture`
>
> Kemungkinan format: Google Drive file ID, URL blob, atau base64. Update dokumen ini setelah verifikasi.

Gambar akan dipindahkan secara manual via **WinSCP** — pipeline ini tidak mengunduh file. Pattern penyimpanan gambar di sistem eksisting:

| Tujuan | Path (disk `public`) |
|---|---|
| Foto struk BBM | `transaction_proofs/struk/{filename}` |
| Foto odometer | `transaction_proofs/odometer/{filename}` |
| Foto kendaraan | `vehicle-photos/{filename}` |

Untuk data legacy, sesuaikan dengan pattern di atas saat memindahkan file nanti.

---

## Pertanyaan Terbuka (Butuh Keputusan Bisnis)

1. **User ID konflik:** Legacy users punya ID 1, 2, 3... yang mungkin bentrok dengan auto-increment users existing. Apakah legacy users di-insert dengan ID baru (auto-increment, kehilangan referensi ID lama) atau dipaksa pakai ID lama (risiko konflik)?

2. **Password:** Password legacy tidak tersedia (sengaja diblokir API). Apa nilai default untuk `users.password`? Opsi: (a) random bcrypt, (b) username + salt konstan, (c) hanya insert user tanpa password (lalu reset manual)?

3. **Site assignment:** Tidak ada data site di legacy. Untuk vehicles dan users, site apa yang akan diberikan sebagai default? Semua ke "Pusat"? Atau ada aturan bisnis tertentu?

4. **Vehicle `created_by`:** Siapa user yang tercatat sebagai pembuat data kendaraan legacy? Bisa superadmin ID 1?

5. **`fuel_volume` di transaction_proofs:** Legacy tidak menyimpan volume BBM, hanya nominal (bill_payments) dan jenis BBM. Bisakah volume dihitung dari bill_payments / harga_per_liter? Atau di-set 0?

6. **Vehicle health data:** Enam kolom data health kendaraan tidak punya tabel tujuan. Apakah perlu dibuat tabel `vehicle_healths` baru di sistem, atau data ini cukup di staging sebagai arsip?

7. **Gasoline type name resolution:** Legacy `gasoline_type` berisi nama display (e.g. "Pertamax"). Apakah akan di-resolve ke `gasoline_code` (e.g. "GASRON92") dengan lookup ke `gasoline_types` yang di-insert dulu, atau disimpan sebagai string nama saja?

---

## Catatan Teknis

- **Database-agnostic constraint:** Semua query harus kompatibel SQLite (tes). Tidak boleh pakai `HOUR()`, `DAY()`, `MONTH()` MySQL-specific.
- **Idempotent:** `updateOrCreate` pattern dengan business key, bukan id auto-increment.
- **Gambar:** Belum diunduh. Nilai mentah disimpan di staging sebagai text. Pemindahan file akan dilakukan manual via WinSCP, mengikuti pattern path eksisting (lihat tabel di atas).
- **Reject handling:** Baris yang gagal di-resolve tidak menggagalkan batch — dicatat ke `sheet_sync_rejects` dan dilanjutkan.