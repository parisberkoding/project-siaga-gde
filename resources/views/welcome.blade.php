<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAGA — PT Geo Dipa Energi (Persero) Unit Patuha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Lora:ital,wght@1,600&display=swap" rel="stylesheet">

    <style>
        :root {
            --gde-navy:        #1B3A6B;
            --gde-navy-dark:   #122850;
            --gde-green:       #5B9A3C;
            --gde-green-light: #7AB84F;
            --gde-green-pale:  #EBF4E5;
            --gde-cream:       #F6F9F4;
            --gde-border:      #e0e6ed;
            --gde-muted:       #6B7A8D;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: #fff;
            color: #2d3a4a;
        }

        /* ── NAVBAR ── */
        .navbar-gde {
            background: #fff;
            border-bottom: 1px solid var(--gde-border);
            box-shadow: 0 1px 8px rgba(27,58,107,.06);
        }

        .nav-logomark {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--gde-green) 30%, var(--gde-navy));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1rem; flex-shrink: 0;
        }

        .nav-brand-title {
            font-size: .95rem; font-weight: 800;
            color: var(--gde-navy); line-height: 1.1;
        }

        .nav-brand-sub {
            font-size: .68rem; font-weight: 600;
            color: var(--gde-muted); letter-spacing: .04em; text-transform: uppercase;
        }

        .btn-login {
            background: var(--gde-green); color: #fff !important;
            border: none; border-radius: 6px; font-weight: 700;
            font-size: .82rem; padding: 9px 22px;
            display: inline-flex; align-items: center; gap: 7px;
            text-decoration: none; white-space: nowrap;
            transition: background .2s, box-shadow .2s;
            font-family: 'Nunito', sans-serif;
        }

        .btn-login:hover {
            background: var(--gde-green-light);
            box-shadow: 0 4px 14px rgba(91,154,60,.32);
        }

        .navbar-toggler {
            border: 1px solid var(--gde-border);
            border-radius: 6px; padding: 5px 9px;
        }

        .navbar-toggler:focus { box-shadow: none; }

        /* ── HERO ── */
        .hero-section {
            position: relative; min-height: 520px;
            display: flex; align-items: center;
            overflow: hidden; background: var(--gde-navy-dark);
        }

        .hero-bg {
            position: absolute; inset: 0;
            background-image: url('/img/landing_page_backdrop.jpeg');
            background-size: cover; background-position: center;
        }

        .hero-bg::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(110deg, rgba(18,40,80,.94) 0%, rgba(18,40,80,.82) 55%, rgba(18,40,80,.45) 100%);
        }

        .hero-content { position: relative; z-index: 2; padding: 88px 0; }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 9px;
            background: rgba(91,154,60,.16);
            border: 1px solid rgba(91,154,60,.45);
            color: #9cd870;
            border-radius: 100px; padding: 5px 16px;
            font-size: .72rem; font-weight: 700;
            letter-spacing: .07em; text-transform: uppercase;
            margin-bottom: 22px;
        }

        .hero-badge .dot {
            width: 6px; height: 6px;
            background: #9cd870; border-radius: 50%;
            animation: blink 2s infinite;
        }

        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

        .hero-title {
            font-size: clamp(1.8rem, 4vw, 2.9rem);
            font-weight: 800; color: #fff;
            line-height: 1.15; letter-spacing: -.025em;
            margin-bottom: 16px;
        }

        .hero-title em {
            font-family: 'Lora', serif;
            font-style: italic; font-weight: 600;
            color: #9cd870;
        }

        .hero-lead {
            font-size: .98rem; color: rgba(255,255,255,.72);
            line-height: 1.8; max-width: 470px; margin-bottom: 36px;
        }

        .btn-hero {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--gde-green); color: #fff;
            text-decoration: none; border-radius: 8px;
            padding: 12px 28px; font-weight: 700; font-size: .9rem;
            box-shadow: 0 4px 18px rgba(91,154,60,.42);
            transition: all .2s;
        }

        .btn-hero:hover { background: var(--gde-green-light); color: #fff; transform: translateY(-2px); }

        .btn-hero-ghost {
            display: inline-flex; align-items: center; gap: 8px;
            background: transparent; color: rgba(255,255,255,.8);
            text-decoration: none; border-radius: 8px;
            padding: 12px 22px; font-weight: 600; font-size: .88rem;
            border: 1px solid rgba(255,255,255,.24);
            transition: all .2s;
        }

        .btn-hero-ghost:hover { background: rgba(255,255,255,.09); color: #fff; }

        /* ── STATS BAR ── */
        .stats-bar { background: var(--gde-navy); }

        .stat-block {
            padding: 20px 16px;
            display: flex; align-items: center; gap: 13px;
            border-right: 1px solid rgba(255,255,255,.07);
        }

        .stat-block:last-child { border-right: none; }

        .stat-icon {
            width: 40px; height: 40px;
            background: rgba(91,154,60,.15);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #9cd870; font-size: 1.1rem; flex-shrink: 0;
        }

        .stat-label {
            font-size: .66rem; font-weight: 700;
            color: rgba(255,255,255,.38); text-transform: uppercase; letter-spacing: .07em;
        }

        .stat-value {
            font-size: .84rem; font-weight: 700; color: rgba(255,255,255,.86);
        }

        /* ── SECTION ── */
        .section-eyebrow {
            font-size: .7rem; font-weight: 800;
            color: var(--gde-green); text-transform: uppercase; letter-spacing: .1em;
        }

        .section-title {
            font-size: clamp(1.5rem, 2.5vw, 2.1rem);
            font-weight: 800; color: var(--gde-navy);
            letter-spacing: -.02em; line-height: 1.2;
        }

        .section-lead {
            font-size: .95rem; color: var(--gde-muted); line-height: 1.8;
        }

        /* ── OPTIMIZATION CARDS (replaces PIJAR) ── */
        .opt-section { background: var(--gde-cream); }

        .opt-card {
            background: #fff; border: 1px solid var(--gde-border);
            border-radius: 12px; padding: 26px 20px;
            height: 100%; position: relative; overflow: hidden;
            transition: transform .25s, box-shadow .25s;
            text-align: center;
        }

        .opt-card::after {
            content: ''; position: absolute;
            left: 0; bottom: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--gde-green), var(--gde-navy));
            transform: scaleX(0); transform-origin: left; transition: transform .3s;
        }

        .opt-card:hover { transform: translateY(-5px); box-shadow: 0 8px 28px rgba(27,58,107,.1); }
        .opt-card:hover::after { transform: scaleX(1); }

        .opt-icon {
            width: 52px; height: 52px;
            background: var(--gde-green-pale); border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: var(--gde-green);
            margin: 0 auto 16px;
        }

        .opt-card-title {
            font-size: .9rem; font-weight: 800;
            color: var(--gde-navy); margin-bottom: 6px;
        }

        .opt-card-desc {
            font-size: .8rem; color: var(--gde-muted); line-height: 1.7;
        }

        /* ── GA PANEL ── */
        .ga-panel {
            background: var(--gde-navy); border-radius: 14px;
            padding: 38px 32px; color: #fff;
            height: 100%; position: relative; overflow: hidden;
        }

        .ga-panel::before {
            content: ''; position: absolute;
            top: -70px; right: -70px; width: 200px; height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(91,154,60,.2), transparent 70%);
        }

        .ga-tag {
            font-size: .68rem; font-weight: 800;
            letter-spacing: .1em; text-transform: uppercase;
            color: #9cd870; margin-bottom: 12px;
        }

        .ga-title {
            font-size: 1.4rem; font-weight: 800;
            line-height: 1.25; letter-spacing: -.02em;
            color: #fff; margin-bottom: 14px;
        }

        .ga-desc { font-size: .84rem; color: rgba(255,255,255,.6); line-height: 1.8; }

        /* ── FEATURE LIST ── */
        .feature-list { list-style: none; padding: 0; margin: 0; }

        .feature-list li {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 15px 0; border-bottom: 1px solid var(--gde-border);
        }

        .feature-list li:last-child { border-bottom: none; }

        .feature-chk {
            width: 30px; height: 30px;
            background: var(--gde-green-pale); border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            color: var(--gde-green); font-size: .88rem; flex-shrink: 0;
        }

        .feature-name {
            font-size: .88rem; font-weight: 800;
            color: var(--gde-navy); display: block; margin-bottom: 2px;
        }

        .feature-desc { font-size: .8rem; color: var(--gde-muted); line-height: 1.6; }

        /* ── ACCESS CARDS ── */
        .access-section { background: #fff; }

        .access-card {
            background: var(--gde-cream); border: 1px solid var(--gde-border);
            border-radius: 14px; padding: 28px 24px;
            height: 100%; text-decoration: none; color: inherit;
            display: flex; flex-direction: column; align-items: center; text-align: center;
            transition: transform .25s, box-shadow .25s;
        }

        .access-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(27,58,107,.1);
            border-color: var(--gde-green);
        }

        .access-icon {
            width: 56px; height: 56px;
            background: var(--gde-green-pale); border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: var(--gde-green);
            margin-bottom: 14px;
            transition: background .25s, color .25s;
        }

        .access-card:hover .access-icon {
            background: var(--gde-green);
            color: #fff;
        }

        .access-title {
            font-size: .95rem; font-weight: 800; color: var(--gde-navy);
            margin-bottom: 4px;
        }

        .access-desc {
            font-size: .78rem; color: var(--gde-muted); line-height: 1.6;
        }

        .access-badge {
            display: inline-block;
            font-size: .6rem; font-weight: 800; letter-spacing: .07em;
            text-transform: uppercase; border-radius: 4px;
            padding: 2px 8px; margin-top: 8px;
        }

        /* ── DIGITALIZATION ── */
        .digital-section {
            background: linear-gradient(135deg, var(--gde-navy) 0%, #1a4a85 100%);
            position: relative; overflow: hidden;
        }

        .digital-section::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 80% 60% at 30% 50%, rgba(91,154,60,.12), transparent 60%);
        }

        .digital-content { position: relative; z-index: 2; }

        .digital-title {
            font-size: clamp(1.4rem, 2.5vw, 2rem);
            font-weight: 800; color: #fff;
            letter-spacing: -.02em; line-height: 1.25;
        }

        .digital-lead {
            font-size: .9rem; color: rgba(255,255,255,.55); line-height: 1.8;
        }

        .digital-grid { display: flex; flex-wrap: wrap; gap: 12px; }

        .digital-chip {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 100px; padding: 8px 18px;
            color: rgba(255,255,255,.7); font-size: .82rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all .2s;
        }

        .digital-chip:hover {
            background: rgba(91,154,60,.2);
            border-color: rgba(91,154,60,.4);
            color: #9cd870;
        }

        .digital-chip i { color: #9cd870; font-size: .9rem; }

        /* ── CTA ── */
        .cta-section {
            background: linear-gradient(135deg, var(--gde-navy) 0%, #1a4a85 100%);
            padding: 72px 0; position: relative;
            overflow: hidden; text-align: center;
        }

        .cta-section::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse 70% 80% at 50% 0%, rgba(91,154,60,.14), transparent 65%);
        }

        .cta-title {
            font-size: clamp(1.6rem, 3vw, 2.3rem);
            font-weight: 800; color: #fff;
            letter-spacing: -.025em; margin-bottom: 12px;
        }

        .cta-lead {
            font-size: .94rem; color: rgba(255,255,255,.6);
            max-width: 420px; margin: 0 auto 32px; line-height: 1.8;
        }

        /* ── FOOTER ── */
        footer { background: #0d2242; color: #fff; padding: 56px 0 0; }

        .footer-logo-mark {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--gde-green), var(--gde-navy));
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .95rem;
        }

        .footer-brand-name { font-size: .9rem; font-weight: 800; color: #fff; }

        .footer-desc { font-size: .82rem; color: rgba(255,255,255,.42); line-height: 1.8; }

        .footer-col-title {
            font-size: .7rem; font-weight: 800;
            color: rgba(255,255,255,.4); text-transform: uppercase;
            letter-spacing: .09em; margin-bottom: 18px;
        }

        .footer-link-list { list-style: none; padding: 0; }
        .footer-link-list li { margin-bottom: 10px; }

        .footer-link-list a {
            color: rgba(255,255,255,.58); text-decoration: none;
            font-size: .84rem; display: inline-flex; align-items: center; gap: 8px;
            transition: color .2s;
        }

        .footer-link-list a:hover { color: #9cd870; }

        .footer-contact-row {
            display: flex; align-items: flex-start; gap: 10px;
            margin-bottom: 12px; font-size: .83rem; color: rgba(255,255,255,.52);
        }

        .footer-contact-row i { color: #9cd870; margin-top: 2px; flex-shrink: 0; }

        .footer-bottom {
            margin-top: 48px; border-top: 1px solid rgba(255,255,255,.06);
            padding: 20px 0;
        }

        .footer-bottom-text { font-size: .76rem; color: rgba(255,255,255,.28); margin: 0; }
        .footer-bottom-badge { font-size: .76rem; color: rgba(255,255,255,.28); display: flex; align-items: center; gap: 5px; }
        .footer-bottom-badge i { color: #9cd870; }

        /* ── Scroll fade ── */
        .fade-up {
            opacity: 0; transform: translateY(20px);
            transition: opacity .55s ease, transform .55s ease;
        }

        .fade-up.in { opacity: 1; transform: none; }

        /* ── Mobile ── */
        @media (max-width: 767.98px) {
            .hero-content { padding: 64px 0; }
            .stat-block { border-right: none; border-bottom: 1px solid rgba(255,255,255,.07); }
            .stats-bar .row > [class*="col"]:nth-child(odd) .stat-block { border-right: 1px solid rgba(255,255,255,.07); }
        }

        @media (max-width: 575.98px) { .ga-panel { padding: 28px 22px; } }
    </style>
</head>
<body>

    <!-- ─── NAVBAR ──────────────────────────────────────────── -->
    <nav class="navbar navbar-gde navbar-expand-md sticky-top">
        <div class="container-fluid px-3 px-md-4">

            <a href="#" class="navbar-brand d-flex align-items-center gap-2 py-2 me-0">
                <img src="https://jojo-static-files-klfm7hjb.s3.ap-southeast-3.amazonaws.com/logo-geodipa-officeless.png"
                     style="width:6%" alt="Logo Geodipa">
                <div>
                    <div class="nav-brand-title">SIAGA</div>
                    <div class="nav-brand-sub">SISTEM INFORMASI ADMINISTRASI GENERAL AFFAIRS</div>
                </div>
            </a>

            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navMain"
                    aria-controls="navMain" aria-expanded="false">
                <i class="bi bi-list" style="font-size:1.25rem; color:var(--gde-muted)"></i>
            </button>

            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav ms-auto align-items-center gap-1 gap-md-0">
                    <li class="nav-item"><a href="/tentang" class="nav-link px-3 fw-semibold" style="color:var(--gde-muted);transition:color .2s;font-size:.84rem">Tentang</a></li>
                    <li class="nav-item"><a href="/panduan" class="nav-link px-3 fw-semibold" style="color:var(--gde-muted);transition:color .2s;font-size:.84rem">Panduan</a></li>
                </ul>
                <div class="ms-3 mt-2 mt-md-0">
                    <a href="/login" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Masuk ke Sistem
                    </a>
                </div>
            </div>
        </div>
    </nav>


    <!-- ─── HERO ─────────────────────────────────────────────── -->
    <section class="hero-section">
        <div class="hero-bg"></div>
        <div class="container">
            <div class="row hero-content">
                <div class="col-12 col-md-10 col-lg-7 col-xl-6">
                    <div class="hero-badge">
                        <span class="dot"></span>
                        PT. Geo Dipa Energi (Persero) Unit Patuha · General Affairs
                    </div>

                    <h1 class="hero-title">
                        SIAGA<br>
                        <em>Sistem Informasi Administrasi General Affairs</em>
                    </h1>

                    <p class="hero-lead">
                        Optimalisasi operasional one stop system melalui digitalisasi proses
                        permohonan, approval, dan pelaporan yang efisien dan akuntabel.
                    </p>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="/login" class="btn-hero">
                            <i class="bi bi-rocket-takeoff"></i>
                            Mulai Sekarang
                        </a>
                        <a href="#fitur" class="btn-hero-ghost">
                            <i class="bi bi-chevron-down"></i>
                            Pelajari Fitur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ─── STATS BAR ────────────────────────────────────────── -->
    <div class="stats-bar">
        <div class="container-fluid px-3 px-md-4">
            <div class="row g-0">
                <div class="col-6 col-md-3">
                    <div class="stat-block">
                        <div class="stat-icon"><i class="bi bi-speedometer2"></i></div>
                        <div>
                            <div class="stat-label">Efisiensi</div>
                            <div class="stat-value">Proses Cepat</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-block">
                        <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <div class="stat-label">Keamanan</div>
                            <div class="stat-value">Akuntabel</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-block">
                        <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <div>
                            <div class="stat-label">Monitoring</div>
                            <div class="stat-value">Real-time</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-block" style="border-right:none">
                        <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <div>
                            <div class="stat-label">Pelaporan</div>
                            <div class="stat-value">Paperless</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- ─── OPTIMALISASI & TRANSPARANSI ──────────────────────── -->
    <section class="opt-section py-5">
        <div class="container py-4">
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-8 text-center fade-up">
                    <div class="section-eyebrow mb-1">Komitmen Kami</div>
                    <h2 class="section-title mb-3">Optimalisasi & Transparansi</h2>
                    <p class="section-lead mx-auto" style="max-width:560px">
                        SIAGA dibangun untuk mendorong efisiensi operasional GA melalui sistem
                        yang transparan, terukur, dan dapat dipertanggungjawabkan.
                    </p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-6 col-md-3 fade-up">
                    <div class="opt-card">
                        <div class="opt-icon"><i class="bi bi-clock-history"></i></div>
                        <div class="opt-card-title">Efisiensi Waktu</div>
                        <p class="opt-card-desc">Proses permohonan dan approval lebih cepat tanpa birokrasi berbelit.</p>
                    </div>
                </div>
                <div class="col-6 col-md-3 fade-up">
                    <div class="opt-card">
                        <div class="opt-icon"><i class="bi bi-eye"></i></div>
                        <div class="opt-card-title">Transparansi Penuh</div>
                        <p class="opt-card-desc">Setiap transaksi terekam dan dapat diaudit secara real-time.</p>
                    </div>
                </div>
                <div class="col-6 col-md-3 fade-up">
                    <div class="opt-card">
                        <div class="opt-icon"><i class="bi bi-bar-chart-line"></i></div>
                        <div class="opt-card-title">Data Terukur</div>
                        <p class="opt-card-desc">Dashboard analitik untuk monitoring dan pengambilan keputusan.</p>
                    </div>
                </div>
                <div class="col-6 col-md-3 fade-up">
                    <div class="opt-card">
                        <div class="opt-icon"><i class="bi bi-file-earmark-check"></i></div>
                        <div class="opt-card-title">Akuntabilitas</div>
                        <p class="opt-card-desc">Setiap aktivitas memiliki jejak digital yang jelas dan sah.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ─── FITUR UNGGULAN ───────────────────────────────────── -->
    <section class="py-5 bg-white" id="fitur">
        <div class="container py-4">
            <div class="row g-4 g-lg-5 align-items-stretch">

                <div class="col-12 col-lg-4 fade-up">
                    <div class="ga-panel">
                        <div class="ga-tag">General Affairs · Unit Patuha</div>
                        <h2 class="ga-title">Solusi Digital General Affairs</h2>
                        <p class="ga-desc">
                            SIAGA mengintegrasikan seluruh layanan administrasi GA dalam satu
                            ekosistem digital yang transparan, efisien, dan akuntabel.
                        </p>
                        <div class="mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,.12)">
                            <a href="/login" class="btn-hero w-100 justify-content-center">
                                <i class="bi bi-box-arrow-in-right"></i>
                                Akses Sistem
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8 fade-up">
                    <div class="section-eyebrow mb-1">Kemampuan Sistem</div>
                    <h2 class="section-title mb-4">Fitur Unggulan SIAGA</h2>
                    <ul class="feature-list">
                        <li>
                            <div class="feature-chk"><i class="bi bi-check2-circle"></i></div>
                            <div>
                                <span class="feature-name">Permohonan Digital</span>
                                <span class="feature-desc">Proses pengajuan layanan GA tanpa kertas yang cepat dan terstruktur.</span>
                            </div>
                        </li>
                        <li>
                            <div class="feature-chk"><i class="bi bi-check2-circle"></i></div>
                            <div>
                                <span class="feature-name">Multi-Level Approval</span>
                                <span class="feature-desc">Workflow approval terotomasi sesuai hierarki perusahaan.</span>
                            </div>
                        </li>
                        <li>
                            <div class="feature-chk"><i class="bi bi-check2-circle"></i></div>
                            <div>
                                <span class="feature-name">Pelaporan Real-time</span>
                                <span class="feature-desc">Dashboard interaktif untuk monitoring aktivitas GA secara langsung.</span>
                            </div>
                        </li>
                        <li>
                            <div class="feature-chk"><i class="bi bi-check2-circle"></i></div>
                            <div>
                                <span class="feature-name">Manajemen Dokumen</span>
                                <span class="feature-desc">Unggah, simpan, dan kelola dokumen pendukung setiap transaksi.</span>
                            </div>
                        </li>
                        <li>
                            <div class="feature-chk"><i class="bi bi-check2-circle"></i></div>
                            <div>
                                <span class="feature-name">Notifikasi Otomatis</span>
                                <span class="feature-desc">Pemberitahuan instan untuk setiap perubahan status permohonan.</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>


    <!-- ─── AKSES CEPAT ──────────────────────────────────────── -->
    @php $isLoggedIn = (bool) Auth::user(); @endphp

    <section class="access-section py-5">
        <div class="container py-4">
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-8 text-center fade-up">
                    <div class="section-eyebrow mb-1">Akses Cepat</div>
                    <h2 class="section-title mb-3">Layanan SIAGA</h2>
                    <p class="section-lead mx-auto" style="max-width:560px">
                        Pilih layanan yang tersedia untuk memulai proses administrasi General Affairs.
                    </p>
                </div>
            </div>

            <div class="row g-3 justify-content-center">
                <div class="col-6 col-md-4 col-lg-3 fade-up">
                    <a href="{{ url('lunch-attendance') }}" class="access-card">
                        <div class="access-icon"><i class="bi bi-fork-knife"></i></div>
                        <div class="access-title">Absen Makan Siang</div>
                        <div class="access-desc">Scan to eat, and <i>bon appetit</i></div>
                        <span class="access-badge text-bg-primary">Klik disini</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3 fade-up">
                    <a href="{{ url('my/lunch-plan') }}" class="access-card">
                        <div class="access-icon"><i class="bi bi-calendar-check"></i></div>
                        <div class="access-title">Mylunch</div>
                        <div class="access-desc">Atur jadwal makan siang mandiri</div>
                        <span class="access-badge text-bg-primary">Klik Disini</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3 fade-up">
                    <a href="{{ $isLoggedIn ? url('/') : url('login') }}" class="access-card">
                        <div class="access-icon"><i class="bi bi-speedometer2"></i></div>
                        <div class="access-title">Dashboard</div>
                        <div class="access-desc">Beranda dan ringkasan aktivitas</div>
                        <span class="access-badge text-bg-secondary">Terautentikasi</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3 fade-up">
                    <a href="{{ $isLoggedIn ? url('/requester/view-request-bbm') : url('login') }}" class="access-card">
                        <div class="access-icon"><i class="bi bi-file-earmark-plus"></i></div>
                        <div class="access-title">Pengajuan</div>
                        <div class="access-desc">Ajukan permohonan layanan GA</div>
                        <span class="access-badge text-bg-secondary">Terautentikasi</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3 fade-up">
                    <a href="{{ $isLoggedIn ? url('/surat_requester/main-surat') : url('login') }}" class="access-card">
                        <div class="access-icon"><i class="bi bi-signpost-2"></i></div>
                        <div class="access-title">Surat Perjalanan Dinas</div>
                        <div class="access-desc">Kelola perjalanan dinas</div>
                        <span class="access-badge text-bg-secondary">Terautentikasi</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3 fade-up">
                    <a href="{{ $isLoggedIn ? url('/requester/bbm-history') : url('login') }}" class="access-card">
                        <div class="access-icon"><i class="bi bi-fuel-pump"></i></div>
                        <div class="access-title">BBM Operasional</div>
                        <div class="access-desc">Manajemen BBM kendaraan</div>
                        <span class="access-badge text-bg-secondary">Terautentikasi</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3 fade-up">
                    <a href="{{ $isLoggedIn ? url('/dispatcher/dashboard') : url('login') }}" class="access-card">
                        <div class="access-icon"><i class="bi bi-truck"></i></div>
                        <div class="access-title">Dispatcher</div>
                        <div class="access-desc">Pengelolaan armada kendaraan</div>
                        <span class="access-badge text-bg-secondary">Terautentikasi</span>
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- ─── DIGITALISASI ─────────────────────────────────────── -->
    <section class="digital-section py-5">
        <div class="container py-4">
            <div class="row align-items-center g-4 digital-content">
                <div class="col-12 col-lg-6 fade-up">
                    <div class="hero-badge" style="margin-bottom:18px">
                        <span class="dot"></span>
                        Transformasi Digital
                    </div>
                    <h2 class="digital-title">Digitalisasi Layanan General Affairs</h2>
                    <p class="digital-lead">
                        SIAGA menghadirkan transformasi digital untuk seluruh layanan administrasi
                        General Affairs — dari pengajuan, approval, hingga pelaporan — dalam satu
                        platform terintegrasi yang modern dan mudah digunakan.
                    </p>
                    <div class="mt-4">
                        <a href="/login" class="btn-hero">
                            <i class="bi bi-rocket-takeoff"></i>
                            Mulai Sekarang
                        </a>
                    </div>
                </div>
                <div class="col-12 col-lg-6 fade-up">
                    <div class="digital-grid">
                        <div class="digital-chip"><i class="bi bi-check-lg"></i>Pengajuan Online</div>
                        <div class="digital-chip"><i class="bi bi-check-lg"></i>Approval Otomatis</div>
                        <div class="digital-chip"><i class="bi bi-check-lg"></i>Dashboard Analitik</div>
                        <div class="digital-chip"><i class="bi bi-check-lg"></i>Manajemen Armada</div>
                        <div class="digital-chip"><i class="bi bi-check-lg"></i>Booking Makan Siang</div>
                        <div class="digital-chip"><i class="bi bi-check-lg"></i>Surat Perjalanan Dinas</div>
                        <div class="digital-chip"><i class="bi bi-check-lg"></i>Manajemen BBM</div>
                        <div class="digital-chip"><i class="bi bi-check-lg"></i>Pelaporan Paperless</div>
                        <div class="digital-chip"><i class="bi bi-check-lg"></i>Notifikasi Real-time</div>
                        <div class="digital-chip"><i class="bi bi-check-lg"></i>Audit Trail</div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ─── CTA ──────────────────────────────────────────────── -->
    <section class="cta-section">
        <div class="container position-relative" style="z-index:2">
            <h2 class="cta-title">Siap menjadi insan Geodipa yang lebih digital?</h2>
            <p class="cta-lead">Masuk ke sistem dan nikmati kemudahan layanan General Affairs yang digital, transparan, dan akuntabel.</p>
            <a href="/login" class="btn-hero d-inline-flex">
                <i class="bi bi-rocket-takeoff"></i>
                Login ke SIAGA
            </a>
        </div>
    </section>


    <!-- ─── FOOTER ───────────────────────────────────────────── -->
    <footer>
        <div class="container">
            <div class="row g-4 g-lg-5">
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="footer-logo-mark"><i class="bi bi-gear-fill"></i></div>
                        <span class="footer-brand-name">SIAGA GDE Patuha</span>
                    </div>
                    <p class="footer-desc">
                        Sistem Informasi Administrasi General Affairs terintegrasi dengan
                        GEODIPA Integrated Management System (GIMS) untuk mendukung
                        Good Corporate Governance.
                    </p>
                </div>

                <div class="col-6 col-md-3 col-lg-2 offset-lg-2">
                    <div class="footer-col-title">Navigasi</div>
                    <ul class="footer-link-list">
                        <li><a href="/"><i class="bi bi-house"></i>Beranda</a></li>
                        <li><a href="/tentang"><i class="bi bi-info-circle"></i>Tentang</a></li>
                        <li><a href="/panduan"><i class="bi bi-book"></i>Panduan</a></li>
                    </ul>
                </div>

                <div class="col-6 col-md-4 col-lg-4">
                    <div class="footer-col-title">Kontak</div>
                    <div class="footer-contact-row">
                        <i class="bi bi-building"></i>
                        <span>PT Geo Dipa Energi (Persero), Unit Patuha</span>
                    </div>
                    <div class="footer-contact-row">
                        <i class="bi bi-geo-alt"></i>
                        <span>Jawa Barat, Indonesia</span>
                    </div>
                    <div class="footer-contact-row">
                        <i class="bi bi-envelope"></i>
                        <span>general.affairs@geodipa.co.id</span>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <p class="footer-bottom-text">
                        &copy; 2026 PT Geo Dipa Energi (Persero) Unit Patuha. All Right Reserved.
                    </p>
                    <div class="footer-bottom-badge">
                        Dikembangkan khusus oleh tim internal IT unit patuha</i>
                    </div>
                </div>
            </div>
        </div>
    </footer>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const fadeEls = document.querySelectorAll('.fade-up');
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) {
                    setTimeout(() => e.target.classList.add('in'), i * 80);
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        fadeEls.forEach(el => io.observe(el));
    </script>
</body>
</html>
