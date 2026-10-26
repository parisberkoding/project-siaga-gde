<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Pemberian Dana - {{ $surat->nomor_surat }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 3cm;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.35;
            color: #000;
        }

        .sheet {
            width: 100%;
            max-width: 1340px;
            margin: 0 auto;
        }

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

        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            margin: 14px 0 2px;
        }

        .doc-line {
            font-size: 11pt;
            margin: 2px 0 22px;
        }

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

        table.pencairan th { font-weight: bold; text-align: left; }
        table.pencairan th.center { text-align: center; }
        table.pencairan td.num { text-align: right; white-space: nowrap; }
        table.pencairan td.center { text-align: center; }
        table.pencairan td.left { text-align: left; }

        table.pencairan tfoot tr.grand td,
        table.pencairan tfoot tr.grand th {
            font-weight: bold;
            font-style: italic;
        }

        .no-print {
            position: sticky;
            top: 12px;
            z-index: 100;
            margin: 0 0 20px;
            padding: 12px 18px;
            background: #fff;
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

        .toolbar-title { display: flex; align-items: center; gap: 10px; min-width: 0; }

        .toolbar-title .doc-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 12px;
            font-weight: 600;
            border-radius: 999px;
            white-space: nowrap;
        }

        .toolbar-title .doc-name {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }

        .toolbar-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

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
            text-decoration: none;
        }

        .act-btn-print { background: #16a34a; color: #fff; }
        .act-btn-close { background: #fff; color: #374151; border-color: #d1d5db; }

        @media print {
            .no-print { display: none !important; }
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
    $rp = fn ($v) => 'Rp. ' . number_format((float) $v, 0, ',', '.');
    $balanceAwal = (float) $pencairans->sum('nominal');
    $balanceAkhir = $balanceAwal - (float) $laporan->total_pengeluaran;
@endphp

<div class="no-print">
    <div class="toolbar">
        <div class="toolbar-title">
            <span class="doc-badge">KHUSUS GA</span>
            <span class="doc-name">Lembar Lampiran Pemberian Dana — {{ $surat->nomor_surat }}</span>
        </div>
        <div class="toolbar-actions">
            <button onclick="window.print()" class="act-btn act-btn-print">Cetak / Simpan PDF</button>
            <button onclick="window.close()" class="act-btn act-btn-close">Tutup</button>
        </div>
    </div>
</div>

<div class="sheet">
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

    <div class="doc-title">Lembar Lampiran Pemberian Dana Perjalanan Dinas</div>
    <div class="doc-line">No. Surat Dinas : <strong>{{ $surat->nomor_surat }}</strong></div>

    <table class="ringkasan-head">
        <tr>
            <td class="lbl">Balance Awal</td>
            <td class="sep">:</td>
            <td class="val">{{ $rp($balanceAwal) }}</td>
            <td class="lbl">Balance Akhir</td>
            <td class="sep">:</td>
            <td class="val">
                {{ $rp($balanceAkhir) }}
                @if($balanceAkhir < 0)
                    <span style="font-size: 9pt;">(kekurangan)</span>
                @endif
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
            @forelse($pencairans as $pencairan)
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
                <td class="num">{{ $rp($balanceAwal) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
    window.onload = function () {
        setTimeout(function () { window.print(); }, 500);
    };
</script>

</body>
</html>
