@php
    // Filter & periode lewat query string. Catatan: controller hanya membatasi angka ringkasan dengan
    // start_date/end_date ("period" hanya mengatur pengelompokan grafik), jadi tombol periode di sini
    // mengirim rentang tanggal supaya angka sesuai label.
    $base = request()->except(['page', 'pivot_page', 'driver_page', 'vehicle_page', 'trip_page']);
    $urlWith = function (array $params) use ($base) {
        $query = http_build_query(array_filter(array_merge($base, $params), fn ($value) => filled($value)));

        return url()->current() . ($query ? '?' . $query : '');
    };

    $today = now()->toDateString();
    $presets = [
        'day' => ['Hari ini', $today, $today],
        'month' => ['Bulan ini', now()->startOfMonth()->toDateString(), $today],
        'year' => ['Tahun ini', now()->startOfYear()->toDateString(), $today],
    ];
    $start = request('start_date');
    $end = request('end_date');
    $activePreset = (! $start && ! $end) ? 'all'
        : (collect($presets)->search(fn ($preset) => $preset[1] === $start && $preset[2] === $end) ?: 'custom');
    $rangeLabel = ($start && $end)
        ? \Illuminate\Support\Carbon::parse($start)->translatedFormat('d M Y') . ' - ' . \Illuminate\Support\Carbon::parse($end)->translatedFormat('d M Y')
        : 'Semua waktu';

    $filterKeys = ['status', 'gasoline_type', 'vehicle_id', 'requester_id', 'nominal_range'];
    $statusLabels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'completed' => 'Selesai', 'rejected' => 'Ditolak'];
    $nominalLabels = ['0-100000' => 'Di bawah Rp 100.000', '100000-300000' => 'Rp 100.000 - 300.000', '300000-500000' => 'Rp 300.000 - 500.000', '500000+' => 'Di atas Rp 500.000'];
    $gasolineTypes = ['Pertamax', 'Pertamina Dex', 'Pertalite'];
    $vehicleLabel = fn ($id) => ($v = collect($vehicles ?? [])->firstWhere('id', $id)) ? $v->consumerial_code . ' - ' . $v->consumerial_name : $id;
    $chips = collect([
        'status' => ['Status', fn ($v) => $statusLabels[$v] ?? $v],
        'gasoline_type' => ['BBM', fn ($v) => $v],
        'vehicle_id' => ['Kendaraan', $vehicleLabel],
        'requester_id' => ['Driver', fn ($v) => collect($drivers ?? [])->firstWhere('id', $v)?->nama_lengkap ?? $v],
        'nominal_range' => ['Nominal', fn ($v) => $nominalLabels[$v] ?? $v],
    ])->filter(fn ($cfg, $key) => request()->filled($key))
      ->map(fn ($cfg, $key) => ['label' => $cfg[0], 'value' => ($cfg[1])(request($key)), 'url' => $urlWith([$key => null])]);
    $segment = 'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground aria-[current=true]:bg-card aria-[current=true]:text-foreground aria-[current=true]:shadow-sm';

    $gasolineData = $stats['gasolineExpenseDistribution'] ?? [];
    $gasolineTotal = array_sum($gasolineData);
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
    $compact = fn ($value) => $value >= 1_000_000 ? 'Rp ' . number_format($value / 1_000_000, 1, ',', '.') . ' jt' : $rupiah($value);

    // Grafik: warna tetap per jenis BBM; ringkasan tren (total & titik tertinggi) dari data per periode.
    $gasolineColors = ['Pertamax' => '#0b50a1', 'Pertalite' => '#71a62f', 'Pertamina Dex' => '#ff771d'];
    $fallbackColors = ['#9747ff', '#4154f1', '#ed1b2f'];
    $gasolineColor = fn ($type, $index) => $gasolineColors[$type] ?? $fallbackColors[$index % count($fallbackColors)];
    $chartPeriod = in_array(request('period'), ['day', 'year'], true) ? request('period') : 'month';
    $periodUnit = ['day' => 'per jam', 'year' => 'per bulan', 'month' => 'per hari'][$chartPeriod];
    $expense = $stats['expenseOverTime'] ?? [];
    $expenseTotal = array_sum($expense);
    $peakKey = $expense ? array_search(max($expense), $expense) : null;
    $expensePeak = [
        $peakKey && $chartPeriod === 'month' ? \Illuminate\Support\Carbon::parse($peakKey)->translatedFormat('d M Y') : $peakKey,
        $expense ? max($expense) : 0,
    ];
    $pageData = [
        'stats' => $stats,
        'chart' => [
            'period' => $chartPeriod,
            'start' => $start,
            'end' => $end,
            'colors' => collect(array_keys($gasolineData))->map(fn ($type, $i) => $gasolineColor($type, $i))->all(),
        ],
    ];
    $activeModule = app(\App\Services\ModuleContext::class)->current();
@endphp

<x-layouts.app :title="$title" :breadcrumbs="['Dashboard' => null]">
    <x-ui.page-header :kicker="$activeModule?->name" :icon="$activeModule?->icon" :title="$title" description="Ringkasan pengeluaran, konsumsi, dan pelaporan BBM.">
        <x-slot:actions>
            @if (Auth::user()->hasPermission('vehicle.report'))
                <x-ui.button variant="outline" :href="route('superadmin.trip-analysis.index')"><i class="bi bi-signpost-2"></i> Trip Analysis</x-ui.button>
            @endif
            @if (Auth::user()->role === 'superadmin')
                <x-ui.button data-dialog-open="printReportDialog"><i class="bi bi-printer"></i> Cetak Laporan Transaksi</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Toolbar periode + filter (pola shadcn data-table toolbar). Semua lewat GET; filter diterapkan
         dengan tombol, bukan tiap isian berubah, supaya halaman tidak dimuat ulang berkali-kali. --}}
    <x-ui.card class="gap-0 py-0">
        <div class="flex flex-wrap items-center gap-3 p-3">
            <div class="inline-flex flex-wrap items-center gap-0.5 rounded-lg bg-muted p-1" role="group" aria-label="Periode">
                @foreach ($presets as $key => [$label, $from, $to])
                    <a href="{{ $urlWith(['start_date' => $from, 'end_date' => $to, 'period' => $key]) }}" class="{{ $segment }}"
                       aria-current="{{ $activePreset === $key ? 'true' : 'false' }}">{{ $label }}</a>
                @endforeach
                <a href="{{ $urlWith(['start_date' => null, 'end_date' => null, 'period' => null]) }}" class="{{ $segment }}"
                   aria-current="{{ $activePreset === 'all' ? 'true' : 'false' }}">Semua waktu</a>

                <details data-menu class="relative">
                    <summary class="{{ $segment }} cursor-pointer list-none [&::-webkit-details-marker]:hidden" aria-current="{{ $activePreset === 'custom' ? 'true' : 'false' }}">
                        <i class="bi bi-calendar-range"></i> Kustom
                    </summary>
                    <form method="GET" action="{{ url()->current() }}" data-custom-range
                          class="absolute left-0 z-40 mt-2 grid w-72 gap-3 rounded-lg border bg-popover p-4 text-popover-foreground shadow-lg">
                        @foreach ($filterKeys as $key)
                            @if (request()->filled($key))<input type="hidden" name="{{ $key }}" value="{{ request($key) }}">@endif
                        @endforeach
                        <input type="hidden" name="period" value="month">
                        <p class="text-sm font-semibold">Rentang tanggal</p>
                        <x-ui.field label="Dari" for="range_start">
                            <x-ui.input type="date" id="range_start" name="start_date" :value="$start" :max="$today" required />
                        </x-ui.field>
                        <x-ui.field label="Sampai" for="range_end">
                            <x-ui.input type="date" id="range_end" name="end_date" :value="$end" :max="$today" required />
                        </x-ui.field>
                        <x-ui.button type="submit" size="sm"><i class="bi bi-check2"></i> Terapkan</x-ui.button>
                    </form>
                </details>
            </div>

            <span class="inline-flex items-center gap-1.5 text-sm text-muted-foreground"><i class="bi bi-calendar3"></i> {{ $rangeLabel }}</span>

            <details data-menu class="relative ml-auto">
                <summary class="inline-flex h-9 cursor-pointer list-none items-center gap-2 rounded-md border bg-card px-3 text-sm font-medium shadow-xs transition-colors hover:bg-accent [&::-webkit-details-marker]:hidden">
                    <i class="bi bi-funnel"></i> Filter
                    @if ($chips->isNotEmpty())
                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-xs text-primary-foreground">{{ $chips->count() }}</span>
                    @endif
                </summary>
                <form method="GET" action="{{ url()->current() }}"
                      data-clean-query class="absolute right-0 z-40 mt-2 grid w-[min(24rem,calc(100vw-2rem))] gap-4 rounded-lg border bg-popover p-4 text-popover-foreground shadow-lg">
                    @foreach (['start_date', 'end_date', 'period'] as $key)
                        @if (request()->filled($key))<input type="hidden" name="{{ $key }}" value="{{ request($key) }}">@endif
                    @endforeach
                    <div>
                        <p class="text-sm font-semibold">Filter transaksi</p>
                        <p class="text-xs text-muted-foreground">Berlaku untuk ringkasan, grafik, dan ranking.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.field label="Status" for="filter_status">
                            <x-ui.select name="status" id="filter_status">
                                <option value="">Semua status</option>
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(request('status') == $value)>{{ $label }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Jenis BBM" for="filter_gasoline">
                            <x-ui.select name="gasoline_type" id="filter_gasoline">
                                <option value="">Semua jenis</option>
                                @foreach ($gasolineTypes as $gt)
                                    <option value="{{ $gt }}" @selected(request('gasoline_type') == $gt)>{{ $gt }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Kendaraan" for="filter_vehicle" class="sm:col-span-2">
                            <x-ui.select name="vehicle_id" id="filter_vehicle">
                                <option value="">Semua kendaraan</option>
                                @foreach ($vehicles ?? [] as $vehicle)
                                    <option value="{{ $vehicle->id }}" @selected(request('vehicle_id') == $vehicle->id)>{{ $vehicle->consumerial_code }} - {{ $vehicle->consumerial_name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Driver / pemohon" for="filter_requester">
                            <x-ui.select name="requester_id" id="filter_requester">
                                <option value="">Semua driver</option>
                                @foreach ($drivers ?? [] as $driver)
                                    <option value="{{ $driver->id }}" @selected(request('requester_id') == $driver->id)>{{ $driver->nama_lengkap }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Nominal" for="filter_nominal">
                            <x-ui.select name="nominal_range" id="filter_nominal">
                                <option value="">Semua nominal</option>
                                @foreach ($nominalLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(request('nominal_range') == $value)>{{ $label }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                    </div>
                    <div class="flex items-center justify-between gap-2 border-t pt-3">
                        <x-ui.button variant="ghost" size="sm" :href="$urlWith(array_fill_keys($filterKeys, null))">Reset</x-ui.button>
                        <x-ui.button type="submit" size="sm"><i class="bi bi-check2"></i> Terapkan</x-ui.button>
                    </div>
                </form>
            </details>
        </div>

        @if ($chips->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2 border-t px-3 py-2.5">
                <span class="text-xs font-medium text-muted-foreground">Filter aktif:</span>
                @foreach ($chips as $chip)
                    <a href="{{ $chip['url'] }}" title="Hapus filter {{ $chip['label'] }}"
                       class="inline-flex items-center gap-1.5 rounded-full border bg-accent/60 py-0.5 pl-2.5 pr-1.5 text-xs text-accent-foreground transition-colors hover:border-destructive/40 hover:bg-destructive-soft hover:text-destructive">
                        <span class="text-muted-foreground">{{ $chip['label'] }}:</span> <span class="max-w-48 truncate font-medium">{{ $chip['value'] }}</span>
                        <i class="bi bi-x"></i>
                    </a>
                @endforeach
                <a href="{{ $urlWith(array_fill_keys($filterKeys, null)) }}" class="text-xs font-medium text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">Hapus semua</a>
            </div>
        @endif
    </x-ui.card>

    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <h2 class="text-lg font-semibold">Ringkasan</h2>
        <span class="text-sm text-muted-foreground">{{ $rangeLabel }}@if ($chips->isNotEmpty()) &middot; {{ $chips->count() }} filter aktif @endif</span>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total transaksi" :value="$stats['totalTransactions'] ?? $stats['total'] ?? 0" :hint="request('status') ? 'Status ' . ($statusLabels[request('status')] ?? request('status')) : 'Semua status'" icon="bi bi-receipt" />
        <x-ui.stat-card label="Total pengeluaran" :value="$rupiah($stats['totalExpense'])" hint="Disetujui dan selesai" icon="bi bi-cash-stack" tone="success" />
        <x-ui.stat-card label="Rata-rata nilai permohonan" :value="$rupiah($stats['avgRequestValue'] ?? 0)" hint="Per permohonan" icon="bi bi-currency-exchange" tone="warning" />
        <x-ui.stat-card label="Rata-rata frekuensi" :value="$stats['avgRequestFrequency']['value']" :hint="$stats['avgRequestFrequency']['label']" icon="bi bi-graph-up" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-ui.card class="gap-4 lg:col-span-2">
            <x-ui.card-header title="Tren pengeluaran" icon="bi bi-bar-chart-line"
                :description="'Disetujui dan selesai, ' . $periodUnit" />
            <x-ui.card-content class="grid gap-4">
                @if ($expenseTotal > 0)
                    <div class="flex flex-wrap gap-x-8 gap-y-2">
                        <div>
                            <p class="text-xs text-muted-foreground">Total periode</p>
                            <p class="text-xl font-bold tabular-nums tracking-tight">{{ $rupiah($expenseTotal) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Tertinggi</p>
                            <p class="text-xl font-bold tabular-nums tracking-tight">{{ $rupiah($expensePeak[1]) }}
                                <span class="text-sm font-normal text-muted-foreground">{{ $expensePeak[0] }}</span></p>
                        </div>
                    </div>
                @endif
                <div class="relative h-72"><canvas id="expenseChart" aria-label="Grafik tren pengeluaran BBM" role="img"></canvas></div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="gap-4">
            <x-ui.card-header title="Distribusi jenis BBM" icon="bi bi-pie-chart" description="Porsi pengeluaran per jenis BBM" />
            <x-ui.card-content class="grid gap-5">
                <div class="relative mx-auto size-48">
                    <canvas id="gasolineChart" aria-label="Grafik distribusi jenis BBM" role="img"></canvas>
                    @if ($gasolineTotal > 0)
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-xs text-muted-foreground">Total</span>
                            <span class="text-base font-bold tabular-nums">{{ $compact($gasolineTotal) }}</span>
                        </div>
                    @endif
                </div>
                <ul class="grid gap-3 text-sm">
                    @forelse ($gasolineData as $type => $amount)
                        @php $percent = $gasolineTotal > 0 ? round($amount / $gasolineTotal * 100, 1) : 0; @endphp
                        <li class="grid gap-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2 font-medium"><span class="size-2.5 rounded-full" style="background: {{ $gasolineColor($type, $loop->index) }}"></span>{{ $type }}</span>
                                <span class="tabular-nums text-muted-foreground">{{ $rupiah($amount) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full" style="width: {{ $percent }}%; background: {{ $gasolineColor($type, $loop->index) }}"></div></div>
                                <span class="w-12 text-right text-xs tabular-nums text-muted-foreground">{{ $percent }}%</span>
                            </div>
                        </li>
                    @empty
                        <li class="text-center text-muted-foreground">Belum ada pengeluaran pada periode ini.</li>
                    @endforelse
                </ul>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    {{-- Top 10: dua daftar berperingkat berdampingan (lebih mudah dibaca daripada grafik batang horizontal). --}}
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ([
            ['title' => 'Top 10 kendaraan', 'icon' => 'bi bi-truck', 'data' => $stats['vehicleExpense'] ?? [], 'bar' => 'bg-module-bbm'],
            ['title' => 'Top 10 driver', 'icon' => 'bi bi-person', 'data' => $stats['driverExpense'] ?? [], 'bar' => 'bg-primary'],
        ] as $ranking)
            @php $max = max(1, ...array_values($ranking['data'] ?: [0])); @endphp
            <x-ui.card class="gap-4">
                <x-ui.card-header :title="$ranking['title']" :icon="$ranking['icon']" description="Pengeluaran BBM terbesar pada periode ini" />
                <x-ui.card-content>
                    @if (empty($ranking['data']))
                        <x-ui.empty-state icon="bi bi-trophy" title="Belum ada data" description="Tidak ada pengeluaran pada periode dan filter ini." />
                    @else
                        <ol class="grid gap-3.5">
                            @foreach ($ranking['data'] as $name => $total)
                                <li class="grid grid-cols-[1.75rem_1fr] items-center gap-x-3 gap-y-1.5">
                                    <span @class([
                                        'row-span-2 flex size-7 items-center justify-center rounded-full text-xs font-bold',
                                        'bg-warning-soft text-warning' => $loop->first,
                                        'bg-muted text-muted-foreground' => ! $loop->first,
                                    ])>{{ $loop->iteration }}</span>
                                    <div class="flex min-w-0 items-baseline justify-between gap-3 text-sm">
                                        <span class="truncate font-medium" title="{{ $name }}">{{ $name }}</span>
                                        <span class="shrink-0 font-semibold tabular-nums">{{ $rupiah($total) }}</span>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                                        <div class="h-full rounded-full {{ $ranking['bar'] }}" style="width: {{ round($total / $max * 100, 1) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.card class="gap-4">
        <x-ui.card-header title="Daftar transaksi" icon="bi bi-table" description="Seluruh permohonan BBM terbaru, tidak terpengaruh periode dan filter di atas.">
            <x-slot:action>
                <div class="flex gap-2">
                    <x-ui.input type="search" class="w-48" data-table-search="pivotTable" placeholder="Cari di halaman ini" aria-label="Cari" />
                    @if (Auth::user()->hasPermission('bbm.export'))
                        <x-ui.button variant="outline" data-export-table="pivotTable" data-filename="pivot_data.csv"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</x-ui.button>
                    @endif
                </div>
            </x-slot:action>
        </x-ui.card-header>
        <x-ui.card-content class="grid gap-4">
            <x-ui.table id="pivotTable">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Driver</th>
                        <th>Kendaraan</th>
                        <th>Tgl pengajuan</th>
                        <th class="text-right">Nominal</th>
                        <th>Status</th>
                        <th>Tgl pengisian</th>
                        <th class="text-right">Volume</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pivotData as $row)
                        <tr>
                            <td><code class="text-xs">{{ $row['requestNumber'] }}</code></td>
                            <td>{{ $row['driverName'] }}</td>
                            <td>{{ $row['vehicleDisplay'] }}</td>
                            <td class="whitespace-nowrap">{{ $row['requestAt']->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap text-right font-medium tabular-nums">{{ $rupiah($row['nominal']) }}</td>
                            <td><x-bbm.status-badge :status="$row['status']" /></td>
                            <td class="whitespace-nowrap">{{ $row['tanggalPengisian']?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-right tabular-nums">{{ $row['volumePengisian'] > 0 ? $row['volumePengisian'] . ' L' : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-muted-foreground">Tidak ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
            {{ $pivotData->links('vendor.pagination.shadcn') }}
        </x-ui.card-content>
    </x-ui.card>

    <x-ui.card class="gap-4">
        <x-ui.card-header title="Statistik per driver" icon="bi bi-person-check" description="Seluruh waktu, tidak terpengaruh periode dan filter di atas.">
            <x-slot:action>
                <x-ui.input type="search" class="w-56" data-table-search="driverStatsTable" placeholder="Cari driver" aria-label="Cari driver" />
            </x-slot:action>
        </x-ui.card-header>
        <x-ui.card-content class="grid gap-4">
            <x-ui.table id="driverStatsTable">
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Ditolak</th>
                        <th class="text-center">Selesai</th>
                        <th class="text-center">Disetujui</th>
                        <th class="text-center">Menunggu</th>
                        <th class="text-center">Pelaporan</th>
                        <th class="text-right">Rata-rata nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($driverStats as $driver)
                        <tr>
                            <td class="font-medium">{{ $driver['driver'] }}</td>
                            <td class="text-center tabular-nums">{{ $driver['total'] }}</td>
                            <td class="text-center"><x-ui.badge variant="danger">{{ $driver['rejected'] }}</x-ui.badge></td>
                            <td class="text-center"><x-ui.badge variant="info">{{ $driver['completed'] }}</x-ui.badge></td>
                            <td class="text-center"><x-ui.badge variant="success">{{ $driver['approved'] }}</x-ui.badge></td>
                            <td class="text-center"><x-ui.badge variant="warning">{{ $driver['pending'] }}</x-ui.badge></td>
                            <td>
                                <div class="mx-auto flex w-28 items-center gap-2">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"><div @class(['h-full rounded-full', 'bg-success' => $driver['reportRate'] >= 80, 'bg-warning' => $driver['reportRate'] >= 40 && $driver['reportRate'] < 80, 'bg-destructive' => $driver['reportRate'] < 40]) style="width: {{ min(100, $driver['reportRate']) }}%"></div></div>
                                    <span class="w-10 text-right text-xs tabular-nums">{{ $driver['reportRate'] }}%</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-right font-medium tabular-nums" title="Tertinggi {{ $rupiah($driver['maxNominal']) }}, terendah {{ $rupiah($driver['minNominal']) }}">
                                {{ $rupiah($driver['avgNominal']) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-muted-foreground">Tidak ada data driver.</td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
            {{ $driverStats->links('vendor.pagination.shadcn') }}
        </x-ui.card-content>
    </x-ui.card>

    <x-ui.card class="gap-4">
        <x-ui.card-header title="Performa KM kendaraan" icon="bi bi-speedometer2" description="Seluruh waktu, tidak terpengaruh periode dan filter di atas." />
        <x-ui.card-content class="grid gap-4">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Kendaraan</th>
                        <th class="text-right">Rata-rata KM</th>
                        <th class="text-right">Total trip</th>
                        <th class="text-right">KM tertinggi</th>
                        <th class="text-right">KM terendah</th>
                        <th class="text-right">Odometer terakhir</th>
                        <th class="text-right">Update terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kmPerformance as $vehicle)
                        <tr>
                            <td class="font-medium">{{ $vehicle['vehicleDisplay'] }}</td>
                            <td class="text-right tabular-nums">{{ $vehicle['avgKmDiff'] }} km</td>
                            <td class="text-right tabular-nums">{{ $vehicle['totalTrips'] }}</td>
                            <td class="text-right tabular-nums">{{ $vehicle['maxKm'] }} km</td>
                            <td class="text-right tabular-nums">{{ $vehicle['minKm'] }} km</td>
                            <td class="text-right font-medium tabular-nums">{{ number_format($vehicle['latestOdo'], 0, ',', '.') }} km</td>
                            <td class="whitespace-nowrap text-right text-muted-foreground">{{ $vehicle['lastUpdate']->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-muted-foreground">Belum ada data odometer.</td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
            {{ $kmPerformance->links('vendor.pagination.shadcn') }}
        </x-ui.card-content>
    </x-ui.card>

    @if (Auth::user()->role === 'superadmin')
        <x-ui.dialog id="printReportDialog" title="Cetak laporan transaksi BBM" description="Pilih rentang tanggal dan status transaksi. PDF dibuka di tab baru." size="lg">
            <form action="{{ route('superadmin.print-report') }}" method="GET" target="_blank" id="formPrintReport" class="grid gap-5">
                <input type="hidden" name="download_token">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Tanggal mulai" for="print_start_date" required>
                        <x-ui.input type="date" id="print_start_date" name="start_date" required max="{{ date('Y-m-d') }}" />
                    </x-ui.field>
                    <x-ui.field label="Tanggal akhir" for="print_end_date" required>
                        <x-ui.input type="date" id="print_end_date" name="end_date" required max="{{ date('Y-m-d') }}" />
                    </x-ui.field>
                </div>

                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">Status transaksi</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach (['approved' => true, 'completed' => true, 'pending' => false, 'rejected' => false] as $status => $checked)
                            <label class="flex cursor-pointer items-center gap-2 rounded-md border p-3 has-[:checked]:border-primary has-[:checked]:bg-accent">
                                <x-ui.checkbox name="status[]" value="{{ $status }}" :checked="$checked" />
                                <x-bbm.status-badge :status="$status" />
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-muted-foreground">Bila tidak ada yang dipilih, laporan memuat status Disetujui dan Selesai.</p>
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Jenis BBM" for="print_gasoline">
                        <x-ui.select name="gasoline_type" id="print_gasoline">
                            <option value="">Semua jenis BBM</option>
                            @foreach (['Pertamax', 'Pertamina Dex', 'Pertalite'] as $gt)
                                <option value="{{ $gt }}">{{ $gt }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Diproses oleh" for="print_authorizer">
                        <x-ui.select name="authorizer_id" id="print_authorizer">
                            <option value="">Semua approver</option>
                            @foreach ($approvers as $approver)
                                <option value="{{ $approver->id }}">{{ $approver->nama_lengkap }} ({{ ucfirst($approver->role) }})</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                </div>
            </form>
            <x-slot:footer>
                <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                <x-ui.button type="submit" form="formPrintReport" name="export_type" value="pdf"><i class="bi bi-file-earmark-pdf"></i> Cetak PDF</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>

        <div id="pdfLoadingOverlay" hidden class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
            <x-ui.card class="w-80 gap-3 px-8 text-center">
                <i class="bi bi-file-earmark-pdf animate-pulse text-4xl text-primary"></i>
                <p class="font-semibold">Memuat dokumen laporan</p>
                <p class="text-sm text-muted-foreground">Sedang memproses data transaksi BBM.</p>
                <div class="h-2 overflow-hidden rounded-full bg-muted"><div id="pdfProgressBar" class="h-full w-0 rounded-full bg-primary transition-[width] duration-300"></div></div>
                <p id="pdfProgressLabel" class="text-xs text-muted-foreground">0%</p>
            </x-ui.card>
        </div>
    @endif

    <script type="application/json" id="page-data">@json($pageData)</script>

    @push('scripts')
        @vite('resources/js/pages/bbm/dashboard.js')
    @endpush
</x-layouts.app>
