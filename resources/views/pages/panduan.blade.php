「<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan SIAGA — PT Geo Dipa Energi (Persero) Unit Patuha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --gde-green:    #8DC63F;
            --gde-green-d:  #7ab832;
            --gde-red:      #ED1B2F;
            --gde-yellow:   #FFF56B;
            --gde-blue:     #0B50A1;
            --gde-blue-d:   #093d7d;
            --ink:          #1e293b;
            --muted:        #64748b;
            --light:        #f8fafc;
            --border:       #e2e8f0;
            --radius:       18px;
            --radius-sm:    12px;
        }

        body { font-family: 'Inter', sans-serif; color: var(--ink); background: var(--light); overflow-x: hidden; }
        h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }

        .navbar-gde { background: #fff; border-bottom: 1px solid var(--border); }
        .navbar-brand-img { width: 34px; height: 34px; flex-shrink: 0; }
        .navbar-brand-text { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.05rem; color: var(--gde-blue); line-height: 1.1; }
        .navbar-brand-sub { font-size: .58rem; font-weight: 600; color: var(--muted); letter-spacing: .06em; text-transform: uppercase; white-space: nowrap; }
        .navbar-brand-wrap { display: flex; align-items: center; gap: 8px; text-decoration: none; min-width: 0; }

        .nav-link-custom {
            font-size: .85rem; font-weight: 500; color: var(--muted);
            padding: 8px 16px; border-radius: var(--radius-sm);
            transition: all .2s; text-decoration: none;
        }
        .nav-link-custom:hover, .nav-link-custom.active { color: var(--gde-green-d); background: rgba(141,198,63,.08); }

        .navbar-toggler { border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 10px; }
        .navbar-toggler:focus { box-shadow: 0 0 0 3px rgba(141,198,63,.25); }

        .btn-burger {
            background: var(--gde-blue); color: #fff; border: none;
            border-radius: 100px; padding: 10px 14px; line-height: 1;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all .2s; white-space: nowrap;
        }
        .btn-burger:hover { background: var(--gde-blue-d); }
        .btn-burger::after { display: none !important; }

        .burger-dropdown {
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius); padding: 6px; min-width: 210px;
            box-shadow: 0 20px 50px rgba(0,0,0,.12);
        }
        .burger-item {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: var(--radius-sm);
            font-size: .85rem; font-weight: 500; color: var(--ink);
            text-decoration: none; transition: all .2s;
        }
        .burger-item:hover { background: rgba(141,198,63,.08); color: var(--gde-green-d); }
        .burger-item i { font-size: 1.05rem; width: 22px; color: var(--gde-green); }

        .page-header {
            padding: 120px 0 60px;
            background: linear-gradient(160deg, var(--gde-blue-d) 0%, var(--gde-blue) 100%);
            position: relative; overflow: hidden;
        }
        .page-header::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse 70% 60% at 30% 50%, rgba(255,255,255,.04), transparent 70%);
        }
        .page-header .container { position: relative; z-index: 2; }
        .page-header h1 { font-size: clamp(2rem, 3.5vw, 2.8rem); font-weight: 800; color: #fff; letter-spacing: -.03em; }
        .page-header p { font-size: .95rem; color: rgba(255,255,255,.6); max-width: 540px; margin-top: 12px; }

        .hero-tag {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
            border-radius: 100px; padding: 6px 16px;
            font-size: .7rem; font-weight: 600; color: rgba(255,255,255,.55);
            letter-spacing: .08em; text-transform: uppercase; margin-bottom: 18px;
        }
        .hero-tag .dot { width: 5px; height: 5px; border-radius: 50%; background: var(--gde-green); }

        .btn-back {
            display: inline-flex; align-items: center; gap: 6px;
            color: rgba(255,255,255,.4); text-decoration: none;
            font-size: .85rem; font-weight: 500; transition: color .2s; margin-bottom: 20px;
        }
        .btn-back:hover { color: rgba(255,255,255,.8); }

        .section-py { padding: 80px 0; }
        .section-eyebrow { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--gde-green-d); margin-bottom: 10px; }

        .guide-nav { position: sticky; top: 80px; }
        .guide-nav a {
            display: block; padding: 10px 16px; border-radius: var(--radius-sm);
            color: var(--muted); text-decoration: none; font-size: .83rem; font-weight: 500;
            transition: all .2s;
        }
        .guide-nav a:hover { color: var(--gde-green-d); background: rgba(141,198,63,.06); }
        .guide-nav a.active { background: rgba(141,198,63,.1); color: var(--gde-green-d); font-weight: 600; }
        .guide-nav a i { margin-right: 8px; width: 18px; }

        .guide-card {
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius); padding: 36px;
            margin-bottom: 24px; scroll-margin-top: 80px;
        }
        .guide-card h3 { display: flex; align-items: center; gap: 10px; font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; }
        .guide-card h3 .icon {
            width: 32px; height: 32px; border-radius: var(--radius-sm);
            background: rgba(141,198,63,.1); color: var(--gde-green-d);
            display: flex; align-items: center; justify-content: center;
            font-size: .9rem; flex-shrink: 0;
        }

        .guide-step { display: flex; gap: 16px; margin-bottom: 18px; }
        .guide-step:last-child { margin-bottom: 0; }
        .step-num {
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gde-green-d), var(--gde-blue));
            color: #fff; font-size: .7rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-top: 2px;
        }
        .step-content { flex: 1; }
        .step-content strong { display: block; font-size: .92rem; margin-bottom: 2px; }
        .step-content p { font-size: .82rem; color: var(--muted); margin: 0; line-height: 1.7; }
        .step-content a { color: var(--gde-green-d); text-decoration: none; font-weight: 500; }
        .step-content a:hover { text-decoration: underline; }
        .badge-pill {
            display: inline-block; background: rgba(141,198,63,.1); color: var(--gde-green-d);
            font-size: .65rem; font-weight: 600; padding: 3px 10px; border-radius: 100px; margin-right: 4px;
        }
        .badge-pill.orange { background: #fef3e7; color: #be6a1a; }
        .badge-pill.blue { background: #e8f0fe; color: #1a56be; }

        .alert-note {
            background: #fefce8; border: 1px solid #fde68a;
            border-radius: var(--radius-sm); padding: 16px;
            margin-top: 16px; font-size: .82rem; color: #92400e;
            display: flex; gap: 10px; align-items: flex-start;
        }
        .alert-note i { flex-shrink: 0; margin-top: 2px; }

        .footer-section { background: var(--gde-blue); color: #fff; padding: 64px 0 0; }
        .footer-brand { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1rem; }
        .footer-desc { font-size: .82rem; color: rgba(255,255,255,.4); line-height: 1.8; max-width: 300px; }
        .footer-col-title { font-size: .68rem; font-weight: 700; color: rgba(255,255,255,.3); text-transform: uppercase; letter-spacing: .1em; margin-bottom: 18px; }
        .footer-links { list-style: none; padding: 0; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a { color: rgba(255,255,255,.45); text-decoration: none; font-size: .84rem; display: inline-flex; align-items: center; gap: 8px; transition: color .2s; }
        .footer-links a:hover { color: var(--gde-green); }
        .footer-contact { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; font-size: .83rem; color: rgba(255,255,255,.38); }
        .footer-contact i { color: var(--gde-green); margin-top: 3px; flex-shrink: 0; }
        .footer-bottom { margin-top: 48px; border-top: 1px solid rgba(255,255,255,.06); padding: 20px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        .footer-bottom p { font-size: .75rem; color: rgba(255,255,255,.2); margin: 0; }

        .fade-up { opacity: 0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
        .fade-up.in { opacity: 1; transform: none; }

        @media (max-width: 991.98px) {
            .guide-nav { position: static; margin-bottom: 24px; }
            .guide-nav-inner { display: flex; flex-wrap: wrap; gap: 6px; }
            .guide-nav a { font-size: .78rem; padding: 8px 14px; }
        }
        @media (max-width: 767.98px) {
            .guide-card { padding: 24px 20px; }
            .navbar-brand-sub { font-size: .52rem; }
            .navbar-brand-text { font-size: .9rem; }
            .navbar-brand-img { width: 28px; height: 28px; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-gde sticky-top py-2">
        <div class="container">
            <a href="/" class="navbar-brand-wrap">
                <img src="https://jojo-static-files-klfm7hjb.s3.ap-southeast-3.amazonaws.com/logo-geodipa-officeless.png"
                     class="navbar-brand-img" alt="Logo Geodipa">
                <div>
                    <div class="navbar-brand-text">SIAGA</div>
                    <div class="navbar-brand-sub">SISTEM INFORMASI ADMINISTRASI GENERAL AFFAIRS</div>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2 ms-auto ms-lg-0 order-lg-last">
                <div class="dropdown d-none d-lg-block">
                    <button class="btn-burger" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-list" style="font-size:1.25rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end burger-dropdown">
                        <li><a href="/login" class="burger-item"><i class="bi bi-box-arrow-in-right"></i>Login</a></li>
                        <li><a href="{{ url('lunch-attendance') }}" class="burger-item"><i class="bi bi-egg-fried"></i>Absen Makan</a></li>
                        <li><hr class="dropdown-divider" style="margin:4px 0"></li>
                        <li><a href="{{ url('lunch-attendance') }}" class="burger-item"><i class="bi bi-person-badge"></i>Absen Makan Tamu</a></li>
                    </ul>
                </div>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"
                        aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="bi bi-list" style="font-size:1.3rem;color:var(--muted)"></i>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav mx-auto align-items-lg-center gap-1 py-2 py-lg-0">
                    <li class="nav-item"><a href="/" class="nav-link-custom">Beranda</a></li>
                    <li class="nav-item"><a href="/tentang" class="nav-link-custom">Tentang</a></li>
                    <li class="nav-item"><a href="/panduan" class="nav-link-custom active">Panduan</a></li>
                </ul>
                <div class="d-lg-none pt-2 border-top mt-2">
                    <a href="/login" class="burger-item d-flex"><i class="bi bi-box-arrow-in-right"></i>Login</a>
                    <a href="{{ url('lunch-attendance') }}" class="burger-item d-flex"><i class="bi bi-egg-fried"></i>Absen Makan</a>
                    <a href="{{ url('lunch-attendance') }}" class="burger-item d-flex"><i class="bi bi-person-badge"></i>Absen Makan Tamu</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <a href="/" class="btn-back"><i class="bi bi-arrow-left"></i> Kembali</a>
            <div class="hero-tag"><span class="dot"></span> Panduan Penggunaan</div>
            <h1>Panduan Lengkap SIAGA</h1>
            <p>Pelajari cara menggunakan fitur-fitur SIAGA langkah demi langkah.</p>
        </div>
    </div>

    <section class="section-py">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-3">
                    <div class="guide-nav">
                        <div class="guide-nav-inner">
                            <a href="#bbm" class="active"><i class="bi bi-fuel-pump"></i> BBM</a>
                            <a href="#spd"><i class="bi bi-file-earmark-text"></i> SPD</a>
                            <a href="#surat"><i class="bi bi-envelope-paper"></i> Cetak Surat</a>
                            <a href="#dispatcher"><i class="bi bi-truck"></i> Dispatcher</a>
                            <a href="#lunch"><i class="bi bi-cup-hot"></i> Makan Siang</a>
                            <a href="#checking"><i class="bi bi-clipboard-check"></i> Checking</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">

                    <div class="guide-card fade-up" id="bbm">
                        <h3><span class="icon"><i class="bi bi-fuel-pump"></i></span> Permohonan BBM</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Login ke SIAGA</strong>
                                <p>Akses <a href="/login">halaman login</a> dan masuk menggunakan akun yang telah didaftarkan.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Buka menu BBM</strong>
                                <p>Dari dashboard, buka menu <span class="badge-pill">BBM</span> di sidebar. Pilih jenis kendaraan yang akan diisi BBM.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Isi formulir permohonan</strong>
                                <p>Lengkapi data: kendaraan, tanggal, jumlah liter, jenis BBM, dan nomor polisi.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">4</div>
                            <div class="step-content">
                                <strong>Submit & tunggu approval</strong>
                                <p>Status dapat dipantau di menu <span class="badge-pill blue">Riwayat Permohonan</span>.</p>
                            </div>
                        </div>
                        <div class="alert-note">
                            <i class="bi bi-info-circle"></i>
                            <div>Setiap permohonan BBM akan dicatat dalam sistem untuk keperluan monitoring dan audit internal.</div>
                        </div>
                    </div>

                    <div class="guide-card fade-up" id="spd">
                        <h3><span class="icon"><i class="bi bi-file-earmark-text"></i></span> Pengajuan SPD</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Akses menu SPD</strong>
                                <p>Di sidebar dashboard, pilih menu <span class="badge-pill">SPD</span> untuk memulai pengajuan.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Isi detail perjalanan</strong>
                                <p>Masukkan data: tujuan, tanggal berangkat & kembali, kendaraan, serta uraian tugas.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Approval atasan</strong>
                                <p>Anda akan mendapat notifikasi ketika SPD telah disetujui atau ditolak.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">4</div>
                            <div class="step-content">
                                <strong>Cetak SPD</strong>
                                <p>Klik tombol <span class="badge-pill orange">Cetak</span>. Format surat sudah sesuai standar perusahaan.</p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-card fade-up" id="surat">
                        <h3><span class="icon"><i class="bi bi-envelope-paper"></i></span> Cetak Surat</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Pilih jenis surat</strong>
                                <p>Di menu <span class="badge-pill">Surat</span>, pilih jenis surat yang akan dicetak — tersedia berbagai template.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Isi parameter surat</strong>
                                <p>Lengkapi data: nomor surat, tanggal, tujuan, perihal, dan isi surat.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Pratinjau & cetak</strong>
                                <p>Surat akan dihasilkan dalam format PDF siap tanda tangan.</p>
                            </div>
                        </div>
                        <div class="alert-note">
                            <i class="bi bi-info-circle"></i>
                            <div>Nomor surat di-generate otomatis oleh sistem berdasarkan format yang telah ditentukan Unit GA.</div>
                        </div>
                    </div>

                    <div class="guide-card fade-up" id="dispatcher">
                        <h3><span class="icon"><i class="bi bi-truck"></i></span> Dispatcher & Administrasi Kendaraan</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Atur jadwal kendaraan</strong>
                                <p>Menu <span class="badge-pill">Dispatcher</span> digunakan untuk mengatur penugasan kendaraan operasional.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Monitoring penggunaan</strong>
                                <p>Pantau status kendaraan secara real-time — digunakan, tersedia, atau perawatan.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Riwayat & pelaporan</strong>
                                <p>Semua data perjalanan tercatat otomatis untuk pelaporan bulanan dan audit.</p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-card fade-up" id="lunch">
                        <h3><span class="icon"><i class="bi bi-cup-hot"></i></span> Booking & Absen Makan Siang</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Booking makan siang</strong>
                                <p>Gunakan menu <span class="badge-pill">Booking Makan Siang</span> di dashboard untuk memilih tanggal dan site.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Absen via QR Code</strong>
                                <p>Scan QR Code di area makan melalui halaman <span class="badge-pill blue">Absen Makan Siang</span>.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Self-booking (tanpa login)</strong>
                                <p>Karyawan dapat booking mandiri melalui halaman <a href="/my/lunch-plan">My Lunch Plan</a>.</p>
                            </div>
                        </div>
                        <div class="alert-note">
                            <i class="bi bi-info-circle"></i>
                            <div>Data absensi makan siang digunakan oleh tim Catering untuk menyiapkan porsi yang tepat setiap harinya.</div>
                        </div>
                    </div>

                    <div class="guide-card fade-up" id="checking">
                        <h3><span class="icon"><i class="bi bi-clipboard-check"></i></span> Routine Checking Kendaraan</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Pilih jenis checking</strong>
                                <p>Tersedia <span class="badge-pill orange">Daily</span>, <span class="badge-pill blue">Weekly</span>, <span class="badge-pill">Monthly</span>, dan <span class="badge-pill">Body Checking</span>.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Isi formulir inspeksi</strong>
                                <p>Lakukan pengecekan sesuai checklist. Status: OK / Rusak Ringan / Rusak Berat.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Laporkan temuan</strong>
                                <p>Sistem akan mencatat dan meneruskan laporan ke unit terkait untuk tindak lanjut.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <footer class="footer-section">
        <div class="container">
            <div class="row g-4 g-lg-5">
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="https://jojo-static-files-klfm7hjb.s3.ap-southeast-3.amazonaws.com/logo-geodipa-officeless.png"
                             style="width:30px;height:30px" alt="Logo">
                        <span class="footer-brand">SIAGA GDE Patuha</span>
                    </div>
                    <p class="footer-desc">
                        Sistem Informasi Administrasi General Affairs terintegrasi dengan
                        GEODIPA Integrated Management System (GIMS) untuk mendukung
                        Good Corporate Governance.
                    </p>
                </div>
                <div class="col-6 col-md-3 col-lg-2 offset-lg-2">
                    <div class="footer-col-title">Navigasi</div>
                    <ul class="footer-links">
                        <li><a href="/"><i class="bi bi-house"></i>Beranda</a></li>
                        <li><a href="/tentang"><i class="bi bi-info-circle"></i>Tentang</a></li>
                        <li><a href="/panduan"><i class="bi bi-book"></i>Panduan</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4 col-lg-4">
                    <div class="footer-col-title">Kontak</div>
                    <div class="footer-contact"><i class="bi bi-building"></i><span>PT Geo Dipa Energi (Persero), Unit Patuha</span></div>
                    <div class="footer-contact"><i class="bi bi-geo-alt"></i><span>Jawa Barat, Indonesia</span></div>
                    <div class="footer-contact"><i class="bi bi-envelope"></i><span>general.affairs@geodipa.co.id</span></div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 PT Geo Dipa Energi (Persero) Unit Patuha. All Right Reserved.</p>
                <p>Dikembangkan oleh tim internal IT Unit Patuha</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const links = document.querySelectorAll('.guide-nav a');
        const cards = document.querySelectorAll('.guide-card');
        const spy = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    links.forEach(l => l.classList.remove('active'));
                    const active = document.querySelector(`.guide-nav a[href="#${e.target.id}"]`);
                    if (active) active.classList.add('active');
                }
            });
        }, { threshold: 0.25, rootMargin: '-80px 0px 0px 0px' });
        cards.forEach(c => spy.observe(c));

        const fadeEls = document.querySelectorAll('.fade-up');
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) {
                    setTimeout(() => e.target.classList.add('in'), i * 120);
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.06, rootMargin: '0px 0px -40px 0px' });
        fadeEls.forEach(el => io.observe(el));
    </script>
</body>
</html>」