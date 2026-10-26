# SIAGA — Documentations

Paket dokumentasi aplikasi **SIAGA (Sistem Informasi Administrasi General Affairs)**
PT Geo Dipa Energi (Persero) — Unit Patuha.

Folder ini berdiri sendiri (self-contained). Untuk membukanya cukup **klik dua kali `index.html`**
atau kompres seluruh folder menjadi satu berkas ZIP lalu kirimkan ke penerima.

---

## Cara membuka

1. Buka `index.html` dengan peramban (disarankan Chrome, Edge, atau Firefox versi terbaru).
2. Halaman beranda menampilkan dua pilihan dokumen:
   - **Software Documentation** — dokumen teknis (SDD) untuk tim IT.
   - **Alur Tutorial** — panduan penggunaan bergambar per modul.
3. Seluruh tautan antar halaman bersifat relatif, sehingga folder bisa dipindahkan atau
   diekstrak di mana saja tanpa merusak navigasi.

> **Catatan koneksi internet.** Font Poppins, ikon Bootstrap Icons, dan pustaka diagram Mermaid
> dimuat dari CDN. Tanpa internet, seluruh teks dan tabel tetap terbaca; hanya ikon dan diagram
> yang tidak tampil. Bila diperlukan mode sepenuhnya luring, CDN dapat diganti berkas lokal
> (lihat bagian *Menjadikan luring* di bawah).

---

## Struktur folder

```
documentation-of-siaga/
├── index.html                      Beranda — pemilih dokumen
├── README.md                       Berkas ini
├── PANDUAN-PENYUSUNAN.md           Kontrak desain & penulisan untuk pemelihara dokumen
├── assets/
│   ├── css/
│   │   ├── docs.css                Design system (Poppins, palet Geodipa, komponen dokumen)
│   │   └── mock.css                Replika antarmuka SIAGA + sistem tooltip tutorial
│   └── js/
│       ├── docs.js                 Navigasi, pencarian, tab, salin kode, tooltip langkah, zoom
│       └── diagrams.js             Pemuatan Mermaid + tema diagram
├── software-documentation/         Dokumen teknis (SDD), 12 halaman
│   ├── index.html                  Ikhtisar, daftar isi, ringkasan eksekutif, metodologi
│   ├── 01-ruang-lingkup.html       Scope, problem statement, stakeholder, aktor, NFR
│   ├── 02-arsitektur-teknologi.html
│   ├── 03-model-use-case.html      Diagram + katalog 45 use case + relasi include/extend
│   ├── 04-spesifikasi-use-case.html
│   ├── 05-desain-perilaku.html     Activity & sequence diagram
│   ├── 06-desain-struktur.html     Class diagram, ERD, kamus data
│   ├── 07-validasi-logika.html     Decision table
│   ├── 08-pengujian.html           Test case, coverage, pengujian non-fungsional
│   ├── 09-traceability.html        Traceability matrix & matriks RBAC
│   ├── 10-glosarium.html
│   └── 11-plantuml-changelog.html  Kode PlantUML, changelog, pemeliharaan dokumen
└── alur-tutorial/                  Panduan penggunaan, 7 halaman
    ├── index.html                  Ikhtisar 6 alur + matriks peran
    ├── 01-permohonan-bbm.html
    ├── 02-spd.html
    ├── 03-cetak-surat.html
    ├── 04-dispatcher.html
    ├── 05-makan-siang.html
    └── 06-checking.html
```

---

## Fitur dokumentasi

| Fitur | Keterangan |
|---|---|
| **Pencarian halaman** | Kotak pencarian di bilah atas; pintasan papan tunjuk `/` untuk fokus cepat. |
| **Navigasi samping** | Daftar isi SDD/menu alur; ikut menyorot bagian yang sedang dibaca (scrollspy). |
| **Diagram** | Ditulis sebagai kode Mermaid di dalam halaman sehingga mudah ditinjau; klik diagram untuk memperbesar. |
| **Kode PlantUML** | Padanan teks untuk diagram utama, lengkap dengan tombol salin. |
| **Tooltip tutorial** | Setiap langkah pada Alur Tutorial ditandai angka; arahkan kursor atau klik untuk melihat penjelasan dan menyorot elemen terkait. |
| **Cetak / PDF** | Tombol printer pada bilah atas menghasilkan versi cetak (navigasi dan tooltip disembunyikan). |

---

## Menjadikan luring (opsional)

1. Unduh `mermaid.min.js`, `bootstrap-icons.css` + fontnya, dan berkas font Poppins.
2. Simpan ke `assets/vendor/`.
3. Ganti tautan CDN pada setiap halaman (`<link href="https://cdn.jsdelivr.net/...">` dan
   `<script src="https://cdn.jsdelivr.net/npm/mermaid...">`) agar menunjuk berkas lokal.
4. Ganti juga alamat logo Geodipa (saat ini menunjuk penyimpanan S3) dengan berkas lokal
   `assets/img/logo-geodipa.png` bila ingin sepenuhnya mandiri.

---

## Pemeliharaan

Dokumen ini harus diperbarui ketika terjadi perubahan berikut:

- penambahan atau penghapusan use case → perbarui Bagian 3, 4, dan 9;
- perubahan aturan bisnis → perbarui Bagian 4, 7 (decision table), dan 8 (test case);
- perubahan skema basis data → perbarui Bagian 6 (ERD &amp; kamus data);
- perubahan hak akses / peran → perbarui Bagian 9 (matriks RBAC) dan 10 (glosarium);
- rilis versi baru → catat pada Bagian 11 (changelog &amp; versi dokumen).

Kontrak penulisan (kelas CSS, blok kepala/sidebar/footer, katalog use case kanonik) ada di
`PANDUAN-PENYUSUNAN.md` — bacalah sebelum menambah halaman baru.

---

## Referensi

- Dokumentasi internal: `PRD.md`, `DOKUMENTASI_PROJECT.md`, `routes/web.php` (repositori SIAGA).
- Metodologi: *Streamlining the Software Lifecycle — Integrating AI Use Case Modeling with
  Visual Paradigm's All-in-One Platform*.

---

*Dokumen internal. Hak cipta &copy; PT Geo Dipa Energi (Persero) Unit Patuha.*
