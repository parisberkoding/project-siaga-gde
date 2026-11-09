@php
    // Filter & periode lewat query string. Catatan: controller hanya membatasi angka ringkasan dengan
    // start_date/end_date ("period" hanya mengatur pengelompokan grafik), jadi tombol periode di sini
    // mengirim rentang tanggal supaya angka sesuai label.
    $base = request()->except(['page']);
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
        ? \Illuminate\Support\Carbon::parse($start)->translatedFormat('d M Y') . ' – ' . \Illuminate\Support\Carbon::parse($end)->translatedFormat('d M Y')
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
    $segment = 'inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium text-muted-foreground sm:py-1.5 transition-colors hover:text-foreground aria-[current=true]:bg-card aria-[current=true]:text-foreground aria-[current=true]:shadow-sm';
    // Opsi Select filter (x-ui.select-menu).
    $opts = fn ($all, $pairs) => [['value' => '', 'label' => $all], ...collect($pairs)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values()->all()];
    $filterSelects = [
        ['status', 'Status', $opts('Semua status', $statusLabels), false],
        ['gasoline_type', 'Jenis BBM', $opts('Semua jenis', array_combine($gasolineTypes, $gasolineTypes)), false],
        ['vehicle_id', 'Kendaraan', $opts('Semua kendaraan', collect($vehicles ?? [])->mapWithKeys(fn ($v) => [$v->id => $v->consumerial_code . ' - ' . $v->consumerial_name])), true],
        ['requester_id', 'Driver / pemohon', $opts('Semua driver', collect($drivers ?? [])->mapWithKeys(fn ($d) => [$d->id => $d->nama_lengkap])), false],
        ['nominal_range', 'Nominal', $opts('Semua nominal', $nominalLabels), false],
    ];

    $gasolineData = $stats['gasolineExpenseDistribution'] ?? [];
    $gasolineTotal = array_sum($gasolineData);
    // Spasi tak terputus: "Rp" tidak terpisah dari angkanya saat kartu sempit.
    $rupiah = fn ($value) => "Rp\u{00A0}" . number_format($value ?? 0, 0, ',', '.');
    $compact = fn ($value) => $value >= 1_000_000 ? "Rp " . number_format($value / 1_000_000, 1, ',', '.') . ' jt' : $rupiah($value);

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
                <x-ui.button variant="outline" class="flex-1 sm:flex-none" :href="route('superadmin.trip-analysis.index')"><i class="bi bi-signpost-2"></i> <span class="sm:hidden">Analisis Trip</span><span class="max-sm:hidden">Analisis Efisiensi Trip</span></x-ui.button>
            @endif
            @if (Auth::user()->role === 'superadmin')
                <x-ui.button class="flex-1 sm:flex-none" data-dialog-open="printReportDialog"><i class="bi bi-printer"></i> <span class="sm:hidden">Cetak Laporan</span><span class="max-sm:hidden">Cetak Laporan Transaksi</span></x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Toolbar periode + filter (pola shadcn data-table toolbar). Semua lewat GET; filter diterapkan
         dengan tombol, bukan tiap isian berubah, supaya halaman tidak dimuat ulang berkali-kali. --}}
    <x-ui.card class="gap-0 py-0">
        <div class="flex flex-wrap items-center gap-2 p-3">
            {{-- Periode: segmen preset (tautan) + segmen "Kustom" yang membuka kalender rentang. --}}
            {{-- HP: grid 6 kolom = 2 baris rata (3 preset di atas, Semua waktu + Kustom di bawah), tanpa geser ke samping. --}}
            <div class="grid w-full grid-cols-6 gap-0.5 rounded-lg bg-muted p-1 sm:inline-flex sm:w-auto sm:flex-wrap sm:items-center" role="group" aria-label="Periode">
                @foreach ($presets as $key => [$label, $from, $to])
                    <a href="{{ $urlWith(['period' => $key, 'start_date' => $from, 'end_date' => $to]) }}" class="{{ $segment }} col-span-2"
                       aria-current="{{ $activePreset === $key ? 'true' : 'false' }}">{{ $label }}</a>
                @endforeach
                <a href="{{ $urlWith(['start_date' => null, 'end_date' => null, 'period' => null]) }}" class="{{ $segment }} col-span-3"
                   aria-current="{{ $activePreset === 'all' ? 'true' : 'false' }}">Semua waktu</a>

                <form method="GET" action="{{ url()->current() }}" data-custom-range class="col-span-3">
                    @foreach ($filterKeys as $key)
                        @if (request()->filled($key))<input type="hidden" name="{{ $key }}" value="{{ request($key) }}">@endif
                    @endforeach
                    <input type="hidden" name="period" value="month">
                    <x-ui.date-range-picker id="customRange" :from="$start" :to="$end" :max="$today" :trigger-class="$segment . ' w-full cursor-pointer sm:w-auto'" :current="$activePreset === 'custom'">
                        <i class="bi bi-calendar-range"></i> Kustom
                    </x-ui.date-range-picker>
                </form>
            </div>

            <span class="inline-flex min-w-0 items-center gap-1.5 text-sm text-muted-foreground"><i class="bi bi-calendar3"></i> <span class="truncate">{{ $rangeLabel }}</span></span>

            <details data-menu class="relative ml-auto shrink-0">
                <summary class="inline-flex h-9 cursor-pointer list-none items-center gap-2 rounded-md border bg-card px-3 text-sm font-medium shadow-xs transition-colors hover:bg-accent [&::-webkit-details-marker]:hidden">
                    <i class="bi bi-funnel"></i> Filter
                    @if ($chips->isNotEmpty())
                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-xs text-primary-foreground">{{ $chips->count() }}</span>
                    @endif
                </summary>
                {{-- HP: bottom sheet (latar gelap + panel dari bawah); layar lebar: panel dropdown di bawah tombol. --}}
                <div data-menu-close aria-hidden="true" class="fixed inset-0 z-40 bg-black/40 sm:hidden"></div>
                <form method="GET" action="{{ url()->current() }}"
                      data-clean-query class="fixed inset-x-0 bottom-0 z-50 grid max-h-[85dvh] gap-4 overflow-y-auto rounded-t-2xl border bg-popover p-4 pb-[max(1rem,env(safe-area-inset-bottom))] text-popover-foreground shadow-lg sm:absolute sm:inset-x-auto sm:bottom-auto sm:right-0 sm:z-40 sm:mt-2 sm:max-h-none sm:w-96 sm:overflow-visible sm:rounded-lg sm:pb-4">
                    @foreach (['start_date', 'end_date', 'period'] as $key)
                        @if (request()->filled($key))<input type="hidden" name="{{ $key }}" value="{{ request($key) }}">@endif
                    @endforeach
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">Filter transaksi</p>
                            <p class="text-xs text-muted-foreground">Berlaku untuk ringkasan, grafik, dan ranking.</p>
                        </div>
                        <button type="button" data-menu-close aria-label="Tutup filter" class="-m-1 inline-flex size-9 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground sm:hidden"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($filterSelects as [$key, $label, $options, $wide])
                            <x-ui.field :label="$label" :for="'filter_' . $key" :class="$wide ? 'sm:col-span-2' : ''">
                                <x-ui.select-menu :id="'filter_' . $key" :name="$key" :value="request($key, '')" :options="$options" />
                            </x-ui.field>
                        @endforeach
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

    {{-- HP: 2 kolom dengan angka ringkas (short) supaya tidak menumpuk 4 kartu ke bawah. --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 2xl:grid-cols-4">
        <x-ui.stat-card label="Total transaksi" :value="$stats['totalTransactions'] ?? $stats['total'] ?? 0" short :hint="request('status') ? 'Status ' . ($statusLabels[request('status')] ?? request('status')) : 'Semua status'" icon="bi bi-receipt" />
        <x-ui.stat-card label="Total pengeluaran" :value="$rupiah($stats['totalExpense'])" :short="$compact($stats['totalExpense'])" hint="Disetujui dan selesai" icon="bi bi-cash-stack" tone="success" />
        <x-ui.stat-card label="Rata-rata nilai permohonan" :value="$rupiah($stats['avgRequestValue'] ?? 0)" :short="$compact($stats['avgRequestValue'] ?? 0)" hint="Per permohonan" icon="bi bi-currency-exchange" tone="warning" />
        <x-ui.stat-card label="Rata-rata frekuensi" :value="$stats['avgRequestFrequency']['value']" short :hint="$stats['avgRequestFrequency']['label']" icon="bi bi-graph-up" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-ui.card class="min-w-0 gap-4 lg:col-span-2">
            <x-ui.card-header title="Tren pengeluaran" icon="bi bi-graph-up"
                description="Pengeluaran yang disetujui dan selesai" />
            <x-ui.card-content class="grid gap-4">
                @if ($expenseTotal > 0)
                    <div class="flex flex-wrap gap-x-8 gap-y-2">
                        <div>
                            <p class="text-xs text-muted-foreground">Total periode</p>
                            <p class="text-xl font-bold tabular-nums tracking-tight">{{ $rupiah($expenseTotal) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Tertinggi {{ $periodUnit }}</p>
                            <p class="text-xl font-bold tabular-nums tracking-tight">{{ $rupiah($expensePeak[1]) }}
                                <span class="text-sm font-normal text-muted-foreground">{{ $expensePeak[0] }}</span></p>
                        </div>
                    </div>
                @endif
                <div class="relative h-60 w-full min-w-0 sm:h-72"><canvas id="expenseChart" aria-label="Grafik tren pengeluaran BBM" role="img"></canvas></div>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="min-w-0 gap-4">
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

    {{-- Top 5 per kategori: ringkas, cukup untuk melihat siapa/apa penyumbang pengeluaran terbesar. --}}
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ([
            ['title' => 'Top 5 kendaraan', 'icon' => 'bi bi-truck', 'data' => $stats['vehicleExpense'] ?? [], 'bar' => 'bg-module-bbm'],
            ['title' => 'Top 5 driver', 'icon' => 'bi bi-person', 'data' => $stats['driverExpense'] ?? [], 'bar' => 'bg-primary'],
        ] as $ranking)
            @php $max = max(1, ...array_values($ranking['data'] ?: [0])); @endphp
            <x-ui.card class="min-w-0 gap-4">
                <x-ui.card-header :title="$ranking['title']" :icon="$ranking['icon']" description="Pengeluaran BBM terbesar pada periode ini" />
                <x-ui.card-content>
                    @if (empty($ranking['data']))
                        <x-ui.empty-state icon="bi bi-trophy" title="Belum ada data" description="Tidak ada pengeluaran pada periode dan filter ini." />
                    @else
                        <ol class="grid gap-3.5">
                            @foreach (array_slice($ranking['data'], 0, 5, true) as $name => $total)
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

    @if (Auth::user()->role === 'superadmin')
        <x-ui.dialog id="printReportDialog" title="Cetak laporan transaksi BBM" description="Pilih periode, status, dan filter. PDF dibuka di tab baru." size="lg">
            <x-bbm.print-report-form :approvers="$approvers" id="formPrintReport" id-prefix="dash_print" />
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
