{{-- Form Ajukan Permohonan BBM (mode ?ajukan=1 di requester-index). Interaksi: resources/js/pages/bbm/request-create.js. --}}
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
                                    placeholder="Contoh: D 1383 ALT atau Innova" role="combobox" aria-autocomplete="list"
                                    aria-expanded="false" aria-controls="vehicleSuggestions"
                                    :value="$selectedVehicleLabel ?? ''" :invalid="$errors->has('vehicle_id')" />
                        {{-- popover (top layer): tidak tertutup kartu Langkah 2, yang punya stacking context sendiri (ornament-corner). --}}
                        <div id="vehicleSuggestions" popover="manual" role="listbox" aria-label="Pilihan kendaraan"
                             class="m-0 max-h-64 overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"></div>
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
                    {{-- Select shadcn (x-ui.select-menu); nilai & daftar nominal diatur request-create.js lewat lib/select-menu. --}}
                    @php
                        $fuelNames = collect($gasolineTypes)->pluck('gasoline_name')->whenEmpty(fn () => collect(['Pertamax', 'Pertamina Dex']));
                        $fuelOptions = $fuelNames->map(fn ($name) => ['value' => $name, 'label' => $name])->all();
                        $nominalOptions = collect($fallbackOptions ?? [])->map(fn ($v) => ['value' => (string) $v, 'label' => 'Rp' . "\u{00A0}" . number_format($v, 0, ',', '.')])->all();
                    @endphp
                    <x-ui.field label="Jenis BBM" for="gasoline_type" required error="gasoline_type">
                        <x-ui.select-menu id="gasoline_type" name="gasoline_type" :options="$fuelOptions" :value="old('gasoline_type')"
                                          placeholder="Pilih jenis BBM" icon="bi bi-fuel-pump" required class="[&_[data-select-trigger]]:h-11" />
                    </x-ui.field>

                    <x-ui.field label="Nominal (Rp)" for="bill_payment" required error="bill_payment">
                        <x-ui.select-menu id="bill_payment" name="bill_payment" :options="$nominalOptions" :value="old('bill_payment')"
                                          placeholder="Pilih nominal" icon="bi bi-cash-coin" required class="[&_[data-select-trigger]]:h-11" />
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
