@php
    // Kuota slot mengikuti calculateUserMaxSlots (5 dikurangi penalti / ditambah bonus, 1 sampai 6).
    $quota = $maxSlots;
    $slotFull = $activeCount >= $quota;
    $freeSlots = max(0, $quota - $activeCount);
    $lockedSlots = max(0, 5 - $quota);
    $voucherRunning = $activeRequests->where('status', 'approved')->count();
    $heroStats = [
        ['Slot terpakai', $activeCount . '/' . $quota, 'bi-grid-3x2-gap'],
        ['Voucher aktif', $voucherRunning, 'bi-ticket-perforated'],
        ['Selesai', $completedCount, 'bi-check2-all'],
    ];
@endphp

<x-layouts.app :title="$title" :breadcrumbs="['Permohonan BBM' => route('requester-index'), 'Permohonan Baru' => null]">
    {{-- Hero: judul halaman + ringkasan slot di atas gradien brand. --}}
    <section class="bg-brand-gradient ornament-corner relative overflow-hidden rounded-xl px-6 py-6 text-white shadow-lg shadow-primary/20 md:px-8" style="--ornament: #fff; --ornament-dot: #fff">
        <i class="bi bi-fuel-pump pointer-events-none absolute -bottom-8 right-4 text-[9rem] leading-none text-white/[0.07]" aria-hidden="true"></i>
        <div class="relative flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-xl">
                <p class="text-xs font-semibold uppercase tracking-wider text-orange-200">Permohonan BBM</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight md:text-3xl">Permohonan Baru</h1>
                <p class="mt-2 text-sm text-white/80">Ajukan permohonan BBM baru dan pantau slot permohonan yang sedang berjalan.</p>
                <a href="{{ route('requester-history') }}" class="mt-4 inline-flex h-9 items-center gap-2 rounded-md bg-white px-4 text-sm font-medium text-primary shadow-sm transition hover:bg-white/90 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-white/50">
                    <i class="bi bi-clock-history"></i> Riwayat Permohonan
                </a>
            </div>
            <dl class="grid w-full grid-cols-3 gap-3 sm:w-auto">
                @foreach ($heroStats as [$label, $value, $icon])
                    <div class="rounded-lg bg-white/10 px-4 py-3 ring-1 ring-white/15 backdrop-blur-sm sm:min-w-32">
                        <dt class="flex items-center gap-1.5 text-xs text-white/75"><i class="bi {{ $icon }}"></i> {{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    @if ($slotFull)
        <x-ui.alert variant="warning" title="Slot permohonan penuh ({{ $activeCount }}/{{ $quota }})">
            Laporkan pembelian voucher yang sudah disetujui, atau tunggu permohonan yang sedang diproses, sebelum mengajukan yang baru.
        </x-ui.alert>
    @else
        <form action="{{ route('requester-create') }}" method="POST" id="requestForm" data-loading class="grid items-start gap-6 lg:grid-cols-3">
            @csrf
            {{-- BUG-SEC-002: key unik per form; kiriman kedua dengan key sama (klik ganda) ditolak server. --}}
            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            {{-- Opsi "Tambah Kendaraan/Alat Baru" dinonaktifkan, form hanya memakai kendaraan siap guna. --}}
            <input type="hidden" name="vehicle_option" value="existing">

            <div class="grid gap-6 lg:col-span-2">
                <x-ui.card class="ornament-corner gap-5" style="--ornament: var(--primary)">
                    <div class="flex items-start gap-3 px-6">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-primary to-indigo-500 text-lg text-white shadow-md shadow-primary/25">
                            <i class="bi bi-car-front"></i>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-primary">Langkah 1</p>
                            <h2 class="font-semibold">Kendaraan / Alat</h2>
                            <p class="text-sm text-muted-foreground">Cari berdasarkan kode, plat nomor, atau nama kendaraan.</p>
                        </div>
                    </div>
                    <x-ui.card-content class="grid gap-4">
                        <x-ui.field label="Kendaraan / Alat" for="vehicle_search" required error="vehicle_id">
                            <div class="relative">
                                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i>
                                <x-ui.input id="vehicle_search" name="vehicle_search" autocomplete="off" class="h-11 w-full pl-9"
                                            placeholder="Contoh: D 1383 ALT atau Innova"
                                            :value="$selectedVehicleLabel ?? ''" :invalid="$errors->has('vehicle_id')" />
                                <div id="vehicleSuggestions" hidden
                                     class="absolute top-full z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border bg-popover p-1 shadow-md"></div>
                            </div>
                            <input type="hidden" id="vehicle_id" name="vehicle_id" value="{{ old('vehicle_id') }}">
                        </x-ui.field>

                        {{-- Ringkasan data kendaraan terpilih, diisi resources/js/pages/bbm/request-create.js. --}}
                        <dl id="vehicleInfo" hidden class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            @foreach ([
                                ['viType', 'Jenis', 'bi-car-front', 'bg-blue-50 text-blue-700'],
                                ['viFuel', 'BBM', 'bi-fuel-pump', 'bg-orange-50 text-orange-700'],
                                ['viTank', 'Tangki', 'bi-droplet-half', 'bg-cyan-50 text-cyan-700'],
                                ['viBenchmark', 'Benchmark', 'bi-cash-coin', 'bg-emerald-50 text-emerald-700'],
                            ] as [$key, $label, $icon, $tint])
                                <div class="flex items-center gap-3 rounded-lg border bg-card p-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $tint }}"><i class="bi {{ $icon }}"></i></span>
                                    <div class="min-w-0">
                                        <dt class="text-xs text-muted-foreground">{{ $label }}</dt>
                                        <dd id="{{ $key }}" class="truncate text-sm font-semibold">-</dd>
                                    </div>
                                </div>
                            @endforeach
                        </dl>
                        <p id="vehicleBudgetInfo" class="text-xs text-muted-foreground empty:hidden"></p>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card class="ornament-corner gap-5" style="--ornament: var(--module-bbm)">
                    <div class="flex items-start gap-3 px-6">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-orange-500 to-amber-500 text-lg text-white shadow-md shadow-orange-500/25">
                            <i class="bi bi-fuel-pump"></i>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-orange-600">Langkah 2</p>
                            <h2 class="font-semibold">BBM & Nominal</h2>
                            <p class="text-sm text-muted-foreground">Terisi otomatis dari data kendaraan, masih bisa disesuaikan.</p>
                        </div>
                    </div>
                    <x-ui.card-content class="grid gap-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.field label="Jenis BBM" for="gasoline_type" required error="gasoline_type">
                                <x-ui.select id="gasoline_type" name="gasoline_type" required>
                                    <option value="">Pilih jenis BBM</option>
                                    @forelse ($gasolineTypes as $gasoline)
                                        <option value="{{ $gasoline->gasoline_name }}" @selected(old('gasoline_type') == $gasoline->gasoline_name)>{{ $gasoline->gasoline_name }}</option>
                                    @empty
                                        <option value="Pertamax" @selected(old('gasoline_type') == 'Pertamax')>Pertamax</option>
                                        <option value="Pertamina Dex" @selected(old('gasoline_type') == 'Pertamina Dex')>Pertamina Dex</option>
                                    @endforelse
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field label="Nominal (Rp)" for="bill_payment" required error="bill_payment">
                                <x-ui.select id="bill_payment" name="bill_payment" required>
                                    <option value="">Pilih nominal</option>
                                </x-ui.select>
                                <p id="billPaymentInfo" class="text-xs text-muted-foreground empty:hidden"></p>
                            </x-ui.field>
                        </div>

                        <x-ui.field label="Catatan (opsional)" for="requester_notes" error="requester_notes" hint="Maksimal 500 karakter.">
                            <x-ui.textarea id="requester_notes" name="requester_notes" rows="3" maxlength="500" class="h-24 resize-none"
                                           placeholder="Contoh: perjalanan dinas ke site Patuha">{{ old('requester_notes') }}</x-ui.textarea>
                        </x-ui.field>
                    </x-ui.card-content>
                </x-ui.card>
            </div>

            {{-- Ringkasan: terisi langsung dari pilihan di form (resources/js/pages/bbm/request-create.js). --}}
            <x-ui.card class="gap-0 overflow-hidden py-0 lg:sticky lg:top-4">
                <div class="relative overflow-hidden border-b border-orange-100 bg-linear-to-br from-orange-50 via-amber-50 to-card px-6 py-5">
                    <i class="bi bi-receipt pointer-events-none absolute -right-3 -top-2 text-7xl text-orange-500/10" aria-hidden="true"></i>
                    <p class="text-xs font-semibold uppercase tracking-wider text-orange-700">Ringkasan permohonan</p>
                    <p id="summaryNominal" class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-foreground">Rp 0</p>
                    <p id="summaryFuel" class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/80 px-2.5 py-0.5 text-xs font-medium text-orange-700 ring-1 ring-orange-200">Pilih kendaraan terlebih dahulu</p>
                </div>
                <div class="grid gap-5 p-6">
                    <dl class="grid gap-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-muted-foreground">Kendaraan</dt>
                            <dd id="summaryVehicle" class="min-w-0 text-right font-medium">-</dd>
                        </div>
                        <div class="grid gap-2">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Sisa slot</dt>
                                <dd class="font-medium tabular-nums">{{ $freeSlots }} dari {{ $quota }}</dd>
                            </div>
                            <div class="flex gap-1" aria-hidden="true">
                                @foreach (range(0, $quota - 1) as $i)
                                    <span @class([
                                        'h-1.5 flex-1 rounded-full',
                                        'bg-amber-400' => isset($activeRequests[$i]) && $activeRequests[$i]->status !== 'approved',
                                        'bg-emerald-500' => isset($activeRequests[$i]) && $activeRequests[$i]->status === 'approved',
                                        'bg-muted' => ! isset($activeRequests[$i]),
                                    ])></span>
                                @endforeach
                            </div>
                        </div>
                    </dl>

                    <ul class="grid gap-2.5 text-xs text-muted-foreground">
                        <li class="flex gap-2.5">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-blue-50 text-blue-700"><i class="bi bi-person-check"></i></span>
                            <span class="pt-0.5">Permohonan diproses oleh Admin GA unit Anda.</span>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-amber-50 text-amber-700"><i class="bi bi-hourglass-split"></i></span>
                            <span class="pt-0.5">Voucher berlaku 50 jam setelah disetujui. Laporkan pembelian sebelum masa berlakunya habis.</span>
                        </li>
                    </ul>

                    <x-ui.button type="submit" id="submitBtn" data-loading-text="Mengirim..." size="lg"
                                 class="w-full bg-linear-to-r from-primary to-indigo-600 shadow-lg shadow-primary/25 hover:brightness-110">
                        <i class="bi bi-send"></i> Ajukan Permohonan
                    </x-ui.button>
                </div>
            </x-ui.card>
        </form>
    @endif

    <section class="grid gap-4" aria-labelledby="slot-heading">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-linear-to-br from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/25">
                    <i class="bi bi-grid-3x2-gap"></i>
                </span>
                <div>
                    <h2 id="slot-heading" class="text-lg font-semibold">Slot Permohonan</h2>
                    <p class="text-sm text-muted-foreground">
                        Kuota Anda saat ini {{ $quota }} slot{{ $lockedSlots ? ' (' . $lockedSlots . ' slot dikunci karena penalti)' : '' }}.
                        @if ($voucherRunning)
                            {{ $voucherRunning }} voucher menunggu laporan pembelian.
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-400"></span> Menunggu</span>
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-500"></span> Voucher aktif</span>
                <span class="rounded-full bg-card px-3 py-1 text-sm font-medium text-foreground ring-1 ring-border tabular-nums">{{ $activeCount }}/{{ $quota }} terpakai</span>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($activeRequests as $i => $request)
                @include($request->status === 'approved' ? 'requester.partials.slot-approved' : 'requester.partials.slot-pending', ['request' => $request, 'slotNum' => $i + 1])
            @endforeach

            @for ($n = $activeCount + 1; $n <= $quota; $n++)
                @if ($slotFull)
                    <div class="flex min-h-48 flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-6 text-center text-muted-foreground">
                        <i class="bi bi-plus-circle text-2xl"></i>
                        <p class="text-sm font-medium">Slot {{ $n }} kosong</p>
                    </div>
                @else
                    <a href="#vehicle_search" data-focus-vehicle
                       class="group flex min-h-48 flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-primary/25 bg-linear-to-br from-accent/70 to-card p-6 text-center transition hover:border-primary/60 hover:shadow-md focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50">
                        <span class="flex size-12 items-center justify-center rounded-full bg-card text-xl text-primary shadow-sm ring-1 ring-primary/15 transition group-hover:scale-105 group-hover:bg-primary group-hover:text-white motion-reduce:transition-none">
                            <i class="bi bi-plus-lg"></i>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold">Slot {{ $n }} kosong</span>
                            <span class="block text-xs text-primary">Ajukan permohonan</span>
                        </span>
                    </a>
                @endif
            @endfor

            @if ($lockedSlots)
                <div class="bg-stripes flex min-h-48 flex-col items-center justify-center gap-2 rounded-xl border p-6 text-center text-muted-foreground">
                    <span class="flex size-12 items-center justify-center rounded-full bg-card text-xl shadow-sm ring-1 ring-border"><i class="bi bi-lock"></i></span>
                    <p class="text-sm font-semibold text-foreground">{{ $lockedSlots === 1 ? 'Slot 5 dikunci' : 'Slot ' . ($quota + 1) . ' sampai 5 dikunci' }}</p>
                    <p class="max-w-56 text-xs">Penalti karena voucher kedaluwarsa sebelum dilaporkan. Slot kembali setelah masa penalti berakhir.</p>
                </div>
            @endif
        </div>
    </section>

    <script type="application/json" id="page-data">@json(['vehicles' => $vehicleOptions, 'fallbackOptions' => $fallbackOptions])</script>

    @push('scripts')
        @vite('resources/js/pages/bbm/request-create.js')
    @endpush
</x-layouts.app>
