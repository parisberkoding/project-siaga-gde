<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan Service - {{ $monthLabel }}</title>
    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }
        body { font-size: 10px; color: #222; margin: 18px; }
        h1 { font-size: 16px; margin: 0 0 4px; text-align: center; }
        .subtitle { text-align: center; color: #555; margin-bottom: 14px; }
        h2 { font-size: 11px; margin: 16px 0 6px; border-bottom: 1px solid #c9d3e3; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #c9d3e3; padding: 4px 6px; vertical-align: top; }
        th { background: #e8eef8; text-align: left; }
        .text-end { text-align: right; }
        .summary td:first-child { width: 55%; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h1>LAPORAN KEUANGAN SERVICE</h1>
    <div class="subtitle">Periode: {{ $monthLabel }}</div>

    <h2>Ringkasan</h2>
    <table class="summary">
        <tr><td>Total Biaya</td><td class="text-end">Rp {{ number_format((float) ($summary['total_cost'] ?? 0), 0, ',', '.') }}</td></tr>
        <tr><td>Jumlah Transaksi</td><td class="text-end">{{ $summary['total_vpr'] ?? 0 }}</td></tr>
        <tr><td>Transaksi Selesai</td><td class="text-end">{{ $summary['completed_count'] ?? 0 }}</td></tr>
        <tr><td>Transaksi Overdue</td><td class="text-end">{{ $summary['overdue_count'] ?? 0 }}</td></tr>
        <tr><td>Total Item</td><td class="text-end">{{ $summary['total_items'] ?? 0 }}</td></tr>
        <tr><td>Rata-rata per Transaksi</td><td class="text-end">Rp {{ number_format((float) ($summary['avg_cost_per_trx'] ?? 0), 0, ',', '.') }}</td></tr>
    </table>

    <h2>Rincian Transaksi</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th>Kode Transaksi</th>
                <th>Tanggal</th>
                <th>Kendaraan</th>
                <th>Driver</th>
                <th class="text-end">Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $index => $trx)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $trx->vprt_code ?? '-' }}</td>
                    <td>{{ $trx->service_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $trx->replacement?->nopol ?? '-' }}</td>
                    <td>{{ $trx->driver?->nama_lengkap ?? '-' }}</td>
                    <td class="text-end">Rp {{ number_format((float) ($trx->total_amount ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if (! empty($topVehicles) && count($topVehicles) > 0)
        <h2>Kendaraan dengan Biaya Tertinggi</h2>
        <table>
            <thead>
                <tr><th>Kendaraan</th><th>Transaksi</th><th class="text-end">Total Biaya</th></tr>
            </thead>
            <tbody>
                @foreach ($topVehicles as $vehicle)
                    <tr>
                        <td>{{ $vehicle->nopol ?? '-' }}</td>
                        <td>{{ $vehicle->trx_count ?? 0 }}</td>
                        <td class="text-end">Rp {{ number_format((float) ($vehicle->total_cost ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($topItems) && count($topItems) > 0)
        <h2>Sparepart Paling Banyak Diganti</h2>
        <table>
            <thead>
                <tr><th>Nama Part</th><th>Jumlah</th><th class="text-end">Total Biaya</th></tr>
            </thead>
            <tbody>
                @foreach ($topItems as $item)
                    <tr>
                        <td>{{ $item->part_name ?? '-' }}</td>
                        <td>{{ $item->total_qty ?? 0 }}</td>
                        <td class="text-end">Rp {{ number_format((float) ($item->total_cost ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($trendLabels))
        <h2>Tren 6 Bulan</h2>
        <table>
            <thead>
                <tr><th>Bulan</th><th>Jumlah Transaksi</th><th class="text-end">Total Biaya</th></tr>
            </thead>
            <tbody>
                @foreach ($trendLabels as $index => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $trendCounts[$index] ?? 0 }}</td>
                        <td class="text-end">Rp {{ number_format((float) ($trendCosts[$index] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
