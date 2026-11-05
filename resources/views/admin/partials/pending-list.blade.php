{{--
  Permohonan menunggu persetujuan. Setujui/Tolak membuka dialog berisi ringkasan + komentar admin;
  centang Pilih (atau Pilih semua) untuk keputusan massal (resources/js/lib/decisions.js).
  Cari / filter BBM / urutan / halaman (10 per halaman) di sisi browser: resources/js/pages/bbm/admin-approval.js.
--}}
@php
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    // Warna chip jenis BBM (selaras dengan grafik dashboard BBM).
    $fuelTones = ['Pertamax' => 'bg-blue-50 text-blue-700 ring-blue-200', 'Pertalite' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'Pertamina Dex' => 'bg-orange-50 text-orange-700 ring-orange-200', 'Dexlite' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'Pertamax Turbo' => 'bg-rose-50 text-rose-700 ring-rose-200'];
    $initials = fn ($name) => collect(explode(' ', trim($name ?? '')))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('') ?: '?';
@endphp

<form method="POST" action="{{ route('admin-bulk-process') }}" id="bulkApprovalForm" class="grid gap-4">
    @csrf
    <input type="hidden" name="decisions" id="decisionsInput">

    @if ($pendingRequests->isEmpty())
        <x-ui.empty-state icon="bi bi-check2-all" title="Tidak ada permohonan menunggu" description="Semua permohonan sudah diproses." class="bg-card" />
    @else
        @php
            $fuelFilter = [['value' => '', 'label' => 'Semua BBM'], ...$pendingRequests->pluck('gasoline_type')->filter()->unique()->sort()->map(fn ($t) => ['value' => $t, 'label' => $t])->values()->all()];
            $sortOptions = [['value' => 'oldest', 'label' => 'Paling lama'], ['value' => 'newest', 'label' => 'Paling baru'], ['value' => 'amount', 'label' => 'Nominal besar']];
        @endphp
        {{-- Toolbar: cari + filter BBM + urutan. HP: cari selebar layar, dua Select berdampingan di bawahnya. --}}
        <div class="grid gap-2 rounded-xl border bg-card p-3 shadow-xs sm:flex sm:items-center">
            <div class="relative sm:flex-1">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i>
                <x-ui.input type="search" data-pending-search placeholder="Cari pemohon, kendaraan, nomor" class="pl-9" aria-label="Cari permohonan" />
            </div>
            <div class="grid grid-cols-2 gap-2 sm:flex">
                <x-ui.select-menu id="pendingFuel" :options="$fuelFilter" value="" icon="bi bi-fuel-pump" class="sm:w-44" aria-label="Filter jenis BBM" />
                <x-ui.select-menu id="pendingSort" :options="$sortOptions" value="oldest" icon="bi bi-sort-down" class="sm:w-48" aria-label="Urutkan permohonan" />
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 px-1">
            <label class="inline-flex cursor-pointer items-center gap-2.5 text-sm font-medium">
                <x-ui.checkbox data-select-all class="size-[18px]" aria-label="Pilih semua permohonan di halaman ini" /> Pilih semua
            </label>
            <span data-pending-count class="text-sm text-muted-foreground" aria-live="polite">{{ $pendingRequests->count() }} permohonan</span>
        </div>

        {{-- Satu kolom: tiap permohonan satu kartu memanjang (lg ke atas isinya berderet). --}}
        <div class="grid gap-3" data-pending-list>
            @foreach ($pendingRequests->sortBy('request_date') as $req)
                @php
                    $vehicleCode = $req->vehicle->consumerial_code ?? '-';
                    $vehicleName = $req->vehicle->consumerial_name ?? '';
                    $summary = ['number' => $req->request_number, 'name' => $req->requester->nama_lengkap, 'vehicle' => trim("$vehicleCode · $vehicleName", ' ·'), 'amount' => $rp($req->bill_amounts)];
                    $waitingDays = $req->request_date->diffInDays(now());
                    $urgent = $waitingDays >= 2;
                    $fuelTone = $fuelTones[$req->gasoline_type] ?? 'bg-muted text-muted-foreground ring-border';
                @endphp
                <article data-decision-item="{{ $req->id }}" data-summary="{{ json_encode($summary) }}"
                         data-search="{{ mb_strtolower(implode(' ', [$req->requester->nama_lengkap, $req->request_number, $vehicleCode, $vehicleName])) }}"
                         data-fuel="{{ $req->gasoline_type }}" data-date="{{ $req->request_date->timestamp }}" data-amount="{{ (int) $req->bill_amounts }}"
                         class="group/card relative flex flex-col gap-4 overflow-hidden rounded-xl border bg-card p-4 pl-5 shadow-xs transition-[box-shadow,border-color] duration-200 hover:shadow-md lg:flex-row lg:items-center lg:gap-5 has-[[data-select]:checked]:border-primary has-[[data-select]:checked]:bg-primary/[0.03] has-[[data-select]:checked]:ring-2 has-[[data-select]:checked]:ring-primary/25">
                    {{-- Aksen kiri: kuning = menunggu, merah = sudah 2 hari atau lebih. --}}
                    <span aria-hidden="true" @class(['absolute inset-y-0 left-0 w-1', 'bg-destructive' => $urgent, 'bg-warning' => ! $urgent])></span>

                    {{-- Pilih (keputusan massal): pojok kanan atas di HP, paling kiri di layar lebar. --}}
                    <label class="absolute right-3 top-3 z-10 flex size-9 cursor-pointer items-center justify-center rounded-lg transition-colors hover:bg-accent lg:static lg:order-first lg:-ml-1 lg:shrink-0" title="Pilih untuk keputusan massal">
                        <x-ui.checkbox data-select class="size-[18px]" aria-label="Pilih permohonan {{ $req->request_number }}" />
                    </label>

                    {{-- Pemohon + status + waktu tunggu --}}
                    <div class="flex min-w-0 items-center gap-3 pr-10 lg:w-60 lg:shrink-0 lg:pr-0">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-primary to-module-spd text-sm font-semibold text-white shadow-sm ring-2 ring-card">
                            {{ $initials($req->requester->nama_lengkap) }}
                        </span>
                        <div class="grid min-w-0 flex-1 gap-1">
                            <p class="truncate font-semibold leading-tight">{{ $req->requester->nama_lengkap }}</p>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                                <span @class(['inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-semibold', 'bg-destructive-soft text-destructive' => $urgent, 'bg-warning-soft text-warning' => ! $urgent])>
                                    <span class="relative flex size-1.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-current opacity-50 motion-reduce:animate-none"></span><span class="relative inline-flex size-1.5 rounded-full bg-current"></span></span>
                                    {{ $urgent ? 'Perlu segera' : 'Menunggu' }}
                                </span>
                                <span @class(['inline-flex items-center gap-1', 'font-medium text-destructive' => $urgent, 'text-muted-foreground' => ! $urgent])
                                      title="Diajukan {{ $req->request_date->format('d/m/Y H:i') }}">
                                    <i class="bi bi-clock-history"></i> {{ $req->request_date->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <span aria-hidden="true" class="hidden h-12 w-px shrink-0 bg-border lg:block"></span>

                    {{-- Rincian: kendaraan, chip BBM / tanggal / nomor, catatan --}}
                    <div class="grid min-w-0 flex-1 gap-2 text-sm">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground"><i class="bi bi-truck"></i></span>
                            <p class="min-w-0 truncate"><span class="font-semibold">{{ $vehicleCode }}</span> <span class="text-muted-foreground">{{ $vehicleName }}</span></p>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 font-medium ring-1 {{ $fuelTone }}"><i class="bi bi-fuel-pump"></i> {{ $req->gasoline_type }}</span>
                            <span class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-muted-foreground"><i class="bi bi-calendar3"></i> {{ $req->request_date->translatedFormat('d M Y, H:i') }}</span>
                            <span class="inline-flex items-center rounded-md bg-muted px-2 py-0.5 font-mono text-[11px] text-muted-foreground">{{ $req->request_number }}</span>
                        </div>
                        @if ($req->requester_notes)
                            <p class="flex min-w-0 items-start gap-2 rounded-md border-l-2 border-primary/30 bg-muted/50 px-2.5 py-1.5 text-xs text-foreground/80">
                                <i class="bi bi-chat-left-quote text-primary/60"></i>
                                <span class="line-clamp-2">{{ $req->requester_notes }}</span>
                            </p>
                        @endif
                    </div>

                    {{-- Nominal + aksi: HP = satu baris di bawah bergaris; layar lebar = kolom kanan. --}}
                    <div class="flex items-center justify-between gap-3 border-t pt-3 lg:shrink-0 lg:gap-5 lg:border-t-0 lg:pt-0">
                        <div class="grid gap-1 lg:min-w-36 lg:justify-items-end">
                            <p class="text-[10px] font-semibold uppercase leading-none tracking-wider text-muted-foreground">Nominal</p>
                            <p class="whitespace-nowrap text-xl font-bold leading-none tracking-tight tabular-nums text-primary"><span class="mr-0.5 text-sm font-semibold text-primary/70">Rp</span>{{ number_format((float) $req->bill_amounts, 0, ',', '.') }}</p>
                        </div>
                        <div class="flex gap-2">
                            <x-ui.button variant="outline" size="sm" data-decide="reject" class="text-destructive hover:bg-destructive-soft hover:text-destructive" title="Tolak permohonan">
                                <i class="bi bi-x-lg"></i> <span>Tolak</span>
                            </x-ui.button>
                            <x-ui.button variant="success" size="sm" data-decide="approve" class="shadow-sm shadow-success/30" title="Setujui permohonan">
                                <i class="bi bi-check-lg"></i> <span>Setujui</span>
                            </x-ui.button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <x-ui.empty-state data-pending-empty hidden icon="bi bi-search" title="Tidak ada yang cocok" description="Ubah kata kunci atau filter jenis BBM." class="bg-card" />
        {{-- Diisi JS: info + pagination (gaya sama dengan pagination tabel). --}}
        <nav data-pending-pager aria-label="Halaman permohonan" class="flex flex-wrap items-center justify-between gap-3 max-sm:justify-center"></nav>
    @endif

    {{-- Bar keputusan massal, muncul setelah ada kartu yang dipilih --}}
    <div id="bulkActionBar" hidden
         class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-primary/30 bg-card/95 p-3 pl-4 shadow-xl backdrop-blur">
        <p class="flex items-center gap-2 text-sm">
            <span class="flex h-7 min-w-7 items-center justify-center rounded-full bg-primary px-2 text-xs font-semibold text-primary-foreground" id="selectedCount">0</span>
            <span class="font-medium">permohonan dipilih</span>
            <button type="button" data-clear-selection class="text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">Batal pilih</button>
        </p>
        <div class="flex gap-2">
            <x-ui.button variant="outline" data-decide-selected="reject" class="text-destructive hover:bg-destructive-soft hover:text-destructive"><i class="bi bi-x-circle"></i> Tolak terpilih</x-ui.button>
            <x-ui.button variant="success" data-decide-selected="approve"><i class="bi bi-check-circle"></i> Setujui terpilih</x-ui.button>
        </div>
    </div>
</form>

<x-bbm.decision-dialog />
