@php
    // Kuota slot mengikuti calculateUserMaxSlots (5 dikurangi penalti / ditambah bonus, 1 sampai 6).
    $quota = $maxSlots;
    $slotFull = $activeCount >= $quota;
    $freeSlots = max(0, $quota - $activeCount);
    $lockedSlots = max(0, 5 - $quota);
    $voucherRunning = $activeRequests->where('status', 'approved')->count();
@endphp

<x-layouts.app :title="$title" :breadcrumbs="['Permohonan BBM' => route('requester-index'), 'Permohonan Baru' => null]">
    <x-ui.page-header kicker="Permohonan BBM" title="Permohonan Baru" description="Ajukan permohonan BBM baru dan pantau slot permohonan yang sedang berjalan.">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('requester-history')">
                <i class="bi bi-clock-history"></i> Riwayat Permohonan
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

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
                <x-ui.card class="gap-5">
                    <x-ui.card-header step="1" title="Kendaraan / Alat" description="Cari berdasarkan kode, plat nomor, atau nama kendaraan." />
                    <x-ui.card-content class="grid gap-4">
                        <x-ui.field label="Kendaraan / Alat" for="vehicle_search" required error="vehicle_id">
                            <div class="relative">
                                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i>
                                <x-ui.input id="vehicle_search" name="vehicle_search" autocomplete="off" class="h-10 w-full pl-9"
                                            placeholder="Contoh: D 1383 ALT atau Innova"
                                            :value="$selectedVehicleLabel ?? ''" :invalid="$errors->has('vehicle_id')" />
                                <div id="vehicleSuggestions" hidden
                                     class="absolute top-full z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border bg-popover p-1 shadow-md"></div>
                            </div>
                            <input type="hidden" id="vehicle_id" name="vehicle_id" value="{{ old('vehicle_id') }}">
                        </x-ui.field>

                        {{-- Ringkasan data kendaraan terpilih, diisi resources/js/pages/bbm/request-create.js. --}}
                        <dl id="vehicleInfo" hidden class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border bg-border text-sm sm:grid-cols-4">
                            @foreach (['viType' => 'Jenis', 'viFuel' => 'BBM', 'viTank' => 'Tangki', 'viBenchmark' => 'Benchmark'] as $key => $label)
                                <div class="bg-muted/40 px-3 py-2.5">
                                    <dt class="text-xs text-muted-foreground">{{ $label }}</dt>
                                    <dd id="{{ $key }}" class="mt-0.5 truncate font-medium">-</dd>
                                </div>
                            @endforeach
                        </dl>
                        <p id="vehicleBudgetInfo" class="text-xs text-muted-foreground empty:hidden"></p>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card class="gap-5">
                    <x-ui.card-header step="2" title="BBM & Nominal" description="Terisi otomatis dari data kendaraan, masih bisa disesuaikan." />
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
            <x-ui.card class="gap-0 overflow-hidden pt-0 lg:sticky lg:top-4">
                <div class="bg-sidebar-gradient px-6 py-5 text-white">
                    <p class="text-xs font-medium uppercase tracking-wider text-white/70">Ringkasan permohonan</p>
                    <p id="summaryNominal" class="mt-2 text-3xl font-bold tabular-nums tracking-tight">Rp 0</p>
                    <p id="summaryFuel" class="mt-1 text-sm text-white/80">Pilih kendaraan terlebih dahulu</p>
                </div>
                <x-ui.card-content class="grid gap-5 pt-5">
                    <dl class="grid gap-2.5 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-muted-foreground">Kendaraan</dt>
                            <dd id="summaryVehicle" class="min-w-0 text-right font-medium">-</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted-foreground">Sisa slot</dt>
                            <dd class="font-medium tabular-nums">{{ $freeSlots }} dari {{ $quota }}</dd>
                        </div>
                    </dl>

                    <div class="grid gap-2 rounded-lg bg-muted/60 p-3 text-xs text-muted-foreground">
                        <p class="flex gap-2"><i class="bi bi-person-check text-sm"></i> Permohonan diproses oleh Admin GA unit Anda.</p>
                        <p class="flex gap-2"><i class="bi bi-hourglass-split text-sm"></i> Voucher berlaku 50 jam setelah disetujui. Laporkan pembelian sebelum masa berlakunya habis.</p>
                    </div>

                    <x-ui.button type="submit" id="submitBtn" data-loading-text="Mengirim..." size="lg" class="w-full">
                        <i class="bi bi-send"></i> Ajukan Permohonan
                    </x-ui.button>
                </x-ui.card-content>
            </x-ui.card>
        </form>
    @endif

    <section class="grid gap-4" aria-labelledby="slot-heading">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="slot-heading" class="text-lg font-semibold">Slot Permohonan</h2>
                <p class="text-sm text-muted-foreground">
                    Kuota Anda saat ini {{ $quota }} slot{{ $lockedSlots ? ' (' . $lockedSlots . ' slot dikunci karena penalti)' : '' }}.
                    @if ($voucherRunning)
                        {{ $voucherRunning }} voucher menunggu laporan pembelian.
                    @endif
                </p>
            </div>
            {{-- Meter slot: satu segmen per slot kuota, berwarna sesuai status. --}}
            <div class="flex items-center gap-3">
                <div class="flex gap-1" aria-hidden="true">
                    @foreach (range(0, $quota - 1) as $i)
                        <span @class([
                            'h-2 w-7 rounded-full',
                            'bg-warning' => isset($activeRequests[$i]) && $activeRequests[$i]->status !== 'approved',
                            'bg-success' => isset($activeRequests[$i]) && $activeRequests[$i]->status === 'approved',
                            'bg-input' => ! isset($activeRequests[$i]),
                        ])></span>
                    @endforeach
                </div>
                <span class="text-sm font-medium tabular-nums">{{ $activeCount }}/{{ $quota }} terpakai</span>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($activeRequests as $i => $request)
                @include($request->status === 'approved' ? 'requester.partials.slot-approved' : 'requester.partials.slot-pending', ['request' => $request, 'slotNum' => $i + 1])
            @endforeach

            @for ($n = $activeCount + 1; $n <= $quota; $n++)
                <div class="flex min-h-40 flex-col items-center justify-center gap-2 rounded-xl border border-dashed bg-card/50 p-6 text-center">
                    <span class="flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground"><i class="bi bi-plus-lg"></i></span>
                    <p class="text-sm font-medium">Slot {{ $n }} kosong</p>
                    @unless ($slotFull)
                        <a href="#vehicle_search" class="text-xs text-primary underline-offset-4 hover:underline" data-focus-vehicle>Ajukan permohonan</a>
                    @endunless
                </div>
            @endfor

            @if ($lockedSlots)
                <div class="flex min-h-40 flex-col items-center justify-center gap-2 rounded-xl border border-dashed bg-muted/40 p-6 text-center text-muted-foreground">
                    <span class="flex size-10 items-center justify-center rounded-full bg-muted"><i class="bi bi-lock"></i></span>
                    <p class="text-sm font-medium text-foreground">{{ $lockedSlots === 1 ? 'Slot 5 dikunci' : 'Slot ' . ($quota + 1) . ' sampai 5 dikunci' }}</p>
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
