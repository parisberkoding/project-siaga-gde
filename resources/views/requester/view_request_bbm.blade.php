@php
    $slots = collect(range(0, 4))->map(fn ($i) => $activeSlots[$i] ?? null);
    $filled = $slots->filter();
    $slotFull = $activeCount >= 5;
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
        <x-ui.alert variant="warning" title="Slot permohonan penuh ({{ $activeCount }}/5)">
            Selesaikan atau laporkan permohonan yang sedang berjalan sebelum mengajukan yang baru.
        </x-ui.alert>
    @else
        <form action="{{ route('requester-create') }}" method="POST" id="requestForm" data-loading class="grid items-start gap-6 lg:grid-cols-3">
            @csrf
            {{-- Opsi "Tambah Kendaraan/Alat Baru" dinonaktifkan, form hanya memakai kendaraan siap guna. --}}
            <input type="hidden" name="vehicle_option" value="existing">

            <x-ui.card class="gap-5 lg:col-span-2">
                <x-ui.card-header title="Form Permohonan" icon="bi bi-fuel-pump" description="Jenis BBM dan nominal terisi otomatis sesuai data kendaraan yang dipilih." />
                <x-ui.card-content class="grid gap-5">
                    <x-ui.field label="Kendaraan / Alat" for="vehicle_search" required error="vehicle_id">
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i>
                            <x-ui.input id="vehicle_search" name="vehicle_search" autocomplete="off" class="w-full pl-9"
                                        placeholder="Ketik kode, plat, atau nama kendaraan"
                                        :value="$selectedVehicleLabel ?? ''" :invalid="$errors->has('vehicle_id')" />
                            <div id="vehicleSuggestions" hidden
                                 class="absolute top-full z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border bg-popover p-1 shadow-md"></div>
                        </div>
                        <input type="hidden" id="vehicle_id" name="vehicle_id" value="{{ old('vehicle_id') }}">
                        <p id="vehicleBudgetInfo" class="text-xs text-muted-foreground"></p>
                    </x-ui.field>

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
                                       placeholder="Catatan tambahan untuk admin">{{ old('requester_notes') }}</x-ui.textarea>
                    </x-ui.field>
                </x-ui.card-content>
            </x-ui.card>

            {{-- Ringkasan: terisi langsung dari pilihan di form (resources/js/pages/bbm/request-create.js). --}}
            <x-ui.card class="gap-0 overflow-hidden pt-0 lg:sticky lg:top-20">
                <div class="bg-sidebar-gradient px-6 py-5 text-white">
                    <p class="text-xs uppercase tracking-wider text-white/70">Nominal diajukan</p>
                    <p id="summaryNominal" class="mt-1 text-3xl font-bold tabular-nums tracking-tight">Rp 0</p>
                    <p id="summaryFuel" class="mt-1 text-sm text-white/80">Pilih kendaraan terlebih dahulu</p>
                </div>
                <x-ui.card-content class="grid gap-4 pt-5">
                    <dl class="grid gap-2.5 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Kendaraan</dt>
                            <dd id="summaryVehicle" class="text-right font-medium">-</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted-foreground">Slot terpakai</dt>
                            <dd class="font-medium tabular-nums">{{ $activeCount }}/5</dd>
                        </div>
                    </dl>
                    <x-ui.button type="submit" id="submitBtn" data-loading-text="Mengirim..." class="w-full">
                        <i class="bi bi-send"></i> Ajukan Permohonan
                    </x-ui.button>
                    <p class="text-center text-xs text-muted-foreground">Permohonan akan diproses oleh Admin GA.</p>
                </x-ui.card-content>
            </x-ui.card>
        </form>
    @endif

    <section class="grid gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">Slot Permohonan Aktif</h2>
                <p class="text-sm text-muted-foreground">Maksimal 5 permohonan berjalan bersamaan.</p>
            </div>
            {{-- Meter slot: satu segmen per slot, berwarna sesuai status. --}}
            <div class="flex items-center gap-3">
                <div class="flex gap-1" aria-hidden="true">
                    @foreach ($slots as $slot)
                        <span @class([
                            'h-2 w-8 rounded-full',
                            'bg-warning' => $slot && in_array($slot->status, ['pending', 'pending_vehicle_approval'], true),
                            'bg-success' => $slot && $slot->status === 'approved',
                            'bg-input' => ! $slot,
                        ])></span>
                    @endforeach
                </div>
                <span class="text-sm font-medium tabular-nums">{{ $activeCount }}/5 terpakai</span>
            </div>
        </div>

        @if ($filled->isEmpty())
            <x-ui.empty-state icon="bi bi-inbox" title="Belum ada permohonan aktif" description="Permohonan yang menunggu persetujuan atau voucher yang belum dilaporkan akan tampil di sini." class="bg-card" />
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($slots as $i => $request)
                    @if ($request && in_array($request->status, ['pending', 'pending_vehicle_approval'], true))
                        @include('requester.partials.slot-pending', ['request' => $request, 'slotNum' => $i + 1])
                    @elseif ($request && $request->status === 'approved')
                        @include('requester.partials.slot-approved', ['request' => $request, 'slotNum' => $i + 1])
                    @endif
                @endforeach
            </div>
        @endif
    </section>

    <script type="application/json" id="page-data">@json(['vehicles' => $vehicleOptions, 'fallbackOptions' => $fallbackOptions])</script>

    @push('scripts')
        @vite('resources/js/pages/bbm/request-create.js')
    @endpush
</x-layouts.app>
