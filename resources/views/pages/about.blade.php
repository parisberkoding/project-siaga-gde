<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang SIAGA — PT Geo Dipa Energi (Persero) Unit Patuha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --bg: #fafbfc; --card: #fff; --ink: #0b1a2e; --ink-70: #3d4d5f; --ink-40: #8b97a6; --ink-20: #c5cdd6; --accent: #0f6e4a; --accent-light: #e6f7f0; --border: #e7eaee; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--ink); line-height: 1.6; }
        .font-jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }

        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: rgba(255,255,255,.88); backdrop-filter: blur(14px);
            box-shadow: 0 1px 0 var(--border); padding: 14px 0;
        }
        .nav-inner { display: flex; align-items: center; gap: 12px; }
        .nav-logo { width: 34px; height: 34px; border-radius: 8px; background: var(--accent); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .85rem; flex-shrink: 0; }
        .nav-title { font-weight: 800; font-size: .95rem; line-height: 1.1; }
        .nav-sub { font-size: .62rem; font-weight: 600; color: var(--ink-40); letter-spacing: .06em; text-transform: uppercase; }
        .nav-spacer { flex: 1; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { text-decoration: none; color: var(--ink-70); font-size: .84rem; font-weight: 500; transition: color .2s; }
        .nav-links a:hover { color: var(--accent); }
        .btn-nav { background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: 10px 22px; font-weight: 600; font-size: .82rem; display: inline-flex; align-items: center; gap: 7px; text-decoration: none; transition: background .2s; font-family: inherit; }
        .btn-nav:hover { background: #0d8a5c; color: #fff; }

        .page-header {
            padding: 140px 0 60px; background: linear-gradient(160deg, #0b1a2e, #162d54);
            position: relative; overflow: hidden;
        }
        .page-header::before { content: ''; position: absolute; width: 400px; height: 400px; border-radius: 50%; background: radial-gradient(circle, rgba(15,110,74,.15), transparent); top: -100px; right: -50px; }
        .page-header .container { position: relative; z-index: 2; }
        .page-header h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(2rem, 3.5vw, 2.8rem); font-weight: 800; color: #fff; letter-spacing: -.03em; }
        .page-header p { font-size: .95rem; color: rgba(255,255,255,.5); max-width: 540px; margin-top: 12px; }

        section { padding: 80px 0; }
        .section-tag { display: inline-block; font-size: .68rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--accent); margin-bottom: 14px; }
        .section-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(1.4rem, 2.5vw, 2rem); font-weight: 800; line-height: 1.15; letter-spacing: -.025em; }

        .hero-tag {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
            border-radius: 100px; padding: 6px 16px;
            font-size: .7rem; font-weight: 600; color: rgba(255,255,255,.5);
            letter-spacing: .08em; text-transform: uppercase; margin-bottom: 18px;
        }
        .hero-tag .dot { width: 5px; height: 5px; border-radius: 50%; background: var(--accent); }

        .about-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 36px 32px; }
        .about-card p { font-size: .9rem; color: var(--ink-70); line-height: 1.9; }

        .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .stat-card { text-align: center; padding: 28px 16px; background: var(--card); border: 1px solid var(--border); border-radius: 12px; }
        .stat-num { font-size: 1.8rem; font-weight: 800; color: var(--accent); }
        .stat-label { font-size: .8rem; color: var(--ink-40); margin-top: 4px; }

        .timeline { position: relative; padding-left: 32px; }
        .timeline::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: var(--border); }
        .tl-item { position: relative; margin-bottom: 32px; }
        .tl-item::before { content: ''; position: absolute; left: -32px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: var(--accent); border: 3px solid var(--bg); }
        .tl-year { font-size: .7rem; font-weight: 700; color: var(--ink-40); letter-spacing: .08em; text-transform: uppercase; }
        .tl-title { font-weight: 700; font-size: .95rem; margin-top: 2px; }
        .tl-desc { font-size: .82rem; color: var(--ink-70); margin-top: 4px; }

        .testimonial-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .testi-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 28px; }
        .testi-stars { color: #f4b740; font-size: .85rem; margin-bottom: 12px; }
        .testi-text { font-size: .88rem; color: var(--ink-70); line-height: 1.8; font-style: italic; }
        .testi-author { display: flex; align-items: center; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); }
        .testi-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; }
        .testi-name { font-weight: 600; font-size: .84rem; }
        .testi-role { font-size: .75rem; color: var(--ink-40); }

        .team-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .team-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 28px 20px; text-align: center; }
        .team-avatar { width: 56px; height: 56px; border-radius: 50%; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; margin: 0 auto 14px; }
        .team-name { font-weight: 700; font-size: .88rem; }
        .team-role { font-size: .76rem; color: var(--ink-40); margin-top: 2px; }

        .btn-back { display: inline-flex; align-items: center; gap: 6px; color: var(--ink-70); text-decoration: none; font-size: .85rem; font-weight: 500; transition: color .2s; }
        .btn-back:hover { color: var(--accent); }

        footer { background: #060f1e; color: #fff; padding: 56px 0 0; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1.5fr; gap: 48px; }
        .footer-brand { font-weight: 800; font-size: .9rem; margin-bottom: 10px; }
        .footer-desc { font-size: .82rem; color: rgba(255,255,255,.3); line-height: 1.8; max-width: 340px; }
        .footer-col-title { font-size: .68rem; font-weight: 700; color: rgba(255,255,255,.3); text-transform: uppercase; letter-spacing: .1em; margin-bottom: 18px; }
        .footer-links { list-style: none; padding: 0; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a { color: rgba(255,255,255,.45); text-decoration: none; font-size: .84rem; display: inline-flex; align-items: center; gap: 8px; transition: color .2s; }
        .footer-links a:hover { color: #4ad99b; }
        .footer-contact { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; font-size: .83rem; color: rgba(255,255,255,.35); }
        .footer-contact i { color: #4ad99b; margin-top: 3px; flex-shrink: 0; }
        .footer-bottom { margin-top: 48px; border-top: 1px solid rgba(255,255,255,.05); padding: 20px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        .footer-bottom p { font-size: .75rem; color: rgba(255,255,255,.2); margin: 0; }

        .reveal { opacity: 0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
        .reveal.in { opacity: 1; transform: none; }

        @media (max-width: 991.98px) { .testimonial-grid, .team-grid { grid-template-columns: repeat(2, 1fr); } .footer-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 575.98px) { .testimonial-grid, .team-grid, .stat-row { grid-template-columns: 1fr; } .footer-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <nav>
        <div class="container">
            <div class="nav-inner">
                <a href="/" style="text-decoration:none;color:inherit;display:contents">
                    <div class="nav-logo"><i class="bi bi-gear-fill"></i></div>
                    <div>
                        <div class="nav-title">SIAGA</div>
                        <div class="nav-sub">General Affairs</div>
                    </div>
                </a>
                <div class="nav-spacer"></div>
                <div class="nav-links">
                    <a href="/">Beranda</a>
                    <a href="/tentang">Tentang</a>
                    <a href="/panduan">Panduan</a>
                </div>
                <a href="/login" class="btn-nav"><i class="bi bi-box-arrow-in-right"></i> Masuk</a>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <a href="/" class="btn-back" style="color:rgba(255,255,255,.4);margin-bottom:20px;display:inline-flex">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <div class="hero-tag"><span class="dot"></span> Tentang SIAGA</div>
            <h1>Tentang Sistem Informasi<br>Administrasi General Affairs</h1>
            <p>Mengenal lebih dekat SIAGA, Unit Patuha, dan tim pengembang.</p>
        </div>
    </div>

    <section>
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-6 reveal">
                    <div class="section-tag">Latar Belakang</div>
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
                <div class="col-lg-6 reveal">
                    <div class="stat-row">
                        <div class="stat-card">
                            <div class="stat-num">2025</div>
                            <div class="stat-label">Tahun Rilis</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-num">7+</div>
                            <div class="stat-label">Modul Aktif</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-num">100%</div>
                            <div class="stat-label">Digital</div>
                        </div>
                    </div>
                    <div class="timeline mt-4">
                        <div class="tl-item">
                            <div class="tl-year">2024</div>
                            <div class="tl-title">Inisiasi Pengembangan</div>
                            <div class="tl-desc">Identifikasi kebutuhan digitalisasi proses administrasi GA di Unit Patuha.</div>
                        </div>
                        <div class="tl-item">
                            <div class="tl-year">2025</div>
                            <div class="tl-title">Peluncuran SIAGA</div>
                            <div class="tl-desc">Rilis perdana dengan modul BBM, SPD, Dispatcher, dan Booking Makan Siang.</div>
                        </div>
                        <div class="tl-item">
                            <div class="tl-year">2026</div>
                            <div class="tl-title">Pengembangan Lanjutan</div>
                            <div class="tl-desc">Penambahan modul Self-Booking, Lunch Management, dan optimalisasi berkelanjutan.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section style="background:#fff;">
        <div class="container">
            <div class="text-center mb-5 reveal">
                <div class="section-tag">Testimoni</div>
                <h2 class="section-title mb-3">Apa Kata Pengguna?</h2>
                <p class="section-lead mx-auto" style="max-width:540px;color:var(--ink-70);font-size:.95rem">Respon dari pengguna yang telah merasakan kemudahan SIAGA.</p>
            </div>
            <div class="testimonial-grid">
                <div class="testi-card reveal">
                    <div class="testi-stars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                    <div class="testi-text">"Dulu ngurus SPD harus bolak-balik print formulir, sekarang cukup dari laptop aja. Approval juga cepet karena notifikasi langsung ke atasan."</div>
                    <div class="testi-author">
                        <div class="testi-avatar">AR</div>
                        <div><div class="testi-name">Aulia Rahman</div><div class="testi-role">Staff Operasional</div></div>
                    </div>
                </div>
                <div class="testi-card reveal">
                    <div class="testi-stars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                    <div class="testi-text">"Fitur booking makan siang sangat membantu. Tinggal pilih tanggal dan site, catering jadi tahu jumlah pesanan lebih akurat."</div>
                    <div class="testi-author">
                        <div class="testi-avatar">RS</div>
                        <div><div class="testi-name">Rina Suryani</div><div class="testi-role">Karyawan</div></div>
                    </div>
                </div>
                <div class="testi-card reveal">
                    <div class="testi-stars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                    <div class="testi-text">"Dashboard BBM real-time sangat membantu monitoring konsumsi. Sekarang laporan bulanan tinggal export, gak perlu rekap manual lagi."</div>
                    <div class="testi-author">
                        <div class="testi-avatar">DW</div>
                        <div><div class="testi-name">Dwi Wijaya</div><div class="testi-role">Supervisor GA</div></div>
                    </div>
                </div>
                <div class="testi-card reveal">
                    <div class="testi-stars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                    <div class="testi-text">"Dulu absen makan siang pake kertas, sekarang scan QR aja. Lebih higienis dan datanya langsung masuk ke sistem catering."</div>
                    <div class="testi-author">
                        <div class="testi-avatar">MP</div>
                        <div><div class="testi-name">Mega Putri</div><div class="testi-role">Admin GA</div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="text-center mb-5 reveal">
                <div class="section-tag">Tim Pengembang</div>
                <h2 class="section-title mb-3">Tim di Balik SIAGA</h2>
                <p class="section-lead mx-auto" style="max-width:540px;color:var(--ink-70);font-size:.95rem">Dedikasi dan inovasi dari tim yang peduli pada transformasi digital.</p>
            </div>
            <div class="team-grid">
                <div class="team-card reveal">
                    <div class="team-avatar"><i class="bi bi-person"></i></div>
                    <div class="team-name">Faris Iftikhar</div>
                    <div class="team-role">Fullstack Developer</div>
                </div>
                <div class="team-card reveal">
                    <div class="team-avatar"><i class="bi bi-person"></i></div>
                    <div class="team-name">Tim GA</div>
                    <div class="team-role">Subject Matter Expert</div>
                </div>
                <div class="team-card reveal">
                    <div class="team-avatar"><i class="bi bi-person"></i></div>
                    <div class="team-name">Manajemen</div>
                    <div class="team-role">Supervisor & Approver</div>
                </div>
                <div class="team-card reveal">
                    <div class="team-avatar"><i class="bi bi-person"></i></div>
                    <div class="team-name">User SIAGA</div>
                    <div class="team-role">Pengguna & Reviewer</div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">SIAGA GDE Patuha</div>
                    <p class="footer-desc">Sistem Informasi Administrasi General Affairs terintegrasi dengan GIMS untuk mendukung Good Corporate Governance.</p>
                </div>
                <div>
                    <div class="footer-col-title">Navigasi</div>
                    <ul class="footer-links">
                        <li><a href="/"><i class="bi bi-house"></i>Beranda</a></li>
                        <li><a href="/tentang"><i class="bi bi-info-circle"></i>Tentang</a></li>
                        <li><a href="/panduan"><i class="bi bi-book"></i>Panduan</a></li>
                    </ul>
                </div>
                <div>
                    <div class="footer-col-title">Kontak</div>
                    <div class="footer-contact"><i class="bi bi-building"></i><span>PT Geo Dipa Energi (Persero), Unit Patuha</span></div>
                    <div class="footer-contact"><i class="bi bi-geo-alt"></i><span>Jawa Barat, Indonesia</span></div>
                    <div class="footer-contact"><i class="bi bi-envelope"></i><span>general.affairs@geodipa.co.id</span></div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 PT Geo Dipa Energi (Persero) Unit Patuha. All Right Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const els = document.querySelectorAll('.reveal');
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) { setTimeout(() => e.target.classList.add('in'), i * 100); io.unobserve(e.target); }
            });
        }, { threshold: 0.06, rootMargin: '0px 0px -40px 0px' });
        els.forEach(el => io.observe(el));
    </script>
</body>
</html>
