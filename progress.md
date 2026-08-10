# PROGRESS PROJECT — BBM/SPD/LPJ

Catatan progres pengembangan. Update terakhir: 10 Agustus 2026.

## Status Umum
- Semua perubahan di bawah sudah diverifikasi `php artisan test --testsuite=Feature` → **15 passed (58 assertions)**.
- Model AI aktif: `gemini-3.1-flash-lite` (diverifikasi HTTP 200 untuk request teks & gambar).

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

---

## Ringkasan File yang Diubah (git status)
```
M app/Http/Controllers/RequestManagementController.php
M app/Http/Controllers/SpdController.php
M resources/views/partials/sidebar.blade.php
M resources/views/pelaporan/spd/daily-form.blade.php
M resources/views/pelaporan/spd/validasi.blade.php
M resources/views/requester/detail_request.blade.php
M resources/views/requester/spd/lpj/index.blade.php
M resources/views/requester/spd/surat/index.blade.php
M resources/views/requester/spd/surat/show.blade.php
M resources/views/superadmin/spd/lpj/index.blade.php
M resources/views/superadmin/spd/surat/detail.blade.php
M resources/views/superadmin/spd/surat/report.blade.php
M resources/views/templates/template-surat/template-spd-lpj.blade.php
M routes/web.php
M tests/Feature/LpjCreateViewTest.php
```
