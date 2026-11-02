# Catatan Query Modul BBM & Permohonan

| | |
|---|---|
| Tanggal | 2 November 2026 |
| Penulis | Dicky Qadr Alamsah (QA / FE) |
| Branch | `feature/ui-revamp` (basis `main` 56a79f7) |
| Sifat | Catatan untuk dev BE. Belum ada query yang diubah, kecuali filter bulan di Riwayat Permohonan (lihat bagian 1). |

Pengecekan dilakukan dengan membaca kode dan mencoba data uji sekitar 120 permohonan di Docker lokal. Dengan data sebanyak itu semua halaman masih cepat, jadi catatan ini soal pertumbuhan data. Yang paling berat ada di halaman yang memuat seluruh data tanpa batas lalu menghitungnya di PHP.

## 1. Yang sudah diubah di branch ini

**Riwayat Permohonan** (`RequestManagementController::requester_history`, route `requester-history`)

- Ditambah filter `?bulan=YYYY-MM` (atau `semua`). Default bulan pengajuan terakhir milik user, jadi yang diambil hanya data satu bulan.
- Pagination dinaikkan dari 20 menjadi 25 per halaman dan memakai `withQueryString()` supaya filter bulan ikut saat pindah halaman.
- Daftar bulan di dropdown dibuat dari satu query `MIN/MAX(request_date)`, lalu diurutkan di PHP (maksimal 24 bulan). Query ini sengaja tidak memakai `DATE_FORMAT` supaya tetap jalan di SQLite (test).
- Di tampilan, navigasi halaman tampil di atas dan bawah tabel untuk layar HP, dan hanya di bawah untuk desktop.

Catatan: urutan data masih memakai subquery per baris `COALESCE((SELECT created_at FROM transaction_proofs ...), request_date)`. Untuk data per bulan ini masih ringan, tapi kalau filter `semua` dipakai dengan data ribuan baris, sebaiknya diganti `leftJoin` ke `transaction_proofs` atau disimpan sebagai kolom `last_activity_at`.

## 2. Prioritas tinggi

### 2.1 Approval BBM memuat seluruh data tanpa batas

`RequestManagementController::admin_index` (route `admin-dashboard`, menu Approval BBM):

- `$allRequests` memakai `->get()` untuk semua permohonan di unit, lalu dipaginasi di browser (DataTables). Kalau data sudah puluhan ribu, halaman akan berat walaupun yang dibuka hanya tab Menunggu.
- `$reports` (laporan transaksi) dan `$myApprovals` juga `->get()` tanpa batas.
- Saran: tab Semua History, Laporan Transaksi, dan Riwayat Approval Saya dimuat lewat pagination server (`paginate`) atau AJAX saat tab dibuka, dengan filter bulan seperti di Riwayat Permohonan.

### 2.2 Dashboard BBM menghitung statistik di PHP

`SuperadminController::dashboardBisnis` dan fungsi pendukungnya:

- `getDriverStatisticsData` mengambil **semua** permohonan unit beserta relasinya, lalu `groupBy('requester_id')` di PHP. Tidak dibatasi tanggal.
- `getVehicleKmPerformanceData` dan `getCompletePivotTableData` juga mengambil semua baris (`->get()`), lalu dipaginasi dengan `paginateCollection`. Artinya pagination terjadi setelah seluruh data dibaca.
- `calculateStats` bagian `vehicleExpense` dan top requester memakai `->get()->groupBy()->map()`, padahal bisa `SUM ... GROUP BY` di database.
- Rata-rata per periode (bagian harian, bulanan, tahunan) juga mengambil semua baris lalu `groupBy` di PHP.
- `getVehicleFuelConsumptionData` tidak memakai `inUnit()` dan tidak dipanggil dari mana pun. Sebaiknya dihapus supaya tidak terpakai ulang tanpa scope unit.
- Saran: pindahkan agregasi ke SQL (`selectRaw('requester_id, COUNT(*), SUM(bill_amounts)')->groupBy(...)`), batasi rentang tanggal sesuai filter dashboard, lalu gunakan `paginate` dari query, bukan dari koleksi.

### 2.3 Index tabel belum ada untuk kolom filter utama

Index yang ada hanya primary key, unique, dan foreign key. Filter yang paling sering dipakai belum punya index gabungan:

| Tabel | Saran index | Dipakai oleh |
|---|---|---|
| `permohonans` | `(unit_id, status, request_date)` | Approval, Kelola Permohonan, Dashboard |
| `permohonans` | `(requester_id, request_date)` | Riwayat Permohonan, Permohonan Baru |
| `approved_requests` | `(status, valid_until)` | `VoucherExpiryService::expireStale` |
| `approved_requests` | `(unit_id, approval_date)` | Tab Voucher Terbit |
| `transaction_proofs` | `(unit_id, created_at)` | Laporan Transaksi |

## 3. Prioritas sedang

- `admin_approval`: `$allRequests` di-query dua kali berturut-turut (`paginate(15)` lalu ditimpa `paginate(20)`). Query pertama terbuang.
- `admin_index` dan `admin_approval`: enam query `count()` terpisah untuk kartu statistik. Bisa satu query `selectRaw('status, COUNT(*)')->groupBy('status')`.
- `requester_index` (Permohonan Baru): enam query `count()` per status untuk user yang sama. Sama seperti di atas, bisa digabung.
- `checkAndExpireVouchers()` dipanggil setiap kali halaman `requester_index` dan `admin_index` dibuka. Sekarang scheduler `bbm-expire-vouchers` sudah ada, jadi pemanggilan ini cukup sebagai cadangan. Kalau server sudah memakai cron, sebaiknya dibatasi, misalnya paling sering satu kali per menit lewat `Cache::lock`.
- `dashboardBisnis`: daftar filter `$drivers` dan `$approvers` masih memakai kolom `role` lama (`where('role', 'requester')`), bukan peran RBAC. Driver dengan peran baru bisa tidak muncul di filter.

## 4. Sudah baik

- Kelola Permohonan (`SuperadminController::indexPermohonan`) sudah `paginate(20)` untuk permohonan dan laporan, dengan eager loading relasi.
- Voucher Terbit sudah dibatasi per tanggal (`voucher_date`, default kemarin).
- Semua query di halaman BBM sudah memakai `inUnit()` sesuai perbaikan QA (BUG-BBM-004, BUG-BBM-005).
