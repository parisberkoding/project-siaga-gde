<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vehicle Checklist - {{ $periodLabel }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #093D7D; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin: 0; color: #093D7D; }
        .header p { margin: 3px 0; font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background-color: #093D7D; color: white; font-size: 8px; text-transform: uppercase; }
        td { font-size: 8px; }
        .vehicle-section { margin-bottom: 20px; page-break-inside: avoid; }
        .vehicle-title { background: #EAF1FA; padding: 6px 10px; font-size: 11px; font-weight: bold; color: #093D7D; margin-bottom: 5px; }
        .badge-ok { background: #71A62F; color: white; padding: 1px 5px; border-radius: 3px; font-size: 7px; }
        .badge-ng { background: #ED1B2F; color: white; padding: 1px 5px; border-radius: 3px; font-size: 7px; }
        .summary-box { background: #F8FAFC; padding: 8px; margin-bottom: 10px; border-radius: 4px; }
        .summary-box span { margin-right: 15px; font-size: 9px; }
        .footer { text-align: center; font-size: 8px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>VEHICLE CHECKLIST REPORT</h1>
        <p>Periode: {{ $periodLabel }}</p>
        <p>Dicetak: {{ now()->translatedFormat('d M Y H:i') }} | Dispatcher: {{ auth()->user()->nama_lengkap }}</p>
    </div>

    {{-- Summary --}}
    <div class="summary-box">
        <span><strong>Total Pengecekan:</strong> {{ $checkings->count() }}</span>
        <span><strong>Routine:</strong> {{ $checkings->where('type', 'routine')->count() }}</span>
        <span><strong>Body:</strong> {{ $checkings->where('type', 'body')->count() }}</span>
        <span><strong>Kendaraan:</strong> {{ $groupedByVehicle->count() }}</span>
    </div>

    @foreach($groupedByVehicle as $vehicleId => $vehicleCheckings)
        @php
            $vehicle = $vehicleCheckings->first()->vehicleChecking->vehicle ?? null;
        @endphp
        <div class="vehicle-section">
            <div class="vehicle-title">
                {{ $vehicle->consumerial_code ?? '-' }} &mdash; {{ $vehicle->consumerial_name ?? '-' }}
                ({{ $vehicleCheckings->count() }} pengecekan)
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 15%;">Kode</th>
                        <th style="width: 10%;">Jenis</th>
                        <th style="width: 12%;">Tanggal</th>
                        <th style="width: 13%;">Dispatcher</th>
                        <th style="width: 13%;">Driver</th>
                        <th style="width: 12%;">Hasil</th>
                        <th style="width: 8%;">Ganti</th>
                        <th style="width: 12%;">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicleCheckings as $idx => $checking)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $checking->checking_detail_code }}</td>
                        <td>
                            @if($checking->type === 'routine')
                                Rutin ({{ ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'][$checking->sub_type] ?? $checking->sub_type }})
                            @else
                                Body
                            @endif
                        </td>
                        <td>{{ $checking->checked_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $checking->dispatcher->nama_lengkap ?? '-' }}</td>
                        <td>{{ $checking->currentDriver->nama_lengkap ?? '-' }}</td>
                        <td>
                            @if($checking->type === 'routine')
                                <span class="badge-ok">OK: {{ $checking->itemResults->where('status', 'ok')->count() }}</span>
                                <span class="badge-ng">NG: {{ $checking->itemResults->where('status', 'ng')->count() }}</span>
                            @else
                                B:{{ $checking->itemResults->where('status', 'baik')->count() }}
                                C:{{ $checking->itemResults->where('status', 'cukup')->count() }}
                                K:{{ $checking->itemResults->where('status', 'kurang')->count() }}
                                T:{{ $checking->itemResults->where('status', 'tidak')->count() }}
                            @endif
                        </td>
                        <td>{{ $checking->itemResults->where('needs_replacement', true)->count() }}</td>
                        <td>{{ Str::limit($checking->notes, 40) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    @if($checkings->isEmpty())
        <p style="text-align: center; color: #999; padding: 30px;">Tidak ada data pengecekan pada periode ini.</p>
    @endif

    <div class="footer">
        Vehicle Checklist Report &mdash; Generated by SIAGA System
    </div>
</body>
</html>
