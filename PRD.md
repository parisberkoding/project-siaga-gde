# PRODUCT REQUIREMENT DOCUMENT (PRD)
## SIAGA: Sistem Informasi Administrasi General Affairs (Geodipa Energy)

---

## 1. PENDAHULUAN & LATAR BELAKANG
### 1.1 Deskripsi Produk
**SIAGA (Sistem Informasi Administrasi General Affairs)** adalah platform web terintegrasi yang dirancang khusus untuk mengelola, mengotomatisasi, dan mengaudit seluruh kegiatan administrasi operasional divisi General Affairs di **Geodipa Energy** (mencakup wilayah kerja Unit Pusat, Unit Patuha, dan Unit Dieng, serta site terkait seperti PMU, Patuha Basecamp, Sumur, dll). 

Sistem ini memfasilitasi administrasi kendaraan dinas, permohonan bahan bakar (BBM), surat perjalanan dinas (SPD), laporan pertanggungjawaban (LPJ), perawatan & pengecekan armada, manajemen katering (lunch management), analisis efisiensi biaya, serta asisten virtual cerdas (AI Assistant).

### 1.2 Tujuan Dokumen
Dokumen Kebutuhan Produk (PRD) ini disusun sebagai referensi tunggal yang mendefinisikan seluruh fungsionalitas, alur bisnis, aturan validasi, peran pengguna, dan spesifikasi teknis aplikasi SIAGA. Dokumen ini bertujuan untuk mempermudah pembuatan:
1. **User Manual / Panduan Pengguna** untuk masing-masing peran.
2. **Dokumentasi Teknis Program** (API, Skema DB, dan Deployment).
3. **Skenario Pengujian (Test Cases)** untuk jaminan kualitas aplikasi.

---

## 2. PERAN PENGGUNA & RBAC (ROLE-BASED ACCESS CONTROL)
SIAGA menerapkan manajemen hak akses granular berbasis Permission (RBAC Phase 2) dengan mempertahankan kompatibilitas kolom peran bawaan (*dual-write strategy* antara tabel `roles` dan kolom `user.role`).

| Peran (Role) | Deskripsi Hak Akses | Ruang Lingkup Data (Scope) |
|---|---|---|
| **Superadmin** | Akses penuh tanpa batas ke seluruh modul, pengaturan sistem, kelola pengguna & hak akses (RBAC), master data kendaraan/alat, serta laporan konsolidasi seluruh unit. | Global (Seluruh Unit/Site) |
| **General Affairs (GA) / Admin** | Mengelola persetujuan BBM, validasi SPD & LPJ, penjadwalan perawatan kendaraan, manajemen suku cadang, dan administrasi makan siang (lunch management). | Terbatasi oleh Unit yang ditugaskan (*Unit Scope*). Jika ditugaskan di Unit Patuha, hanya dapat mengelola data site Patuha. |
| **Dispatcher** | Petugas lapangan yang melakukan pengecekan kendaraan (Daily, Weekly, Monthly, Body), menetapkan PIC pengecekan, serta mengelola permohonan penggantian suku cadang (VPR). | Terbatasi oleh Unit (*Unit Scope*) |
| **Requester / Driver** | Pengguna akhir (karyawan/pengemudi) yang mengajukan permohonan BBM, membuat SPD, melakukan pelaporan LPJ perjalan dinas, dan memberikan konfirmasi pengecekan kendaraan. | Personal / Sesuai penugasan |
| **Manager** | Memantau kinerja operasional melalui Dashboard Bisnis secara real-time dan melakukan approval tertentu yang memerlukan otorisasi manajemen. | Global / Unit Scope |
| **View Only** | Pengguna eksternal atau auditor yang hanya memiliki hak akses membaca (*read-only*) laporan dan statistik tanpa kemampuan memodifikasi data. | Global / Unit Scope |
| **Vendor** | Peran khusus untuk penyedia katering guna melihat rekap kuota porsi makan siang yang telah dikunci (*finalized*) hari ini per site untuk keperluan penyajian. | Sesuai Site Vendor |

---

## 3. ARSITEKTUR SISTEM & SPESIFIKASI TEKNOLOGI
* **Framework Utama:** Laravel 10/12 (PHP 8.1+)
* **Database:** MySQL / MariaDB (Logika query dibuat kompatibel dengan SQLite untuk kebutuhan testing automated).
* **Frontend UI:** HTML5/CSS3, Bootstrap 5 dengan NiceAdmin Template, Chart.js untuk visualisasi grafik.
* **Integrasi AI (OCR & Assistant):**
  * **Model AI OCR:** `gemini-3.1-flash-lite` untuk mengekstrak data nominal/jenis BBM dari foto struk belanja secara otomatis.
  * **AI Assistant (Gaga):** Memakai API Groq dengan model `llama-3.3-70b-versatile` (OpenAI-compatible client) untuk interaksi tanya jawab data real-time via Data Mart internal.
* **Fitur Pendukung:**
  * **PDF Export:** DomPDF (`barryvdh/laravel-dompdf`) dengan optimalisasi kompresi gambar lossless.
  * **Excel Export:** Maatwebsite Excel (`maatwebsite/excel`) untuk ekspor multi-sheet laporan katering dan voucher.
  * **QR Code Engine:** BaconQrCode (`endroid/qr-code`) untuk tanda tangan digital SPD dan absensi makan siang.

---

## 4. KEBUTUHAN FUNGSIONAL PER MODUL

### 4.1 Modul 1: Manajemen Permohonan BBM (Fuel Request Management)
Modul ini menangani siklus hidup pengadaan bahan bakar kendaraan dinas operasional maupun alat-alat pendukung kerja.

#### A. Alur Kerja Utama (Workflow)
1. **Pengajuan:** Driver/Requester mengajukan permohonan BBM dengan memilih kendaraan, memasukkan perkiraan nominal pengisian, dan memilih jenis bahan bakar.
2. **Validasi & Otorisasi:** Admin/GA meninjau permohonan. Admin dapat menyetujui (*approve*), menolak (*reject*), atau memodifikasi nominal berdasarkan batas kapasitas kendaraan.
3. **Penerbitan Voucher:** Sistem menerbitkan voucher digital dengan kode unik dan tanggal kedaluwarsa (*expiry date*).
4. **Realisasi Pembelian:** Driver membeli BBM di SPBU, mengambil foto struk fisik dan foto odometer kendaraan, kemudian mengunggahnya ke sistem.
5. **Review Realisasi:** Admin meninjau kesesuaian antara voucher yang disetujui dengan bukti transaksi riil. Status permohonan diperbarui menjadi *Completed*.

#### B. Fitur Unggulan & Validasi
* **Pembersihan Filter & Realtime Auto-Submit:** Form pencarian dan filter pada dashboard admin akan otomatis mengirimkan data (*auto-submit*) 400ms setelah input berubah tanpa perlu menekan tombol "Filter".
* **Bulk Approval:** Admin dapat melakukan persetujuan massal untuk banyak permohonan sekaligus disertai kolom input catatan khusus (*authorizer notes*).
* **Smart Dropdown & Auto-fill:** Sistem secara otomatis mendeteksi batas tangki kendaraan dan memberikan saran nominal full-tank yang logis menggunakan kelipatan Rp50.000.
* **Master Harga BBM (Gasoline Price Sync):** Integrasi scheduler untuk sinkronisasi harian harga bahan bakar resmi (seperti Pertamax & Pertamina Dex) dari API publik berdasarkan *province_slug* masing-masing site.
* **Voucher Ekspor Kemarin:** Tab filter default otomatis untuk voucher yang disetujui kemarin lengkap dengan tracking status permohonan, status approval, dan validitas voucher ke format `.xlsx`.

---

### 4.2 Modul 2: Perjalanan Dinas (SPD & LPJ)
Modul untuk administrasi perjalanan dinas dari mulai surat penugasan hingga pelaporan biaya pertanggungjawaban.

#### A. Surat Perjalanan Dinas (SPD)
* **Pemberi Tugas Hibrid:** Form pembuatan SPD memungkinkan penugasan pejabat pemberi tugas baik secara otomatis dari Master Pekerja (level Manager/Asisten Manager) maupun input manual nama & jabatan.
* **Format Penomoran Surat:** Penomoran otomatis berbasis kode wilayah kerja dan konversi angka Romawi untuk bulan aktif berjalan.
* **Perpanjangan Perjalanan (Chain Extension):** Driver dapat mengajukan perpanjangan hari SPD secara berantai (Surat Asli → Ekstensi 1 → Ekstensi 2). Sistem akan memperbarui tanggal kembali serta menghitung akumulasi tambahan anggaran secara otomatis.
* **Cetak Satu Bundel (PDF):** Fitur untuk mengunduh dokumen gabungan berisi:
  * Halaman 1: Ringkasan Surat Tugas (SPD).
  * Halaman 2: Ringkasan LPJ (Total pengeluaran dan verifikasi odometer).
  * Halaman 3: Rincian Penggunaan Biaya (Tabel item pengeluaran beserta tanda tangan).
  * Halaman 4+: Lampiran foto struk yang di-embed dalam format Base64.
* **Digital QR Signature:** Dokumen PDF yang dicetak dilengkapi QR Code dinamis sebagai bukti tanda tangan elektronik yang valid.

#### B. Laporan Pertanggungjawaban (LPJ)
* **Batas Waktu Pelaporan:** Driver wajib melaporkan LPJ maksimal 3 hari setelah tanggal kepulangan dinas (`lpj_deadline`). Jika terlambat, status akan ditandai secara otomatis sebagai terlambat (*is_late*).
* **Aturan Anggaran (Budget Rule):** Plafon budget harian ditetapkan sebesar Rp1.400.000,- per hari. Pengeluaran di atas batas akan memicu status *is_over_budget*.
* **Recalculation Boot Triggers:** Setiap ada penambahan/perubahan detail pengeluaran (tol, parkir, bbm, dll) pada sub-tabel detail LPJ, model akan memicu *boot triggers* untuk menghitung ulang total secara otomatis di database.

---

### 4.3 Modul 3: Pengecekan & Kesehatan Kendaraan (Vehicle Checking & Health Matrix)
Modul untuk memastikan kondisi armada tetap aman dan layak pakai melalui inspeksi berkala.

#### A. Jenis Pengecekan
1. **Daily Checking (Harian):** Pengecekan singkat oleh dispatcher/driver mengenai kesiapan dasar kendaraan sebelum beroperasi. Status hasil berupa binary: OK / NG (Not Good).
2. **Weekly Checking (Mingguan):** Pengecekan komponen mekanis ringan secara mingguan.
3. **Monthly Checking (Bulanan):** Pengecekan mendalam bulanan. Sistem memiliki scheduler bulanan untuk men-generate target checking otomatis.
4. **Body Inspection (Inspeksi Fisik):** Pengecekan body luar menggunakan skala penilaian: Baik / Cukup / Kurang / Tidak Layak. Dispatcher wajib menyertakan foto kerusakan visual yang dipetakan ke 4 zona utama: Depan, Belakang, Sisi Kiri, dan Sisi Kanan.

#### B. Fitur & Validasi
* **Pemisahan Controller Pengecekan:** Setiap jenis pengecekan dikelola oleh controller terpisah (`DailyCheckingController`, `WeeklyCheckingController`, `MonthlyCheckingController`, `BodyCheckingController`) demi menjaga performa dan kemudahan pemeliharaan kode.
* **Schedules Tracking:** Pengawasan status jadwal inspeksi (`Pending`, `Due`, `Overdue`, `Completed`). Dispatcher dapat ditugaskan sebagai PIC khusus untuk mengecek kendaraan tertentu.
* **QR Code Riwayat Kesehatan:** Setiap kendaraan memiliki lembar QR Code yang tertempel secara fisik. Saat di-scan, QR Code tersebut akan langsung mengarahkan ke ringkasan riwayat kesehatan kendaraan tersebut tanpa perlu masuk ke menu pencarian.

---

### 4.4 Modul 4: Penggantian Suku Cadang & Perbaikan (Sparepart Replacement & Repair)
Modul ini bertindak sebagai jembatan dari temuan kerusakan kendaraan ke aksi perbaikan nyata.

#### A. Alur Kerja Penggantian (VPR)
1. **Temuan Lapangan:** Saat melakukan Body Checking atau Routine Checking, dispatcher menandai suku cadang tertentu membutuhkan penggantian (`needs_replacement = 1`).
2. **Auto-Ajukan VPR:** Melalui tombol "Auto-Ajukan" di halaman hasil pengecekan, sistem akan otomatis membuat draf pengajuan perbaikan (*Vehicle Parts Replacement* - VPR) berisi daftar suku cadang rusak tanpa input manual ulang.
3. **Approval & Penjadwalan:** Admin/GA meninjau pengajuan, menyetujui, dan menjadwalkan tanggal servis kendaraan di bengkel rekanan.
4. **Realisasi Servis:** Driver membawa kendaraan ke bengkel, melakukan perbaikan, mengunggah foto suku cadang baru terpasang, menginput rincian biaya aktual, dan melampirkan kuitansi bengkel.

---

### 4.5 Modul 5: Lunch Management (Manajemen Katering)
Modul mandiri untuk mengelola kebutuhan katering karyawan dan tamu di 3 site operasional.

#### A. Master Data Pekerja (Employee Master)
* **Master Pekerja Pusat (`EmployeeDefaults`):** Database pekerja yang bertindak sebagai sumber data utama makan siang, menggantikan tabel manual per site.
* **Employee Aliases:** Mendukung data alias nama pekerja untuk mengakomodasi pencocokan nama berbasis kemiripan (*fuzzy name matching*) saat melakukan pencarian di sistem katering.

#### B. Forecast Engine (Prediksi Kuota Katering H+1)
Sistem memprediksi kebutuhan porsi makan siang hari esok secara otomatis yang berjalan setiap pukul 15:00. Prioritas penentuan lokasi makan siang didasarkan pada hirarki berikut:
1. **Manual Override:** Input perubahan lokasi langsung oleh Admin/GA secara manual.
2. **Tomorrow Confirmation:** Jawaban konfirmasi lokasi makan siang esok hari yang diisi oleh karyawan saat melakukan absensi hari ini.
3. **Historical Pattern (Learning Profile):** Analisis histori kebiasaan makan karyawan berdasarkan profil riwayat transaksi di site tertentu.
4. **Default Site:** Lokasi site utama tempat karyawan tersebut resmi terdaftar bekerja.

#### C. Distribusi & Transaksi QR
* **Lock & Distribution:** Admin mengunci data prediksi menjadi porsi final (* final status*) pada pukul 15:00 untuk dikirimkan ke pihak vendor katering.
* **Public QR Attendance:** Halaman absensi publik tanpa autentikasi login yang diakses melalui tablet/smartphone di lokasi kantin. Karyawan cukup memindai QR Code pribadi mereka atau mengetik nama mereka, lalu memilih lokasi makan esok hari untuk mencatatkan kehadiran makan siang hari ini.
* **Role Vendor View:** Tampilan dashboard khusus vendor katering untuk memonitor secara real-time jumlah porsi yang harus disiapkan dan daftar karyawan yang sudah mengambil jatah makan siang hari ini.
* **Guest Reservation:** Reservasi makan siang khusus bagi tamu perusahaan di site tertentu. Pengajuan reservasi wajib dikonfirmasi maksimal 2 hari sebelum kedatangan (H-2).

---

### 4.6 Modul 6: Analisis Efisiensi BBM (Fuel Trip Analysis)
Modul analitik bagi Superadmin/Manager untuk melacak penggunaan bahan bakar dan mendeteksi anomali.

* **Metrik Efisiensi:** Membandingkan transaksi BBM berturut-turut pada kendaraan yang sama guna menghitung selisih odometer (KM) dan konsumsi liter BBM untuk menghasilkan angka efisiensi rata-rata (KM/Liter).
* **Deteksi Refueling Dini (Premature Refueling):** Sistem secara otomatis memberikan indikator peringatan (*warning/critical*) apabila kendaraan kembali mengisi bahan bakar saat jarak tempuh pemakaian sejak pengisian terakhir masih kurang dari 60% kapasitas tangki normal.
* **Baseline Management:** Fitur pengaturan baseline batas konsumsi BBM standar per jenis kendaraan. Admin dapat melakukan *override* batas baseline dengan menyertakan alasan yang logis.

---

### 4.7 Modul 7: Gaga (GA AI Assistant) — Beta
Asisten virtual cerdas berbasis AI yang terintegrasi di dalam sistem untuk membantu pengelolaan data administrasi.

* **Antarmuka Percakapan (Chat UI):** Menyediakan kolom chat khusus bagi superadmin untuk berinteraksi menanyakan performa data operasional.
* **Multi-Modul Data Mart Pipeline:** Gaga dapat membaca dan menganalisis database real-time dari 9 domain data mart, meliputi: Permohonan BBM, Kendaraan, Driver, Harga Bensin, SPD, LPJ, Katering Makan Siang, Suku Cadang, dan Pengecekan Kendaraan.
* **Format Output Markdown Terstruktur:** Respons dari asisten disajikan dalam format Markdown yang rapi (penggunaan heading, bullet points, tabel zebra, dan penebalan angka nominal). Output dirender secara aman menggunakan parser *Github Flavored Markdown* guna mencegah serangan XSS (*Cross-Site Scripting*).
* **Auto-Session Title:** Sesi obrolan baru akan dinamai secara otomatis oleh AI (maksimal 6 kata ringkas) sesaat setelah pesan asisten pertama dikirim, memberikan pengalaman seperti aplikasi AI modern.

---

### 4.8 Modul 8: Master Data Wilayah Bisnis (Unit & Site CRUD)
* **Relasi Hierarki:** Unit Bisnis mewakili wilayah administrasi tingkat tinggi (contoh: Unit Pusat, Unit Patuha, Unit Dieng). Site/Lokasi mewakili pos operasional yang berada di bawah Unit (contoh: PTH-BC, PMU, Basecamp, PP-Dieng). **Setiap Site wajib terasosiasi dengan 1 Unit Bisnis.**
* **Unit Scope Rule:**
  * Pengguna dengan peran GA yang dikunci pada unit tertentu (misalnya `unit_id = Patuha`) hanya berhak melihat, mengedit, dan memproses transaksi yang terjadi pada site di bawah Unit Patuha.
  * Pengguna GA Global/Pusat (tanpa `unit_id`) dapat memproses data dari seluruh Unit Bisnis secara universal.
* **Validasi Penghapusan (Dependency Check):** Unit Bisnis atau Site tidak dapat dihapus apabila masih memiliki data terkait (karyawan aktif, kendaraan terdaftar, atau transaksi katering).

---

## 5. KEBUTUHAN NON-FUNGSIONAL (NON-FUNCTIONAL REQUIREMENTS)

### 5.1 Keamanan (Security)
* **Throttling Proteksi:** Halaman publik seperti absensi katering dilengkapi pembatasan akses (*rate limiting*) untuk menghindari injeksi request massal dari pihak luar.
* **Anti-XSS Filter:** Semua input mentah dari AI Assistant dibersihkan melalui filter konverter markdown agar tidak meloloskan tag script HTML berbahaya.
* **Enkripsi File:** Berkas sensitif seperti foto kuitansi keuangan, foto odometer, dan QR Code tanda tangan tidak dapat diakses langsung secara publik, melainkan harus melewati rute perantara dengan otentikasi login (`StorageFileController`).

### 5.2 Performa & Keandalan (Performance & Reliability)
* **Realtime Filters:** Penerapan pola debounce (350ms - 400ms) pada input pencarian di frontend untuk meminimalkan beban query database berlebih.
* **Kompresi Gambar Otomatis:** Foto struk dan odometer yang diunggah oleh driver melalui smartphone akan dikompresi di sisi server untuk menghemat ruang penyimpanan dan mempercepat proses rendering laporan PDF.
* **Logical Database-Agnostic:** Seluruh query perhitungan tanggal dan pengelompokan laporan tidak boleh menggunakan syntax spesifik MySQL (seperti `HOUR()` atau `DAY()`) yang bisa menyebabkan kegagalan uji coba pada database SQLite saat *PHP testing process* berlangsung.

### 5.3 Kemudahan Pengoperasian (Usability)
* **Desain Satu Halaman Terpadu:** Dashboard bisnis Superadmin disajikan dalam bentuk *single-page scrollable* yang memuat kartu statistik, grafik Chart.js, performa kilometer, hingga pivot tabel tanpa interupsi navigasi tab yang membingungkan.
* **Sidebar Tanpa View Jumping:** Navigasi sidebar dikelompokkan secara logis per domain utama (misal: sub-menu Katering terbungkus rapi dalam 1 dropdown collapsible "Lunch Management") dan status menu aktif dikunci di sisi server untuk menghindari efek pergeseran tata letak halaman saat diklik.

---

## 6. LOG PERUBAHAN BESAR & SISTEM LEGACY (CHANGELOG)
Untuk mencegah kesalahan referensi saat penyusunan panduan program, berikut adalah fitur lama yang telah resmi dihapus dari sistem SIAGA terhitung per Juli 2026:
* **Penghapusan Modul Trip Booking Kendaraan Lama:** Skema pemesanan kendaraan lama yang meliputi pencatatan rute, nebeng manual, penjadwalan supir harian, serta tabel-tabel pendukung (`trips`, `trip_passengers`, `trip_booking_requests`, `driver_schedules`, `vehicle_specifications`, `pekerja_patuha`) telah dihapus secara permanen untuk dialihkan ke fungsionalitas modul SPD & LPJ yang lebih komprehensif.
* **Peralihan Engine AI:** Layanan asisten cerdas yang semula terikat dengan API Google Gemini kini dipindahkan ke infrastruktur Groq (menggunakan model Llama 3.3 70B) demi performa respon function-calling yang lebih cepat dan bebas dari kendala skema tanda tangan pemikiran (*thought signature*).
