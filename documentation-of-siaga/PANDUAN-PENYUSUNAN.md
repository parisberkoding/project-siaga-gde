# Panduan Penyusunan Halaman Dokumentasi SIAGA

Dokumen ini adalah **kontrak desain & penulisan** untuk semua halaman di dalam
`/documentation-of-siaga/`. Ikuti blok-blok di bawah ini apa adanya agar seluruh halaman
konsisten dan dapat dibuka tanpa server (cukup klik `index.html`).

---

## 1. Struktur folder

```
documentation-of-siaga/
├── index.html                     ← landing page (pilih dokumen)
├── README.md
├── PANDUAN-PENYUSUNAN.md          ← dokumen ini
├── assets/
│   ├── css/docs.css               ← design system (Poppins + palet Geodipa)
│   ├── css/mock.css               ← replika UI SIAGA + tooltip tutorial
│   ├── js/docs.js                 ← navigasi, pencarian, tab, pin/tooltip, zoom
│   └── js/diagrams.js             ← pemuat Mermaid (CDN) + tema diagram
├── software-documentation/
│   ├── index.html
│   ├── 01-ruang-lingkup.html
│   ├── 02-arsitektur-teknologi.html
│   ├── 03-model-use-case.html
│   ├── 04-spesifikasi-use-case.html
│   ├── 05-desain-perilaku.html
│   ├── 06-desain-struktur.html
│   ├── 07-validasi-logika.html
│   ├── 08-pengujian.html
│   ├── 09-traceability.html
│   ├── 10-glosarium.html
│   └── 11-plantuml-changelog.html
└── alur-tutorial/
    ├── index.html
    ├── 01-permohonan-bbm.html
    ├── 02-spd.html
    ├── 03-cetak-surat.html
    ├── 04-dispatcher.html
    ├── 05-makan-siang.html
    └── 06-checking.html
```

Semua halaman di `software-documentation/` dan `alur-tutorial/` memakai `data-base="../"`.
Halaman `index.html` di akar memakai `data-base=""`.

---

## 2. Kepala halaman (wajib, ganti judul saja)

```html
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>JUDUL HALAMAN — SIAGA</title>
<meta name="description" content="RINGKASAN SATU KALIMAT">
<link rel="icon" href="https://jojo-static-files-klfm7hjb.s3.ap-southeast-3.amazonaws.com/logo-geodipa-officeless.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/docs.css">
</head>
<body data-base="../">
```

## 3. Topbar (wajib, sama untuk semua halaman dalam subfolder)

```html
<header class="topbar">
  <div class="topbar__inner">
    <button class="icon-btn btn-menu" id="docMenuBtn" aria-label="Buka navigasi" aria-expanded="false"><i class="bi bi-list"></i></button>
    <a class="brand" href="../index.html">
      <img class="brand__logo" src="https://jojo-static-files-klfm7hjb.s3.ap-southeast-3.amazonaws.com/logo-geodipa-officeless.png" alt="Logo Geo Dipa Energi">
      <span>
        <span class="brand__name">SIAGA</span>
        <span class="brand__sub">Sistem Informasi Administrasi General Affairs</span>
      </span>
    </a>
    <span class="brand__divider"></span>
    <span class="brand__doc">Software Documentation</span>
    <div class="topbar__spacer"></div>
    <div class="searchbox">
      <i class="bi bi-search"></i>
      <input type="search" id="docSearch" placeholder="Cari halaman…  ( / )" aria-label="Cari halaman dokumentasi">
      <div class="search-results" id="docSearchResults"></div>
    </div>
    <div class="topbar__actions">
      <button class="icon-btn" data-print title="Cetak / simpan PDF"><i class="bi bi-printer"></i></button>
      <a class="icon-btn" href="../index.html" title="Kembali ke beranda dokumentasi"><i class="bi bi-house"></i></a>
    </div>
  </div>
</header>

<div class="scrim" id="docScrim"></div>
```

## 4. Sidebar Software Documentation (wajib)

Salin blok ini; tambahkan `is-active` **hanya** pada item halaman yang sedang dibuat.

```html
<aside class="sidebar" id="docSidebar">
  <p class="sidebar__label">Software Documentation</p>
  <ul class="nav-list">
    <li><a class="nav-link" href="index.html"><span class="nav-link__num">&#9679;</span><span class="nav-link__txt">Ikhtisar &amp; daftar isi</span></a></li>
    <li><a class="nav-link" href="01-ruang-lingkup.html"><span class="nav-link__num">1</span><span class="nav-link__txt">Ruang lingkup &amp; visi produk</span></a></li>
    <li><a class="nav-link" href="02-arsitektur-teknologi.html"><span class="nav-link__num">2</span><span class="nav-link__txt">Arsitektur &amp; teknologi</span></a></li>
    <li><a class="nav-link" href="03-model-use-case.html"><span class="nav-link__num">3</span><span class="nav-link__txt">Model use case</span></a></li>
    <li><a class="nav-link" href="04-spesifikasi-use-case.html"><span class="nav-link__num">4</span><span class="nav-link__txt">Spesifikasi use case</span></a></li>
    <li><a class="nav-link" href="05-desain-perilaku.html"><span class="nav-link__num">5</span><span class="nav-link__txt">Desain perilaku</span><span class="nav-badge">Activity · Sequence</span></a></li>
    <li><a class="nav-link" href="06-desain-struktur.html"><span class="nav-link__num">6</span><span class="nav-link__txt">Desain struktur</span><span class="nav-badge">Class · ERD</span></a></li>
    <li><a class="nav-link" href="07-validasi-logika.html"><span class="nav-link__num">7</span><span class="nav-link__txt">Validasi logika</span></a></li>
    <li><a class="nav-link" href="08-pengujian.html"><span class="nav-link__num">8</span><span class="nav-link__txt">Pengujian &amp; test case</span></a></li>
    <li><a class="nav-link" href="09-traceability.html"><span class="nav-link__num">9</span><span class="nav-link__txt">Traceability &amp; RBAC</span></a></li>
    <li><a class="nav-link" href="10-glosarium.html"><span class="nav-link__num">10</span><span class="nav-link__txt">Glosarium</span></a></li>
    <li><a class="nav-link" href="11-plantuml-changelog.html"><span class="nav-link__num">11</span><span class="nav-link__txt">PlantUML &amp; changelog</span></a></li>
  </ul>

  <p class="sidebar__label">Dokumen lain</p>
  <ul class="nav-list">
    <li><a class="nav-link" href="../alur-tutorial/index.html"><i class="bi bi-signpost-split"></i><span class="nav-link__txt">Alur Tutorial</span></a></li>
    <li><a class="nav-link" href="../index.html"><i class="bi bi-house"></i><span class="nav-link__txt">Beranda dokumentasi</span></a></li>
  </ul>
</aside>
```

Untuk halaman di `alur-tutorial/`, ganti judul label menjadi **Alur Tutorial** dan daftar itemnya:

```html
<ul class="nav-list">
  <li><a class="nav-link" href="index.html"><span class="nav-link__num">&#9679;</span><span class="nav-link__txt">Ikhtisar alur</span></a></li>
  <li><a class="nav-link" href="01-permohonan-bbm.html"><i class="bi bi-fuel-pump"></i><span class="nav-link__txt">1. Permohonan BBM</span></a></li>
  <li><a class="nav-link" href="02-spd.html"><i class="bi bi-envelope-paper"></i><span class="nav-link__txt">2. Pengajuan SPD</span></a></li>
  <li><a class="nav-link" href="03-cetak-surat.html"><i class="bi bi-printer"></i><span class="nav-link__txt">3. Cetak Surat</span></a></li>
  <li><a class="nav-link" href="04-dispatcher.html"><i class="bi bi-truck"></i><span class="nav-link__txt">4. Dispatcher</span></a></li>
  <li><a class="nav-link" href="05-makan-siang.html"><i class="bi bi-cup-hot"></i><span class="nav-link__txt">5. Makan Siang</span></a></li>
  <li><a class="nav-link" href="06-checking.html"><i class="bi bi-clipboard-check"></i><span class="nav-link__txt">6. Routine Checking</span></a></li>
</ul>
```

## 5. Kerangka isi & kaki halaman

```html
<main class="shell">
  <aside class="sidebar" id="docSidebar"> …blok sidebar di atas… </aside>

  <div class="content">
    <div class="wrap">
      <nav><ol class="crumbs">
        <li><a href="../index.html">Documentation</a></li>
        <li>Software Documentation</li>
        <li>Bagian 1</li>
      </ol></nav>

      <div class="page-head">
        <span class="eyebrow"><span class="dot"></span> Bagian 1</span>
        <h2>Judul bagian</h2>
        <p class="lead">Satu paragraf pengantar maksimal 3 baris.</p>
      </div>

      <div class="section-block" id="sub-bagian-1">
        <h2><span class="num"><i class="bi bi-1-circle"></i></span> Sub bagian</h2>
        …
      </div>
    </div>
  </div>
</main>

<footer class="foot">
  <div class="foot__inner">
    <p>&copy; <span data-year>2026</span> PT Geo Dipa Energi (Persero) Unit Patuha — Divisi General Affairs.</p>
    <div class="foot__links">
      <a href="../index.html">Beranda dokumentasi</a>
      <a href="index.html">Ikhtisar SDD</a>
      <a href="../alur-tutorial/index.html">Alur Tutorial</a>
    </div>
  </div>
</footer>

<script src="../assets/js/docs.js"></script>
<script src="../assets/js/diagrams.js" defer></script>
</body>
</html>
```

Setiap halaman **wajib** diakhiri callout lanjutan sebelum `</div></div></main>`:

```html
<div class="callout callout--info">
  <span class="callout__icon"><i class="bi bi-arrow-right-circle"></i></span>
  <div class="callout__body">
    <strong>Lanjut ke Bagian N — Judul</strong>
    Satu kalimat isi bagian berikutnya.
    <div style="margin-top:10px;"><a class="btn btn--primary btn--sm" href="0N-nama.html">Buka Bagian N <i class="bi bi-arrow-right-short"></i></a></div>
  </div>
</div>
```

---

## 6. Komponen yang tersedia (jangan menulis CSS baru)

| Kebutuhan | Markup |
|---|---|
| Kartu | `<div class="card">…</div>` (varian `card--soft`, `card--tight`, `card--flat`) |
| Judul kartu | `<div class="card__head"><span class="icon-sq"><i class="bi bi-x"></i></span><div><h3>…</h3><p>…</p></div></div>` |
| KPI | `<div class="kpi"><div class="kpi__label">…</div><div class="kpi__value">…</div><div class="kpi__hint">…</div></div>` |
| Tabel | `<div class="table-wrap"><table class="tbl">…</table></div>` — tambahkan `tbl--compact` bila padat |
| Badge | `<span class="badge">Tinggi</span>`, `badge--blue`, `badge--red`, `badge--yellow`, `badge--gray`, `badge--solid` |
| Chip | `<span class="chip"><i class="bi bi-x"></i> <b>Label</b> isi</span>` |
| Callout | `callout--info`, `callout--tip`, `callout--warn`, `callout--danger`, `callout--neutral` |
| Langkah | `<ol class="steps"><li><strong>…</strong><p>…</p></li></ol>` |
| Daftar centang | `<ul class="list-check"><li>…</li></ul>` (varian coret: `list-cross`) |
| Timeline | `<ul class="timeline"><li><div class="timeline__date">…</div><div class="timeline__title">…</div><div class="timeline__desc">…</div></li></ul>` |
| Grid | `<div class="grid grid--2">`, `grid--3`, `grid--4`, `grid--auto` |
| Accordion | `<details class="acc"><summary>…</summary><div class="acc__body">…</div></details>` |
| Tab | `<div class="tabs" data-tabs><div class="tabs__nav"><button class="tabs__btn is-active">A</button>…</div><div class="tabs__panel is-active">…</div><div class="tabs__panel">…</div></div>` |
| Progres | `<div class="progress"><span style="width:87%"></span></div>` |
| Diagram | `<figure><div class="fig"><pre class="mermaid">…</pre></div><figcaption><b>Gambar N</b> — …</figcaption></figure>` |
| Kode + salin | `<div class="code-head"><span>plantuml</span><button data-copy="id-kode">Salin</button></div><pre id="id-kode"><code>…</code></pre>` |
| Daftar tautan | `<ul class="anchor-list"><li><a href="…">…<span>Fase 1</span></a></li></ul>` |
| Atribut | `<ul class="meta-list"><li><span class="meta-list__k">Versi</span><span class="meta-list__v">1.0</span></li></ul>` |

---

## 7. Aturan diagram (Mermaid 10, tema Geodipa otomatis)

* Tulis diagram di dalam `<pre class="mermaid">`. Gunakan `&amp;` dan `&laquo;`/`&raquo;` bila perlu.
* Hindari karakter `"` di dalam label; gunakan tanda kutip tunggal atau tanpa kutip.
* **Use case diagram**: pakai `flowchart`, aktor sebagai node `([...])` atau `[...]`, use case sebagai
  `(["Nama use case"])`, batas sistem sebagai `subgraph`. Relasi: `-->` untuk asosiasi,
  `-.->|include|` dan `-.->|extend|` untuk relasi UML.
* **Activity diagram**: `flowchart TD`/`LR` dengan `subgraph` sebagai swimlane.
* **Sequence diagram**: `sequenceDiagram` + `actor`, `participant`, `alt`/`opt`/`loop`.
* **Class diagram**: `classDiagram` dengan atribut `+tipe nama` dan operasi `+nama()`.
* **ERD**: `erDiagram` dengan `||--o{` dsb.
* Setiap diagram **wajib** memiliki `<figcaption>` bernomor (`Gambar N.M`).
* Sertakan juga **kode PlantUML padanan** dalam accordion `<details class="acc">` untuk diagram utama.

---

## 8. Aturan isi

1. Bahasa Indonesia formal-teknis. Istilah asing yang lazim (use case, precondition) boleh dipertahankan.
2. Semua klaim fitur harus dapat ditelusuri ke `PRD.md`, `DOKUMENTASI_PROJECT.md`, `routes/web.php`, atau view di `resources/views/`.
3. Jangan mengarang fitur. Bila perlu menambahkan rekomendasi, tandai dengan `callout--warn` berlabel **Usulan**.
4. Setiap use case memakai kode `UC-<MODUL>-<nn>`; setiap aturan bisnis `BR-<MODUL>-<nn>`.
5. Setiap tabel wajib punya `<thead>` dengan nama kolom yang jelas.
6. Tautan internal selalu relatif terhadap file saat ini.
7. Panjang halaman wajar: 250–500 baris. Utamakan tabel dan diagram dibanding prosa panjang.

## 9. Modul & prefiks kode

| Modul | Prefiks | Aktor utama |
|---|---|---|
| Permohonan BBM | `BBM` | Requester/Driver, Admin GA |
| Perjalanan dinas SPD & LPJ | `SPD` / `LPJ` | Requester/Driver, Superadmin |
| Pengecekan kendaraan | `CHK` | Dispatcher, Driver |
| Penggantian suku cadang (VPR) | `VPR` | Dispatcher, Admin GA |
| Lunch management | `LNC` | Admin GA, Karyawan, Vendor |
| Analisis efisiensi BBM | `EFF` | Superadmin, Manager |
| Asisten AI (Gaga) | `AI` | Superadmin |
| Master data unit & site | `MST` | Superadmin |
| RBAC & pengguna | `RBAC` | Superadmin |

---

## 10. KATALOG USE CASE KANONIK — jangan mengubah kode

Seluruh halaman **wajib** memakai kode dan nama use case berikut agar konsisten antar bagian.
Total **45 use case**.

### Modul BBM — permohonan bahan bakar (6)
| Kode | Nama use case | Aktor utama | Prioritas |
|---|---|---|---|
| UC-BBM-01 | Mengajukan Permohonan BBM | Requester/Driver | Tinggi |
| UC-BBM-02 | Meninjau & Menyetujui Permohonan | Admin GA | Kritis |
| UC-BBM-03 | Melakukan Persetujuan Massal | Admin GA | Sedang |
| UC-BBM-04 | Menerbitkan Voucher BBM | Admin GA | Kritis |
| UC-BBM-05 | Mengunggah Bukti Transaksi & Odometer | Requester/Driver | Tinggi |
| UC-BBM-06 | Mencetak Laporan BBM | Admin GA, Superadmin | Sedang |

### Modul SPD & LPJ — perjalanan dinas (7)
| Kode | Nama use case | Aktor utama | Prioritas |
|---|---|---|---|
| UC-SPD-01 | Membuat Surat Perjalanan Dinas | Requester/Driver, Superadmin | Tinggi |
| UC-SPD-02 | Melihat Daftar & Detail Surat Tugas | Requester/Driver | Sedang |
| UC-SPD-03 | Mengajukan Perpanjangan Perjalanan (ETD) | Requester/Driver | Sedang |
| UC-SPD-04 | Memvalidasi Surat Tugas | Superadmin | Tinggi |
| UC-SPD-05 | Mencetak Surat Tugas & Bundel PDF | Semua peran terkait | Tinggi |
| UC-LPJ-01 | Membuat Laporan Pertanggungjawaban | Requester/Driver | Kritis |
| UC-LPJ-02 | Memvalidasi Laporan Pertanggungjawaban | Superadmin | Kritis |

### Modul pengecekan kendaraan (7)
| Kode | Nama use case | Aktor utama | Prioritas |
|---|---|---|---|
| UC-CHK-01 | Melakukan Pengecekan Harian | Dispatcher | Tinggi |
| UC-CHK-02 | Melakukan Pengecekan Mingguan | Dispatcher | Tinggi |
| UC-CHK-03 | Melakukan Pengecekan Bulanan | Dispatcher | Tinggi |
| UC-CHK-04 | Melakukan Body Inspection | Dispatcher | Sedang |
| UC-CHK-05 | Menetapkan PIC & Jadwal Pengecekan | Dispatcher | Sedang |
| UC-CHK-06 | Mengonfirmasi Hasil Pengecekan | Requester/Driver | Sedang |
| UC-CHK-07 | Memindai QR Riwayat Kesehatan Kendaraan | Dispatcher | Sedang |

### Modul penggantian suku cadang / VPR (4)
| Kode | Nama use case | Aktor utama | Prioritas |
|---|---|---|---|
| UC-VPR-01 | Mengajukan Penggantian Suku Cadang | Dispatcher | Tinggi |
| UC-VPR-02 | Menyetujui & Menjadwalkan Service | Admin GA | Kritis |
| UC-VPR-03 | Mencatat Realisasi Service | Requester/Driver | Tinggi |
| UC-VPR-04 | Memonitor Transaksi Perbaikan | Admin GA | Sedang |

### Modul lunch management (9)
| Kode | Nama use case | Aktor utama | Prioritas |
|---|---|---|---|
| UC-LNC-01 | Mengelola Master Pekerja & Alias | Admin GA | Tinggi |
| UC-LNC-02 | Menghasilkan Forecast Porsi H+1 | Admin GA / Scheduler | Kritis |
| UC-LNC-03 | Melakukan Override Manual Lokasi Makan | Admin GA | Sedang |
| UC-LNC-04 | Mengunci & Mendistribusikan Porsi ke Vendor | Admin GA | Kritis |
| UC-LNC-05 | Melakukan Absensi Makan Siang | Karyawan | Tinggi |
| UC-LNC-06 | Melakukan Booking Mandiri Lokasi Makan | Karyawan | Sedang |
| UC-LNC-07 | Mereservasi Makan Siang Tamu | Admin GA | Sedang |
| UC-LNC-08 | Melihat Dashboard Vendor | Vendor | Sedang |
| UC-LNC-09 | Melihat Analitik Katering | Admin GA, Manager | Rendah |

### Modul analisis efisiensi BBM (3)
| Kode | Nama use case | Aktor utama | Prioritas |
|---|---|---|---|
| UC-EFF-01 | Menghitung Ulang Metrik Efisiensi Trip | Superadmin | Sedang |
| UC-EFF-02 | Memvalidasi Hasil Analisis Trip | Superadmin | Sedang |
| UC-EFF-03 | Mengelola Baseline Konsumsi BBM | Superadmin | Sedang |

### Modul asisten AI Gaga (2)
| Kode | Nama use case | Aktor utama | Prioritas |
|---|---|---|---|
| UC-AI-01 | Mengobrol dengan Asisten Gaga | Superadmin | Rendah |
| UC-AI-02 | Mengelola Sesi Percakapan AI | Superadmin | Rendah |

### Modul master data unit & site (3)
| Kode | Nama use case | Aktor utama | Prioritas |
|---|---|---|---|
| UC-MST-01 | Mengelola Unit Bisnis | Superadmin | Sedang |
| UC-MST-02 | Mengelola Site / Lokasi | Superadmin | Sedang |
| UC-MST-03 | Mengelola Master Kendaraan & Alat | Superadmin | Tinggi |

### Modul RBAC & pengguna (4)
| Kode | Nama use case | Aktor utama | Prioritas |
|---|---|---|---|
| UC-RBAC-01 | Mengelola Pengguna | Superadmin | Tinggi |
| UC-RBAC-02 | Mengelola Role & Permission | Superadmin | Tinggi |
| UC-RBAC-03 | Mengelola Harga BBM (Master Gasoline) | Superadmin | Sedang |
| UC-RBAC-04 | Melihat Log Aktivitas | Superadmin | Rendah |

### Use case bersama (di-«include» oleh banyak use case)
| Kode | Nama use case | Dipakai oleh |
|---|---|---|
| UC-SYS-01 | Autentikasi Pengguna | Seluruh use case terproteksi |
| UC-SYS-02 | Memeriksa Hak Akses (RBAC) | Seluruh use case terproteksi |
| UC-SYS-03 | Menyimpan Berkas Terproteksi | UC-BBM-05, UC-LPJ-01, UC-VPR-03, UC-CHK-04 |
| UC-SYS-04 | Mengirim Notifikasi | UC-LPJ-01, UC-VPR-02, UC-BBM-04 |

### Jumlah diagram yang direncanakan per halaman
| Halaman | Jumlah diagram |
|---|---|
| index.html | 1 |
| 01-ruang-lingkup.html | 2 |
| 02-arsitektur-teknologi.html | 2 |
| 03-model-use-case.html | 4 |
| 04-spesifikasi-use-case.html | 0 (tabel) |
| 05-desain-perilaku.html | 4 |
| 06-desain-struktur.html | 3 |
| 07-validasi-logika.html | 0 (tabel) |
| 08-pengujian.html | 0 (tabel) |
| 09-traceability.html | 1 |
| 10-glosarium.html | 0 |
| 11-plantuml-changelog.html | 0 (kode PlantUML) |
| **Total** | **17** |
