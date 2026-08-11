<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Body Check Report - {{ $periodLabel }}</title>
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
        .badge-baik { background: #198754; color: white; padding: 1px 5px; border-radius: 3px; font-size: 7px; }
        .badge-cukup { background: #0dcaf0; color: #333; padding: 1px 5px; border-radius: 3px; font-size: 7px; }
        .badge-kurang { background: #ffc107; color: #333; padding: 1px 5px; border-radius: 3px; font-size: 7px; }
        .badge-tidak { background: #dc3545; color: white; padding: 1px 5px; border-radius: 3px; font-size: 7px; }
        .summary-box { background: #f8f9fa; padding: 8px; margin-bottom: 10px; border-radius: 4px; }
        .summary-box span { margin-right: 15px; font-size: 9px; }
        .photo-section { margin-top: 5px; }
        .photo-item { display: inline-block; margin: 3px; text-align: center; }
        .photo-item img { max-width: 120px; max-height: 90px; border: 1px solid #ddd; border-radius: 3px; }
        .photo-item p { font-size: 7px; margin: 2px 0 0; color: #666; }
        .footer { text-align: center; font-size: 8px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BODY CHECK REPORT</h1>
        <p>Laporan Pengecekan Body Kendaraan</p>
        <p>Periode: {{ $periodLabel }}</p>
        <p>Dicetak: {{ now()->translatedFormat('d M Y H:i') }} | Dispatcher: {{ auth()->user()->nama_lengkap }}</p>
    </div>

    {{-- Summary --}}
    <div class="summary-box">
        <span><strong>Total Pengecekan:</strong> {{ $checkings->count() }}</span>
        @php
            $allResults = $checkings->flatMap->itemResults;
            $totalItems = $allResults->count();
            $baikCount = $allResults->where('status', 'baik')->count();
            $cukupCount = $allResults->where('status', 'cukup')->count();
            $kurangCount = $allResults->where('status', 'kurang')->count();
            $tidakCount = $allResults->where('status', 'tidak')->count();
            $totalPhotos = $checkings->flatMap->damagePhotos->count();
        @endphp
        <span><strong>Total Item:</strong> {{ $totalItems }}</span>
        <span><strong>Baik:</strong> {{ $baikCount }}</span>
        <span><strong>Cukup:</strong> {{ $cukupCount }}</span>
        <span><strong>Kurang:</strong> {{ $kurangCount }}</span>
        <span><strong>Tidak:</strong> {{ $tidakCount }}</span>
        <span><strong>Foto Kerusakan:</strong> {{ $totalPhotos }}</span>
    </div>

    {{-- Summary Table --}}
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Kendaraan</th>
                <th>Tanggal</th>
                <th>Dispatcher</th>
                <th>Driver</th>
                <th>Baik</th>
                <th>Cukup</th>
                <th>Kurang</th>
                <th>Tidak</th>
                <th>Ganti</th>
                <th>Foto</th>
                <th>Ack</th>
            </tr>
        </thead>
        <tbody>
            @forelse($checkings as $idx => $checking)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $checking->checking_detail_code }}</td>
                <td>{{ $checking->vehicleChecking->nopol ?? '-' }}</td>
                <td>{{ $checking->checked_at->format('d/m/Y H:i') }}</td>
                <td>{{ $checking->dispatcher->nama_lengkap ?? '-' }}</td>
                <td>{{ $checking->currentDriver->nama_lengkap ?? '-' }}</td>
                <td><span class="badge-baik">{{ $checking->itemResults->where('status', 'baik')->count() }}</span></td>
                <td><span class="badge-cukup">{{ $checking->itemResults->where('status', 'cukup')->count() }}</span></td>
                <td><span class="badge-kurang">{{ $checking->itemResults->where('status', 'kurang')->count() }}</span></td>
                <td><span class="badge-tidak">{{ $checking->itemResults->where('status', 'tidak')->count() }}</span></td>
                <td>{{ $checking->itemResults->where('needs_replacement', true)->count() }}</td>
                <td>{{ $checking->damagePhotos->count() }}</td>
                <td>{{ $checking->driver_acknowledged ? 'Ya' : 'Belum' }}</td>
            </tr>
            @empty
            <tr><td colspan="13" style="text-align:center;">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Detail per Pengecekan --}}
    @foreach($checkings as $checking)
    <div class="check-section">
        <div class="check-title">
            {{ $checking->checking_detail_code }} &mdash;
            {{ $checking->vehicleChecking->nopol ?? '-' }}
            ({{ $checking->vehicleChecking->vehicle->consumerial_name ?? '' }}) &mdash;
            {{ $checking->checked_at->format('d/m/Y H:i') }}
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 30%;">Item</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 8%;">Ganti</th>
                    <th style="width: 45%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($checking->itemResults as $ri => $result)
                <tr>
                    <td>{{ $ri + 1 }}</td>
                    <td>{{ $result->vehiclePart->name ?? '-' }}</td>
                    <td>
                        @php $statusClass = 'badge-' . $result->status; @endphp
                        <span class="{{ $statusClass }}">{{ ucfirst($result->status) }}</span>
                    </td>
                    <td>{{ $result->needs_replacement ? 'Ya' : '-' }}</td>
                    <td>{{ $result->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Damage Photos --}}
        @if($checking->damagePhotos->count() > 0)
        <div class="photo-section">
            <strong>Foto Kerusakan:</strong>
            @foreach($checking->damagePhotos as $photo)
            <div class="photo-item">
                @php
                    $photoPath = storage_path('app/public/' . $photo->photo);
                    $photoExists = file_exists($photoPath);
                @endphp
                @if($photoExists)
                    <img src="{{ $photoPath }}" alt="Kerusakan">
                @else
                    <span style="color: #999;">[Foto tidak tersedia]</span>
                @endif
                <p>{{ ['depan' => 'Depan', 'belakang' => 'Belakang', 'kiri' => 'Kiri', 'kanan' => 'Kanan', 'atas' => 'Atas', 'bawah' => 'Bawah', 'interior' => 'Interior'][$photo->zone] ?? $photo->zone }}
                    @if($photo->description) - {{ $photo->description }} @endif
                </p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach

    <div class="footer">
        Body Check Report &mdash; Generated by SIAGA System
    </div>
</body>
</html>
