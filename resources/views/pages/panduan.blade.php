<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan SIAGA — PT Geo Dipa Energi (Persero) Unit Patuha</title>
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

        .hero-tag {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
            border-radius: 100px; padding: 6px 16px;
            font-size: .7rem; font-weight: 600; color: rgba(255,255,255,.5);
            letter-spacing: .08em; text-transform: uppercase; margin-bottom: 18px;
        }
        .hero-tag .dot { width: 5px; height: 5px; border-radius: 50%; background: var(--accent); }

        section { padding: 80px 0; }
        .section-tag { display: inline-block; font-size: .68rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--accent); margin-bottom: 14px; }
        .section-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(1.4rem, 2.5vw, 2rem); font-weight: 800; line-height: 1.15; letter-spacing: -.025em; }

        .guide-nav { position: sticky; top: 76px; }
        .guide-nav a { display: block; padding: 8px 14px; border-radius: 8px; color: var(--ink-70); text-decoration: none; font-size: .83rem; font-weight: 500; transition: all .2s; }
        .guide-nav a:hover, .guide-nav a.active { background: var(--accent-light); color: var(--accent); }
        .guide-nav a i { margin-right: 8px; width: 18px; }

        .guide-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 36px; margin-bottom: 28px; scroll-margin-top: 80px; }
        .guide-card h3 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .guide-card h3 .icon { width: 32px; height: 32px; border-radius: 8px; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
        .guide-step { display: flex; gap: 16px; margin-bottom: 18px; }
        .guide-step:last-child { margin-bottom: 0; }
        .step-num { width: 28px; height: 28px; border-radius: 50%; background: var(--accent); color: #fff; font-size: .7rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; }
        .step-content { flex: 1; }
        .step-content strong { display: block; font-size: .92rem; margin-bottom: 2px; }
        .step-content p { font-size: .82rem; color: var(--ink-70); margin: 0; line-height: 1.7; }
        .step-content .badge { display: inline-block; background: var(--accent-light); color: var(--accent); font-size: .62rem; font-weight: 600; padding: 2px 8px; border-radius: 4px; margin-right: 4px; }
        .step-content .badge.orange { background: #fef3e7; color: #be6a1a; }
        .step-content .badge.blue { background: #e8f0fe; color: #1a56be; }

        .guide-card .alert-note { background: #fefce8; border: 1px solid #fde68a; border-radius: 10px; padding: 16px; margin-top: 16px; font-size: .82rem; color: #92400e; display: flex; gap: 10px; align-items: flex-start; }
        .guide-card .alert-note i { flex-shrink: 0; margin-top: 2px; }

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
        .footer-bottom { margin-top: 48px; border-top: 1px solid rgba(255,255,255,.05); padding: 20px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        .footer-bottom p { font-size: .75rem; color: rgba(255,255,255,.2); margin: 0; }

        .reveal { opacity: 0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
        .reveal.in { opacity: 1; transform: none; }

        @media (max-width: 991.98px) { .guide-nav { position: static; margin-bottom: 28px; } .guide-nav-inner { display: flex; flex-wrap: wrap; gap: 6px; } .guide-nav a { font-size: .78rem; padding: 6px 12px; } .footer-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 575.98px) { .footer-grid { grid-template-columns: 1fr; } }
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
            <div class="hero-tag"><span class="dot"></span> Panduan Penggunaan</div>
            <h1>Panduan Lengkap SIAGA</h1>
            <p>Pelajari cara menggunakan fitur-fitur SIAGA langkah demi langkah.</p>
        </div>
    </div>

    <section>
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

                    <div class="guide-card reveal" id="bbm">
                        <h3><span class="icon"><i class="bi bi-fuel-pump"></i></span> Permohonan BBM</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Login ke SIAGA</strong>
                                <p>Akses <a href="/login" style="color:var(--accent)">halaman login</a> dan masuk menggunakan akun yang telah didaftarkan. Setiap pengguna memiliki hak akses sesuai peran masing-masing.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Buka menu BBM</strong>
                                <p>Dari dashboard, buka menu <span class="badge">BBM</span> di sidebar. Pilih jenis kendaraan yang akan diisi BBM.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Isi formulir permohonan</strong>
                                <p>Lengkapi data: kendaraan, tanggal, jumlah liter, jenis BBM (Solar/Pertalite, dll), dan nomor polisi. Pastikan data sesuai dengan kondisi aktual.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">4</div>
                            <div class="step-content">
                                <strong>Submit & tunggu approval</strong>
                                <p>Setelah dikirim, permohonan akan masuk ke antrian approval atasan. Status dapat dipantau di menu <span class="badge blue">Riwayat Permohonan</span>. Jika disetujui, BBM dapat segera digunakan.</p>
                            </div>
                        </div>
                        <div class="alert-note">
                            <i class="bi bi-info-circle"></i>
                            <div>Setiap permohonan BBM akan dicatat dalam sistem untuk keperluan monitoring dan audit internal.</div>
                        </div>
                    </div>

                    <div class="guide-card reveal" id="spd">
                        <h3><span class="icon"><i class="bi bi-file-earmark-text"></i></span> Pengajuan SPD (Surat Perjalanan Dinas)</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Akses menu SPD</strong>
                                <p>Di sidebar dashboard, pilih menu <span class="badge">SPD</span> untuk memulai pengajuan surat perjalanan dinas baru.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Isi detail perjalanan</strong>
                                <p>Masukkan data: tujuan, tanggal berangkat & kembali, kendaraan yang digunakan, serta uraian tugas. Lampirkan dokumen pendukung jika diperlukan.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Approval atasan</strong>
                                <p>Permohonan akan diteruskan ke atasan langsung. Anda akan mendapat notifikasi ketika SPD telah disetujui atau ditolak.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">4</div>
                            <div class="step-content">
                                <strong>Cetak SPD</strong>
                                <p>SPD yang sudah disetujui dapat dicetak langsung dari sistem. Format surat sudah sesuai standar perusahaan — tinggal klik tombol <span class="badge orange">Cetak</span>.</p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-card reveal" id="surat">
                        <h3><span class="icon"><i class="bi bi-envelope-paper"></i></span> Cetak Surat</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Pilih jenis surat</strong>
                                <p>Di menu <span class="badge">Surat</span>, pilih jenis surat yang akan dicetak — tersedia berbagai template seperti surat tugas, surat pengantar, dan surat keterangan.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Isi parameter surat</strong>
                                <p>Lengkapi data yang diperlukan: nomor surat (otomatis atau manual), tanggal, tujuan, perihal, dan isi surat. Beberapa field akan terisi otomatis dari data pengguna.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Pratinjau & cetak</strong>
                                <p>Sebelum mencetak, Anda dapat melihat pratinjau surat. Pastikan data sudah benar, lalu klik <span class="badge orange">Cetak</span>. Surat akan dihasilkan dalam format PDF siap tanda tangan.</p>
                            </div>
                        </div>
                        <div class="alert-note">
                            <i class="bi bi-info-circle"></i>
                            <div>Nomor surat di-generate otomatis oleh sistem berdasarkan format yang telah ditentukan oleh Unit GA.</div>
                        </div>
                    </div>

                    <div class="guide-card reveal" id="dispatcher">
                        <h3><span class="icon"><i class="bi bi-truck"></i></span> Dispatcher & Administrasi Kendaraan</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Atur jadwal kendaraan</strong>
                                <p>Menu <span class="badge">Dispatcher</span> digunakan untuk mengatur penugasan kendaraan operasional. Tentukan kendaraan, driver, dan tujuan.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Monitoring penggunaan</strong>
                                <p>Pantau status kendaraan secara real-time — mana yang sedang digunakan, tersedia, atau dalam perawatan.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Riwayat & pelaporan</strong>
                                <p>Semua data perjalanan tercatat otomatis untuk keperluan pelaporan bulanan dan audit.</p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-card reveal" id="lunch">
                        <h3><span class="icon"><i class="bi bi-cup-hot"></i></span> Booking & Absen Makan Siang</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Booking makan siang</strong>
                                <p>Gunakan menu <span class="badge">Booking Makan Siang</span> di dashboard untuk memilih tanggal dan site (PMU/Patuha/Basecamp/Booking Lama). Tentukan juga preferensi Dine In / Take Away atau Skip.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Absen via QR Code</strong>
                                <p>Saat jam makan siang, scan QR Code yang tersedia di area makan menggunakan kamera atau fitur scan di halaman <span class="badge blue">Absen Makan Siang</span>. Cukup masukkan nama, pilih site, dan konfirmasi.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Self-booking (tanpa login)</strong>
                                <p>Karyawan juga dapat melakukan booking mandiri tanpa login melalui halaman <a href="/my/lunch-plan" style="color:var(--accent)">My Lunch Plan</a>. Cukup cari nama karyawan, pilih tanggal, dan simpan.</p>
                            </div>
                        </div>
                        <div class="alert-note">
                            <i class="bi bi-info-circle"></i>
                            <div>Data absensi makan siang digunakan oleh tim Catering untuk menyiapkan jumlah porsi yang tepat setiap harinya.</div>
                        </div>
                    </div>

                    <div class="guide-card reveal" id="checking">
                        <h3><span class="icon"><i class="bi bi-clipboard-check"></i></span> Routine Checking Kendaraan</h3>
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Pilih jenis checking</strong>
                                <p>Tersedia <span class="badge orange">Daily</span>, <span class="badge blue">Weekly</span>, <span class="badge">Monthly</span>, dan <span class="badge">Body Checking</span>. Pilih sesuai jadwal inspeksi.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Isi formulir inspeksi</strong>
                                <p>Lakukan pengecekan sesuai checklist yang tersedia di form. Setiap item memiliki status: OK / Rusak Ringan / Rusak Berat.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Laporkan temuan</strong>
                                <p>Jika ditemukan kerusakan, sistem akan mencatat dan meneruskan laporan ke unit terkait untuk tindak lanjut perbaikan.</p>
                            </div>
                        </div>
                    </div>

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
        // Scroll spy anchor highlight
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

        // Reveal animation
        const els = document.querySelectorAll('.reveal');
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) { setTimeout(() => e.target.classList.add('in'), i * 120); io.unobserve(e.target); }
            });
        }, { threshold: 0.06, rootMargin: '0px 0px -40px 0px' });
        els.forEach(el => io.observe(el));
    </script>
</body>
</html>
