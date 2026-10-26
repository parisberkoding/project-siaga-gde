「<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang SIAGA — PT Geo Dipa Energi (Persero) Unit Patuha</title>
    <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('DashboardTemplates/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">

  <!-- GDE UI Templates — design system Geodipa (dimuat setelah NiceAdmin agar menimpanya) -->
  <link href="{{ asset('gde-ui-templates/fonts/poppins.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-tokens.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-base.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-components.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-app.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-compat.css') }}" rel="stylesheet">


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

        body { font-family: 'Poppins', sans-serif; color: var(--ink); background: #fff; overflow-x: hidden; }
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

        .section-py { padding: 90px 0; }
        .section-eyebrow { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--gde-green-d); margin-bottom: 10px; }
        .section-title { font-size: clamp(1.6rem, 2.8vw, 2.3rem); font-weight: 800; color: var(--gde-blue); letter-spacing: -.02em; line-height: 1.15; }

        .about-card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 36px 32px; }
        .about-card p { font-size: .9rem; color: var(--ink); line-height: 1.9; opacity: .8; }

        .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .stat-card-item { text-align: center; padding: 28px 16px; background: #fff; border: 1px solid var(--border); border-radius: var(--radius-sm); }
        .stat-num { font-family: 'Poppins', sans-serif; font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--gde-green-d), var(--gde-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .stat-card-label { font-size: .8rem; color: var(--muted); margin-top: 4px; font-weight: 500; }

        .timeline { position: relative; padding-left: 32px; }
        .timeline::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: var(--border); }
        .tl-item { position: relative; margin-bottom: 32px; }
        .tl-item::before { content: ''; position: absolute; left: -36px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: var(--gde-green); border: 3px solid #fff; }
        .tl-year { font-size: .7rem; font-weight: 700; color: var(--gde-green-d); letter-spacing: .08em; text-transform: uppercase; }
        .tl-title { font-weight: 700; font-size: .95rem; margin-top: 2px; }
        .tl-desc { font-size: .82rem; color: var(--muted); margin-top: 4px; line-height: 1.6; }

        .team-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .team-card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 32px 20px; text-align: center; transition: transform .25s, box-shadow .25s; }
        .team-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,.08); }
        .team-avatar {
            width: 56px; height: 56px; border-radius: 50%;
            background: linear-gradient(135deg, rgba(141,198,63,.15), rgba(11,80,161,.15));
            color: var(--gde-green); display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: 700; margin: 0 auto 14px;
        }
        .team-name { font-weight: 700; font-size: .88rem; }
        .team-role { font-size: .76rem; color: var(--muted); margin-top: 4px; }

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
            .team-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 767.98px) {
            .team-grid, .stat-row { grid-template-columns: 1fr; }
            .section-py { padding: 60px 0; }
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
                    <li class="nav-item"><a href="/tentang" class="nav-link-custom active">Tentang</a></li>
                    <li class="nav-item"><a href="/panduan" class="nav-link-custom">Panduan</a></li>
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
            <div class="hero-tag"><span class="dot"></span> Tentang SIAGA</div>
            <h1>Tentang Sistem Informasi<br>Administrasi General Affairs</h1>
            <p>Mengenal lebih dekat SIAGA, Unit Patuha, dan tim pengembang.</p>
        </div>
    </div>

    <section class="section-py">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-6 fade-up">
                    <div class="section-eyebrow">Latar Belakang</div>
                    <h2 class="section-title mb-3">Apa itu SIAGA?</h2>
                    <div class="about-card">
                        <p>
                            <strong>SIAGA</strong> (Sistem Informasi Administrasi General Affairs) adalah
                            platform digital terintegrasi yang dikembangkan oleh <strong>PT Geo Dipa Energi
                            (Persero) Unit Patuha</strong> untuk mengelola seluruh layanan administrasi
                            General Affairs secara paperless, transparan, dan akuntabel.
                        </p>
                        <p class="mt-3">
                            Berlandaskan prinsip <strong>Good Corporate Governance (GCG)</strong> dan
                            selaras dengan <strong>GEODIPA Integrated Management System (GIMS)</strong>,
                            SIAGA menghadirkan digitalisasi proses mulai dari permohonan, approval
                            multi-level, hingga pelaporan — dalam satu ekosistem yang modern.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6 fade-up">
                    <div class="stat-row">
                        <div class="stat-card-item">
                            <div class="stat-num">2025</div>
                            <div class="stat-card-label">Tahun Rilis</div>
                        </div>
                        <div class="stat-card-item">
                            <div class="stat-num">7+</div>
                            <div class="stat-card-label">Modul Aktif</div>
                        </div>
                        <div class="stat-card-item">
                            <div class="stat-num">100%</div>
                            <div class="stat-card-label">Digital</div>
                        </div>
                    </div>
                    <div class="timeline mt-4">
                        <div class="tl-item">
                            <div class="tl-year">2024</div>
                            <div class="tl-title">Project Planning</div>
                            <div class="tl-desc">Identifikasi kebutuhan digitalisasi proses administrasi GA di Unit Patuha.</div>
                        </div>
                        <div class="tl-item">
                            <div class="tl-year">2025</div>
                            <div class="tl-title">Sistem Permohonan BBM Unit Patuha</div>
                            <div class="tl-desc">Rilis perdana dengan single module single purpose app berbasis google appscript.</div>
                        </div>
                        <div class="tl-item">
                            <div class="tl-year">2026</div>
                            <div class="tl-title">Pengembangan Lanjutan sebagai <b>SIAGA</b></div>
                            <div class="tl-desc">One-Stop Solution untuk Proses Operasional yang berjalan di unit patuha dan masih terus dikembangkan untuk administrasi yang lebih transparan dan akuntabel.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-py" style="background:var(--light)">
        <div class="container">
            <div class="text-center mb-5 fade-up">
                <div class="section-eyebrow">Tim Pengembang</div>
                <h2 class="section-title mb-3">Tim di Balik SIAGA</h2>
                <p style="font-size:.95rem;color:var(--muted);max-width:540px;margin:0 auto">
                    Dedikasi dan inovasi dari tim yang peduli pada transformasi digital.
                </p>
            </div>
            <div class="team-grid">
                <div class="team-card fade-up">
                    <div class="team-avatar"><i class="bi bi-person"></i></div>
                    <div class="team-name">Faris Iftikhar Alfarisi</div>
                    <div class="team-role">Developer</div>
                </div>
                <div class="team-card fade-up">
                    <div class="team-avatar"><i class="bi bi-person"></i></div>
                    <div class="team-name">Ridwan Pratama Cahya Gustian</div>
                    <div class="team-role">Developer</div>
                </div>
                <div class="team-card fade-up">
                    <div class="team-avatar"><i class="bi bi-person"></i></div>
                    <div class="team-name">R. Maulana Suhadharatan</div>
                    <div class="team-role">Project Leader</div>
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

    <script src="{{ asset('DashboardTemplates/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        const fadeEls = document.querySelectorAll('.fade-up');
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) {
                    setTimeout(() => e.target.classList.add('in'), i * 100);
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.06, rootMargin: '0px 0px -40px 0px' });
        fadeEls.forEach(el => io.observe(el));
    </script>
</body>
</html>」