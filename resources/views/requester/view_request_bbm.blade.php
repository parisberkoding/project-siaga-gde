{{--
  Permohonan BBM driver (requester-index), dua tampilan di satu route:
  - awal: voucher yang sedang berjalan + permohonan yang menunggu, tombol Ajukan di kanan atas;
  - ?ajukan=1 (atau ada error validasi): form pengajuan. Error memakai back() sehingga kembali ke form,
    sukses diarahkan ke requester-index sehingga kembali ke tampilan awal.
  Kuota = calculateUserMaxSlots: 5 dasar, minus penalti voucher kedaluwarsa, plus bonus tepat waktu (1 sampai 7).
--}}
@php
    $baseSlots = 5;
    $quota = $maxSlots;
    $slotFull = $activeCount >= $quota;
    $freeSlots = max(0, $quota - $activeCount);
    $lockedSlots = max(0, $baseSlots - $quota);
    $bonusSlots = max(0, $quota - $baseSlots);
    $vouchers = $activeRequests->where('status', 'approved')->values();
    $waiting = $activeRequests->where('status', '!=', 'approved')->values();
    $showForm = request()->boolean('ajukan') || old('vehicle_id') || $errors->hasAny(['vehicle_id', 'gasoline_type', 'bill_payment', 'requester_notes']);
    $heroStats = [
        ['Slot terpakai', $activeCount . '/' . $quota, 'bi-grid-3x2-gap'],
        ['Voucher aktif', $vouchers->count(), 'bi-ticket-perforated'],
        ['Selesai', $completedCount, 'bi-check2-all'],
    ];
@endphp

<x-layouts.app :title="$showForm ? 'Ajukan Permohonan BBM' : 'Permohonan Aktif'"
               :breadcrumbs="$showForm ? ['BBM Operasional' => route('requester-index'), 'Ajukan Permohonan' => null] : ['BBM Operasional' => route('requester-index'), 'Permohonan Aktif' => null]">
    @if ($showForm)
        <x-ui.page-header kicker="Pengajuan Saya" title="Ajukan Permohonan"
                          :description="'Sisa ' . $freeSlots . ' dari ' . $quota . ' slot. Permohonan diproses oleh Admin GA unit Anda.'">
            <x-slot:actions>
                <x-ui.button variant="outline" :href="route('requester-index')"><i class="bi bi-arrow-left"></i> Kembali</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if ($slotFull)
            <x-ui.alert variant="warning" title="Slot permohonan penuh ({{ $activeCount }}/{{ $quota }})">
                Laporkan pembelian voucher yang sudah disetujui, atau tunggu permohonan yang sedang diproses, sebelum mengajukan yang baru.
            </x-ui.alert>
        @else
            @include('requester.partials.request-form')
        @endif
    @else
        {{-- Hero: judul, aksi utama di kanan atas (tetap di atas pada layar HP), ringkasan slot. --}}
        <section class="bg-brand-gradient ornament-corner relative overflow-hidden rounded-xl px-5 py-5 text-white shadow-lg shadow-primary/20 md:px-8 md:py-6" style="--ornament: #fff; --ornament-dot: #fff">
            <i class="bi bi-fuel-pump pointer-events-none absolute -bottom-8 right-4 text-[9rem] leading-none text-white/[0.07]" aria-hidden="true"></i>
            <div class="relative flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-orange-200">Pengajuan Saya</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight md:text-3xl">Permohonan Aktif</h1>
                    <p class="mt-1 hidden text-sm text-white/80 sm:block">Voucher yang sedang berjalan dan permohonan yang menunggu persetujuan.</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('requester-history') }}" title="Riwayat Permohonan" aria-label="Riwayat Permohonan"
                       class="inline-flex h-10 items-center gap-2 rounded-md px-3 text-sm font-medium text-white ring-1 ring-white/30 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-white/50">
                        <i class="bi bi-clock-history"></i><span class="hidden sm:inline">Riwayat</span>
                    </a>
                    @if ($slotFull)
                        <span aria-disabled="true" title="Slot penuh: laporkan voucher atau tunggu persetujuan dulu"
                              class="inline-flex h-10 cursor-not-allowed items-center gap-2 rounded-md bg-white/40 px-4 text-sm font-semibold text-primary/70">
                            <i class="bi bi-plus-lg"></i> Ajukan<span class="hidden sm:inline"> Permohonan</span>
                        </span>
                    @else
                        <a href="{{ route('requester-index', ['ajukan' => 1]) }}"
                           class="inline-flex h-10 items-center gap-2 rounded-md bg-white px-4 text-sm font-semibold text-primary shadow-sm transition hover:bg-white/90 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-white/50">
                            <i class="bi bi-plus-lg"></i> Ajukan<span class="hidden sm:inline"> Permohonan</span>
                        </a>
                    @endif
                </div>
            </div>
            <dl class="relative mt-5 grid grid-cols-3 gap-2 sm:gap-3 md:max-w-xl">
                @foreach ($heroStats as [$label, $value, $icon])
                    {{-- HP: ikon label disembunyikan & angka rata bawah, supaya label tidak terlipat dan angka sejajar. --}}
                    <div class="flex flex-col justify-between rounded-lg bg-white/10 px-3 py-2.5 ring-1 ring-white/15 backdrop-blur-sm sm:px-4 sm:py-3">
                        <dt class="flex items-center gap-1.5 text-[11px] leading-tight text-white/75 sm:text-xs"><i class="bi {{ $icon }} max-sm:hidden"></i> {{ $label }}</dt>
                        <dd class="mt-0.5 text-xl font-bold tabular-nums sm:text-2xl">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Kuota slot: 5 dasar, slot terkunci karena penalti, slot bonus karena tepat waktu. --}}
        <x-ui.card class="gap-3 py-4">
            <x-ui.card-content class="grid gap-3 px-4 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    <p class="font-medium">Kuota {{ $quota }} slot <span class="font-normal text-muted-foreground">· {{ $freeSlots }} tersedia</span></p>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-500"></span> Voucher aktif</span>
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-400"></span> Menunggu</span>
                        @if ($lockedSlots)<span class="flex items-center gap-1.5"><span class="bg-stripes size-2.5 rounded-full ring-1 ring-border"></span> Dikunci</span>@endif
                    </div>
                </div>
                <div class="flex gap-1.5" aria-hidden="true">
                    @foreach (range(0, $quota - 1) as $i)
                        <span @class([
                            'h-2 flex-1 rounded-full',
                            'bg-emerald-500' => isset($activeRequests[$i]) && $activeRequests[$i]->status === 'approved',
                            'bg-amber-400' => isset($activeRequests[$i]) && $activeRequests[$i]->status !== 'approved',
                            'bg-muted' => ! isset($activeRequests[$i]),
                            'ring-2 ring-sky-400/60 ring-offset-1 ring-offset-card' => $i >= $baseSlots,
                        ])></span>
                    @endforeach
                    @for ($i = 0; $i < $lockedSlots; $i++)
                        <span class="bg-stripes h-2 flex-1 rounded-full ring-1 ring-border"></span>
                    @endfor
                </div>
                @if ($lockedSlots)
                    <p class="flex gap-2 text-xs text-destructive"><i class="bi bi-lock"></i> {{ $lockedSlots }} slot dikunci karena voucher kedaluwarsa sebelum dilaporkan. Slot kembali setelah masa penalti 3 bulan berakhir.</p>
                @endif
                @if ($bonusSlots)
                    <p class="flex gap-2 text-xs text-sky-700"><i class="bi bi-stars"></i> +{{ $bonusSlots }} slot bonus karena Anda konsisten melapor tepat waktu. Bonus berlaku 1 minggu.</p>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        @if ($slotFull)
            <x-ui.alert variant="warning" title="Slot permohonan penuh ({{ $activeCount }}/{{ $quota }})">
                Laporkan pembelian voucher yang sudah disetujui, atau tunggu permohonan yang sedang diproses, sebelum mengajukan yang baru.
            </x-ui.alert>
        @endif

        <section class="grid gap-3" aria-labelledby="voucher-heading">
            <x-bbm.section-heading id="voucher-heading" title="Voucher siap dipakai" :count="$vouchers->count()" icon="bi-ticket-perforated" numbered
                :points="[['Tunjukkan kode voucher di SPBU', null], ['Laporkan pembelian sebelum kedaluwarsa', null]]" />
            @if ($vouchers->isEmpty())
                <x-ui.empty-state icon="bi bi-ticket-perforated" title="Belum ada voucher aktif"
                                  :description="$waiting->isEmpty() ? 'Ajukan permohonan BBM. Voucher muncul di sini setelah disetujui Admin GA.' : 'Voucher muncul di sini setelah permohonan Anda disetujui Admin GA.'">
                    @if (! $slotFull && $waiting->isEmpty())
                        <x-ui.button class="mt-2" :href="route('requester-index', ['ajukan' => 1])"><i class="bi bi-plus-lg"></i> Ajukan Permohonan</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($vouchers as $i => $request)
                        @include('requester.partials.slot-approved', ['request' => $request, 'slotNum' => $i + 1])
                    @endforeach
                </div>
            @endif
        </section>

        @if ($waiting->isNotEmpty())
            <section class="grid gap-3" aria-labelledby="waiting-heading">
                <x-bbm.section-heading id="waiting-heading" title="Menunggu persetujuan" :count="$waiting->count()" icon="bi-hourglass-split" tone="warning"
                    :points="[['Diproses Admin GA unit Anda', 'bi-person-check'], ['Bisa dibatalkan selama belum disetujui', 'bi-x-circle']]" />
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($waiting as $i => $request)
                        @include('requester.partials.slot-pending', ['request' => $request, 'slotNum' => $vouchers->count() + $i + 1])
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    <script type="application/json" id="page-data">@json(['vehicles' => $vehicleOptions, 'fallbackOptions' => $fallbackOptions])</script>

    @push('scripts')
        @vite('resources/js/pages/bbm/request-create.js')
    @endpush
</x-layouts.app>
