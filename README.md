# SIAGA
### Sistem Informasi Administrasi General Affairs

---

SIAGA adalah sistem informasi berbasis web yang dirancang untuk mendukung seluruh kegiatan administrasi operasional General Affairs. Sistem ini mengintegrasikan manajemen kendaraan, bahan bakar, catering, perjalanan dinas, dan pengguna dalam satu platform terpusat, terstruktur, dan dapat diaudit.

---

## Fitur

### Permohonan BBM
Alur pengajuan dan distribusi bahan bakar dari awal hingga selesai.
- Pengajuan permohonan oleh pengemudi atau pemohon
- Verifikasi stok dan persetujuan oleh dispatcher
- Penerbitan voucher BBM dengan kode unik
- Pencatatan realisasi pengisian dan foto bukti transaksi
- Laporan konsumsi BBM per kendaraan, pengemudi, dan periode

### Surat Perjalanan Dinas (SPD) & LPJ
Manajemen perjalanan dinas terintegrasi dari surat tugas hingga laporan penggantian.
- Pembuatan Surat Perjalanan Dinas (biasa & mendadak)
- Approval surat oleh superadmin/admin
- Perpanjangan (ETD) surat tugas
- Laporan Penggantian (LPJ) dengan approval multi-tahap
- Laporan spending SPD per surat

### Pengecekan & Perawatan Kendaraan
Modul inspeksi dan pemeliharaan armada yang terintegrasi.
- Checklist kondisi kendaraan (harian, mingguan, bulanan, body)
- Pencatatan temuan kerusakan lengkap dengan foto dokumentasi
- Alur pengajuan perbaikan dan permintaan sparepart
- Persetujuan perbaikan oleh admin dan superadmin
- Vehicle Health matrix untuk monitoring kondisi armada
- Riwayat perawatan lengkap per unit kendaraan

### Detailing Kendaraan
Dokumentasi lengkap untuk setiap aset kendaraan.
- Informasi jenis, merk, model, tahun pembuatan
- Nomor rangka (VIN), nomor mesin, warna
- Kapasitas penumpang dan bahan bakar
- Jenis bahan bakar dan volume tangki

### Lunch Management
Pengelolaan kebutuhan konsumsi catering operasional.
- Dashboard konsumsi harian
- Employee default & guest reservation
- Lunch forecast & distribution
- Transaksi dengan QR code scan
- Export vendor & booking mandiri

### RBAC & Hak Akses
Sistem role dan permission dinamis.
- Manajemen role (Superadmin, General Affairs, Dispatcher, Requester, Manager, View Only)
- Permission berbasis kode per fitur
- Asignasi role ke user secara fleksibel

### Laporan & Analisis
- Analisis Trip & Efisiensi BBM
- Laporan Keuangan Service
- Laporan Transaksi Sparepart
- Cetak Laporan BBM (Excel multi-sheet, PDF)
- Financial report per kendaraan

---

## Peran Pengguna

| Peran | Akses |
|---|---|
| **Pemohon / Pengemudi** | Mengajukan permohonan BBM, membuat SPD & LPJ, melihat hasil pengecekan kendaraan |
| **Dispatcher** | Memverifikasi permohonan BBM, melakukan pengecekan kendaraan (harian/mingguan/bulanan/body), mengelola permohonan sparepart |
| **Admin** | Approval BBM, validasi SPD & LPJ, manajemen sparepart & jadwal service, monitoring transaksi, lunch management |
| **Superadmin** | Akses penuh ke seluruh fitur, kelola pengguna & RBAC, master data kendaraan/alat, aktivasi kendaraan, laporan komprehensif |
| **Manager** | Dashboard bisnis untuk monitoring operasional |

---

## Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 10+ (PHP 8.1+) |
| Frontend | Bootstrap 5, NiceAdmin Template |
| Database | MySQL |
| Visualisasi | Chart.js |
| Barcode/QR | Picqer PHP Barcode Generator |
| Export | Excel (multi-sheet), PDF (DomPDF/Browser Print) |
| Deployment | Windows Server + IIS / Shared Hosting |

---

## Instalasi

### Prasyarat
- PHP >= 8.1
- Composer
- MySQL

### Langkah Instalasi

```bash
# Clone repositori
git clone https://github.com/username/siaga.git
cd siaga

# Install dependensi PHP
composer install

# Salin file environment
cp .env.example .env

# Generate application key
php artisan key:generate

# Konfigurasi database di file .env
# DB_DATABASE=siaga
# DB_USERNAME=root
# DB_PASSWORD=

# Jalankan migrasi dan seeder
php artisan migrate --seed

# Buat symlink storage
php artisan storage:link

# Jalankan server lokal
php artisan serve
```

### Bagikan ke internet (Cloudflare Tunnel)

Cukup jalankan:

```bash
./share.sh
```

Script akan menyalakan server Laravel (default port `8000`) dan membuka
**Cloudflare Quick Tunnel** tanpa perlu akun. URL publik
`https://<acak>.trycloudflare.com` ditampilkan di terminal dan hanya aktif
selama script berjalan. Tekan `Ctrl+C` untuk menutup server + tunnel.

```bash
PORT=8001 ./share.sh          # kalau port 8000 sudah dipakai
CF_PROTOCOL=http2 ./share.sh  # kalau jaringan memblokir QUIC
```

Prasyarat: `cloudflared` (`brew install cloudflared`), PHP, dan `curl`.
Script mempercayai loopback sebagai proxy (`bootstrap/app.php`) agar URL yang
dihasilkan tetap `https://`, bukan `http://`.

---

## Struktur Modul

```
siaga/
├── Permohonan BBM              # Pengajuan, verifikasi, voucher, realisasi
├── Surat Perjalanan Dinas      # SPD, LPJ, perpanjangan, approval
├── Pengecekan Kendaraan        # Checklist harian/mingguan/bulanan/body, sparepart
├── Detailing Kendaraan         # Dokumentasi lengkap aset kendaraan
├── Lunch Management            # Catering, forecast, distribusi, transaksi QR
├── RBAC & Hak Akses            # Role & permission dinamis
├── Manajemen Pengguna          # CRUD user, reset password, restore
└── Laporan & Analitik          # Dashboard, trip analysis, ekspor Excel/PDF
```
