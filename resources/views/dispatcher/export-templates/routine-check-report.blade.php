<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Routine Check Report - {{ $periodLabel }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #012970; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin: 0; color: #012970; }
        .header p { margin: 3px 0; font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background-color: #012970; color: white; font-size: 8px; text-transform: uppercase; }
        td { font-size: 8px; }
        .check-section { margin-bottom: 20px; page-break-inside: avoid; }
        .check-title { background: #e8f0fe; padding: 6px 10px; font-size: 10px; font-weight: bold; color: #012970; margin-bottom: 5px; }
        .badge-ok { background: #198754; color: white; padding: 1px 5px; border-radius: 3px; font-size: 7px; }
        .badge-ng { background: #dc3545; color: white; padding: 1px 5px; border-radius: 3px; font-size: 7px; }
        .summary-box { background: #f8f9fa; padding: 8px; margin-bottom: 10px; border-radius: 4px; }
        .summary-box span { margin-right: 15px; font-size: 9px; }
        .sub-type-daily { background: #0d6efd; color: white; padding: 1px 5px; border-radius: 3px; }
        .sub-type-weekly { background: #0dcaf0; color: #333; padding: 1px 5px; border-radius: 3px; }
        .sub-type-monthly { background: #6c757d; color: white; padding: 1px 5px; border-radius: 3px; }
        .footer { text-align: center; font-size: 8px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ROUTINE CHECK REPORT</h1>
        <p>Laporan Pengecekan Rutin (Harian / Mingguan / Bulanan)</p>
        <p>Periode: {{ $periodLabel }}</p>
        <p>Dicetak: {{ now()->format('d M Y H:i') }} | Dispatcher: {{ auth()->user()->nama_lengkap }}</p>
    </div>

    {{-- Summary --}}
    <div class="summary-box">
        <span><strong>Total Pengecekan:</strong> {{ $checkings->count() }}</span>
        <span><strong>Harian:</strong> {{ $checkings->where('sub_type', 'daily')->count() }}</span>
        <span><strong>Mingguan:</strong> {{ $checkings->where('sub_type', 'weekly')->count() }}</span>
        <span><strong>Bulanan:</strong> {{ $checkings->where('sub_type', 'monthly')->count() }}</span>
    </div>

    {{-- Summary Table --}}
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Kendaraan</th>
                <th>Frekuensi</th>
                <th>Tanggal</th>
                <th>Dispatcher</th>
                <th>Driver</th>
                <th>OK</th>
                <th>NG</th>
                <th>Perlu Ganti</th>
                <th>Ack Driver</th>
            </tr>
        </thead>
        <tbody>
            @forelse($checkings as $idx => $checking)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $checking->checking_detail_code }}</td>
                <td>{{ $checking->vehicleChecking->nopol ?? '-' }}</td>
                <td>
                    @php $subLabels = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan']; @endphp
                    <span class="sub-type-{{ $checking->sub_type }}">{{ $subLabels[$checking->sub_type] ?? $checking->sub_type }}</span>
                </td>
                <td>{{ $checking->checked_at->format('d/m/Y H:i') }}</td>
                <td>{{ $checking->dispatcher->nama_lengkap ?? '-' }}</td>
                <td>{{ $checking->currentDriver->nama_lengkap ?? '-' }}</td>
                <td><span class="badge-ok">{{ $checking->itemResults->where('status', 'ok')->count() }}</span></td>
                <td><span class="badge-ng">{{ $checking->itemResults->where('status', 'ng')->count() }}</span></td>
                <td>{{ $checking->itemResults->where('needs_replacement', true)->count() }}</td>
                <td>{{ $checking->driver_acknowledged ? 'Ya' : 'Belum' }}</td>
            </tr>
            @empty
            <tr><td colspan="11" style="text-align:center;">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Detail per Pengecekan --}}
    @foreach($checkings as $checking)
    <div class="check-section">
        <div class="check-title">
            {{ $checking->checking_detail_code }} &mdash;
            {{ $checking->vehicleChecking->nopol ?? '-' }} &mdash;
            {{ $checking->checked_at->format('d/m/Y H:i') }}
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 30%;">Item</th>
                    <th style="width: 10%;">Kategori</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;">Ganti</th>
                    <th style="width: 35%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($checking->itemResults as $ri => $result)
                <tr>
                    <td>{{ $ri + 1 }}</td>
                    <td>{{ $result->vehiclePart->name ?? '-' }}</td>
                    <td>{{ $result->vehiclePart->category ?? '-' }}</td>
                    <td>
                        @if($result->status === 'ok')
                            <span class="badge-ok">OK</span>
                        @else
                            <span class="badge-ng">NG</span>
                        @endif
                    </td>
                    <td>{{ $result->needs_replacement ? 'Ya' : '-' }}</td>
                    <td>{{ $result->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    <div class="footer">
        Routine Check Report &mdash; Generated by SIAGA System
    </div>
</body>
</html>
