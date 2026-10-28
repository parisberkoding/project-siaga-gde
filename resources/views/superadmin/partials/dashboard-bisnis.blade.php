@php
    // Jumlah filter aktif, tanpa menghitung periode dan parameter halaman tabel (page, *_page).
    $activeFilters = collect(request()->query())
        ->reject(fn ($value, $key) => $key === 'period' || str_ends_with($key, 'page'))
        ->filter(fn ($value) => filled($value))
        ->count();
    // Ganti periode: filter lain ikut, parameter halaman tabel dibuang supaya tidak mendarat di halaman kosong.
    $periodUrl = fn (string $period) => url()->current() . '?' . http_build_query(
        collect(request()->query())->reject(fn ($value, $key) => str_ends_with($key, 'page'))->put('period', $period)->all()
    );
    $gasolineData = $stats['gasolineExpenseDistribution'] ?? [];
    $gasolineTotal = array_sum($gasolineData);
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
@endphp

<x-layouts.app :title="$title" :breadcrumbs="['Permohonan BBM' => null, $title => null]">
    <x-ui.page-header :title="$title" description="Ringkasan pengeluaran, konsumsi, dan pelaporan BBM.">
        <x-slot:actions>
            @if (Auth::user()->hasPermission('vehicle.report'))
                <x-ui.button variant="outline" :href="route('superadmin.trip-analysis.index')"><i class="bi bi-signpost-2"></i> Trip Analysis</x-ui.button>
            @endif
            @if (Auth::user()->role === 'superadmin')
                <x-ui.button data-dialog-open="printReportDialog"><i class="bi bi-printer"></i> Cetak Laporan Transaksi</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Filter: terkirim otomatis saat isian berubah --}}
    <x-ui.card class="gap-0 py-0">
        <details class="group" open>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-6 py-4 [&::-webkit-details-marker]:hidden">
                <span class="flex items-center gap-2 font-semibold">
                    <i class="bi bi-funnel text-muted-foreground"></i> Filter data
                    @if ($activeFilters)<x-ui.badge>{{ $activeFilters }} filter aktif</x-ui.badge>@endif
                </span>
                <span class="text-sm text-muted-foreground"><i class="bi bi-chevron-down inline-block transition-transform group-open:rotate-180"></i></span>
            </summary>

            <form method="GET" action="{{ url()->current() }}" data-autosubmit class="grid gap-4 border-t px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.field label="Tanggal mulai" for="filter_start_date">
                    <x-ui.input type="date" name="start_date" id="filter_start_date" :value="request('start_date')" max="{{ date('Y-m-d') }}" />
                </x-ui.field>
                <x-ui.field label="Tanggal akhir" for="filter_end_date">
                    <x-ui.input type="date" name="end_date" id="filter_end_date" :value="request('end_date')" max="{{ date('Y-m-d') }}" />
                </x-ui.field>
                <x-ui.field label="Status transaksi" for="filter_status">
                    <x-ui.select name="status" id="filter_status">
                        <option value="">Semua status</option>
                        @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'completed' => 'Selesai', 'rejected' => 'Ditolak'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') == $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="Jenis BBM" for="filter_gasoline">
                    <x-ui.select name="gasoline_type" id="filter_gasoline">
                        <option value="">Semua jenis BBM</option>
                        @foreach (['Pertamax', 'Pertamina Dex', 'Pertalite'] as $gt)
                            <option value="{{ $gt }}" @selected(request('gasoline_type') == $gt)>{{ $gt }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="Kendaraan" for="filter_vehicle">
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
                <x-ui.field label="Rentang nominal" for="filter_nominal">
                    <x-ui.select name="nominal_range" id="filter_nominal">
                        <option value="">Semua nominal</option>
                        @foreach (['0-100000' => 'Di bawah Rp 100.000', '100000-300000' => 'Rp 100.000 sampai Rp 300.000', '300000-500000' => 'Rp 300.000 sampai Rp 500.000', '500000+' => 'Di atas Rp 500.000'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('nominal_range') == $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
                <div class="flex items-end">
                    <x-ui.button variant="outline" class="w-full" :href="url()->current() . (request('period') ? '?period=' . urlencode(request('period')) : '')">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset filter
                    </x-ui.button>
                </div>
            </form>
        </details>
    </x-ui.card>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">Ringkasan {{ $stats['periodLabel'] }}</h2>
        <div class="inline-flex rounded-md border bg-card p-0.5 shadow-xs" role="group" aria-label="Periode">
            @foreach (['day' => 'Hari ini', 'month' => 'Bulan ini', 'year' => 'Tahun ini'] as $period => $label)
                <a href="{{ $periodUrl($period) }}" aria-pressed="{{ request('period', 'month') === $period ? 'true' : 'false' }}"
                   class="rounded-sm px-3 py-1 text-sm text-muted-foreground aria-pressed:bg-accent aria-pressed:font-medium aria-pressed:text-accent-foreground">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total transaksi" :value="$stats['totalTransactions'] ?? $stats['total'] ?? 0" hint="Semua status" icon="bi bi-receipt" />
        <x-ui.stat-card label="Total pengeluaran" :value="$rupiah($stats['totalExpense'])" hint="Disetujui dan selesai" icon="bi bi-cash-stack" tone="success" />
        <x-ui.stat-card label="Rata-rata nilai permohonan" :value="$rupiah($stats['avgRequestValue'] ?? 0)" hint="Per permohonan" icon="bi bi-currency-exchange" tone="warning" />
        <x-ui.stat-card label="Rata-rata frekuensi" :value="$stats['avgRequestFrequency']['value']" :hint="$stats['avgRequestFrequency']['label']" icon="bi bi-graph-up" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <x-ui.card-header title="Tren pengeluaran" icon="bi bi-graph-up-arrow" />
            <x-ui.card-content><div class="relative h-80"><canvas id="expenseLineChart"></canvas></div></x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header title="Distribusi jenis BBM" icon="bi bi-pie-chart" />
            <x-ui.card-content class="grid gap-4">
                <div class="relative h-56"><canvas id="gasolinePieChart"></canvas></div>
                <ul class="grid gap-2 text-sm">
                    @foreach ($gasolineData as $type => $amount)
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><span class="size-2.5 rounded-full" data-legend="{{ $loop->index }}"></span>{{ $type }}</span>
                            <span class="tabular-nums text-muted-foreground">{{ $rupiah($amount) }} ({{ $gasolineTotal > 0 ? round($amount / $gasolineTotal * 100, 1) : 0 }}%)</span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    <x-ui.card>
        <x-ui.card-header title="Top 10 konsumsi BBM" icon="bi bi-trophy">
            <x-slot:action>
                <div class="inline-flex rounded-md border bg-card p-0.5 shadow-xs" role="group" aria-label="Mode ranking">
                    <button type="button" data-ranking="vehicle" aria-pressed="true" class="rounded-sm px-3 py-1 text-sm text-muted-foreground aria-pressed:bg-accent aria-pressed:font-medium aria-pressed:text-accent-foreground">Per kendaraan</button>
                    <button type="button" data-ranking="driver" aria-pressed="false" class="rounded-sm px-3 py-1 text-sm text-muted-foreground aria-pressed:bg-accent aria-pressed:font-medium aria-pressed:text-accent-foreground">Per driver</button>
                </div>
            </x-slot:action>
        </x-ui.card-header>
        <x-ui.card-content><div class="relative h-80"><canvas id="rankingChart"></canvas></div></x-ui.card-content>
    </x-ui.card>

    <x-ui.card class="gap-4">
        <x-ui.card-header title="Tabel pivot data lengkap" icon="bi bi-table">
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
        <x-ui.card-header title="Statistik per driver" icon="bi bi-person-check">
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
                            <td class="text-center tabular-nums">{{ $driver['reportRate'] }}%</td>
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
        <x-ui.card-header title="Performa KM kendaraan" icon="bi bi-speedometer2" />
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

    <script type="application/json" id="page-data">@json(['stats' => $stats])</script>

    @push('scripts')
        @vite('resources/js/pages/bbm/dashboard.js')
    @endpush
</x-layouts.app>
