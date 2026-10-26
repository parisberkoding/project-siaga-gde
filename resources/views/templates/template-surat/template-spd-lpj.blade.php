<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPD &amp; LPJ - {{ $surat->nomor_surat }}</title>
    <style>
        /* ═══════════════════════════════════════════════════════════
           DOKUMEN GABUNGAN SPD + LPJ (A4, margin 3 cm semua sisi)

           Susunan halaman:
             1. Surat Tugas (SPD)
             2. Lembar Ringkasan Penggunaan Biaya Perjalanan Dinas
             3. [KHUSUS GA] Lembar Lampiran Pemberian Dana Perjalanan Dinas
             4. Lembar Persetujuan Penggantian Pertanggungjawaban
             5. Lampiran Penggunaan Dana (foto struk)
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

        /* Judul dokumen LPJ rata kiri (sesuai mock Lembar Ringkasan) */
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 2px;
        }

        .doc-line {
            font-size: 11pt;
            margin: 2px 0 22px;
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

        /* ═══════════════════════════════════════════════════════════
           LEMBAR RINGKASAN PENGGUNAAN BIAYA PERJALANAN DINAS
           ═══════════════════════════════════════════════════════════ */
        table.ringkasan-head {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 18px;
            font-size: 11pt;
        }

        table.ringkasan-head td {
            padding: 2px 4px;
            vertical-align: top;
        }

        table.ringkasan-head .lbl { width: 115px; white-space: nowrap; }
        table.ringkasan-head .sep { width: 14px; }
        table.ringkasan-head .val { width: 34%; }

        .date-block {
            width: 62%;
            border-collapse: collapse;
            margin: 0 0 16px;
            font-size: 10.5pt;
            page-break-inside: avoid;
        }

        .date-block th,
        .date-block td {
            border: 1px solid #000;
            padding: 3px 8px;
            text-align: left;
        }

        .date-block th { font-weight: normal; width: 55%; }
        .date-block td { text-align: right; }

        .date-block tr.grand th,
        .date-block tr.grand td {
            font-weight: bold;
            font-style: italic;
        }

        .ringkasan-total {
            width: 62%;
            border-collapse: collapse;
            font-size: 10.5pt;
            font-weight: bold;
        }

        .ringkasan-total th,
        .ringkasan-total td {
            border: 1px solid #000;
            padding: 4px 8px;
            text-align: left;
        }

        .ringkasan-total td { text-align: right; }

        /* ═══════════════════════════════════════════════════════════
           LEMBAR LAMPIRAN PEMBERIAN DANA PERJALANAN DINAS (Khusus GA)
           ═══════════════════════════════════════════════════════════ */
        table.pencairan {
            width: 100%;
            border-collapse: collapse;
            margin: 22px 0 0;
            font-size: 10pt;
        }

        table.pencairan th,
        table.pencairan td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }

        table.pencairan th {
            font-weight: bold;
            text-align: left;
        }

        table.pencairan th.center { text-align: center; }

        table.pencairan td.num { text-align: right; white-space: nowrap; }
        table.pencairan td.center { text-align: center; }

        table.pencairan tfoot tr.grand td,
        table.pencairan tfoot tr.grand th {
            font-weight: bold;
            font-style: italic;
        }

        /* ═══════════════════════════════════════════════════════════
           LEMBAR PERSETUJUAN PENGGANTIAN PERTANGGUNGJAWABAN
           ═══════════════════════════════════════════════════════════ */
        .pj-frame {
            border: 1.5px solid #000;
            padding: 0;
            margin: 0 0 16px;
        }

        .pj-header {
            text-align: center;
            padding: 8px 10px;
            border-bottom: 1.5px solid #000;
            line-height: 1.35;
        }

        .pj-header .l1 { font-weight: bold; }

        table.pj-info {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
            border-bottom: 1.5px solid #000;
        }

        table.pj-info td {
            padding: 3px 8px;
            vertical-align: top;
        }

        table.pj-info .lbl { width: 130px; white-space: nowrap; }
        table.pj-info .sep { width: 14px; }
        table.pj-info .lbl2 { width: 90px; white-space: nowrap; }

        table.pj-info tr.divider td { border-top: 1px solid #000; }

        .pj-uraian {
            padding: 4px 8px 10px;
            border-bottom: 1.5px solid #000;
            font-size: 10.5pt;
        }

        .pj-caption {
            padding: 6px 8px;
            font-size: 10.5pt;
            border-bottom: 1px solid #000;
        }

        table.pj-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }

        table.pj-table th,
        table.pj-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }

        table.pj-table th { font-weight: bold; }
        table.pj-table td.num { text-align: right; white-space: nowrap; }
        table.pj-table tr.subtotal td,
        table.pj-table tr.subtotal th { font-weight: bold; background: #f7f7f7; }
        table.pj-table tr.total td,
        table.pj-table tr.total th { font-weight: bold; }
        table.pj-table td.right { text-align: right; }
        table.pj-table td.left { text-align: left; }

        .pj-sign {
            display: flex;
            gap: 16px;
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .pj-sign .box {
            flex: 1;
            border: 1.5px solid #000;
            min-height: 190px;
            padding: 8px 10px;
            font-size: 10pt;
            display: flex;
            flex-direction: column;
        }

        .pj-sign .box .t { margin-bottom: 6px; }

        .pj-sign .box .bc {
            width: 78px;
            height: 78px;
            margin: 6px 0 6px;
        }

        .pj-sign .box .bc svg { width: 100%; height: 100%; }

        .pj-sign .box .bc-empty {
            width: 78px;
            height: 78px;
            margin: 6px 0 6px;
            border: 1px dashed #999;
        }

        .pj-sign .box .note {
            font-size: 8.5pt;
            color: #333;
            margin-bottom: 14px;
        }

        .pj-sign .box .nm {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-weight: bold;
            margin-top: auto;
        }

        .pj-sign .box .jb { margin-top: 2px; }

        /* ── Lampiran Penggunaan Dana (foto struk) ── */
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
            border-top: 1px solid #ddd;
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

        .act-btn-ga {
            background: #4338ca;
            color: #fff;
            box-shadow: 0 1px 3px rgba(67, 56, 202, 0.35);
        }

        .act-btn-ga:hover {
            background: #3730a3;
            transform: translateY(-1px);
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

@php
    $kolomBiaya = \App\Models\LpjPenggantian::KOLOM_BIAYA;
    $labelBiaya = \App\Models\StrukItem::KATEGORI_LABEL;
    $rp = fn ($v) => 'Rp. ' . number_format((float) $v, 0, ',', '.');
    $balanceAwal = $laporan->balance_awal;
    $balanceAkhir = $laporan->balance_akhir;
    $periodeDinas = \Carbon\Carbon::parse($surat->tanggal_berangkat)->translatedFormat('d F Y')
        . ' s/d ' . \Carbon\Carbon::parse($surat->tanggal_kembali)->translatedFormat('d F Y');
@endphp

<div class="no-print">
    <div class="toolbar">
        <div class="toolbar-title">
            <span class="doc-badge">DOKUMEN</span>
            <span class="doc-name">Surat SPD &amp; LPJ — {{ $surat->nomor_surat }}</span>
        </div>
        <div class="toolbar-actions">
            @if($includePemberianDana ?? false)
                <a href="{{ route('spd.laporan.pemberian-dana', $laporan) }}" target="_blank" class="act-btn act-btn-ga">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v12"></path>
                        <path d="m7 10 5 5 5-5"></path>
                        <path d="M5 21h14"></path>
                    </svg>
                    Lembar Pemberian Dana (GA)
                </a>
            @endif
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

        {{-- QR / Barcode tanda tangan digital --}}
        @if(!empty($qrSvgContent))
            <div style="width: 70px; height: 70px; margin: 6px 0 0 auto;">
                {!! $qrSvgContent !!}
            </div>
            <div style="font-size: 8px; color: #666; margin-top: 2px;">Scan untuk verifikasi</div>
        @endif

        <div style="margin: 10mm 0 2px; font-weight: bold; text-decoration: underline;">{{ $surat->pemberi_tugas_nama }}</div>
        <div>{{ $surat->pemberi_tugas_jabatan }}</div>
    </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 2 : LEMBAR RINGKASAN PENGGUNAAN BIAYA PERJALANAN DINAS
         ═══════════════════════════════════════════════════════════ --}}
    <div class="page-break"></div>

    <div class="ringkasan-page">
        <div class="doc-title">Lembar Ringkasan Penggunaan Biaya Perjalanan Dinas</div>
        <div class="doc-line">No. Surat Dinas : <strong>{{ $surat->nomor_surat }}</strong></div>

        <table class="ringkasan-head">
            <tr>
                <td class="lbl">Uang Dari</td>
                <td class="sep">:</td>
                <td class="val">{{ $laporan->uang_dari ?: 'KAS' }}</td>
                <td class="lbl">Pengeluaran</td>
                <td class="sep">:</td>
                <td class="val">{{ $rp($laporan->total_pengeluaran) }}</td>
            </tr>
            <tr>
                <td class="lbl">Balance Awal</td>
                <td class="sep">:</td>
                <td class="val">{{ $rp($balanceAwal) }}</td>
                <td class="lbl">Pengembalian</td>
                <td class="sep">:</td>
                <td class="val">{{ $rp($laporan->pengembalian) }}</td>
            </tr>
            <tr>
                <td class="lbl">Balance Akhir</td>
                <td class="sep">:</td>
                <td class="val">
                    {{ $rp($balanceAkhir) }}
                    @if($balanceAkhir < 0)
                        <span style="font-size: 9pt;">(kekurangan / driver menombok)</span>
                    @endif
                </td>
                <td class="lbl">Kekurangan</td>
                <td class="sep">:</td>
                <td class="val">{{ $rp($laporan->kekurangan) }}</td>
            </tr>
        </table>

        {{-- Blok per tanggal — diulang seiring berapa kali input pada tanggalnya --}}
        @forelse($rincian as $row)
            <table class="date-block">
                <tr>
                    <th>Tanggal</th>
                    <td>{{ $row['tanggal'] ? $row['tanggal']->translatedFormat('d F Y') : '-' }}</td>
                </tr>
                @foreach($kolomBiaya as $kolom)
                    <tr>
                        <th>{{ $labelBiaya[$kolom] ?? $kolom }}</th>
                        <td>{{ $rp($row[$kolom]) }}</td>
                    </tr>
                @endforeach
                <tr class="grand">
                    <th>Grand Total</th>
                    <td>{{ $rp($row['subtotal']) }}</td>
                </tr>
            </table>
        @empty
            <p style="font-size: 10.5pt;">Tidak ada rincian pengeluaran pada LPJ ini.</p>
        @endforelse

        @if(!empty($rincian))
            <table class="ringkasan-total">
                <tr>
                    <th>Grand Total Pengeluaran</th>
                    <td>{{ $rp($grandTotalRincian ?? collect($rincian)->sum('subtotal')) }}</td>
                </tr>
            </table>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 3 : [KHUSUS GENERAL AFFAIRS]
                  LEMBAR LAMPIRAN PEMBERIAN DANA PERJALANAN DINAS
         ═══════════════════════════════════════════════════════════ --}}
    @if($includePemberianDana ?? false)
        <div class="page-break"></div>

        <div class="pemberian-page">
            <div class="doc-title">Lembar Lampiran Pemberian Dana Perjalanan Dinas</div>
            <div class="doc-line">No. Surat Dinas : <strong>{{ $surat->nomor_surat }}</strong></div>

            <table class="ringkasan-head">
                <tr>
                    <td class="lbl">Balance Awal</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $rp(($pencairans ?? collect())->sum('nominal')) }}</td>
                    <td class="lbl">Balance Akhir</td>
                    <td class="sep">:</td>
                    <td class="val">
                        {{ $rp(($pencairans ?? collect())->sum('nominal') - (float) $laporan->total_pengeluaran) }}
                    </td>
                </tr>
            </table>

            <table class="pencairan">
                <thead>
                    <tr>
                        <th class="center" style="width: 5%;">No.</th>
                        <th style="width: 16%;">Tanggal</th>
                        <th style="width: 22%;">Nomor Referensi Perbankan</th>
                        <th class="center" style="width: 16%;">Nominal</th>
                        <th style="width: 20%;">Bentuk Pencairan<br><span style="font-weight: normal;">Tunai / Transfer</span></th>
                        <th style="width: 21%;">Rek. Tujuan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pencairans ?? [] as $pencairan)
                        <tr>
                            <td class="center">{{ $loop->iteration }}</td>
                            <td>{{ $pencairan->tanggal ? $pencairan->tanggal->translatedFormat('d F Y') : '-' }}</td>
                            <td>{{ $pencairan->bank_reference ?: '-' }}</td>
                            <td class="num">{{ $rp($pencairan->nominal) }}</td>
                            <td>{{ $pencairan->bentuk_pencairan_label }}</td>
                            <td>{{ $pencairan->isTransfer() ? ($pencairan->rek_tujuan ?: '-') : '- (tunai)' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="center">Belum ada data pencairan dana.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="grand">
                        <th colspan="3" class="left">Grand Total</th>
                        <td class="num">{{ $rp(($pencairans ?? collect())->sum('nominal')) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 4 : LEMBAR PERSETUJUAN
                  PENGGANTIAN PERTANGGUNGJAWABAN
                  PEMBAYARAN BIAYA PERJALANAN DINAS
         ═══════════════════════════════════════════════════════════ --}}
    <div class="page-break"></div>

    <div class="persetujuan-page">
        <div class="pj-frame">
            <div class="pj-header">
                <div class="l1">Lembar Persetujuan</div>
                <div class="l1">Penggantian Pertanggungjawaban</div>
                <div class="l1">Pembayaran biaya perjalanan dinas</div>
            </div>

            <table class="pj-info">
                <tr>
                    <td class="lbl">No. Kendaraan</td>
                    <td class="sep">:</td>
                    <td>{{ $laporan->kendaraan_label }}</td>
                    <td class="lbl2">Tanggal</td>
                    <td class="sep">:</td>
                    <td>{{ $laporan->created_at ? $laporan->created_at->locale('id')->translatedFormat('d F Y H:i') : '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Nama</td>
                    <td class="sep">:</td>
                    <td colspan="4">{{ $laporan->nama ?: $surat->pengemudi_nama }}</td>
                </tr>
                <tr>
                    <td class="lbl">Jabatan</td>
                    <td class="sep">:</td>
                    <td colspan="4">{{ $laporan->jabatan ?: '-' }}</td>
                </tr>
            </table>

            <div class="pj-uraian">
                <strong>Uraian Perjalanan</strong> : {{ $laporan->uraian ?: '-' }}
            </div>

            <div class="pj-caption">Tanggal : {{ $periodeDinas }}</div>

            <table class="pj-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">No.</th>
                        <th style="width: 16%;">Tanggal</th>
                        @foreach($kolomBiaya as $kolom)
                            <th>{{ $labelBiaya[$kolom] ?? $kolom }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rincian as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['tanggal'] ? $row['tanggal']->translatedFormat('d M Y') : '-' }}</td>
                            @foreach($kolomBiaya as $kolom)
                                <td class="num">{{ $row[$kolom] > 0 ? $rp($row[$kolom]) : '' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($kolomBiaya) + 2 }}">Tidak ada rincian pengeluaran.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="subtotal">
                        <td class="left" colspan="2">&lt;end of pada tanggal itu&gt;</td>
                        @foreach($kolomBiaya as $kolom)
                            <td class="num">{{ $rp($spending[$kolom] ?? 0) }}</td>
                        @endforeach
                    </tr>
                    <tr class="total">
                        <td class="right" colspan="7">Grand Total</td>
                        <td class="num">{{ $rp($grandTotalRincian ?? collect($rincian)->sum('subtotal')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="pj-sign">
            <div class="box">
                <div class="t">Menyetujui Pengguna</div>

                @if(!empty($qrPengguna))
                    <div class="bc">{!! $qrPengguna !!}</div>
                @else
                    <div class="bc-empty"></div>
                @endif

                <div class="note">
                    Barcode dari salah satu orang yang berangkat SPD kecuali driver.
                </div>

                <div class="nm">{{ $laporan->approverPengguna?->nama_lengkap ?: '_________________' }}</div>
                <div class="jb">{{ $laporan->approverPengguna?->jabatan ?: '_________________' }}</div>
            </div>

            <div class="box">
                <div class="t">Menyetujui Divisi GA</div>

                @if(!empty($qrDivisiGa))
                    <div class="bc">{!! $qrDivisiGa !!}</div>
                @else
                    <div class="bc-empty"></div>
                @endif

                <div class="note">
                    Barcode dari salah satu divisi GA dari unit yang sama dengan unit driver dan pengguna.
                </div>

                <div class="nm">{{ $laporan->approverDivisiGa?->nama_lengkap ?: '_________________' }}</div>
                <div class="jb">{{ $laporan->approverDivisiGa?->jabatan ?: '_________________' }}</div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PAGE 5+ : LAMPIRAN PENGGUNAAN DANA (FOTO-FOTO STRUK)
         ═══════════════════════════════════════════════════════════ --}}
    @if($photoGrid->isNotEmpty())
        @foreach($photoGrid as $pageIndex => $page)
        <div class="page-break"></div>

        <div class="photo-page-header">
            <h2>Lampiran Penggunaan Dana</h2>
            <div class="desc">
                Bukti foto struk — {{ $surat->nomor_surat }} — Halaman {{ $pageIndex + 1 }} dari {{ $photoGrid->count() }}
            </div>
            <div class="pjum-date">
                Tanggal PJUM: <strong>{{ \Carbon\Carbon::parse($laporan->tanggal_lpj)->locale('id')->translatedFormat('l, d F Y') }}</strong>
            </div>
        </div>

        <div class="photo-grid">
            @php $prevKategori = null; @endphp
            @foreach($page as $entry)
                @if($prevKategori !== null && $entry['item']->struk_kategori !== $prevKategori)
                    <div class="grid-divider">{{ $labelBiaya[$entry['item']->struk_kategori] ?? $entry['item']->struk_kategori }}</div>
                @endif
                <div class="items">
                    {{-- Foto ditampilkan lebih dulu, info ringkas di bawahnya --}}
                    <div class="photo-wrap">
                        @if($entry['image_base64'])
                            <img src="data:{{ $entry['mime'] }};base64,{{ $entry['image_base64'] }}" alt="Struk">
                        @else
                            <div class="no-photo">Foto tidak tersedia</div>
                        @endif
                    </div>
                    <div class="credential">
                        <div><span class="cat">Jenis Lampiran :</span> {{ $entry['item']->struk_kategori }}</div>
                        <div><span class="cat">Nominal :</span> {{ $rp($entry['item']->nominal) }}</div>
                        <div><span class="cat">Tanggal Upload :</span>
                            {{ $entry['item']->created_at ? $entry['item']->created_at->locale('id')->translatedFormat('l, d F Y') : '-' }}
                        </div>
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
