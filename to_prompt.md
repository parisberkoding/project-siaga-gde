# Diskusi: Integrasi OCR / VLM untuk Ekstraksi & Klasifikasi Struk pada Modul Permohonan Bahan Bakar (BBM)

> Dokumen ini dibuat untuk diskusi dengan AI/model lain guna merancang fitur ekstraksi otomatis
> dari gambar struk yang di-upload driver. Tujuannya: menarik data (OCR), mengklasifikasikan
> jenis struk (tol / BBM / restoran / hotel / parkir / lain-lain), lalu mengisi/merekomedasikan
> field LPJ secara otomatis.

---

## 1. Ringkasan Project

- **Framework:** Laravel 10 (PHP), Blade + Bootstrap 5 + Bootstrap Icons (UI khas admin panel).
- **Konteks bisnis:** PT Geodipa — manajemen perjalanan dinas (SPD), permohonan & pelaporan BBM,
  dan LPJ (Laporan Pertanggungjawaban) pengeluaran driver.
- **User utama:** Driver / Requester yang melaporkan pengeluaran via HP (mobile-first).

---

## 2. Modul "Permohonan Bahan Bakar" — Cara Kerja Saat Ini

### 2.1 Alur permohonan BBM (status-driven)
1. Requester membuat permohonan (`Permohonan`) dengan pilihan kendaraan + jenis BBM + nominal
   (`bill_amounts`, min 150rb – max 850rb).
2. Superadmin menyetujui → status `approved` + generate `Approved_Request` (voucher_code).
3. Requester **upload bukti pembelian** (wajib status `approved`).

### 2.2 Endpoint & Controller terkait (file: `app/Http/Controllers/RequestManagementController.php`)
- `POST /requester/request/{id}/submit-report` → `requester_submit_report()` (route `requester.submit-report`).
- `POST /requester/request/{id}/upload-proof` → `requester_upload_proof()` (route `requester-upload-proof`).

### 2.3 Upload gambar driver (yang ingin kita perkuat dengan VLM)
Pada `requester_submit_report()` (sekitar baris 520–620), driver mengirim:
- `foto_struk` **(wajib, image, jpeg/png/jpg, max 5MB)** → disimpan ke
  `storage/app/public/transaction_proofs/struk/STRUK-{requestNumber}-{ddmmyyyy}.{ext}`.
- `foto_km` (wajib jika kendaraan, opsional jika alat) → `transaction_proofs/odometer/`.
- Field manual yang diisi driver: `purchase_date`, `volume_bbm`, `km_akhir`.

### 2.4 Model & Penyimpanan
- **Model `Transaction_Proofs`** (tabel `transaction_proofs`), fillable:
  `transaction_id, req_id, requester_id, voucher_number, purchase_datetime,
   fuel_volume, km_terakhir, struk_bbm_path, odometer_photo_path`.
- **Model `Permohonan`** (tabel `permohonans`), field utama:
  `request_number, requester_id, request_date, gasoline_type, bill_amounts,
   status, consumerial_tools_id`.
- **Storage disk:** `public` (`storage/app/public`, perlu `php artisan storage:link`).
  Akses URL via `asset('storage/' . $path)`.

### 2.5 Keterbatasan Saat Ini (Masalah yang ingin diselesaikan)
- Struk di-upload sebagai **gambar mentah**, field (`volume_bbm`, `purchase_date`, nominal)
  diisi **manual** oleh driver → rawan salah ketik & fraud.
- Tidak ada verifikasi bahwa foto benar-benar struk BBM (bisa salah upload / manipulasi).
- Tidak ada klasifikasi jenis struk (tol/restoran/hotel/parkir) — padahal modul LPJ SPD
  (`resources/views/requester/spd/lpj/create.blade.php`) sudah mendukung kategori:
  **Hotel, BBM, Parkir, Tol, Lain-lain**.

---

## 3. Kebutuhan Tambahan (Fitur VLM/OCR)

Kita ingin menambahkan **satu model VLM/OCR** yang dipanggil saat driver upload struk, dengan kemampuan:

1. **OCR & ekstraksi field** dari gambar struk:
   - Merchant / nama tempat (SPBU, tol, restoran, hotel).
   - Tanggal & waktu transaksi.
   - Nominal total (dan jika ada: volume liter, nominal per liter).
   - Nomor struk / nomor voucher (opsional).
2. **Klasifikasi otomatis** jenis struk ke salah satu kategori:
   - `Tol` (struk jalan tol / gate)
   - `BBM` (struk SPBU / pom bensin)
   - `Restoran` / `Makan` (struk kafe/resto — masuk kategori `Lain-lain` di LPJ)
   - `Hotel` (struk menginap)
   - `Parkir` (tiket parkir)
   - `Lain-lain` (default / tidak terdeteksi)
3. **Retrieve & auto-fill**: hasil ekstraksi dipakai untuk:
   - Mengisi/pre-fill field form LPJ (`tanggal`, `nominal`, `tipe_penggunaan`, `keterangan`).
   - Validasi silang: nominal struk vs `bill_amounts` permohonan (deteksi selisih/fraud).
   - (Opsional) Confidence score → jika rendah, minta konfirmasi driver.

---

## 4. Bagaimana Model VLM/OCR Akan Bekerja (Arsitektur Usulan)

### 4.1 Opsi A — VLM API pihak ke-3 (Gemini / GPT-4o / Claude vision)
- Upload gambar ke endpoint kita → kita kirim base64 gambar + prompt terstruktur ke VLM.
- Prompt meminta respons **JSON** dengan field:
  ```json
  {
    "category": "Tol|BBM|Restoran|Hotel|Parkir|Lain-lain",
    "merchant": "string",
    "datetime": "YYYY-MM-DD HH:mm:ss",
    "total_nominal": number,
    "volume_liter": number|null,
    "confidence": 0.0-1.0
  }
  ```
- Kelebihan: akurasi tinggi untuk struk Indonesia, tidak butuh training.
- Kekurangan: butuh API key, latency, biaya per call, pengiriman gambar ke luar server.

### 4.2 Opsi B — OCR lokal (Tesseract) + classifier ringan
- `sudo apt install tesseract-ocr` + `nicolus/tesseract` (PHP) atau Python microservice.
- Ekstrak teks → rule-based / keyword matching (`"TOL"`, `"PERTAMINA"`, `"SPBU"`, `"RESTO"`)
  untuk klasifikasi.
- Kelebihan: gratis, on-premise (data tidak keluar).
- Kekurangan: akurasi struk foto HP rendah, butuh preprocessing (deskew/denoise).

### 4.3 Opsi C — Hybrid (Rekomendasi)
- OCR lokal untuk teks mentah (fast path / fallback).
- Jika confidence rendah / butuh klasifikasi kompleks → lempar ke VLM API.
- Simpan hasil ke `Transaction_Proofs` (tambah kolom: `ocr_raw`, `ocr_category`,
  `ocr_confidence`, `ocr_merchant`) agar auditabel & superadmin bisa review.

### 4.4 Integrasi ke Flow Laravel (usulan)
1. Di `requester_submit_report()`: setelah file `foto_struk` tersimpan,
   panggil `OcrService::analyze($strukPath)`.
2. `OcrService` mengembalikan DTO hasil ekstraksi + klasifikasi.
3. Simpan metadata OCR ke `Transaction_Proofs`.
4. Pada halaman LPJ SPD (`create.blade.php`), tampilkan hasil OCR sebagai "Auto-fill suggestion":
   driver tinggal konfirmasi, bukan ketik ulang.
5. (Future) Webhook/queue agar upload tidak menunggu VLM (pakai Laravel Queue).

---

## 5. Pertanyaan untuk Diskusi dengan AI Lain

1. **Pemilihan model:** VLM API (Gemini Flash / GPT-4o-mini) vs OCR lokal + LLM?
   Trade-off latency vs akurasi vs biaya vs privasi data (struk berisi info keuangan).
2. **Format prompt:** bagaimana merancang prompt agar VLM selalu balas JSON valid
   dan konsisten untuk struk Indonesia (Pertamina, tol, resto lokal)?
3. **Fallback:** bagaimana menangani struk blur / tidak terbaca / klasifikasi `Lain-lain`?
4. **Validasi silang:** rule apa yang dipakai untuk mendeteksi fraud
   (nominal struk jauh di atas `bill_amounts`, dll)?
5. **Skema DB:** perlu migrasi `Transaction_Proofs` tambah kolom OCR?
   Atau tabel terpisah `transaction_ocr_results`?
6. **Async:** apakah layak pakai Queue (job `ProcessStrukOcr`) agar UX driver tidak lambat?

---

## 6. Referensi File Penting

| File | Peran |
|------|-------|
| `app/Http/Controllers/RequestManagementController.php` | Logika upload struk & submit report |
| `app/Models/Transaction_Proofs.php` | Model bukti transaksi (path gambar) |
| `app/Models/Permohonan.php` | Model permohonan BBM |
| `routes/web.php` (line 64, 67) | Route upload-proof & submit-report |
| `resources/views/requester/partials/slot-approved.blade.php` | Form upload struk driver |
| `resources/views/requester/spd/lpj/create.blade.php` | Form LPJ (kategori: Hotel/BBM/Parkir/Tol/Lain-lain) |
| `config/filesystems.php` | Disk `public` untuk storage gambar |
