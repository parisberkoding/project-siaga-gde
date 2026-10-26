<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $filename ?? 'Laporan Transaksi BBM' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin-top: 0;
            margin-bottom: 0;
            margin-left: 0;
            margin-right: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #111;
        }

        .page-wrap {
            padding: 35px 35px 35px 35px;
        }

        /* ================================================================
           SECTION HEADER
           ================================================================ */
        .doc-header {
            margin-bottom: 5mm;
        }
        .header-logo-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4mm;
        }
        .header-logo-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .header-logo-img {
            width: 22mm;
            padding-right: 4mm;
        }
        .header-logo-img img {
            width: 20mm;
            height: auto;
            display: block;
        }
        .header-company-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            color: #111;
            line-height: 1.2;
        }
        .header-company-sub {
            font-size: 9pt;
            color: #444;
            margin-top: 1mm;
        }
        .header-company-address {
            font-size: 7pt;
            color: #777;
            margin-top: 1mm;
            line-height: 1.5;
        }
        .header-title-wrap {
            text-align: center;
            margin-bottom: 2mm;
        }
        .header-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8pt;
            color: #111;
        }
        .header-subtitle {
            font-size: 8.5pt;
            color: #555;
            margin-top: 1mm;
        }
        .header-divider-thick {
            border: none;
            border-top: 3px solid #1a56db;
            margin: 2mm 0 0.5mm 0;
        }
        .header-divider-thin {
            border: none;
            border-top: 1px solid #7aadff;
            margin: 0 0 4mm 0;
        }

        /* ================================================================
           META TABLE
           ================================================================ */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
            border: 1px solid #d0d8f0;
            background: #f5f8ff;
        }
        .meta-table td {
            border: none;
            padding: 1.2mm 3mm;
            font-size: 7.5pt;
            color: #333;
        }
        .meta-table .ml { font-weight: bold; color: #1a56db; width: 22%; }
        .meta-table .ms { width: 2%; color: #999; }
        .meta-table .mv { width: 26%; }

        /* ================================================================
           TRANSACTION CARDS
           ================================================================ */
        .card-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
        }
        .card-table > tbody > tr {
            page-break-inside: avoid;
        }
        .card-table > tbody > tr > td {
            border: 1px solid #333;
            width: 50%;
            vertical-align: top;
            padding: 0;
        }
        .card-table > tbody > tr > td.empty-cell {
            border: 1px solid #ccc;
        }
        .card-head {
            background: #1a56db;
            color: #fff;
            padding: 1.5mm 3mm;
        }
        .card-head-inner {
            width: 100%;
            border-collapse: collapse;
        }
        .card-head-inner td {
            border: none;
            padding: 0;
            color: #fff;
            vertical-align: middle;
        }
        .card-req  { font-size: 7.5pt; font-weight: bold; }
        .card-date { font-size: 6pt; color: #cce0ff; }
        .card-status {
            font-size: 6.5pt;
            font-weight: bold;
            padding: 0.5mm 2mm;
            border-radius: 2px;
        }
        .status-completed { background: #16a34a; }
        .status-approved  { background: #d97706; }
        .status-rejected  { background: #dc2626; }
        .status-pending   { background: #6b7280; }

        .card-body-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .card-body-table > tr > td {
            border: none;
            vertical-align: top;
            padding: 0;
        }
        .col-info {
            width: 58%;
            padding: 2mm 2mm 2mm 3mm;
            border-right: 1px solid #e0e0e0;
        }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td {
            border: none;
            padding: 0.6mm 0;
            vertical-align: top;
        }
        .info-lbl {
            font-size: 5.5pt;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            white-space: nowrap;
            padding-right: 1mm;
            width: 38%;
        }
        .info-val  { font-size: 7pt; }
        .info-val strong { font-size: 7pt; }
        .info-sub  { font-size: 6pt; color: #777; }
        .info-val-red { font-size: 6.5pt; color: #dc2626; font-style: italic; }
        .info-divider td { padding: 0.5mm 0; }
        .info-divider-line { border: none; border-top: 1px dashed #ddd; }
        .info-section-label {
            font-size: 5pt;
            font-weight: bold;
            color: #1a56db;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            padding-top: 1mm;
            padding-bottom: 0.3mm;
        }
        .col-photo {
            width: 42%;
            padding: 2mm 2mm 1mm 2mm;
            text-align: center;
        }
        .col-photo img {
            width: 100%;
            max-height: 52mm;
            object-fit: cover;
            display: block;
            margin: 0 auto;
        }
        .no-photo {
            width: 100%;
            height: 45mm;
            border: 1px dashed #ccc;
            background: #f8f8f8;
            font-size: 7pt;
            color: #bbb;
            text-align: center;
            padding-top: 18mm;
        }
        .photo-lbl {
            font-size: 5.5pt;
            color: #999;
            margin-top: 1mm;
            text-align: center;
        }

        /* ================================================================
           DATA TABLE
           ================================================================ */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            background: #1a56db;
            color: #fff;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
            padding: 2mm;
            border: 1px solid #1040a0;
        }
        .data-table td {
            font-size: 7.5pt;
            padding: 1.5mm 2mm;
            border: 1px solid #aaa;
            vertical-align: top;
        }
        .data-table tbody tr:nth-child(even) td { background: #f5f8ff; }
        .data-table tfoot td {
            background: #dce8ff;
            font-weight: bold;
            font-size: 8pt;
            border: 1px solid #555;
        }

        /* ================================================================
           FINANCIAL TABLE
           ================================================================ */
        .fin-table { width: 100%; border-collapse: collapse; }
        .fin-table td {
            border: 1px solid #ccc;
            padding: 2mm 3mm;
            font-size: 9.5pt;
        }
        .fin-table .section-row td {
            background: #eef2ff;
            font-size: 7.5pt;
            font-weight: bold;
            color: #1a56db;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }
        .fin-table .total-row td {
            background: #1a56db;
            color: #fff;
            font-weight: bold;
            font-size: 11pt;
        }
        .fin-table td:last-child {
            text-align: right;
            font-weight: bold;
        }

        /* ================================================================
           FOOTER TEXT
           ================================================================ */
        .footer-text {
            text-align: center;
            font-size: 7pt;
            color: #666;
            padding: 4mm 0 2mm 0;
            line-height: 1.8;
        }

        /* ================================================================
           PAGE BREAK
           ================================================================ */
        .page-break { page-break-before: always; }

    </style>
</head>
<body>

@php
    $logoPath   = public_path('DashboardTemplates/assets/img/geodipa-logo.png');
    $logoBase64 = file_exists($logoPath)
                    ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                    : '';
@endphp

{{-- ================================================================
     SECTION 1 — LAPORAN TRANSAKSI BBM
     Natural page break, tidak pakai chunk
     ================================================================ --}}
<div class="page-wrap">

    <div class="doc-header">
        <table class="header-logo-table">
            <tr>
                @if($logoBase64)
                <td class="header-logo-img">
                    <img src="{{ $logoBase64 }}" alt="Logo GeoDipa">
                </td>
                @endif
                <td>
                    <div class="header-company-name">PT. Geo Dipa Energi (Persero)</div>
                    <div class="header-company-sub">Unit Patuha</div>
                    <div class="header-company-address">
                        Jl. Raya Rancabolang KM. 14, Kp. Kendeng - Ds. Sugihmukti,
                        Kec. Pasir Jambu - Kab. Bandung 40973
                    </div>
                </td>
            </tr>
        </table>
        <div class="header-title-wrap">
            <div class="header-title">Laporan Transaksi Bahan Bakar Minyak (BBM)</div>
            <div class="header-subtitle">
                Periode: {{ $startDate->translatedFormat('d F Y') }} s/d {{ $endDate->translatedFormat('d F Y') }}
            </div>
        </div>
        <hr class="header-divider-thick">
        <hr class="header-divider-thin">
        <table class="meta-table">
            <tr>
                <td class="ml">Status</td>
                <td class="ms">:</td>
                <td class="mv">
                    @if(!empty($filterInfo['status']))
                        {{ implode(', ', array_map('ucfirst', $filterInfo['status'])) }}
                    @else
                        Approved, Completed
                    @endif
                </td>
                <td class="ml">Jenis BBM</td>
                <td class="ms">:</td>
                <td class="mv">{{ $filterInfo['gasoline_type'] ?? 'Semua Jenis' }}</td>
            </tr>
            <tr>
                <td class="ml">Total Transaksi</td>
                <td class="ms">:</td>
                <td class="mv">{{ $totalTransaksi }} transaksi</td>
                <td class="ml">Total Pengeluaran</td>
                <td class="ms">:</td>
                <td class="mv">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Kartu transaksi — natural flow, DomPDF auto break kalau penuh --}}
    <table class="card-table mt-3">
        <tbody>
        @foreach($transactions->chunk(2) as $rowPair)
        <tr>
            @foreach($rowPair as $trx)
            <td>
                <div class="card-head">
                    <table class="card-head-inner">
                        <tr>
                            <td>
                                <div class="card-req">No. : {{ $trx->request_number }}</div>
                                <div class="card-date"> Tgl Permohonan : {{ $trx->request_date ? $trx->request_date->format('d/m/Y') : '-' }}</div>
                            </td>
                            <td style="text-align:right;">
                                <span class="card-status status-{{ $trx->status }}">
                                    {{ strtoupper($trx->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                <table class="card-body-table">
                    <tr>
                        <td class="col-info">
                            <table class="info-table">
                                <tr>
                                    <td class="info-lbl">Pemohon</td>
                                    <td class="info-val"><strong>{{ $trx->requester->nama_lengkap ?? 'Unknown' }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="info-lbl">Kendaraan</td>
                                    <td class="info-val">
                                        <strong>{{ $trx->vehicle->consumerial_code ?? '-' }}</strong>
                                        <span class="info-sub"><br>{{ $trx->vehicle->consumerial_name ?? '' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="info-lbl">Nominal</td>
                                    <td class="info-val">
                                        <strong>Rp {{ number_format($trx->bill_amounts, 0, ',', '.') }}</strong>
                                        <span class="info-sub"> {{ $trx->gasoline_type }}</span>
                                    </td>
                                </tr>
                                <tr class="info-divider">
                                    <td colspan="2"><hr class="info-divider-line"></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="info-section-label">Data Pengisian</td>
                                </tr>
                                <tr>
                                    <td class="info-lbl">Tgl Isi</td>
                                    <td>
                                        @if($trx->transactionProof)
                                            <span class="info-val">{{ $trx->transactionProof->purchase_datetime->format('d/m/Y') }}</span>
                                            <span class="info-sub"> {{ $trx->transactionProof->purchase_datetime->format('H:i') }}</span>
                                        @else
                                            <span class="info-val-red">Belum dilaporkan</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($trx->transactionProof)
                                <tr>
                                    <td class="info-lbl">Volume</td>
                                    <td class="info-val">
                                        {{ $trx->transactionProof->fuel_volume ?? 0 }} L
                                        <span class="info-sub"> | ODO: {{ number_format($trx->transactionProof->km_terakhir ?? 0, 0, ',', '.') }} km</span>
                                    </td>
                                </tr>
                                @endif
                                <tr class="info-divider">
                                    <td colspan="2"><hr class="info-divider-line"></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="info-section-label">Persetujuan</td>
                                </tr>
                                <tr>
                                    <td class="info-lbl">Disetujui</td>
                                    <td>
                                        @if($trx->transactionProof)
                                            <span class="info-val"><strong>{{ $trx->authorizer->nama_lengkap ?? 'Unknown' }}</strong></span>
                                        @else
                                            <span class="info-val-red">Belum disetujui</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="info-lbl">Tgl Approve </td>
                                    <td>
                                        @if($trx->transactionProof || $trx->authorization_date)
                                            <span class="info-val"><strong>{{ $trx->authorization_date->format('d/m/Y') }}</strong></span>
                                        @else
                                            <span class="info-val-red">Belum disetujui</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="info-lbl">Tgl Laporan</td>
                                    <td>
                                        @if($trx->transactionProof)
                                            <span class="info-val">{{ $trx->transactionProof->created_at->format('d/m/Y') }}</span>
                                            <span class="info-sub"> {{ $trx->transactionProof->created_at->format('H:i') }}</span>
                                        @else
                                            <span class="info-val-red">—</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="col-photo">
                            @if($trx->transactionProof?->compressed_image)
                                <img src="{{ $trx->transactionProof->compressed_image }}" alt="Struk BBM">
                            @else
                                <div class="no-photo">Tidak ada foto</div>
                            @endif
                            <div class="photo-lbl">Struk BBM</div>
                        </td>
                    </tr>
                </table>
            </td>
            @endforeach

            @if($rowPair->count() === 1)
            <td class="empty-cell"></td>
            @endif
        </tr>
        @endforeach
        </tbody>
    </table>

</div>


{{-- ================================================================
     SECTION 2 — SPREADSHEET
     ================================================================ --}}
<div class="page-break">
    <div class="page-wrap">
        <div class="doc-header">
            <table class="header-logo-table">
                <tr>
                    @if($logoBase64)
                    <td class="header-logo-img">
                        <img src="{{ $logoBase64 }}" alt="Logo GeoDipa">
                    </td>
                    @endif
                    <td>
                        <div class="header-company-name">PT. Geo Dipa Energi (Persero)</div>
                        <div class="header-company-sub">Unit Patuha</div>
                        <div class="header-company-address">
                            Jl. Raya Rancabolang KM. 14, Kp. Kendeng - Ds. Sugihmukti,
                            Kec. Pasir Jambu - Kab. Bandung 40973
                        </div>
                    </td>
                </tr>
            </table>
            <div class="header-title-wrap">
                <div class="header-title">Spreadsheet Laporan Penggunaan BBM</div>
                <div class="header-subtitle">
                    Periode: {{ $startDate->translatedFormat('d F Y') }} s/d {{ $endDate->translatedFormat('d F Y') }}
                </div>
            </div>
            <hr class="header-divider-thick">
            <hr class="header-divider-thin">
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:4%">No</th>
                    <th style="width:11%">Tgl Permohonan</th>
                    <th style="width:18%">Requester</th>
                    <th style="width:9%">Kode Kend.</th>
                    <th style="width:16%">Nama Kendaraan</th>
                    <th style="width:9%">Status</th>
                    <th style="width:11%">Tgl Laporan</th>
                    <th style="width:8%">Volume</th>
                    <th style="width:14%">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $idx => $trx)
                <tr>
                    <td style="text-align:center">{{ $idx + 1 }}</td>
                    <td>{{ $trx->request_date ? $trx->request_date->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $trx->requester->nama_lengkap ?? 'Unknown' }}</td>
                    <td style="text-align:center; font-weight:bold">{{ $trx->vehicle->consumerial_code ?? '-' }}</td>
                    <td>{{ $trx->vehicle->consumerial_name ?? '-' }}</td>
                    <td style="text-align:center">
                        @if($trx->status === 'completed')
                            <span style="color:#16a34a; font-weight:bold">COMPLETED</span>
                        @elseif($trx->status === 'approved')
                            <span style="color:#d97706; font-weight:bold">APPROVED</span>
                        @else
                            <span style="color:#dc2626; font-weight:bold">{{ strtoupper($trx->status) }}</span>
                        @endif
                    </td>
                    <td>{{ $trx->transactionProof ? $trx->transactionProof->created_at->format('d/m/Y H:i') : '-' }}</td>
                    <td style="text-align:right">
                        {{ $trx->transactionProof ? ($trx->transactionProof->fuel_volume ?? '-') . ' L' : '-' }}
                    </td>
                    <td style="text-align:right; font-weight:bold">
                        Rp {{ number_format($trx->bill_amounts, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" style="text-align:right">TOTAL:</td>
                    <td style="text-align:right">{{ number_format($totalVolume, 2, ',', '.') }} L</td>
                    <td style="text-align:right">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>


{{-- ================================================================
     SECTION 3 — SUMMARY KEUANGAN
     ================================================================ --}}
<div class="page-break">
    <div class="page-wrap">
        <div class="doc-header">
            <table class="header-logo-table">
                <tr>
                    @if($logoBase64)
                    <td class="header-logo-img">
                        <img src="{{ $logoBase64 }}" alt="Logo GeoDipa">
                    </td>
                    @endif
                    <td>
                        <div class="header-company-name">PT. Geo Dipa Energi (Persero)</div>
                        <div class="header-company-sub">Unit Patuha</div>
                        <div class="header-company-address">
                            Jl. Raya Rancabolang KM. 14, Kp. Kendeng - Ds. Sugihmukti,
                            Kec. Pasir Jambu - Kab. Bandung 40973
                        </div>
                    </td>
                </tr>
            </table>
            <div class="header-title-wrap">
                <div class="header-title">Summary Keuangan</div>
                <div class="header-subtitle">
                    Periode: {{ $startDate->translatedFormat('d F Y') }} s/d {{ $endDate->translatedFormat('d F Y') }}
                </div>
            </div>
            <hr class="header-divider-thick">
            <hr class="header-divider-thin">
        </div>

        <table class="fin-table">
            <tr class="section-row"><td colspan="2">Ringkasan Transaksi</td></tr>
            <tr>
                <td>Total Transaksi Completed</td>
                <td>{{ $transactions->where('status','completed')->count() }} transaksi</td>
            </tr>
            <tr>
                <td>Total Transaksi Approved (Belum Lapor)</td>
                <td>{{ $transactions->where('status','approved')->count() }} transaksi</td>
            </tr>
            <tr class="section-row"><td colspan="2">Ringkasan Keuangan</td></tr>
            <tr>
                <td>Nilai Pengeluaran Terlapor</td>
                <td>Rp {{ number_format($transactions->where('status','completed')->sum('bill_amounts'), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Nilai Pengeluaran Belum Terlapor</td>
                <td>Rp {{ number_format($transactions->where('status','approved')->sum('bill_amounts'), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Volume BBM Terpakai</td>
                <td>{{ number_format($totalVolume, 2, ',', '.') }} Liter</td>
            </tr>
            <tr>
                <td>Rata-rata Nominal per Transaksi</td>
                <td>Rp {{ number_format($totalTransaksi > 0 ? $totalPengeluaran / $totalTransaksi : 0, 0, ',', '.') }}</td>
            </tr>
            <tr class="section-row"><td colspan="2">Per Jenis BBM</td></tr>
            <tr>
                <td>Pertamina Dex</td>
                <td>Rp {{ number_format($transactions->where('gasoline_type','Pertamina Dex')->sum('bill_amounts'), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pertamax</td>
                <td>Rp {{ number_format($transactions->where('gasoline_type','Pertamax')->sum('bill_amounts'), 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL PENGELUARAN BBM</td>
                <td>Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</div>


{{-- ================================================================
     SECTION 4 — KPI DRIVER
     ================================================================ --}}
@php
$driverStats = $transactions->groupBy('requester_id')->map(function ($reqs) {
    $total     = $reqs->count();
    $completed = $reqs->where('status', 'completed')->count();
    $rejected  = $reqs->where('status', 'rejected')->count();
    return [
        'name'      => $reqs->first()->requester->nama_lengkap ?? 'Unknown',
        'total'     => $total,
        'rejected'  => $rejected,
        'completed' => $completed,
        'rate'      => $total > 0 ? round($completed / $total * 100, 1) : 0,
    ];
})->sortByDesc('total')->values();
@endphp

<div class="page-break">
    <div class="page-wrap">
        <div class="doc-header">
            <table class="header-logo-table">
                <tr>
                    @if($logoBase64)
                    <td class="header-logo-img">
                        <img src="{{ $logoBase64 }}" alt="Logo GeoDipa">
                    </td>
                    @endif
                    <td>
                        <div class="header-company-name">PT. Geo Dipa Energi (Persero)</div>
                        <div class="header-company-sub">Unit Patuha</div>
                        <div class="header-company-address">
                            Jl. Raya Rancabolang KM. 14, Kp. Kendeng - Ds. Sugihmukti,
                            Kec. Pasir Jambu - Kab. Bandung 40973
                        </div>
                    </td>
                </tr>
            </table>
            <div class="header-title-wrap">
                <div class="header-title">KPI Driver</div>
                <div class="header-subtitle">
                    Periode: {{ $startDate->translatedFormat('d F Y') }} s/d {{ $endDate->translatedFormat('d F Y') }}
                </div>
            </div>
            <hr class="header-divider-thick">
            <hr class="header-divider-thin">
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:5%">No</th>
                    <th style="width:40%">Nama Driver</th>
                    <th style="width:15%">Jml Pengajuan</th>
                    <th style="width:13%">Rejected</th>
                    <th style="width:13%">Completed</th>
                    <th style="width:14%">% Pelaporan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($driverStats as $idx => $d)
                <tr>
                    <td style="text-align:center">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold">{{ $d['name'] }}</td>
                    <td style="text-align:center; font-weight:bold">{{ $d['total'] }}</td>
                    <td style="text-align:center">{{ $d['rejected'] }}</td>
                    <td style="text-align:center">{{ $d['completed'] }}</td>
                    <td style="text-align:center">
                        @php $rateColor = $d['rate'] >= 80 ? '#16a34a' : ($d['rate'] >= 50 ? '#d97706' : '#dc2626'); @endphp
                        <strong style="color:{{ $rateColor }}">{{ $d['rate'] }}%</strong>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $totalAll     = $driverStats->sum('total');
                    $completedAll = $driverStats->sum('completed');
                    $overallRate  = $totalAll > 0 ? round($completedAll / $totalAll * 100, 1) : 0;
                @endphp
                <tr>
                    <td colspan="2" style="text-align:right">TOTAL:</td>
                    <td style="text-align:center; font-weight:bold">{{ $totalAll }}</td>
                    <td style="text-align:center; font-weight:bold">{{ $driverStats->sum('rejected') }}</td>
                    <td style="text-align:center; font-weight:bold">{{ $completedAll }}</td>
                    <td style="text-align:center; font-weight:bold">{{ $overallRate }}%</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer-text">
            Dokumen dibuat secara otomatis pada {{ now()->translatedFormat('d F Y') }} pukul {{ now()->format('H:i') }} WIB<br>
            Dicetak oleh: {{ Auth::user()->nama_lengkap ?? Auth::user()->username ?? 'System' }}<br>
            <em>* Dokumen ini sah tanpa tanda tangan dan stempel *</em><br>
            <em>* Disepakati secara internal oleh divisi HCGAF PT. Geo Dipa Energi (Persero) Unit Patuha *</em>
        </div>
    </div>
</div>


{{-- ================================================================
     PHP CANVAS — bar hijau atas & merah bawah di setiap halaman
     ================================================================ --}}
{{-- <script type="text/php">
    if (isset($pdf)) {
        $pageWidth  = $pdf->get_width();
        $pageHeight = $pdf->get_height();

        $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics)
            use ($pageWidth, $pageHeight)
        {
            // BAR ATAS HIJAU TUA (8pt)
            $canvas->filled_rectangle(0, 0, $pageWidth, 8, [22/255, 163/255, 74/255]);

            // BAR ATAS HIJAU MUDA (3pt)
            $canvas->filled_rectangle(0, 8, $pageWidth, 3, [74/255, 222/255, 128/255]);

            // BAR BAWAH MERAH MUDA (3pt)
            $canvas->filled_rectangle(0, $pageHeight - 11, $pageWidth, 3, [248/255, 113/255, 113/255]);

            // BAR BAWAH MERAH TUA (8pt)
            $canvas->filled_rectangle(0, $pageHeight - 8, $pageWidth, 8, [220/255, 38/255, 38/255]);
        });
    }
</script> --}}

</body>
</html>
