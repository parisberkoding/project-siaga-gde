<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Checklist Rutin - {{ $vehicle->consumerial_code ?? '' }} - {{ $periodLabel ?? '' }}</title>
<style>
    /* ================================================================
       PAGE — A4 LANDSCAPE
    ================================================================ */
    @page {
        size: A4 landscape;
        margin-top: 0;
        margin-bottom: 0;
        margin-left: 0;
        margin-right: 0;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 8.5pt;
        color: #111;
    }
    .page-wrap { padding: 22px 28px 22px 28px; }

    /* ================================================================
       HEADER — persis sama dengan template BBM
    ================================================================ */
    .header-logo-table { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
    .header-logo-table td { border: none; padding: 0; vertical-align: middle; }
    .header-logo-img { width: 22mm; padding-right: 4mm; }
    .header-logo-img img { width: 20mm; height: auto; display: block; }
    .header-company-name {
        font-size: 13pt; font-weight: bold;
        text-transform: uppercase; letter-spacing: 0.5pt;
        color: #111; line-height: 1.2;
    }
    .header-company-sub  { font-size: 8.5pt; color: #444; margin-top: 1mm; }
    .header-company-addr { font-size: 7pt;   color: #777; margin-top: 1mm; line-height: 1.5; }
    .header-title-wrap   { text-align: center; margin-bottom: 2mm; }
    .header-title        { font-size: 11pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8pt; color: #111; }
    .header-subtitle     { font-size: 8pt;  color: #555; margin-top: 1mm; }
    .header-divider-thick { border: none; border-top: 3px solid #1a56db; margin: 2mm 0 0.5mm 0; }
    .header-divider-thin  { border: none; border-top: 1px solid #7aadff; margin: 0 0 3mm 0; }

    /* ================================================================
       META BOX — seperti meta-table BBM
    ================================================================ */
    .meta-table {
        width: 100%; border-collapse: collapse;
        margin-bottom: 4mm; border: 1px solid #d0d8f0; background: #f5f8ff;
    }
    .meta-table td { border: none; padding: 1mm 3mm; font-size: 7.5pt; color: #333; }
    .meta-table .ml { font-weight: bold; color: #1a56db; width: 18%; }
    .meta-table .ms { width: 2%; color: #999; }
    .meta-table .mv { width: 30%; }

    /* ================================================================
       CHECKLIST TABLE — landscape 31-hari
    ================================================================ */
    .cl-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin-bottom: 3mm;
    }
    .cl-table th, .cl-table td {
        border: 0.5px solid #999;
        text-align: center;
        vertical-align: middle;
        padding: 1.5px 0;
        overflow: hidden;
    }
    /* column widths (landscape A4 = ~267mm usable) */
    .col-no   { width: 18px;  font-size: 7.5pt; }
    .col-item { width: 92px;  text-align: left !important; padding-left: 4px !important; font-size: 7.5pt; }
    .col-per  { width: 38px;  font-size: 6.5pt; }
    .col-day  { width: 7.5px; font-size: 6pt; }
    .col-ket  { width: 46px;  text-align: left !important; padding-left: 3px !important; font-size: 6.5pt; }

    /* header rows */
    .th-blue { background: #1a56db; color: #fff; font-weight: bold; font-size: 8pt; }
    .th-sub  { background: #dce8ff; font-weight: bold; font-size: 6.5pt; color: #1a56db; }

    /* group header rows */
    .group-row td {
        background: #1a56db; color: #fff;
        font-weight: bold; font-size: 7.5pt;
        text-align: left !important; padding-left: 6px !important;
        height: 14px;
    }

    /* data rows */
    .item-row td             { height: 13px; font-size: 7pt; }
    .item-row:nth-child(odd) td { background: #fff; }
    .item-row:nth-child(even) td { background: #f5f8ff; }

    /* periodik badges */
    .per-h  { background: #e8f0ff; color: #1a56db; font-size: 6pt; }
    .per-m  { background: #fff8e6; color: #b45309; font-size: 6pt; }
    .per-b  { background: #e6f4ea; color: #166534; font-size: 6pt; }

    /* result cells */
    .cell-ok   { background: #dcfce7; color: #166534; font-weight: bold; font-size: 7.5pt; }
    .cell-ng   { background: #fee2e2; color: #dc2626; font-weight: bold; font-size: 7.5pt; }
    .cell-null { color: #ddd; font-size: 6pt; }

    /* paraf rows */
    .paraf-row td {
        background: #f0f4ff; font-weight: bold;
        font-size: 7.5pt; height: 26px;
    }

    /* ================================================================
       STANDAR PENGECEKAN
    ================================================================ */
    .std-wrap { margin-top: 3mm; }
    .std-title {
        background: #1a56db; color: #fff;
        font-weight: bold; font-size: 7.5pt;
        padding: 2px 6px; margin-bottom: 0;
    }
    .std-table { width: 100%; border-collapse: collapse; }
    .std-table td { border: 0.5px solid #ccc; padding: 1.5px 4px; font-size: 6.5pt; }
    .std-table .sl { background: #dce8ff; font-weight: bold; color: #1a56db; white-space: nowrap; width: 80px; }

    /* ================================================================
       LEGEND
    ================================================================ */
    .legend-bar {
        font-size: 7pt; color: #555;
        background: #f5f8ff; padding: 2px 6px;
        border: 1px solid #d0d8f0; margin-top: 2mm;
    }
    .lbl-ok { background: #dcfce7; color: #166534; padding: 0 4px; border-radius: 2px; font-weight: bold; }
    .lbl-ng { background: #fee2e2; color: #dc2626; padding: 0 4px; border-radius: 2px; font-weight: bold; }

    /* ================================================================
       FOOTER
    ================================================================ */
    .footer-text { text-align: center; font-size: 7pt; color: #666; padding: 3mm 0 0 0; line-height: 1.8; }
    .page-break  { page-break-after: always; }
</style>
</head>
<body>
<div class="page-wrap">

@php
    $logoPath   = public_path('DashboardTemplates/assets/img/geodipa-logo.png');
    $logoBase64 = file_exists($logoPath)
                    ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                    : '';

    $itemGroups = [
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

    {{--
        $vehicle     = VehicleAndToolsConsumers
        $checkings   = Collection of VehicleCheckingDetail (type=routine), relasi loaded:
                       vehicleChecking, dispatcher, currentDriver, itemResults
        $periodStart = Carbon (awal bulan)
        $periodLabel = string e.g. "Maret 2026"
        $dispatcher  = User (dispatcher bulan ini)

        Build dayMap: [part_id][day] = status
    --}}
    $dayMap = [];
    $dispatcherName = '-';
    $driverName     = '-';

    foreach ($checkings as $det) {
        if ($det->checked_at) {
            $day = (int) $det->checked_at->format('j');
            if ($det->dispatcher && $dispatcherName === '-') $dispatcherName = $det->dispatcher->nama_lengkap;
            if ($det->currentDriver && $driverName === '-')  $driverName     = $det->currentDriver->nama_lengkap;
            foreach ($det->itemResults as $res) {
                $pid = $res->vehicle_part_id;
                if (!isset($dayMap[$pid][$day])) $dayMap[$pid][$day] = $res->status;
            }
        }
    }

    $daysInMonth  = $periodStart->daysInMonth;
    $totalOk      = 0; $totalNg = 0;
    foreach ($dayMap as $partDays) {
        foreach ($partDays as $s) {
            if ($s === 'ok') $totalOk++; else $totalNg++;
        }
    }
    $totalChecks = $totalOk + $totalNg;
    $scoreStr    = $totalChecks > 0 ? round($totalOk / $totalChecks * 100, 1) . '%' : '-';
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
    <div class="header-title">Formulir Checklist Kendaraan Operasional — Pengecekan Rutin</div>
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
        <td class="mv" style="font-weight:bold; color:#111;">{{ $vehicle->consumerial_code }}</td>
        <td class="ml">Dispatcher</td>
        <td class="ms">:</td>
        <td class="mv">{{ $dispatcherName }}</td>
    </tr>
    <tr>
        <td class="ml">Jenis Kendaraan</td>
        <td class="ms">:</td>
        <td class="mv">{{ $vehicle->consumerial_name ?? '-' }}</td>
        <td class="ml">Pengemudi Aktif</td>
        <td class="ms">:</td>
        <td class="mv">{{ $driverName }}</td>
    </tr>
    <tr>
        <td class="ml">Jumlah Pengecekan</td>
        <td class="ms">:</td>
        <td class="mv">{{ $checkings->count() }}× dalam periode ini</td>
        <td class="ml">Score Kepatuhan</td>
        <td class="ms">:</td>
        <td class="mv" style="font-weight:bold; color:#1a56db;">
            {{ $scoreStr }}
            &nbsp;(OK: {{ $totalOk }} · NG: {{ $totalNg }})
        </td>
    </tr>
</table>

{{-- ── CHECKLIST TABLE ──────────────────────────────────────────── --}}
<table class="cl-table">
    <thead>
        <tr>
            <th class="col-no th-blue" rowspan="2">No</th>
            <th class="col-item th-blue" rowspan="2" style="text-align:left; padding-left:4px;">Kondisi</th>
            <th class="col-per th-blue" rowspan="2">Periodik</th>
            <th colspan="{{ $daysInMonth }}" class="th-blue" style="font-size:8.5pt;">
                Bln : {{ $periodLabel }}
            </th>
            @if($daysInMonth < 31)
                <th colspan="{{ 31 - $daysInMonth }}" class="th-blue" style="background:#c0cfe8;"></th>
            @endif
            <th class="col-ket th-blue" rowspan="2">Keterangan</th>
        </tr>
        <tr>
            @for($d = 1; $d <= 31; $d++)
                @if($d <= $daysInMonth)
                    <th class="col-day th-sub">{{ $d }}</th>
                @else
                    <th class="col-day" style="background:#eaeaea; color:#bbb; font-size:6pt;">{{ $d }}</th>
                @endif
            @endfor
        </tr>
    </thead>
    <tbody>
        @foreach($itemGroups as $groupName => $groupItems)
            <tr class="group-row">
                <td colspan="{{ 3 + 31 + 1 }}">&nbsp;&nbsp;{{ $groupName }}</td>
            </tr>
            @foreach($groupItems as $partNo => $info)
                @php
                    $hasRepl = false;
                    foreach($checkings as $det) {
                        if ($det->itemResults->where('vehicle_part_id', $partNo)->where('needs_replacement', true)->isNotEmpty()) {
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
                        @if($hasRepl)<span style="color:#dc2626; font-weight:bold;">⚠ Ganti</span>@endif
                    </td>
                </tr>
            @endforeach
        @endforeach

        {{-- PARAF --}}
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

{{-- ── LEGEND + STANDAR ─────────────────────────────────────────── --}}
<div class="legend-bar">
    <strong>Catatan :</strong>&nbsp;
    <span class="lbl-ok">V = OK</span>&nbsp;&nbsp;
    <span class="lbl-ng">X = NG</span>&nbsp;&nbsp;
    Jika ada masalah segera lapor ke bagian Koordinator. &nbsp;
    <span style="color:#dc2626;">⚠</span> = Item perlu penggantian sparepart.
</div>

<div class="std-wrap">
    <div class="std-title">Standar Pengecekan</div>
    <table class="std-table">
        <tr>
            <td class="sl">Mesin</td>
            <td>Normal (Tidak ada kelainan)</td>
            <td class="sl">Olie Rem</td>
            <td>Sesuai Level</td>
            <td class="sl">Air Accu/Aki</td>
            <td>Sesuai Level / Kondisi Baik</td>
        </tr>
        <tr>
            <td class="sl">Solar/Bensin</td>
            <td>Volume cukup</td>
            <td class="sl">Peralatan Emergency</td>
            <td>Ada &amp; Lengkap</td>
            <td class="sl">Air Radiator</td>
            <td>Harus Penuh</td>
        </tr>
        <tr>
            <td class="sl">Ban</td>
            <td>Tidak bocor (Tekanan Sesuai Standar)</td>
            <td class="sl">Copling/Matic</td>
            <td>Berfungsi</td>
            <td class="sl">Olie Mesin</td>
            <td>Sesuai standar &amp; Kualitas</td>
        </tr>
        <tr>
            <td class="sl">Rem</td>
            <td>Berfungsi</td>
            <td class="sl">Peralatan Listrik</td>
            <td>Semua Lampu Harus Nyala</td>
            <td class="sl">Izin Kendaraan</td>
            <td>Masih berlaku</td>
        </tr>
    </table>
</div>

<div class="footer-text">
    Dokumen dibuat secara otomatis pada {{ now()->translatedFormat('d F Y') }} pukul {{ now()->format('H:i') }} WIB<br>
    Dicetak oleh: {{ Auth::user()->nama_lengkap ?? Auth::user()->username ?? 'System' }}<br>
    <em>* Dokumen ini sah tanpa tanda tangan dan stempel *</em>
</div>

</div>{{-- .page-wrap --}}
</body>
</html>
