<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Checklist Body – {{ $vehicle->consumerial_code ?? '' }} – {{ $periodLabel ?? '' }}</title>
<style>
    /* ================================================================
       PAGE — A4 PORTRAIT
    ================================================================ */
    @page {
        size: A4 portrait;
        margin-top: 0; margin-bottom: 0;
        margin-left: 0; margin-right: 0;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 9pt; color: #111;
    }
    .page-wrap { padding: 30px 32px 30px 32px; }

    /* ================================================================
       HEADER — sama persis dengan template BBM
    ================================================================ */
    .header-logo-table  { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
    .header-logo-table td { border: none; padding: 0; vertical-align: middle; }
    .header-logo-img    { width: 22mm; padding-right: 4mm; }
    .header-logo-img img{ width: 20mm; height: auto; display: block; }
    .header-company-name{ font-size: 13pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5pt; color: #111; line-height: 1.2; }
    .header-company-sub { font-size: 8.5pt; color: #444; margin-top: 1mm; }
    .header-company-addr{ font-size: 7pt;   color: #777; margin-top: 1mm; line-height: 1.5; }
    .header-title-wrap  { text-align: center; margin-bottom: 2mm; }
    .header-title       { font-size: 12pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8pt; color: #111; }
    .header-subtitle    { font-size: 8.5pt; color: #555; margin-top: 1mm; }
    .header-divider-thick { border: none; border-top: 3px solid #1a56db; margin: 2mm 0 0.5mm 0; }
    .header-divider-thin  { border: none; border-top: 1px solid #7aadff; margin: 0 0 4mm 0; }

    /* ================================================================
       META BOX
    ================================================================ */
    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 4mm; border: 1px solid #d0d8f0; background: #f5f8ff; }
    .meta-table td { border: none; padding: 1.2mm 3mm; font-size: 7.5pt; color: #333; }
    .meta-table .ml { font-weight: bold; color: #1a56db; width: 24%; }
    .meta-table .ms { width: 2%; color: #999; }
    .meta-table .mv { width: 24%; }

    /* ================================================================
       CARD STYLE — persis seperti kartu transaksi BBM
    ================================================================ */
    .card-outer {
        border: 1px solid #333;
        margin-bottom: 4mm;
        page-break-inside: avoid;
    }
    .card-head {
        background: #1a56db; color: #fff;
        padding: 1.5mm 3mm;
    }
    .card-head-inner { width: 100%; border-collapse: collapse; }
    .card-head-inner td { border: none; padding: 0; color: #fff; vertical-align: middle; }
    .card-date-no  { font-size: 8pt; font-weight: bold; }
    .card-sub-date { font-size: 6pt; color: #cce0ff; }
    .card-badge {
        font-size: 6.5pt; font-weight: bold;
        padding: 0.5mm 2.5mm; border-radius: 2px;
    }
    .badge-baik   { background: #16a34a; }
    .badge-cukup  { background: #d97706; }
    .badge-kurang { background: #ea580c; }
    .badge-tidak  { background: #dc2626; }
    .badge-mixed  { background: #7c3aed; }

    /* ── CARD BODY: info kiri + hasil cek kanan ── */
    .card-body { width: 100%; border-collapse: collapse; }
    .card-body > tr > td { border: none; vertical-align: top; padding: 0; }

    .col-info {
        width: 42%;
        padding: 2mm 2mm 2mm 3mm;
        border-right: 1px solid #e0e0e0;
    }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table td { border: none; padding: 0.6mm 0; vertical-align: top; }
    .info-lbl {
        font-size: 5.5pt; font-weight: bold; color: #666;
        text-transform: uppercase; white-space: nowrap;
        padding-right: 1.5mm; width: 36%;
    }
    .info-val       { font-size: 7.5pt; }
    .info-val strong{ font-size: 7.5pt; }
    .info-sub       { font-size: 6pt; color: #777; }
    .info-section-label {
        font-size: 5pt; font-weight: bold; color: #1a56db;
        text-transform: uppercase; letter-spacing: 0.3pt;
        padding-top: 1mm; padding-bottom: 0.3mm;
    }
    .info-divider td { padding: 0.5mm 0; }
    .info-divider-line { border: none; border-top: 1px dashed #ddd; }

    /* ── HASIL CHECKLIST KANAN ── */
    .col-checklist { width: 58%; padding: 2mm 3mm; }

    .checklist-inner { width: 100%; border-collapse: collapse; }
    .checklist-inner th, .checklist-inner td {
        border: 0.5px solid #bbb;
        padding: 1.5px 3px;
        font-size: 7pt;
        vertical-align: middle;
    }
    .checklist-inner th {
        background: #1a56db; color: #fff;
        font-weight: bold; text-align: center;
    }
    .col-ci-no  { width: 18px; text-align: center; }
    .col-ci-itm { text-align: left; }
    .col-ci-st  { width: 30px; text-align: center; }
    .col-ci-kt  { width: 70px; text-align: left; font-size: 6.5pt; }

    .ci-even td { background: #f5f8ff; }

    /* status cell fills */
    .st-baik   { background: #dcfce7; color: #166534; font-weight: bold; }
    .st-cukup  { background: #fef3c7; color: #92400e; font-weight: bold; }
    .st-kurang { background: #ffedd5; color: #9a3412; font-weight: bold; }
    .st-tidak  { background: #fee2e2; color: #dc2626; font-weight: bold; }
    .st-empty  { color: #ccc; }

    /* ── SUMMARY SCORE BAR ── */
    .score-bar-wrap { margin-top: 2mm; }
    .score-bar-label { font-size: 7pt; color: #555; margin-bottom: 1mm; }
    .score-bar-outer {
        width: 100%; height: 8px; background: #e5e7eb;
        border-radius: 4px; overflow: hidden;
    }
    .score-bar-fill { height: 100%; border-radius: 4px; }

    /* ── CATATAN BOX ── */
    .notes-box {
        border: 1px dashed #bbb; background: #fafbff;
        padding: 2mm 3mm; font-size: 7.5pt; margin-top: 2mm;
        min-height: 10mm;
    }

    /* ================================================================
       SIGN OFF TABLE
    ================================================================ */
    .sign-table { width: 100%; border-collapse: collapse; margin-top: 5mm; }
    .sign-table td { border: 1px solid #bbb; padding: 2mm 3mm; text-align: center; font-size: 8.5pt; vertical-align: middle; }
    .sign-header td { background: #1a56db; color: #fff; font-weight: bold; height: 18px; }
    .sign-space  td { height: 55px; }
    .sign-name   td { background: #f0f4ff; font-weight: bold; height: 16px; font-size: 8pt; }

    /* ================================================================
       FOOTER
    ================================================================ */
    .footer-text { text-align: center; font-size: 7pt; color: #666; padding: 4mm 0 0 0; line-height: 1.8; }
    .page-break  { page-break-after: always; }

    /* ================================================================
       LEGEND
    ================================================================ */
    .legend-bar {
        font-size: 7pt; color: #555;
        background: #f5f8ff; padding: 2px 6px;
        border: 1px solid #d0d8f0; margin-bottom: 3mm;
    }
</style>
</head>
<body>
<div class="page-wrap">

@php
    $logoPath   = public_path('DashboardTemplates/assets/img/geodipa-logo.png');
    $logoBase64 = file_exists($logoPath)
                    ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                    : '';

    $bodyItems = [
        1  => 'Head Lamp',
        2  => 'Fog Lamp',
        3  => 'AC',
        4  => 'Klakson',
        5  => 'Lampu Jarak Jauh',
        6  => 'Sein',
        7  => 'Lampu Rem',
        8  => 'Rem',
        9  => 'Seatbelt',
        10 => 'Lain - lain',
    ];

    {{--
        $vehicle     = VehicleAndToolsConsumers
        $checkings   = Collection of VehicleCheckingDetail (type=body), relasi:
                       vehicleChecking, dispatcher, currentDriver, itemResults.vehiclePart
        $periodStart = Carbon
        $periodLabel = string
    --}}

    $totalChecks = $checkings->count();
    $overallBaik = 0; $overallTotal = 0;
    foreach ($checkings as $det) {
        foreach ($det->itemResults as $res) {
            $overallTotal++;
            if ($res->status === 'baik') $overallBaik++;
        }
    }
    $overallScore  = $overallTotal > 0 ? round($overallBaik / $overallTotal * 100, 1) : 0;
    $overallColor  = $overallScore >= 85 ? '#16a34a' : ($overallScore >= 70 ? '#2563eb' : ($overallScore >= 50 ? '#d97706' : '#dc2626'));
@endphp

{{-- ── HEADER ──────────────────────────────────────────────────── --}}
<table class="header-logo-table">
    <tr>
        @if($logoBase64)
        <td class="header-logo-img"><img src="{{ $logoBase64 }}" alt="Logo"></td>
        @endif
        <td>
            <div class="header-company-name">PT. Geo Dipa Energi (Persero)</div>
            <div class="header-company-sub">Unit Patuha</div>
            <div class="header-company-addr">
                Jl. Raya Rancabolang KM. 14, Kp. Kendeng – Ds. Sugihmukti,
                Kec. Pasir Jambu – Kab. Bandung 40973
            </div>
        </td>
    </tr>
</table>
<div class="header-title-wrap">
    <div class="header-title">Pengecekan Umum &amp; Body Kendaraan</div>
    <div class="header-subtitle">
        Kendaraan: <strong>{{ $vehicle->consumerial_code }}</strong>
        &nbsp;·&nbsp; {{ $vehicle->consumerial_name ?? '-' }}
        &nbsp;·&nbsp; Periode: {{ $periodLabel }}
    </div>
</div>
<hr class="header-divider-thick">
<hr class="header-divider-thin">

{{-- ── META BOX ─────────────────────────────────────────────────── --}}
<table class="meta-table">
    <tr>
        <td class="ml">No. Polisi</td>
        <td class="ms">:</td>
        <td class="mv" style="font-weight:bold;">{{ $vehicle->consumerial_code }}</td>
        <td class="ml">Total Pengecekan Body</td>
        <td class="ms">:</td>
        <td class="mv">{{ $totalChecks }} kali dalam periode ini</td>
    </tr>
    <tr>
        <td class="ml">Jenis Kendaraan</td>
        <td class="ms">:</td>
        <td class="mv">{{ $vehicle->consumerial_name ?? '-' }}</td>
        <td class="ml">Score Kondisi Rata-rata</td>
        <td class="ms">:</td>
        <td class="mv" style="font-weight:bold; color:{{ $overallColor }};">{{ $overallScore }}%</td>
    </tr>
</table>

{{-- ── LEGEND ───────────────────────────────────────────────────── --}}
<div class="legend-bar">
    <strong>Keterangan :</strong>&nbsp;
    <span style="background:#dcfce7; color:#166534; padding:0 4px; border-radius:2px; font-weight:bold;">Baik</span> = Kondisi prima &nbsp;
    <span style="background:#fef3c7; color:#92400e; padding:0 4px; border-radius:2px; font-weight:bold;">Cukup</span> = Masih layak pakai &nbsp;
    <span style="background:#ffedd5; color:#9a3412; padding:0 4px; border-radius:2px; font-weight:bold;">Kurang</span> = Perlu perhatian &nbsp;
    <span style="background:#fee2e2; color:#dc2626; padding:0 4px; border-radius:2px; font-weight:bold;">Tidak</span> = Tidak layak / perlu penggantian
</div>

{{-- ── KARTU PER PENGECEKAN — mengikuti gaya kartu BBM ────────────
     2 kartu per baris (chunk 2), sama persis seperti card-table BBM
──────────────────────────────────────────────────────────────────── --}}
<table style="width:100%; border-collapse:collapse; table-layout:fixed;">
    <tbody>
    @foreach($checkings->chunk(2) as $rowPair)
    <tr>
        @foreach($rowPair as $checking)
        @php
            $results   = $checking->itemResults->keyBy('vehicle_part_id');
            $dispatcher= $checking->dispatcher;
            $driver    = $checking->currentDriver;

            $cBaik   = $checking->itemResults->where('status','baik')->count();
            $cCukup  = $checking->itemResults->where('status','cukup')->count();
            $cKurang = $checking->itemResults->where('status','kurang')->count();
            $cTidak  = $checking->itemResults->where('status','tidak')->count();
            $cTotal  = $checking->itemResults->count();
            $cScore  = $cTotal > 0 ? round($cBaik / $cTotal * 100) : 0;
            $cColor  = $cScore >= 85 ? '#16a34a' : ($cScore >= 70 ? '#2563eb' : ($cScore >= 50 ? '#d97706' : '#dc2626'));
            $hasRepl = $checking->itemResults->where('needs_replacement', true)->isNotEmpty();

            $worstStatus = 'baik';
            if ($cTidak > 0)       $worstStatus = 'tidak';
            elseif ($cKurang > 0)  $worstStatus = 'kurang';
            elseif ($cCukup > 0)   $worstStatus = 'cukup';
        @endphp
        <td style="border:1px solid #333; width:50%; vertical-align:top; padding:0;">

            {{-- CARD HEAD — biru seperti BBM --}}
            <div class="card-head">
                <table class="card-head-inner">
                    <tr>
                        <td>
                            <div class="card-date-no">
                                Pengecekan #{{ $checking->checking_detail_code }}
                            </div>
                            <div class="card-sub-date">
                                {{ $checking->checked_at ? $checking->checked_at->translatedFormat('d F Y, H:i') : '-' }} WIB
                            </div>
                        </td>
                        <td style="text-align:right;">
                            @if($hasRepl)
                                <span class="card-badge badge-tidak">⚠ ADA PENGGANTIAN</span>
                            @else
                                <span class="card-badge badge-{{ $worstStatus }}">
                                    {{ strtoupper($worstStatus) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            {{-- CARD BODY --}}
            <table class="card-body">
                <tr>
                    {{-- KOLOM KIRI: info --}}
                    <td class="col-info">
                        <table class="info-table">
                            <tr>
                                <td class="info-lbl">Dispatcher</td>
                                <td class="info-val"><strong>{{ $dispatcher->nama_lengkap ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="info-lbl">Pengemudi</td>
                                <td class="info-val"><strong>{{ $driver->nama_lengkap ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="info-lbl">Divisi</td>
                                <td class="info-val">{{ $driver->divisi ?? '-' }}</td>
                            </tr>
                            <tr class="info-divider">
                                <td colspan="2"><hr class="info-divider-line"></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="info-section-label">Ringkasan Hasil</td>
                            </tr>
                            <tr>
                                <td class="info-lbl">Baik</td>
                                <td class="info-val" style="color:#166534; font-weight:bold;">{{ $cBaik }} item</td>
                            </tr>
                            <tr>
                                <td class="info-lbl">Cukup</td>
                                <td class="info-val" style="color:#92400e;">{{ $cCukup }} item</td>
                            </tr>
                            <tr>
                                <td class="info-lbl">Kurang</td>
                                <td class="info-val" style="color:#9a3412;">{{ $cKurang }} item</td>
                            </tr>
                            <tr>
                                <td class="info-lbl">Tidak</td>
                                <td class="info-val" style="color:#dc2626; font-weight:bold;">{{ $cTidak }} item</td>
                            </tr>
                            <tr class="info-divider">
                                <td colspan="2"><hr class="info-divider-line"></td>
                            </tr>
                            <tr>
                                <td class="info-lbl">Score</td>
                                <td class="info-val" style="color:{{ $cColor }}; font-weight:bold; font-size:9pt;">
                                    {{ $cScore }}%
                                </td>
                            </tr>
                            @if($hasRepl)
                            <tr>
                                <td colspan="2" style="color:#dc2626; font-size:6.5pt; font-weight:bold; padding-top:1mm;">
                                    ⚠ Terdapat item perlu penggantian
                                </td>
                            </tr>
                            @endif
                            @if($checking->notes)
                            <tr class="info-divider">
                                <td colspan="2"><hr class="info-divider-line"></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="info-section-label">Catatan</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="info-val">{{ $checking->notes }}</td>
                            </tr>
                            @endif
                        </table>

                        {{-- Score bar --}}
                        <div class="score-bar-wrap">
                            <div class="score-bar-label">Kondisi keseluruhan: <strong style="color:{{ $cColor }};">{{ $cScore }}%</strong></div>
                            <div class="score-bar-outer">
                                <div class="score-bar-fill" style="width:{{ $cScore }}%; background:{{ $cColor }};"></div>
                            </div>
                        </div>
                    </td>

                    {{-- KOLOM KANAN: tabel detail item --}}
                    <td class="col-checklist">
                        <table class="checklist-inner">
                            <thead>
                                <tr>
                                    <th class="col-ci-no">No</th>
                                    <th class="col-ci-itm">Item</th>
                                    <th class="col-ci-st">Status</th>
                                    <th class="col-ci-kt">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bodyItems as $partNo => $itemLabel)
                                    @php
                                        $res    = $results->get($partNo);
                                        $status = $res ? $res->status : null;
                                        $stCls  = $status ? 'st-' . $status : 'st-empty';
                                        $stTxt  = match($status) {
                                            'baik'   => 'BAIK',
                                            'cukup'  => 'CUKUP',
                                            'kurang' => 'KURANG',
                                            'tidak'  => 'TIDAK',
                                            default  => '—',
                                        };
                                    @endphp
                                    <tr class="{{ $loop->even ? 'ci-even' : '' }}">
                                        <td class="col-ci-no">{{ $partNo }}</td>
                                        <td class="col-ci-itm">{{ $itemLabel }}</td>
                                        <td class="col-ci-st {{ $stCls }}">{{ $stTxt }}</td>
                                        <td class="col-ci-kt">
                                            {{ $res->notes ?? '' }}
                                            @if($res && $res->needs_replacement)
                                                <span style="color:#dc2626; font-weight:bold;">⚠</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

        </td>
        @endforeach

        {{-- padding kalau ganjil --}}
        @if($rowPair->count() === 1)
        <td style="border:1px solid #ccc; width:50%;"></td>
        @endif
    </tr>
    @endforeach
    </tbody>
</table>

{{-- ── TANDA TANGAN ─────────────────────────────────────────────── --}}
<table class="sign-table">
    <tr class="sign-header">
        <td>Disetujui</td>
        <td>Diperiksa</td>
        <td>Diketahui</td>
    </tr>
    <tr class="sign-space">
        <td></td><td></td><td></td>
    </tr>
    <tr class="sign-name">
        <td>Pengemudi</td>
        <td>Dispatcher</td>
        <td>General Affair</td>
    </tr>
</table>

{{-- ── FOOTER ───────────────────────────────────────────────────── --}}
<div class="footer-text">
    Dokumen dibuat secara otomatis pada {{ now()->translatedFormat('d F Y') }} pukul {{ now()->format('H:i') }} WIB<br>
    Dicetak oleh: {{ Auth::user()->nama_lengkap ?? Auth::user()->username ?? 'System' }}<br>
    <em>* Dokumen ini sah tanpa tanda tangan dan stempel *</em>
</div>

</div>{{-- .page-wrap --}}
</body>
</html>
