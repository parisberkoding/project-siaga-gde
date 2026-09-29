<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPD &amp; LPJ - {{ $surat->nomor_surat }}</title>
    <style>
        /* ═══════════════════════════════════════════════════════════
           DOKUMEN GABUNGAN SPD + LPJ (A4, margin 3 cm semua sisi)
           ═══════════════════════════════════════════════════════════ */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 3cm;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
        }
        
        /* Lebar konten: readable di layar (800-1340px), pas di halaman saat cetak */
        .sheet {
            width: 100%;
            max-width: 1340px;
            margin: 0 auto;
        }

        @media screen {
            .sheet { min-width: 800px; }
        }

        @media print {
            .sheet { min-width: 0; }
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .page-title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 10px 0 4px;
        }

        .page-subtitle {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 14px;
        }

        .intro {
            margin: 12px 0 4px;
            font-weight: bold;
        }

        .closing-text {
            margin-top: 16px;
            text-align: justify;
        }

        /* ── Kop Surat ── */
        .kop {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 6px 0 10px;
        }

        .kop-logo { width: 82px; flex-shrink: 0; }
        .kop-logo img { width: 100%; }

        .kop-text .company {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .kop-text .unit {
            font-size: 13pt;
            font-weight: bold;
            color: #333;
        }

        .kop-text .address {
            font-size: 9.5pt;
            color: #333;
        }

        /* ── Tabel Info Surat / LPJ ── */
        table.info {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }

        table.info td {
            padding: 3px 4px;
            vertical-align: top;
        }

        table.info .label { width: 180px; }

        table.info .value {
            border-bottom: 1px dotted #000;
            min-height: 16px;
        }

        table.info td.dashed {
            border-bottom: 1px dotted #000;
        }

        /* ── Daftar Nama Penumpang ── */
        table.nama {
            width: calc(100% - 190px);
            margin-left: 190px;
            border-collapse: collapse;
        }

        table.nama td {
            padding: 2px 4px;
            vertical-align: top;
        }

        table.nama .no { width: 30px; }
        table.nama .nm { border-bottom: 1px dotted #000; }

        /* ── Ringkasan LPJ (Page 2) ── */
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
            margin: 10px 0 16px;
        }

        .summary-grid .item-label {
            font-weight: bold;
        }

        .box {
            border: 1.2px solid #000;
            border-radius: 4px;
            padding: 12px 16px;
            margin: 12px 0;
            page-break-inside: avoid;
        }

        .box h3 {
            font-size: 11pt;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 8px;
        }

        /* ── Tabel Rincian (Page 3) ── */
        table.rincian {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 10pt;
        }

        table.rincian th,
        table.rincian td {
            border: 1px solid #000;
            padding: 5px 6px;
            text-align: center;
        }

        table.rincian th { background: #f0f0f0; font-weight: bold; }
        table.rincian td.num { text-align: right; }
        table.rincian tr.total td { font-weight: bold; background: #f7f7f7; }

        /* ── Halaman Surat SPD — harus muat 1 lembar A4 ── */
        .spd-page {
            font-size: 10.5pt;
            line-height: 1.25;
        }

        .spd-page .page-title {
            font-size: 12pt;
            margin: 6px 0 2px;
        }

        .spd-page .page-subtitle {
            font-size: 9.5pt;
            margin-bottom: 8px;
        }

        .spd-page .intro {
            margin: 8px 0 2px;
        }

        .spd-page table.info td {
            padding: 1px 4px;
        }

        .spd-page table.nama td {
            padding: 1px 4px;
        }

        .spd-page .closing-text {
            margin-top: 10px;
        }

        .spd-page .signature-loc {
            margin-top: 20px;
        }

        /* ── Halaman Rincian — TTD selalu di bawah halaman ── */
        .rincian-page {
            display: flex;
            flex-direction: column;
            min-height: 237mm;
        }

        .rincian-page .ttd-wrap {
            margin-top: auto;
            padding-top: 34px;
        }

        /* ── Tanda Tangan ── */
        .ttd-wrap {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            page-break-inside: avoid;
        }

        .ttd {
            flex: 1;
            text-align: center;
            min-height: 150px;
        }

        .ttd .title { font-weight: bold; margin-bottom: 64px; }
        .ttd .name {
            border-top: 1px solid #000;
            padding-top: 6px;
            font-weight: bold;
        }
        .ttd .role { font-size: 9.5pt; margin-top: 2px; }

        /* ── Lampiran Foto (masonry grid) ── */
        .photo-page-header {
            text-align: center;
            margin: 4px 0 14px;
        }

        .photo-page-header h2 {
            font-size: 13pt;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .photo-page-header .desc {
            font-size: 10pt;
            margin-top: 2px;
        }

        .photo-page-header .pjum-date {
            font-size: 10.5pt;
            margin-top: 4px;
        }

        .items .credential .edit-note {
            margin: 3px 0 2px;
            padding: 4px 6px;
            border: 1px dashed #c00;
            border-radius: 3px;
            background: #fff5f5;
            font-size: 8.5pt;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            align-items: start;
            width: 100%;
        }

        .photo-grid .grid-divider {
            grid-column: 1 / -1;
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #a00;
            border-bottom: 1px solid #a00;
            padding: 2px 0;
            margin-top: 2px;
        }

        .photo-grid .items {
            display: flex;
            flex-direction: column;
            border: 1px solid #999;
            border-radius: 6px;
            overflow: hidden;
            background: #fff;
            page-break-inside: avoid;
        }

        .items .credential {
            padding: 8px 10px;
            font-size: 9pt;
            line-height: 1.4;
            background: #fafafa;
            border-bottom: 1px solid #ddd;
        }

        .items .credential .cat {
            font-weight: bold;
            text-transform: uppercase;
            color: #a00;
        }

        .items .credential .code {
            font-family: 'Courier New', monospace;
            font-size: 8pt;
            color: #444;
        }

        .items .photo-wrap {
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            background: #fff;
        }

        .items .photo-wrap img {
            max-width: 100%;
            max-height: 240px;
            object-fit: contain;
        }

        .items .no-photo {
            color: #888;
            font-size: 9pt;
            padding: 20px;
        }

        /* ── Toolbar cetak (hidden saat cetak) ── */
        .no-print {
            position: sticky;
            top: 12px;
            z-index: 100;
            margin: 0 0 20px;
            padding: 12px 18px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
        }

        .toolbar {
            max-width: 21cm;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .toolbar-title {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .toolbar-title .doc-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.03em;
            border-radius: 999px;
            white-space: nowrap;
        }

        .toolbar-title .doc-name {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .act-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
            padding: 10px 18px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .act-btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .act-btn-print {
            background: #16a34a;
            color: #fff;
            box-shadow: 0 1px 3px rgba(22, 163, 74, 0.35);
        }

        .act-btn-print:hover {
            background: #15803d;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(22, 163, 74, 0.3);
        }

        .act-btn-close {
            background: #ffffff;
            color: #374151;
            border-color: #d1d5db;
        }

        .act-btn-close:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            transform: translateY(-1px);
        }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .signature-barcode svg { width: 100%; height: 100%; }
        }

        @media screen {
            body { background: #E4E9F0; padding: 20px; }
            .sheet {
                background: #fff;
                box-shadow: 0 0 15px rgba(0,0,0,0.2);
                padding: 2.2cm 3cm;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <div class="toolbar">
        <div class="toolbar-title">
            <span class="doc-badge">DOKUMEN</span>
            <span class="doc-name">Surat SPD &amp; LPJ — {{ $surat->nomor_surat }}</span>
        </div>
        <div class="toolbar-actions">
            <button onclick="window.print()" class="act-btn act-btn-print">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" class="act-btn act-btn-close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                Tutup
            </button>
        </div>
    </div>
</div>

<div class="sheet">

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 1 : SURAT TUGAS (SPD) — template lengkap dari template-spd
         ═══════════════════════════════════════════════════════════ --}}
    <div class="spd-page">
    <div class="kop">
        <div class="kop-logo">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo">
            @endif
        </div>
        <div class="kop-text">
            <div class="company">Geo Dipa Energi</div>
            <div class="unit">{{ $surat->user?->unit?->name ?: ('Unit '.($surat->user?->defaultSite?->name ?: 'Patuha')) }}</div>
        </div>
    </div>

    <div class="page-title">Surat Tugas</div>
    <div class="page-subtitle">Nomor: <strong>{{ $surat->nomor_surat }}</strong></div>

    <p class="intro">Yang bertanda tangan dibawah ini,</p>

    <table class="info">
        <tr>
            <td class="label">Nama</td>
            <td>:</td>
            <td class="value">{{ $surat->pemberi_tugas_nama }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td>:</td>
            <td class="value">{{ $surat->pemberi_tugas_jabatan }}</td>
        </tr>
    </table>

    <p class="intro">Dengan ini menugaskan kepada,</p>

    <table class="info">
        <tr>
            <td class="label">Nama Pengemudi</td>
            <td>:</td>
            <td class="value">{{ $surat->pengemudi_nama }}</td>
        </tr>
        <tr>
            <td class="label">Tempat Tujuan</td>
            <td>:</td>
            <td class="value">{{ $surat->tempat_tujuan }}</td>
        </tr>
        <tr>
            <td class="label">Melayani Bidang</td>
            <td>:</td>
            <td class="value">{{ $surat->melayani_bidang }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Pemakai</td>
            <td>:</td>
            <td class="value">{{ $surat->jumlah_pemakai }} orang</td>
        </tr>
        <tr>
            <td class="label">Nama Lengkap</td>
            <td>:</td>
            <td class="value"></td>
        </tr>
    </table>

    <table class="nama">
        @foreach($surat->details->take(7) as $index => $detail)
        <tr>
            <td class="no">{{ $index + 1 }}.</td>
            <td class="nm">{{ $detail->nama_lengkap }}</td>
        </tr>
        @endforeach
        @for($i = $surat->details->count(); $i < 7; $i++)
        <tr>
            <td class="no">{{ $i + 1 }}.</td>
            <td class="nm">&nbsp;</td>
        </tr>
        @endfor
    </table>

    <table class="info" style="margin-top: 12px;">
        <tr>
            <td class="label">Lama Perjalanan Dinas</td>
            <td>:</td>
            <td class="value">{{ $surat->lama_perjalanan_hari }} hari</td>
        </tr>
        <tr>
            <td class="label">Tanggal Berangkat</td>
            <td>:</td>
            <td class="value">{{ \Carbon\Carbon::parse($surat->tanggal_berangkat)->locale('id')->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Kembali</td>
            <td>:</td>
            <td class="value">{{ \Carbon\Carbon::parse($surat->tanggal_kembali)->locale('id')->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Kendaraan No. Pol.</td>
            <td>:</td>
            <td class="value">{{ $surat->vehicle->consumerial_code }} - {{ $surat->vehicle->consumerial_name }}</td>
        </tr>
    </table>

    <p class="closing-text">Demikian agar dilaksanakan dengan sebaik-baiknya.</p>

    <div style="margin-top: 20px; text-align: right; page-break-inside: avoid;">
        <div>{{ $surat->tanggal_surat }}</div>
        <div style="font-weight: bold; margin-top: 8px;">Yang Memberi Perintah,</div>
        
        <div style="margin: 14mm 0 2px; font-weight: bold; text-decoration: underline; margin-top: 100px;">{{ $surat->pemberi_tugas_nama }}</div>
        <div>{{ $surat->pemberi_tugas_jabatan }}</div>
    </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 2 : RINGKASAN LAPORAN
         ═══════════════════════════════════════════════════════════ --}}
    <div class="page-break"></div>

    <div class="page-title">Ringkasan Laporan</div>
    <div class="page-subtitle">Laporan Pertanggungjawaban - {{ $laporan->lpj_number }}</div>

    <table class="info">
        <tr>
            <td class="label">Nomor Surat Tugas</td>
            <td>:</td>
            <td class="value">{{ $surat->nomor_surat }}</td>
        </tr>
        <tr>
            <td class="label">Pengemudi</td>
            <td>:</td>
            <td class="value">{{ $laporan->nama ?: $surat->pengemudi_nama }}</td>
        </tr>
        <tr>
            <td class="label">Kendaraan</td>
            <td>:</td>
            <td class="value">{{ $laporan->kendaraan_label }}</td>
        </tr>
        <tr>
            <td class="label">Periode SPD</td>
            <td>:</td>
            <td class="value">{{ \Carbon\Carbon::parse($surat->tanggal_berangkat)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($surat->tanggal_kembali)->translatedFormat('d M Y') }} ({{ $surat->lama_perjalanan_hari }} hari)</td>
        </tr>
    </table>

    <div class="box">
        <h3>Rekapitulasi Pengeluaran</h3>
        <div class="summary-grid">
            <div>
                <span class="item-label">Kebutuhan BBM</span><br>
                <span>{{ $laporan->kebutuhan_bbm_liter !== null ? number_format((float) $laporan->kebutuhan_bbm_liter, 0, ',', '.') . ' liter' : '-' }}</span>
            </div>
            <div>
                <span class="item-label">Spending BBM</span><br>
                <span>Rp {{ number_format($spending['BBM'], 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="item-label">Spending Parkir</span><br>
                <span>Rp {{ number_format($spending['Parkir'], 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="item-label">Spending Tol</span><br>
                <span>Rp {{ number_format($spending['Tol'], 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="item-label">Spending Lainnya</span><br>
                <span>Rp {{ number_format($spending['Lainnya'], 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="item-label">Total Pengeluaran</span><br>
                <strong>Rp {{ number_format($laporan->total_pengeluaran ?: array_sum($spending), 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <div class="box">
        <h3>Rincian Tempuh Kendaraan</h3>
        <div class="summary-grid">
            <div>
                <span class="item-label">Odometer sebelum SPD</span><br>
                <span>{{ $laporan->odo_before !== null ? number_format((float) $laporan->odo_before, 0, ',', '.') . ' km' : '-' }}</span>
            </div>
            <div>
                <span class="item-label">Odometer setelah SPD</span><br>
                <span>{{ $laporan->odo_after !== null ? number_format((float) $laporan->odo_after, 0, ',', '.') . ' km' : '-' }}</span>
            </div>
            <div>
                <span class="item-label">Total jarak tempuh</span><br>
                <strong>{{ $laporan->getTotalJarakTempuh() !== null ? number_format($laporan->getTotalJarakTempuh(), 0, ',', '.') . ' km' : '-' }}</strong>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 3 : RINCIAN PENGGUNAAN
         ═══════════════════════════════════════════════════════════ --}}
    <div class="page-break"></div>

    <div class="rincian-page">
    <div class="page-title">Rincian Penggunaan</div>
    <div class="page-subtitle">{{ $surat->nomor_surat }}</div>

    <table class="rincian">
        <thead>
            <tr>
                <th style="width: 22%;">Tanggal</th>
                <th>Tol</th>
                <th>BBM</th>
                <th>Parkir</th>
                <th>Lainnya</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rincian as $row)
            <tr>
                <td>{{ $row['tanggal'] ? $row['tanggal']->translatedFormat('d M Y') : '-' }}</td>
                <td class="num">{{ $row['Tol'] > 0 ? 'Rp ' . number_format($row['Tol'], 0, ',', '.') : '' }}</td>
                <td class="num">{{ $row['BBM'] > 0 ? 'Rp ' . number_format($row['BBM'], 0, ',', '.') : '' }}</td>
                <td class="num">{{ $row['Parkir'] > 0 ? 'Rp ' . number_format($row['Parkir'], 0, ',', '.') : '' }}</td>
                <td class="num">{{ $row['Lainnya'] > 0 ? 'Rp ' . number_format($row['Lainnya'], 0, ',', '.') : '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5">Tidak ada rincian pengeluaran.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total">
                <td>Total</td>
                <td class="num">Rp {{ number_format($spending['Tol'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($spending['BBM'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($spending['Parkir'], 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($spending['Lainnya'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="ttd-wrap">
        <div class="ttd">
            <div class="title">User</div>
            <div class="name">{{ $laporan->nama ?: '_________________' }}</div>
            <div class="role">Pengemudi / User</div>
        </div>
        <div class="ttd">
            <div class="title">General Affairs</div>
            <div class="name">{{ $laporan->approverDivisiGa?->nama_lengkap ?: '_________________' }}</div>
            <div class="role">Divisi GA</div>
        </div>
        <div class="ttd">
            <div class="title">Manager / Asst. Manager</div>
            <div class="name">_________________</div>
            <div class="role">Management</div>
        </div>
    </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 4+ : LAMPIRAN FOTO-FOTO
         ═══════════════════════════════════════════════════════════ --}}
    @if($photoGrid->isNotEmpty())
        @foreach($photoGrid as $pageIndex => $page)
        <div class="page-break"></div>

        <div class="photo-page-header">
            <h2>Lampiran Foto-Foto</h2>
            <div class="desc">
                {{ $surat->nomor_surat }} — Halaman {{ $pageIndex + 1 }} dari {{ $photoGrid->count() }}
            </div>
            <div class="pjum-date">
                Tanggal PJUM: <strong>{{ \Carbon\Carbon::parse($laporan->tanggal_lpj)->locale('id')->translatedFormat('l, d F Y') }}</strong>
            </div>
        </div>

        <div class="photo-grid">
            @php $prevKategori = null; @endphp
            @foreach($page as $entry)
                @if($prevKategori !== null && $entry['item']->struk_kategori !== $prevKategori)
                    <div class="grid-divider">{{ $entry['item']->struk_kategori }}</div>
                @endif
                <div class="items">
                    <div class="credential">
                        @php
                            $meta = $entry['item']->metadata_json ?: [];
                            $volume = isset($meta['volume']) && $meta['volume'] !== '' && $meta['volume'] !== null
                                ? (float) $meta['volume'] : null;
                            $harga = isset($meta['harga_per_liter']) && $meta['harga_per_liter'] !== '' && $meta['harga_per_liter'] !== null
                                ? (float) $meta['harga_per_liter'] : null;
                        @endphp
                        <div><span class="cat">Jenis :</span> {{ $entry['item']->struk_kategori }}
                            @if($volume !== null)<span class="vol"> · {{ number_format($volume, 2, ',', '.') }} L</span>@endif
                        </div>
                        <div><span class="cat">Keterangan :</span> {{ $entry['item']->keterangan ?: '-' }}
                            @if($harga !== null)<span class="vol"> ({{ number_format($harga, 0, ',', '.') }}/L)</span>@endif
                        </div>
                        <div><span class="cat">Nominal :</span> Rp {{ number_format((float) $entry['item']->nominal, 0, ',', '.') }}</div>
                        <div><span class="cat">Tanggal Laporan :</span>
                            {{ $entry['item']->tanggal ? $entry['item']->tanggal->locale('id')->translatedFormat('l, d F Y') : '-' }}
                        </div>
                        @if($entry['item']->edited_at)
                            <div class="edit-note">
                                <div><span class="cat">1. Awal :</span> {{ optional($entry['item']->created_at)->locale('id')->translatedFormat('l, d F Y') }}</div>
                                <div><span class="cat">2. Diedit :</span> {{ $entry['item']->edited_at->locale('id')->translatedFormat('l, d F Y') }}</div>
                            </div>
                        @endif
                        <div class="code">{{ $entry['item']->item_code }}</div>
                    </div>
                    <div class="photo-wrap">
                        @if($entry['image_base64'])
                            <img src="data:{{ $entry['mime'] }};base64,{{ $entry['image_base64'] }}" alt="Struk">
                        @else
                            <div class="no-photo">Foto tidak tersedia</div>
                        @endif
                    </div>
                </div>
                @php $prevKategori = $entry['item']->struk_kategori; @endphp
            @endforeach
        </div>
        @endforeach
    @endif

</div>

<script>
    window.onload = function () {
        setTimeout(function () { window.print(); }, 500);
    };
</script>

</body>
</html>
