<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAGA — PT Geo Dipa Energi (Persero) Unit Patuha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
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

        body {
            font-family: 'Inter', sans-serif;
            color: var(--ink);
            background: #fff;
            overflow-x: hidden;
        }

        h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }

        /* ─── NAVBAR ─── */
        .navbar-gde { background: #fff; border-bottom: 1px solid var(--border); }
        .navbar-brand-img { width: 34px; height: 34px; flex-shrink: 0; }
        .navbar-brand-text { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.05rem; color: var(--gde-blue); line-height: 1.1; }
        .navbar-brand-sub { font-size: .58rem; font-weight: 600; color: var(--muted); letter-spacing: .06em; text-transform: uppercase; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .navbar-brand-wrap { display: flex; align-items: center; gap: 8px; text-decoration: none; min-width: 0; overflow: hidden; }

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

        /* ─── HERO ─── */
        .hero-section {
            position: relative; min-height: 560px;
            display: flex; align-items: center; overflow: hidden;
            background: var(--gde-blue-d);
        }
        .hero-bg {
            position: absolute; inset: 0;
            background-image: url('/img/landing_page_backdrop.jpeg');
            background-size: cover; background-position: center;
        }
        .hero-bg::after {
            content: ''; position: absolute; inset: 0;
            background: rgba(0,0,0,.5);
        }
        .hero-content { position: relative; z-index: 2; padding: 100px 0 80px; }
        .hero-title { font-size: clamp(2rem, 4.5vw, 3.2rem); font-weight: 800; color: #fff; line-height: 1.1; letter-spacing: -.03em; }
        .hero-subtitle { font-size: 1.05rem; color: rgba(255,255,255,.72); max-width: 560px; line-height: 1.8; margin-top: 16px; }
        .btn-hero-cta {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--gde-green); color: #fff; text-decoration: none;
            border-radius: 100px; padding: 14px 32px; font-weight: 600;
            font-size: .92rem; transition: all .25s; margin-top: 28px;
        }
        .btn-hero-cta:hover { background: var(--gde-green-d); color: #fff; transform: translateY(-2px); box-shadow: 0 12px 30px rgba(141,198,63,.35); }

        /* ─── SECTIONS ─── */
        .section-py { padding: 90px 0; }
        .section-eyebrow { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--gde-green-d); margin-bottom: 10px; }
        .section-title { font-size: clamp(1.6rem, 2.8vw, 2.3rem); font-weight: 800; color: var(--gde-blue); letter-spacing: -.02em; line-height: 1.15; }
        .section-lead { font-size: .95rem; color: var(--muted); line-height: 1.8; }

        /* ─── ABOUT ─── */
        .about-img { border-radius: var(--radius); box-shadow: 0 8px 30px rgba(0,0,0,.08); }

        /* ─── SERVICES CARDS ─── */
        .svc-card {
            border-radius: var(--radius); padding: 32px 28px;
            height: 100%; transition: transform .3s, box-shadow .3s;
        }
        .svc-card:hover { transform: translateY(-6px); box-shadow: 0 20px 50px rgba(0,0,0,.1); }
        .svc-icon {
            width: 48px; height: 48px; border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-bottom: 18px;
        }
        .svc-title { font-weight: 700; font-size: 1.05rem; margin-bottom: 8px; }
        .svc-desc { font-size: .85rem; line-height: 1.7; opacity: .75; margin-bottom: 20px; }

        .svc-card-dark { background: var(--gde-blue); color: #fff; }
        .svc-card-dark .svc-icon { background: rgba(255,255,255,.12); color: var(--gde-green); }
        .svc-card-dark .svc-desc { opacity: .65; }

        .svc-card-green { background: var(--gde-green); color: #fff; position: relative; overflow: hidden; padding: 0; }
        .svc-card-green-inner { padding: 28px; position: relative; z-index: 2; }
        .svc-card-green::after {
            content: ''; position: absolute; bottom: -40px; right: -40px;
            width: 200px; height: 200px; border-radius: 50%;
            background: rgba(255,255,255,.08);
        }
        .svc-badge {
            display: inline-block; background: rgba(255,255,255,.25);
            color: #fff; border-radius: 100px; padding: 6px 16px;
            font-size: .75rem; font-weight: 600; letter-spacing: .04em;
            backdrop-filter: blur(10px); margin-top: 14px;
        }
        .svc-card-green .svc-icon { background: rgba(255,255,255,.18); color: #fff; }

        .svc-card-light { background: var(--light); }
        .svc-card-light .svc-icon { background: #fff; color: var(--gde-green); }
        .skill-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .skill-label { font-size: .78rem; font-weight: 600; color: var(--ink); min-width: 90px; }
        .skill-dots { display: flex; gap: 5px; }
        .skill-dot { width: 12px; height: 12px; border-radius: 50%; transition: transform .2s; }
        .skill-dot.filled { background: var(--gde-green); }
        .skill-dot.empty { background: #cbd5e1; }
        .skill-dot:hover { transform: scale(1.4); }

        /* ─── STATS ─── */
        .stats-section { background: #fff; }
        .stat-card-item { text-align: center; padding: 28px 16px; }
        .stat-number { font-family: 'Poppins', sans-serif; font-size: 2.2rem; font-weight: 800; background: linear-gradient(135deg, var(--gde-green-d), var(--gde-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .stat-label { font-size: .82rem; color: var(--muted); margin-top: 6px; font-weight: 500; }

        /* ─── SERVICE GRID / STAGGERED ─── */
        .grid-section { background: var(--light); }
        .btn-explore {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--gde-blue); color: #fff; border-radius: 100px;
            padding: 12px 28px; font-weight: 600; font-size: .88rem;
            text-decoration: none; transition: all .25s; margin-top: 20px;
        }
        .btn-explore:hover { background: var(--gde-blue-d); color: #fff; transform: translateY(-2px); }
        .grid-img {
            width: 100%; border-radius: var(--radius); object-fit: cover;
            box-shadow: 0 8px 25px rgba(0,0,0,.06);
            transition: transform .3s;
        }
        .grid-img:hover { transform: scale(1.02); }
        .grid-img-tall { height: 320px; }
        .grid-img-short { height: 220px; }

        /* ─── FOOTER ─── */
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
            .hero-section { min-height: 480px; }
            .hero-content { padding: 80px 0 60px; }
            .hero-title { font-size: 1.8rem; }
            .grid-img-tall { height: 240px; }
            .grid-img-short { height: 180px; }
        }
        @media (max-width: 767.98px) {
            .section-py { padding: 60px 0; }
            .stat-card-item { padding: 18px 8px; }
            .stat-number { font-size: 1.6rem; }
            .navbar-brand-sub { font-size: .5rem; display: none; }
            .navbar-brand-text { font-size: .9rem; }
            .navbar-brand-img { width: 28px; height: 28px; }
            .hero-section { min-height: 400px; }
            .hero-content { padding: 60px 0 48px; }
            .hero-title { font-size: 1.5rem; }
            .hero-subtitle { font-size: .9rem; }
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
                    <li class="nav-item"><a href="/" class="nav-link-custom active">Beranda</a></li>
                    <li class="nav-item"><a href="/tentang" class="nav-link-custom">Tentang</a></li>
                    <li class="nav-item"><a href="/panduan" class="nav-link-custom">Panduan</a></li>
                    <li class="nav-item"><a href="#layanan" class="nav-link-custom">Layanan</a></li>
                </ul>
                <div class="d-lg-none pt-2 border-top mt-2">
                    <a href="/login" class="burger-item d-flex"><i class="bi bi-box-arrow-in-right"></i>Login</a>
                    <a href="{{ url('lunch-attendance') }}" class="burger-item d-flex"><i class="bi bi-egg-fried"></i>Absen Makan</a>
                    <a href="{{ url('lunch-attendance') }}" class="burger-item d-flex"><i class="bi bi-person-badge"></i>Absen Makan Tamu</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ─── HERO ─────────────────────────────────────────────── -->
    <section class="hero-section">
        <div class="hero-bg"></div>
        <div class="container">
            <div class="row hero-content">
                <div class="col-12 col-lg-8 col-xl-7 fade-up">
                    <h1 class="hero-title">
                        Sistem Informasi Administrasi General Affairs
                    </h1>
                    <p class="hero-subtitle">
                        Optimalisasi operasional one stop system melalui digitalisasi proses permohonan,
                        approval, dan pelaporan yang efisien dan akuntabel — di bawah naungan
                        PT Geo Dipa Energi (Persero) Unit Patuha.
                    </p>
                    <a href="/login" class="btn-hero-cta">
                        <i class="bi bi-rocket-takeoff"></i> Mulai Sekarang
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── ABOUT SECTION ────────────────────────────────────── -->
    <section class="section-py" id="tentang">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6 fade-up">
                    <div class="section-eyebrow">Tentang SIAGA</div>
                    <h2 class="section-title mb-3">Transformasi Digital Layanan General Affairs</h2>
                    <p class="section-lead">
                        <strong>SIAGA</strong> (Sistem Informasi Administrasi General Affairs) adalah platform digital
                        terintegrasi yang dikembangkan oleh PT Geo Dipa Energi (Persero) Unit Patuha untuk mengelola
                        seluruh layanan administrasi General Affairs secara paperless, transparan, dan akuntabel.
                    </p>
                    <p class="section-lead mt-2">
                        Berlandaskan prinsip Good Corporate Governance (GCG) dan selaras dengan GEODIPA Integrated
                        Management System (GIMS), SIAGA menghadirkan digitalisasi proses mulai dari permohonan,
                        approval multi-level, hingga pelaporan dalam satu ekosistem yang modern.
                    </p>
                </div>
                <div class="col-lg-6 fade-up">
                    <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=600&h=400&fit=crop"
                         alt="SIAGA About" class="img-fluid about-img w-100" style="height:380px;object-fit:cover">
                </div>
            </div>
        </div>
    </section>

    <!-- ─── SERVICES SECTION (3 Cards) ───────────────────────── -->
    <section class="section-py" style="background: var(--light)" id="layanan">
        <div class="container">
            <div class="text-center mb-5 fade-up">
                <div class="section-eyebrow">Layanan Kami</div>
                <h2 class="section-title mb-2">Fitur Unggulan SIAGA</h2>
                <p class="section-lead mx-auto" style="max-width:560px">Tiga pilar utama yang mendukung kelancaran operasional administrasi General Affairs di Unit Patuha.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 fade-up">
                    <div class="svc-card svc-card-dark">
                        <div class="svc-icon"><i class="bi bi-speedometer2"></i></div>
                        <h3 class="svc-title">Dashboard & Monitoring</h3>
                        <p class="svc-desc">Pantau seluruh aktivitas GA secara real-time melalui dashboard interaktif yang intuitif dan mudah digunakan. Setiap transaksi tercatat rapih.</p>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="notifSwitch" checked>
                            <label class="form-check-label" for="notifSwitch" style="font-size:.82rem;color:rgba(255,255,255,.7)">Notifikasi Otomatis</label>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 fade-up">
                    <div class="svc-card svc-card-green">
                        <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=500&h=300&fit=crop"
                             alt="Service" class="img-fluid w-100" style="height:200px;object-fit:cover">
                        <div class="svc-card-green-inner">
                            <div class="svc-icon"><i class="bi bi-people-fill"></i></div>
                            <h3 class="svc-title" style="color:#fff">Multi-Level Approval</h3>
                            <p class="svc-desc" style="color:rgba(255,255,255,.75);opacity:1">Workflow approval terotomasi sesuai hierarki perusahaan dengan notifikasi instan.</p>
                            <span class="svc-badge">Private & Group Lessons</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 fade-up">
                    <div class="svc-card svc-card-light">
                        <div class="svc-icon"><i class="bi bi-trophy-fill"></i></div>
                        <h3 class="svc-title" style="color:var(--gde-blue)">7+ Modul Aktif</h3>
                        <div class="skill-row">
                            <span class="skill-label">BBM</span>
                            <div class="skill-dots">
                                <span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span>
                            </div>
                        </div>
                        <div class="skill-row">
                            <span class="skill-label">SPD</span>
                            <div class="skill-dots">
                                <span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot empty"></span><span class="skill-dot empty"></span>
                            </div>
                        </div>
                        <div class="skill-row">
                            <span class="skill-label">Dispatcher</span>
                            <div class="skill-dots">
                                <span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot filled"></span><span class="skill-dot empty"></span><span class="skill-dot empty"></span><span class="skill-dot empty"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── STATS SECTION ────────────────────────────────────── -->
    <section class="stats-section section-py">
        <div class="container">
            <div class="row">
                <div class="col-6 col-md-3 fade-up">
                    <div class="stat-card-item">
                        <div class="stat-number">300+</div>
                        <div class="stat-label">Pengguna Aktif</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 fade-up">
                    <div class="stat-card-item">
                        <div class="stat-number">12 000+</div>
                        <div class="stat-label">Transaksi / Tahun</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 fade-up">
                    <div class="stat-card-item">
                        <div class="stat-number">7+</div>
                        <div class="stat-label">Modul Layanan</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 fade-up">
                    <div class="stat-card-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Paperless</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── SERVICE GRID / STAGGERED ─────────────────────────── -->
    <section class="grid-section section-py">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5 fade-up">
                    <div class="section-eyebrow">Ekosistem SIAGA</div>
                    <h2 class="section-title mb-3">Satu Platform, Semua Layanan GA</h2>
                    <p class="section-lead">
                        Dari pengajuan BBM, surat perjalanan dinas, manajemen armada kendaraan, hingga booking
                        makan siang — semua terintegrasi dalam satu dashboard yang rapi dan mudah diakses.
                    </p>
                    <a href="/login" class="btn-explore">
                        <i class="bi bi-arrow-right"></i> Explore Lebih Lanjut
                    </a>
                </div>
                <div class="col-lg-7 fade-up">
                    <div class="row g-3">
                        <div class="col-6">
                            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=500&fit=crop"
                                 alt="Dashboard" class="grid-img grid-img-tall">
                        </div>
                        <div class="col-6 d-flex flex-column gap-3">
                            <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&h=250&fit=crop"
                                 alt="Analytics" class="grid-img grid-img-short mt-4">
                            <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=400&h=250&fit=crop"
                                 alt="Reports" class="grid-img grid-img-short">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── FOOTER ───────────────────────────────────────────── -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script>
        const fadeEls = document.querySelectorAll('.fade-up');
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) {
                    setTimeout(() => e.target.classList.add('in'), i * 100);
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        fadeEls.forEach(el => io.observe(el));
    </script>
</body>
</html>