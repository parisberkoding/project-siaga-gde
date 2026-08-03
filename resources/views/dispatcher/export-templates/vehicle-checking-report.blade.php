<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Vehicle Checking Report – {{ $periodLabel ?? '' }}</title>
<style>
    /* ================================================================
       PAGE — A4 PORTRAIT (semua section)
       Section routine pakai landscape via page-break trick tapi
       DomPDF tidak bisa mixed → kita render landscape semua atau
       buat section routine tetap terbaca dengan font sangat kecil
       di portrait. Pilihan terbaik: landscape untuk seluruh dokumen
       karena ada grid 31 hari.
    ================================================================ */
    @page {
        size: A4 landscape;
        margin-top: 0; margin-bottom: 0;
        margin-left: 0; margin-right: 0;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 8.5pt; color: #111;
    }
    .page-wrap { padding: 22px 28px 22px 28px; }

    /* ================================================================
       SHARED HEADER STYLES — sama dengan template BBM
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
    .header-subtitle    { font-size: 8pt; color: #555; margin-top: 1mm; }
    .header-divider-thick { border: none; border-top: 3px solid #1a56db; margin: 2mm 0 0.5mm 0; }
    .header-divider-thin  { border: none; border-top: 1px solid #7aadff; margin: 0 0 3mm 0; }

    /* ================================================================
       META BOX
    ================================================================ */
    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 4mm; border: 1px solid #d0d8f0; background: #f5f8ff; }
    .meta-table td { border: none; padding: 1.2mm 3mm; font-size: 7.5pt; color: #333; }
    .meta-table .ml { font-weight: bold; color: #1a56db; width: 20%; }
    .meta-table .ms { width: 2%; color: #999; }
    .meta-table .mv { width: 28%; }

    /* ================================================================
       SECTION TITLE (dark blue bar)
    ================================================================ */
    .sec-title {
        background: #1a56db; color: #fff;
        font-size: 10pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.5pt; padding: 4px 8px;
        margin: 5mm 0 3mm 0;
    }
    .sec-sub {
        background: #dce8ff; color: #1a56db;
        font-size: 8pt; font-weight: bold;
        padding: 2px 8px; margin-bottom: 3mm;
        border-left: 4px solid #1a56db;
    }

    /* ================================================================
       DATA TABLE — persis seperti data-table BBM
    ================================================================ */
    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
    .data-table th {
        background: #1a56db; color: #fff;
        font-size: 7.5pt; font-weight: bold;
        text-align: center; padding: 2mm;
        border: 1px solid #1040a0;
    }
    .data-table td {
        font-size: 7.5pt; padding: 1.5mm 2mm;
        border: 1px solid #aaa; vertical-align: middle;
    }
    .data-table tbody tr:nth-child(even) td { background: #f5f8ff; }
    .data-table tfoot td {
        background: #dce8ff; font-weight: bold;
        font-size: 8pt; border: 1px solid #555;
    }

    /* Health score badge */
    .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 7pt; font-weight: bold; }
    .b-excellent { background: #dcfce7; color: #166534; }
    .b-good      { background: #dbeafe; color: #1e40af; }
    .b-warning   { background: #fef3c7; color: #92400e; }
    .b-critical  { background: #fee2e2; color: #dc2626; }

    /* health bar */
    .hbar { height: 7px; background: #e5e7eb; border-radius: 3px; overflow: hidden; min-width: 50px; }
    .hbar-fill { height: 100%; border-radius: 3px; }

    /* ================================================================
       FINANCIAL TABLE — persis fin-table BBM
    ================================================================ */
    .fin-table { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
    .fin-table td { border: 1px solid #ccc; padding: 2mm 3mm; font-size: 8.5pt; }
    .fin-table .section-row td {
        background: #eef2ff; font-size: 7.5pt; font-weight: bold;
        color: #1a56db; text-transform: uppercase; letter-spacing: 0.3pt;
    }
    .fin-table .total-row td {
        background: #1a56db; color: #fff;
        font-weight: bold; font-size: 10pt;
    }
    .fin-table td:last-child { text-align: right; font-weight: bold; }

    /* ================================================================
       SERVICE CARD (kendaraan sedang servis) — gaya card BBM
    ================================================================ */
    .service-cards { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .service-cards > tbody > tr > td {
        border: 1px solid #333; width: 33.33%;
        vertical-align: top; padding: 0;
    }
    .service-cards > tbody > tr > td.empty-cell { border: 1px solid #ccc; }

    .sc-head {
        background: #dc2626; color: #fff;
        padding: 1.5mm 3mm; font-size: 8pt; font-weight: bold;
    }
    .sc-head-ok { background: #16a34a; }
    .sc-body { padding: 2mm 3mm; }
    .sc-lbl { font-size: 5.5pt; font-weight: bold; color: #666; text-transform: uppercase; white-space: nowrap; }
    .sc-val { font-size: 7.5pt; }

    /* ================================================================
       CHECKLIST TABLE (landscape grid 31 hari) — dipakai di section routine
    ================================================================ */
    .cl-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 4mm; }
    .cl-table th, .cl-table td {
        border: 0.5px solid #999; text-align: center;
        vertical-align: middle; padding: 1.5px 0; overflow: hidden;
    }
    .col-no   { width: 16px; font-size: 7.5pt; }
    .col-item { width: 86px; text-align: left !important; padding-left: 3px !important; font-size: 7pt; }
    .col-per  { width: 36px; font-size: 6pt; }
    .col-day  { width: 7px;  font-size: 6pt; }
    .col-ket  { width: 42px; text-align: left !important; padding-left: 2px !important; font-size: 6pt; }
    .th-blue  { background: #1a56db; color: #fff; font-weight: bold; font-size: 7.5pt; }
    .th-sub   { background: #dce8ff; font-weight: bold; font-size: 6pt; color: #1a56db; }
    .group-row td {
        background: #1a56db; color: #fff; font-weight: bold;
        font-size: 7pt; text-align: left !important; padding-left: 5px !important; height: 12px;
    }
    .item-row td          { height: 12px; font-size: 6.5pt; }
    .item-row:nth-child(odd) td  { background: #fff; }
    .item-row:nth-child(even) td { background: #f5f8ff; }
    .per-h { background: #e8f0ff; color: #1a56db; font-size: 5.5pt; }
    .per-m { background: #fff8e6; color: #b45309; font-size: 5.5pt; }
    .per-b { background: #e6f4ea; color: #166534; font-size: 5.5pt; }
    .cell-ok   { background: #dcfce7; color: #166534; font-weight: bold; font-size: 7pt; }
    .cell-ng   { background: #fee2e2; color: #dc2626; font-weight: bold; font-size: 7pt; }
    .cell-null { color: #e0e0e0; font-size: 6pt; }
    .paraf-row td { background: #f0f4ff; font-weight: bold; font-size: 7pt; height: 22px; }

    /* ================================================================
       BODY CHECKLIST (compact, 2 kendaraan per baris)
    ================================================================ */
    .body-cards { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 4mm; }
    .body-cards > tbody > tr > td {
        border: 1px solid #333; width: 50%;
        vertical-align: top; padding: 0;
    }
    .body-cards > tbody > tr > td.empty-cell { border: 1px solid #ccc; }

    .bcard-head {
        background: #1a56db; color: #fff;
        padding: 1.5mm 3mm;
    }
    .bcard-head-inner { width: 100%; border-collapse: collapse; }
    .bcard-head-inner td { border: none; padding: 0; color: #fff; vertical-align: middle; }
    .bcard-nopol  { font-size: 8.5pt; font-weight: bold; }
    .bcard-sub    { font-size: 6pt; color: #cce0ff; }
    .bcard-score  { font-size: 8.5pt; font-weight: bold; }

    .bcard-table { width: 100%; border-collapse: collapse; }
    .bcard-table th, .bcard-table td {
        border: 0.5px solid #bbb; padding: 1.5px 3px;
        font-size: 7pt; vertical-align: middle;
    }
    .bcard-table th { background: #1a56db; color: #fff; font-weight: bold; text-align: center; }
    .bcard-table tr:nth-child(even) td { background: #f5f8ff; }
    .bc-no  { width: 16px; text-align: center; }
    .bc-itm { text-align: left; }
    .bc-st  { width: 28px; text-align: center; }
    .bc-kt  { width: 80px; text-align: left; font-size: 6pt; }
    .st-baik   { background: #dcfce7; color: #166534; font-weight: bold; }
    .st-cukup  { background: #fef3c7; color: #92400e; font-weight: bold; }
    .st-kurang { background: #ffedd5; color: #9a3412; font-weight: bold; }
    .st-tidak  { background: #fee2e2; color: #dc2626; font-weight: bold; }

    /* ================================================================
       FOOTER
    ================================================================ */
    .footer-text { text-align: center; font-size: 7pt; color: #666; padding: 4mm 0 2mm 0; line-height: 1.8; }
    .page-break  { page-break-before: always; }
</style>
</head>
<body>

@php
    $logoPath   = public_path('DashboardTemplates/assets/img/geodipa-logo.png');
    $logoBase64 = file_exists($logoPath)
                    ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                    : '';

    {{--
      Variabel dari controller (VehicleCheckingSuperadminController):
        $vehicles    = Collection VehicleAndToolsConsumers, sudah di-map dengan:
                         ->health_score, ->active_replacement_count, ->last_checked_at
                         ->checkings->details->itemResults
        $periodLabel = string  e.g. "Maret 2026"
        $periodStart = Carbon  (awal bulan yang dipilih)
        $periodEnd   = Carbon  (akhir bulan)
        $replacements= Collection VehiclePartsReplacement (status: pending/approved/in_service)
                         dengan relasi: vehicle, dispatcher, replacementDetails, transaction
        $serviceTransactions = Collection VehiclePartsReplacementTransaction (bulan ini)
                         dengan relasi: replacement.vehicle, driver, details
    --}}

    $totalVehicles = $vehicles->count();
    $cntExcellent  = $vehicles->filter(fn($v) => $v->health_score >= 85)->count();
    $cntGood       = $vehicles->filter(fn($v) => $v->health_score >= 70 && $v->health_score < 85)->count();
    $cntWarning    = $vehicles->filter(fn($v) => $v->health_score >= 50 && $v->health_score < 70)->count();
    $cntCritical   = $vehicles->filter(fn($v) => $v->health_score < 50)->count();

    $totalServiceCost = $serviceTransactions->sum('total_amount') ?? 0;
    $inServiceCount   = $replacements->whereIn('status',['in_service','scheduled'])->count();

    $itemGroupsDef = [
        'KESIAPAN' => [
            1  => ['label' => 'Mesin',                       'per' => 'Harian',   'cls' => 'per-h'],
            2  => ['label' => 'Solar/Bensin',                'per' => 'Harian',   'cls' => 'per-h'],
            3  => ['label' => 'Ban',                         'per' => 'Harian',   'cls' => 'per-h'],
            4  => ['label' => 'Ban Cadangan',                'per' => 'Harian',   'cls' => 'per-h'],
            5  => ['label' => 'Rem',                         'per' => 'Harian',   'cls' => 'per-h'],
            6  => ['label' => 'Copling/Matic',               'per' => 'Harian',   'cls' => 'per-h'],
            7  => ['label' => 'Electric & Penerangan/Lampu', 'per' => 'Harian',   'cls' => 'per-h'],
            8  => ['label' => 'Olie Rem',                    'per' => 'Harian',   'cls' => 'per-h'],
            9  => ['label' => 'Olie Mesin',                  'per' => 'Harian',   'cls' => 'per-h'],
            10 => ['label' => 'Kondisi AKI/Baterai',         'per' => 'Harian',   'cls' => 'per-h'],
            11 => ['label' => 'Air Radiator',                'per' => 'Harian',   'cls' => 'per-h'],
        ],
        'KESELAMATAN' => [
            12 => ['label' => 'Sabuk Keselamatan',           'per' => 'Mingguan', 'cls' => 'per-m'],
            13 => ['label' => 'Peralatan Emergency/P3K',     'per' => 'Mingguan', 'cls' => 'per-m'],
            14 => ['label' => 'Pemecah Kaca',                'per' => 'Mingguan', 'cls' => 'per-m'],
            15 => ['label' => 'APAR',                        'per' => 'Mingguan', 'cls' => 'per-m'],
            16 => ['label' => 'Donkrak Ban',                 'per' => 'Mingguan', 'cls' => 'per-m'],
        ],
        'KELENGKAPAN' => [
            17 => ['label' => 'Alarm Mundur',                'per' => 'Mingguan', 'cls' => 'per-m'],
            18 => ['label' => 'Dashcam',                     'per' => 'Mingguan', 'cls' => 'per-m'],
            19 => ['label' => 'GPS',                         'per' => 'Mingguan', 'cls' => 'per-m'],
            20 => ['label' => 'Izin Kendaraan/STNK',         'per' => 'Bulanan',  'cls' => 'per-b'],
            21 => ['label' => 'KIR',                         'per' => 'Bulanan',  'cls' => 'per-b'],
        ],
    ];

    $bodyItemsDef = [
        1=>'Head Lamp', 2=>'Fog Lamp', 3=>'AC', 4=>'Klakson',
        5=>'Lampu Jarak Jauh', 6=>'Sein', 7=>'Lampu Rem', 8=>'Rem',
        9=>'Seatbelt', 10=>'Lain - lain',
    ];
@endphp

{{-- ══════════════════════════════════════════════════════════════
     SECTION 1 — OVERVIEW ARMADA
══════════════════════════════════════════════════════════════ --}}
<div class="page-wrap">

    {{-- HEADER --}}
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
        <div class="header-title">Laporan Pengecekan Kendaraan Operasional</div>
        <div class="header-subtitle">
            Periode: {{ $periodLabel }}
            &nbsp;·&nbsp; Total {{ $totalVehicles }} Kendaraan Aktif
        </div>
    </div>
    <hr class="header-divider-thick">
    <hr class="header-divider-thin">

    {{-- META BOX --}}
    <table class="meta-table">
        <tr>
            <td class="ml">Status</td><td class="ms">:</td>
            <td class="mv">Laporan Bulanan</td>
            <td class="ml">Dicetak Oleh</td><td class="ms">:</td>
            <td class="mv">{{ Auth::user()->nama_lengkap ?? 'System' }}</td>
        </tr>
        <tr>
            <td class="ml">Total Kendaraan</td><td class="ms">:</td>
            <td class="mv">{{ $totalVehicles }} unit</td>
            <td class="ml">Tgl Cetak</td><td class="ms">:</td>
            <td class="mv">{{ now()->translatedFormat('d F Y, H:i') }} WIB</td>
        </tr>
        <tr>
            <td class="ml">Total Biaya Servis</td><td class="ms">:</td>
            <td class="mv" style="font-weight:bold; color:#1a56db;">
                Rp {{ number_format($totalServiceCost, 0, ',', '.') }}
            </td>
            <td class="ml">Kendaraan di Servis</td><td class="ms">:</td>
            <td class="mv" style="font-weight:bold; color:{{ $inServiceCount > 0 ? '#dc2626' : '#166534' }};">
                {{ $inServiceCount }} unit
            </td>
        </tr>
    </table>

    {{-- FLEET STATS CARD-STYLE — 4 kotak seperti pada matrix UI --}}
    <div class="sec-title">Status Armada — {{ $periodLabel }}</div>
    <table style="width:100%; border-collapse:collapse; margin-bottom:4mm;">
        <tr>
            @php $statItems = [
                ['Excellent (≥85%)', $cntExcellent, '#16a34a', '#dcfce7', '#166534'],
                ['Good (70–84%)',    $cntGood,      '#2563eb', '#dbeafe', '#1e40af'],
                ['Warning (50–69%)', $cntWarning,   '#d97706', '#fef3c7', '#92400e'],
                ['Critical (<50%)',  $cntCritical,  '#dc2626', '#fee2e2', '#dc2626'],
            ]; @endphp
            @foreach($statItems as $s)
            <td style="border:1px solid #ccc; padding:4mm 6mm; text-align:center; width:25%;
                       background:{{ $s[3] }};">
                <div style="font-size:22pt; font-weight:bold; color:{{ $s[2] }}; line-height:1;">
                    {{ $s[1] }}
                </div>
                <div style="font-size:7.5pt; color:{{ $s[4] }}; margin-top:2mm;">{{ $s[0] }}</div>
                <div style="height:5px; background:#e0e0e0; border-radius:3px; margin-top:2mm; overflow:hidden;">
                    <div style="height:100%; border-radius:3px; background:{{ $s[2] }};
                         width:{{ $totalVehicles > 0 ? round($s[1]/$totalVehicles*100) : 0 }}%;"></div>
                </div>
            </td>
            @endforeach
        </tr>
    </table>

    {{-- TABEL OVERVIEW SEMUA KENDARAAN --}}
    <div class="sec-sub">Tabel Overview Kondisi Kendaraan</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:10%">No. Polisi</th>
                <th style="width:18%">Jenis Kendaraan</th>
                <th style="width:12%">Health Score</th>
                <th style="width:12%">Cek Terakhir</th>
                <th style="width:9%">Body Cek</th>
                <th style="width:9%">Cek Harian</th>
                <th style="width:9%">Cek Minggu</th>
                <th style="width:9%">Cek Bulanan</th>
                <th style="width:8%">VPR Aktif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehicles as $idx => $vehicle)
            @php
                $sc = $vehicle->health_score ?? 0;
                $scBadge = $sc >= 85 ? 'b-excellent' : ($sc >= 70 ? 'b-good' : ($sc >= 50 ? 'b-warning' : 'b-critical'));
                $scColor = $sc >= 85 ? '#16a34a' : ($sc >= 70 ? '#2563eb' : ($sc >= 50 ? '#d97706' : '#dc2626'));
                $allDets = $vehicle->checkings->flatMap->details;
            @endphp
            <tr>
                <td style="text-align:center">{{ $loop->iteration }}</td>
                <td style="font-weight:bold; text-align:center; background:#fff8dc;">
                    {{ $vehicle->consumerial_code }}
                </td>
                <td>{{ $vehicle->consumerial_name ?? '-' }}</td>
                <td style="text-align:center;">
                    <span class="badge {{ $scBadge }}">{{ $sc }}%</span>
                    <div class="hbar" style="margin-top:1.5mm;">
                        <div class="hbar-fill" style="width:{{ $sc }}%; background:{{ $scColor }};"></div>
                    </div>
                </td>
                <td style="text-align:center; font-size:7pt;">
                    {{ $vehicle->last_checked_at
                        ? \Carbon\Carbon::parse($vehicle->last_checked_at)->translatedFormat('d M Y')
                        : 'Belum' }}
                </td>
                <td style="text-align:center;">{{ $allDets->where('type','body')->count() }}×</td>
                <td style="text-align:center;">{{ $allDets->where('type','routine')->where('sub_type','daily')->count() }}×</td>
                <td style="text-align:center;">{{ $allDets->where('type','routine')->where('sub_type','weekly')->count() }}×</td>
                <td style="text-align:center;">{{ $allDets->where('type','routine')->where('sub_type','monthly')->count() }}×</td>
                <td style="text-align:center; font-weight:bold; color:{{ ($vehicle->active_replacement_count ?? 0) > 0 ? '#dc2626' : '#166534' }};">
                    {{ $vehicle->active_replacement_count ?? 0 }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>{{-- /page-wrap section 1 --}}


{{-- ══════════════════════════════════════════════════════════════
     SECTION 2 — PENGELUARAN SERVIS BULAN INI
══════════════════════════════════════════════════════════════ --}}
<div class="page-break">
<div class="page-wrap">

    {{-- Mini header --}}
    <table class="header-logo-table">
        <tr>
            @if($logoBase64)
            <td class="header-logo-img"><img src="{{ $logoBase64 }}" alt="Logo"></td>
            @endif
            <td>
                <div class="header-company-name">PT. Geo Dipa Energi (Persero)</div>
                <div class="header-company-sub">Unit Patuha</div>
            </td>
        </tr>
    </table>
    <div class="header-title-wrap">
        <div class="header-title">Laporan Pengeluaran Servis Kendaraan</div>
        <div class="header-subtitle">Periode: {{ $periodLabel }}</div>
    </div>
    <hr class="header-divider-thick">
    <hr class="header-divider-thin">

    {{-- SUMMARY KEUANGAN — fin-table --}}
    <div class="sec-title">Ringkasan Biaya Servis &amp; Penggantian Sparepart</div>

    <table class="fin-table">
        <tr class="section-row"><td colspan="2">Ringkasan Transaksi Servis</td></tr>
        <tr>
            <td>Total Transaksi Servis Bulan Ini</td>
            <td>{{ $serviceTransactions->count() }} transaksi</td>
        </tr>
        <tr>
            <td>Transaksi Selesai (Completed)</td>
            <td>{{ $serviceTransactions->whereHas('replacement', fn($q)=>$q->where('status','completed'))->count() }} transaksi</td>
        </tr>
        <tr>
            <td>Kendaraan Sedang dalam Servis</td>
            <td>{{ $inServiceCount }} unit</td>
        </tr>

        <tr class="section-row"><td colspan="2">Ringkasan Keuangan</td></tr>
        <tr>
            <td>Total Pengeluaran Servis</td>
            <td>Rp {{ number_format($totalServiceCost, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Rata-rata Biaya per Transaksi</td>
            <td>Rp {{ number_format($serviceTransactions->count() > 0 ? $totalServiceCost / $serviceTransactions->count() : 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Item Sparepart Diganti</td>
            <td>{{ $serviceTransactions->flatMap->details->sum('quantity') }} item</td>
        </tr>

        <tr class="total-row">
            <td>TOTAL BIAYA SERVIS PERIODE {{ strtoupper($periodLabel) }}</td>
            <td>Rp {{ number_format($totalServiceCost, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- KENDARAAN SEDANG SERVIS --}}
    <div class="sec-title">Kendaraan Sedang / Terjadwal Servis</div>
    @php $activeRepls = $replacements->whereIn('status', ['in_service','scheduled','approved']); @endphp

    @if($activeRepls->isEmpty())
        <div style="text-align:center; padding:8mm; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-weight:bold; margin-bottom:4mm;">
            ✓ Tidak ada kendaraan yang sedang dalam proses servis saat ini
        </div>
    @else
        <table class="service-cards">
            <tbody>
            @foreach($activeRepls->chunk(3) as $rowSet)
            <tr>
                @foreach($rowSet as $repl)
                @php
                    $isServis = in_array($repl->status, ['in_service']);
                @endphp
                <td>
                    <div class="sc-head {{ $isServis ? '' : 'sc-head-ok' }}">
                        {{ $repl->vehicle->consumerial_code ?? '-' }} — {{ strtoupper($repl->status) }}
                    </div>
                    <div class="sc-body">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="font-size:5.5pt; font-weight:bold; color:#666; text-transform:uppercase; width:36%;">Kendaraan</td>
                                <td style="font-size:7.5pt;">{{ $repl->vehicle->consumerial_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="sc-lbl">Dispatcher</td>
                                <td class="sc-val">{{ $repl->dispatcher->nama_lengkap ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="sc-lbl">VPR Code</td>
                                <td class="sc-val" style="font-weight:bold;">{{ $repl->vpr_code }}</td>
                            </tr>
                            <tr>
                                <td class="sc-lbl">Tgl Ajuan</td>
                                <td class="sc-val">{{ $repl->created_at ? $repl->created_at->format('d/m/Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="sc-lbl">Item</td>
                                <td class="sc-val">{{ $repl->replacementDetails->count() }} sparepart</td>
                            </tr>
                        </table>
                    </div>
                </td>
                @endforeach
                @if($rowSet->count() < 3)
                    @for($i = 0; $i < 3 - $rowSet->count(); $i++)
                        <td class="empty-cell"></td>
                    @endfor
                @endif
            </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    {{-- TABEL DETAIL SPAREPART DIGANTI --}}
    <div class="sec-title">Detail Sparepart Diganti Bulan Ini</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:10%">No. Polisi</th>
                <th style="width:12%">VPR Code</th>
                <th style="width:12%">Tgl Servis</th>
                <th>Nama Sparepart</th>
                <th style="width:8%">Qty</th>
                <th style="width:14%">Harga Satuan</th>
                <th style="width:14%">Total</th>
                <th style="width:9%">Valid</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNo = 1; @endphp
            @foreach($serviceTransactions as $trx)
                @foreach($trx->details as $detail)
                <tr>
                    <td style="text-align:center">{{ $rowNo++ }}</td>
                    <td style="font-weight:bold; text-align:center;">
                        {{ $trx->replacement->nopol ?? '-' }}
                    </td>
                    <td style="font-size:7pt;">{{ $trx->replacement->vpr_code ?? '-' }}</td>
                    <td style="text-align:center;">
                        {{ $trx->service_date ? \Carbon\Carbon::parse($trx->service_date)->format('d/m/Y') : '-' }}
                    </td>
                    <td>{{ $detail->part_name ?? $detail->item_name ?? '-' }}</td>
                    <td style="text-align:center;">{{ $detail->quantity ?? '-' }}</td>
                    <td style="text-align:right;">
                        Rp {{ number_format($detail->unit_price ?? 0, 0, ',', '.') }}
                    </td>
                    <td style="text-align:right; font-weight:bold;">
                        Rp {{ number_format($detail->total_price ?? 0, 0, ',', '.') }}
                    </td>
                    <td style="text-align:center;">
                        @if($trx->is_verified ?? false)
                            <span style="color:#16a34a; font-weight:bold;">✓ Valid</span>
                        @else
                            <span style="color:#d97706;">Pending</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" style="text-align:right;">TOTAL PENGELUARAN SERVIS:</td>
                <td style="text-align:right;">Rp {{ number_format($totalServiceCost, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

</div>{{-- /page-wrap section 2 --}}
</div>{{-- /page-break --}}


{{-- ══════════════════════════════════════════════════════════════
     SECTION 3..n — BODY CHECKING PER KENDARAAN (2 kendaraan / hal.)
══════════════════════════════════════════════════════════════ --}}
<div class="page-break">
<div class="page-wrap">

    <table class="header-logo-table">
        <tr>
            @if($logoBase64)
            <td class="header-logo-img"><img src="{{ $logoBase64 }}" alt="Logo"></td>
            @endif
            <td>
                <div class="header-company-name">PT. Geo Dipa Energi (Persero)</div>
                <div class="header-company-sub">Unit Patuha</div>
            </td>
        </tr>
    </table>
    <div class="header-title-wrap">
        <div class="header-title">Pengecekan Body Kendaraan — Semua Armada</div>
        <div class="header-subtitle">Periode: {{ $periodLabel }}</div>
    </div>
    <hr class="header-divider-thick">
    <hr class="header-divider-thin">

    <table class="body-cards">
        <tbody>
        @foreach($vehicles->chunk(2) as $rowPair)
        <tr>
            @foreach($rowPair as $vehicle)
            @php
                $bodyDetails = $vehicle->checkings->flatMap->details->where('type','body')->sortByDesc('checked_at');
                $latestBody  = $bodyDetails->first();
                $results     = $latestBody ? $latestBody->itemResults->keyBy('vehicle_part_id') : collect();

                $cBaik  = $latestBody ? $latestBody->itemResults->where('status','baik')->count()   : 0;
                $cCukup = $latestBody ? $latestBody->itemResults->where('status','cukup')->count()  : 0;
                $cKrg   = $latestBody ? $latestBody->itemResults->where('status','kurang')->count() : 0;
                $cTdk   = $latestBody ? $latestBody->itemResults->where('status','tidak')->count()  : 0;
                $cTot   = $cBaik + $cCukup + $cKrg + $cTdk;
                $cSc    = $cTot > 0 ? round($cBaik / $cTot * 100) : 0;
                $cClr   = $cSc >= 85 ? '#16a34a' : ($cSc >= 70 ? '#2563eb' : ($cSc >= 50 ? '#d97706' : '#dc2626'));
                $hasRepl= $latestBody ? $latestBody->itemResults->where('needs_replacement',true)->isNotEmpty() : false;
            @endphp
            <td>
                {{-- Card head --}}
                <div class="bcard-head">
                    <table class="bcard-head-inner">
                        <tr>
                            <td>
                                <div class="bcard-nopol">{{ $vehicle->consumerial_code }}</div>
                                <div class="bcard-sub">
                                    {{ $vehicle->consumerial_name ?? '-' }}
                                    &nbsp;·&nbsp;
                                    @if($latestBody)
                                        Cek: {{ $latestBody->checked_at->translatedFormat('d M Y') }}
                                        · Driver: {{ $latestBody->currentDriver->nama_lengkap ?? '-' }}
                                    @else
                                        Belum ada pengecekan
                                    @endif
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <span class="bcard-score">{{ $cSc }}%</span>
                                @if($hasRepl)
                                    <br><span style="font-size:6pt; color:#fca5a5;">⚠ Ada penggantian</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                @if($latestBody)
                {{-- Checklist items --}}
                <table class="bcard-table">
                    <thead>
                        <tr>
                            <th class="bc-no">No</th>
                            <th class="bc-itm">Item</th>
                            <th class="bc-st">Status</th>
                            <th class="bc-kt">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bodyItemsDef as $partNo => $itemLabel)
                        @php
                            $res    = $results->get($partNo);
                            $status = $res ? $res->status : null;
                            $stCls  = $status ? 'st-' . $status : '';
                            $stTxt  = match($status) {
                                'baik'   => 'BAIK',
                                'cukup'  => 'CUKUP',
                                'kurang' => 'KURANG',
                                'tidak'  => 'TIDAK',
                                default  => '—',
                            };
                        @endphp
                        <tr>
                            <td class="bc-no">{{ $partNo }}</td>
                            <td class="bc-itm">{{ $itemLabel }}</td>
                            <td class="bc-st {{ $stCls }}">{{ $stTxt }}</td>
                            <td class="bc-kt">
                                {{ $res->notes ?? '' }}
                                @if($res && $res->needs_replacement)
                                    <span style="color:#dc2626; font-weight:bold;">⚠</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Total summary bar --}}
                <div style="padding:1.5mm 3mm; border-top:0.5px solid #ddd; font-size:6.5pt; background:#f5f8ff;">
                    <span style="color:#166534;">Baik:{{ $cBaik }}</span> &nbsp;
                    <span style="color:#92400e;">Cukup:{{ $cCukup }}</span> &nbsp;
                    <span style="color:#9a3412;">Kurang:{{ $cKrg }}</span> &nbsp;
                    <span style="color:#dc2626; font-weight:bold;">Tidak:{{ $cTdk }}</span>
                    &nbsp;&nbsp;
                    <span style="color:{{ $cClr }}; font-weight:bold;">Score: {{ $cSc }}%</span>
                    &nbsp;·&nbsp; Total cek bulan ini: {{ $bodyDetails->count() }}×
                </div>
                @else
                <div style="padding:10mm 3mm; text-align:center; color:#999; font-size:8pt;">
                    Belum ada data pengecekan body bulan ini
                </div>
                @endif
            </td>
            @endforeach

            @if($rowPair->count() === 1)
            <td class="empty-cell"></td>
            @endif
        </tr>

        {{-- Page break tiap 2 kendaraan --}}
        @if(!$loop->last)
        <tr><td colspan="2" style="border:none; padding:0;">
            <div class="page-break"></div>
            <table class="header-logo-table" style="margin-top:0; margin-bottom:2mm;">
                <tr>
                    @if($logoBase64)<td class="header-logo-img"><img src="{{ $logoBase64 }}" alt="Logo"></td>@endif
                    <td>
                        <div style="font-size:9pt; font-weight:bold; color:#111;">PT. Geo Dipa Energi (Persero) — Unit Patuha</div>
                        <div style="font-size:7.5pt; color:#555;">Pengecekan Body Kendaraan · Periode: {{ $periodLabel }}</div>
                    </td>
                </tr>
            </table>
            <hr class="header-divider-thick" style="margin-bottom:2mm;">
        </td></tr>
        @endif
        @endforeach
        </tbody>
    </table>

</div>{{-- /page-wrap section 3 --}}
</div>{{-- /page-break --}}


{{-- ══════════════════════════════════════════════════════════════
     SECTION n+1 — ROUTINE CHECKING PER KENDARAAN (1 kendaraan / hal.)
══════════════════════════════════════════════════════════════ --}}
@foreach($vehicles as $vehicle)
@php
    $routineDetails = $vehicle->checkings->flatMap->details
        ->where('type','routine')
        ->sortByDesc('checked_at');

    $dayMap = [];
    $dispName = '-';
    $drvName  = '-';
    foreach ($routineDetails as $det) {
        if (!$det->checked_at) continue;
        $day = (int)$det->checked_at->format('j');
        if ($det->dispatcher  && $dispName === '-') $dispName = $det->dispatcher->nama_lengkap;
        if ($det->currentDriver && $drvName === '-') $drvName  = $det->currentDriver->nama_lengkap;
        foreach ($det->itemResults as $res) {
            $pid = $res->vehicle_part_id;
            if (!isset($dayMap[$pid][$day])) $dayMap[$pid][$day] = $res->status;
        }
    }
    $daysInMonth = $periodStart->daysInMonth;
    $totOk = 0; $totNg = 0;
    foreach ($dayMap as $pdm) { foreach ($pdm as $s) { if($s==='ok') $totOk++; else $totNg++; } }
    $totAll = $totOk + $totNg;
    $scoreStr = $totAll > 0 ? round($totOk / $totAll * 100, 1) . '%' : 'Belum ada data';
@endphp
<div class="page-break">
<div class="page-wrap">

    {{-- Mini header --}}
    <table class="header-logo-table">
        <tr>
            @if($logoBase64)<td class="header-logo-img"><img src="{{ $logoBase64 }}" alt="Logo"></td>@endif
            <td>
                <div class="header-company-name">PT. Geo Dipa Energi (Persero)</div>
                <div class="header-company-sub">Unit Patuha</div>
            </td>
        </tr>
    </table>
    <div class="header-title-wrap">
        <div class="header-title">Formulir Checklist Rutin — {{ $vehicle->consumerial_code }}</div>
        <div class="header-subtitle">
            {{ $vehicle->consumerial_name ?? '-' }}
            &nbsp;·&nbsp; Periode: {{ $periodLabel }}
        </div>
    </div>
    <hr class="header-divider-thick">
    <hr class="header-divider-thin">

    {{-- Meta box kendaraan --}}
    <table class="meta-table">
        <tr>
            <td class="ml">No. Polisi</td><td class="ms">:</td>
            <td class="mv" style="font-weight:bold;">{{ $vehicle->consumerial_code }}</td>
            <td class="ml">Dispatcher</td><td class="ms">:</td>
            <td class="mv">{{ $dispName }}</td>
        </tr>
        <tr>
            <td class="ml">Jenis Kendaraan</td><td class="ms">:</td>
            <td class="mv">{{ $vehicle->consumerial_name ?? '-' }}</td>
            <td class="ml">Pengemudi</td><td class="ms">:</td>
            <td class="mv">{{ $drvName }}</td>
        </tr>
        <tr>
            <td class="ml">Total Pengecekan</td><td class="ms">:</td>
            <td class="mv">{{ $routineDetails->count() }}× ({{ $totOk }} OK · {{ $totNg }} NG)</td>
            <td class="ml">Score Kepatuhan</td><td class="ms">:</td>
            <td class="mv" style="font-weight:bold; color:#1a56db;">{{ $scoreStr }}</td>
        </tr>
    </table>

    {{-- Checklist grid --}}
    <table class="cl-table">
        <thead>
            <tr>
                <th class="col-no th-blue" rowspan="2">No</th>
                <th class="col-item th-blue" rowspan="2" style="text-align:left; padding-left:3px;">Kondisi</th>
                <th class="col-per th-blue" rowspan="2">Periodik</th>
                <th colspan="{{ $daysInMonth }}" class="th-blue" style="font-size:7.5pt;">
                    Bln : {{ $periodLabel }}
                </th>
                @if($daysInMonth < 31)
                <th colspan="{{ 31 - $daysInMonth }}" class="th-blue" style="background:#93b8e8;"></th>
                @endif
                <th class="col-ket th-blue" rowspan="2">Ket.</th>
            </tr>
            <tr>
                @for($d = 1; $d <= 31; $d++)
                    @if($d <= $daysInMonth)
                        <th class="col-day th-sub">{{ $d }}</th>
                    @else
                        <th class="col-day" style="background:#e8e8e8; color:#ccc; font-size:5pt;">{{ $d }}</th>
                    @endif
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($itemGroupsDef as $groupName => $groupItems)
            <tr class="group-row">
                <td colspan="{{ 3 + 31 + 1 }}">&nbsp;&nbsp;{{ $groupName }}</td>
            </tr>
            @foreach($groupItems as $partNo => $info)
            @php
                $hasRepl = false;
                foreach($routineDetails as $det) {
                    if($det->itemResults->where('vehicle_part_id',$partNo)->where('needs_replacement',true)->isNotEmpty()) {
                        $hasRepl = true; break;
                    }
                }
            @endphp
            <tr class="item-row">
                <td class="col-no">{{ $partNo }}</td>
                <td class="col-item">{{ $info['label'] }}</td>
                <td class="col-per {{ $info['cls'] }}">{{ $info['per'] }}</td>
                @for($d = 1; $d <= 31; $d++)
                    @php $res = $dayMap[$partNo][$d] ?? null; @endphp
                    @if($d <= $daysInMonth)
                        <td class="col-day {{ $res === 'ok' ? 'cell-ok' : ($res === 'ng' ? 'cell-ng' : 'cell-null') }}">
                            @if($res === 'ok') V
                            @elseif($res === 'ng') X
                            @else <span>·</span>
                            @endif
                        </td>
                    @else
                        <td class="col-day" style="background:#f0f0f0;"></td>
                    @endif
                @endfor
                <td class="col-ket">
                    @if($hasRepl)<span style="color:#dc2626; font-weight:bold;">⚠</span>@endif
                </td>
            </tr>
            @endforeach
            @endforeach

            {{-- Paraf --}}
            <tr class="paraf-row">
                <td colspan="3" style="text-align:center;">Paraf Dispatcher</td>
                @for($d = 1; $d <= 32; $d++)<td class="col-day"></td>@endfor
            </tr>
            <tr class="paraf-row">
                <td colspan="3" style="text-align:center;">Paraf Pengemudi</td>
                @for($d = 1; $d <= 32; $d++)<td class="col-day"></td>@endfor
            </tr>
        </tbody>
    </table>

    {{-- Legend --}}
    <div style="font-size:6.5pt; color:#555; background:#f5f8ff; padding:2px 6px; border:1px solid #d0d8f0; margin-top:2mm; margin-bottom:2mm;">
        <strong>Catatan:</strong>&nbsp;
        <span style="background:#dcfce7; color:#166534; padding:0 3px; border-radius:2px; font-weight:bold;">V = OK</span>&nbsp;&nbsp;
        <span style="background:#fee2e2; color:#dc2626; padding:0 3px; border-radius:2px; font-weight:bold;">X = NG</span>&nbsp;&nbsp;
        <span style="color:#dc2626;">⚠</span> = Perlu penggantian sparepart. Jika ada masalah segera lapor ke Koordinator.
    </div>

    {{-- Standar pengecekan --}}
    <table style="width:100%; border-collapse:collapse; font-size:6.5pt;">
        <tr>
            <td style="background:#1a56db; color:#fff; font-weight:bold; padding:1.5px 5px;" colspan="6">Standar Pengecekan</td>
        </tr>
        <tr>
            <td style="background:#dce8ff; color:#1a56db; font-weight:bold; border:0.5px solid #ccc; padding:1px 3px; width:80px;">Mesin</td>
            <td style="border:0.5px solid #ccc; padding:1px 3px;">Normal (Tidak ada kelainan)</td>
            <td style="background:#dce8ff; color:#1a56db; font-weight:bold; border:0.5px solid #ccc; padding:1px 3px; width:100px;">Olie Rem</td>
            <td style="border:0.5px solid #ccc; padding:1px 3px;">Sesuai Level</td>
            <td style="background:#dce8ff; color:#1a56db; font-weight:bold; border:0.5px solid #ccc; padding:1px 3px; width:100px;">Air Radiator</td>
            <td style="border:0.5px solid #ccc; padding:1px 3px;">Harus Penuh</td>
        </tr>
        <tr>
            <td style="background:#dce8ff; color:#1a56db; font-weight:bold; border:0.5px solid #ccc; padding:1px 3px;">Solar/Bensin</td>
            <td style="border:0.5px solid #ccc; padding:1px 3px;">Volume cukup</td>
            <td style="background:#dce8ff; color:#1a56db; font-weight:bold; border:0.5px solid #ccc; padding:1px 3px;">Peralatan Emergency</td>
            <td style="border:0.5px solid #ccc; padding:1px 3px;">Ada &amp; Lengkap</td>
            <td style="background:#dce8ff; color:#1a56db; font-weight:bold; border:0.5px solid #ccc; padding:1px 3px;">Olie Mesin</td>
            <td style="border:0.5px solid #ccc; padding:1px 3px;">Sesuai standar &amp; Kualitas</td>
        </tr>
        <tr>
            <td style="background:#dce8ff; color:#1a56db; font-weight:bold; border:0.5px solid #ccc; padding:1px 3px;">Rem / Copling</td>
            <td style="border:0.5px solid #ccc; padding:1px 3px;">Berfungsi dengan baik</td>
            <td style="background:#dce8ff; color:#1a56db; font-weight:bold; border:0.5px solid #ccc; padding:1px 3px;">Peralatan Listrik</td>
            <td style="border:0.5px solid #ccc; padding:1px 3px;">Semua Lampu Harus Nyala</td>
            <td style="background:#dce8ff; color:#1a56db; font-weight:bold; border:0.5px solid #ccc; padding:1px 3px;">Izin Kendaraan</td>
            <td style="border:0.5px solid #ccc; padding:1px 3px;">Masih berlaku</td>
        </tr>
    </table>

    @if($loop->last)
    {{-- Footer hanya di halaman terakhir --}}
    <div class="footer-text" style="margin-top:4mm;">
        Dokumen dibuat secara otomatis pada {{ now()->translatedFormat('d F Y') }} pukul {{ now()->format('H:i') }} WIB<br>
        Dicetak oleh: {{ Auth::user()->nama_lengkap ?? Auth::user()->username ?? 'System' }}<br>
        <em>* Dokumen ini sah tanpa tanda tangan dan stempel *</em><br>
        <em>* Disepakati secara internal oleh divisi HCGAF PT. Geo Dipa Energi (Persero) Unit Patuha *</em>
    </div>
    @endif

</div>{{-- /page-wrap --}}
</div>{{-- /page-break --}}
@endforeach

</body>
</html>
