<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LPJ Penggantian - {{ $nama ?? '' }}</title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm 2cm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .title {
            font-weight: bold;
            font-size: 11pt;
            margin: 5px 0;
        }

        .tanggal-lpj {
            text-align: right;
            font-size: 9pt;
            margin-bottom: 10px;
        }

        .info-section {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            margin: 3px 0;
        }

        .info-label {
            width: 80px;
            flex-shrink: 0;
            font-weight: bold;
        }

        .info-colon {
            width: 15px;
            flex-shrink: 0;
        }

        .info-value {
            flex: 1;
        }

        .uraian-box {
            border: 1px solid #000;
            padding: 8px;
            margin-top: 5px;
            min-height: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9pt;
        }

        table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-weight: bold;
        }

        table td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
        }

        table td.left-align {
            text-align: left;
        }

        table td.right-align {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .signature-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 30%;
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            min-height: 100px;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 60px;
            font-size: 9pt;
        }

        .signature-name {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
        }

        .footer-section {
            margin-top: 15px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }

        .footer-row {
            display: flex;
            margin: 3px 0;
        }

        .footer-label {
            width: 150px;
            flex-shrink: 0;
        }

        .footer-colon {
            width: 15px;
            flex-shrink: 0;
        }

        .footer-value {
            flex: 1;
            border-bottom: 1px solid #000;
        }

        .catatan {
            margin-top: 10px;
            font-size: 8pt;
            font-style: italic;
        }

        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">LEMBAR PERSETUJUAN</div>
        <div class="title">PENGGANTIAN PERTANGGUNG JAWABAN</div>
        <div class="title">PEMBIAYAAN BIAYA JALAN TOL, BBM, PARKIR</div>
    </div>

    <div class="tanggal-lpj">
        Tanggal: {{ $tanggal ?? '__________________' }}
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">No. POL</div>
            <div class="info-colon">:</div>
            <div class="info-value">{{ $nomor_polisi ?? '' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Nama</div>
            <div class="info-colon">:</div>
            <div class="info-value">{{ $nama ?? '' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Jabatan</div>
            <div class="info-colon">:</div>
            <div class="info-value">{{ $jabatan ?? '' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Uraian</div>
            <div class="info-colon">:</div>
            <div class="info-value"></div>
        </div>
        <div class="uraian-box">
            {{ $uraian ?? '' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 10%;">Tanggal</th>
                <th colspan="4">Biaya</th>
                <th rowspan="2" style="width: 15%;">Lain-lain</th>
            </tr>
            <tr>
                <th style="width: 15%;">Biaya Tol</th>
                <th style="width: 15%;">Biaya Portir</th>
                <th style="width: 15%;">Biaya BBM</th>
            </tr>
        </thead>
        <tbody>
            {{-- Loop data dari backend --}}
            {{-- @foreach($lpj_items as $item)
            <tr>
                <td>{{ $item->tanggal }}</td>
                <td class="right-align">Rp. {{ number_format($item->biaya_tol, 0, ',', '.') }}</td>
                <td class="right-align">Rp. {{ number_format($item->biaya_portir, 0, ',', '.') }}</td>
                <td class="right-align">Rp. {{ number_format($item->biaya_bbm, 0, ',', '.') }}</td>
                <td class="right-align">{{ $item->lain_lain ? 'Rp. ' . number_format($item->lain_lain, 0, ',', '.') : '' }}</td>
            </tr>
            @endforeach --}}

            {{-- Static rows for layout - nanti diganti dengan loop --}}
            @for($i = 1; $i <= 27; $i++)
            <tr>
                <td>{{ $i }}</td>
                <td class="right-align">Rp.</td>
                <td class="right-align">Rp.</td>
                <td class="right-align">Rp.</td>
                <td class="right-align"></td>
            </tr>
            @endfor

            {{-- Total Row --}}
            <tr class="total-row">
                <td colspan="1">Total Jumlah Rp.</td>
                <td class="right-align">{{ isset($total_tol) ? 'Rp. ' . number_format($total_tol, 0, ',', '.') : 'Rp.' }}</td>
                <td class="right-align">{{ isset($total_portir) ? 'Rp. ' . number_format($total_portir, 0, ',', '.') : 'Rp.' }}</td>
                <td class="right-align">{{ isset($total_bbm) ? 'Rp. ' . number_format($total_bbm, 0, ',', '.') : 'Rp.' }}</td>
                <td class="right-align">{{ isset($total_lain) ? 'Rp. ' . number_format($total_lain, 0, ',', '.') : '' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-title">User (yang berangkat)</div>
            <div class="signature-name">( {{ $pengguna_nama ?? $laporan->approverPengguna?->nama_lengkap ?? $nama ?? '_______________' }} )</div>
        </div>

        <div class="signature-box">
            <div class="signature-title">Divisi GA</div>
            <div class="signature-name">( {{ $divisi_ga_nama ?? $laporan->approverDivisiGa?->nama_lengkap ?? '_______________' }} )</div>
        </div>
    </div>

    <div class="footer-section">
        <div class="footer-row">
            <div class="footer-label">Catatan:</div>
            <div class="footer-colon"></div>
            <div class="footer-value">{{ $catatan ?? 'Bon Terlampir' }}</div>
        </div>
        <div class="footer-row">
            <div class="footer-label">Uang dari:</div>
            <div class="footer-colon"></div>
            <div class="footer-value">{{ $uang_dari ?? 'KAS / Transfer' }}</div>
        </div>
        <div class="footer-row">
            <div class="footer-label">Jumlah Rp.</div>
            <div class="footer-colon">:</div>
            <div class="footer-value">{{ isset($jumlah) ? 'Rp. ' . number_format($jumlah, 0, ',', '.') : '' }}</div>
        </div>
        <div class="footer-row">
            <div class="footer-label">Pengeluaran</div>
            <div class="footer-colon">:</div>
            <div class="footer-value">{{ isset($pengeluaran) ? 'Rp. ' . number_format($pengeluaran, 0, ',', '.') : '' }}</div>
        </div>
        <div class="footer-row">
            <div class="footer-label">Pengembalian</div>
            <div class="footer-colon">:</div>
            <div class="footer-value">{{ isset($pengembalian) ? 'Rp. ' . number_format($pengembalian, 0, ',', '.') : '' }}</div>
        </div>
        <div class="footer-row">
            <div class="footer-label">Kekurangan</div>
            <div class="footer-colon">:</div>
            <div class="footer-value">{{ isset($kekurangan) ? 'Rp. ' . number_format($kekurangan, 0, ',', '.') : '' }}</div>
        </div>

        <div class="catatan">

        </div>
    </div>
</body>
</html>
