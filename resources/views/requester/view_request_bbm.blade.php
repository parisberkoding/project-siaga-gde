<x-layouts.app :title="$title" :breadcrumbs="['Permohonan BBM' => route('requester-index'), 'Permohonan Baru' => null]">
    <x-ui.page-header title="Permohonan BBM" description="Ajukan permohonan BBM baru dan pantau slot permohonan yang sedang berjalan.">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('requester-history')">
                <i class="bi bi-clock-history"></i> Riwayat Permohonan
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.card-header title="Form Permohonan Baru" description="Jenis BBM dan nominal terisi otomatis sesuai data kendaraan yang dipilih." />
        <x-ui.card-content>
            @if ($activeCount >= 5)
                <x-ui.alert variant="warning" title="Slot permohonan penuh ({{ $activeCount }}/5)">
                    Selesaikan atau laporkan permohonan yang sedang berjalan sebelum mengajukan yang baru.
                </x-ui.alert>
            @else
                <form action="{{ route('requester-create') }}" method="POST" id="requestForm" data-loading class="grid gap-5 md:grid-cols-2">
                    @csrf
                    {{-- Opsi "Tambah Kendaraan/Alat Baru" dinonaktifkan, form hanya memakai kendaraan siap guna. --}}
                    <input type="hidden" name="vehicle_option" value="existing">

                    <x-ui.field label="Kendaraan / Alat" for="vehicle_search" required error="vehicle_id">
                        <div class="relative">
                            <x-ui.input id="vehicle_search" name="vehicle_search" autocomplete="off"
                                        placeholder="Ketik kode, plat, atau nama kendaraan"
                                        :value="$selectedVehicleLabel ?? ''" :invalid="$errors->has('vehicle_id')" />
                            <div id="vehicleSuggestions" hidden
                                 class="absolute top-full z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border bg-popover p-1 shadow-md"></div>
                        </div>
                        <input type="hidden" id="vehicle_id" name="vehicle_id" value="{{ old('vehicle_id') }}">
                        <p id="vehicleBudgetInfo" class="text-xs text-muted-foreground"></p>
                    </x-ui.field>

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
                        <p id="billPaymentInfo" class="text-xs text-muted-foreground"></p>
                    </x-ui.field>

                    <x-ui.field label="Catatan (opsional)" for="requester_notes" error="requester_notes">
                        <x-ui.textarea id="requester_notes" name="requester_notes" rows="2"
                                       placeholder="Catatan tambahan untuk admin">{{ old('requester_notes') }}</x-ui.textarea>
                    </x-ui.field>

                    <div class="md:col-span-2">
                        <x-ui.button type="submit" id="submitBtn" data-loading-text="Mengirim...">
                            <i class="bi bi-send"></i> Ajukan Permohonan
                        </x-ui.button>
                    </div>
                </form>
            @endif
        </x-ui.card-content>
    </x-ui.card>

    <section class="grid gap-4">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-lg font-semibold">Slot Permohonan Aktif</h2>
            <x-ui.badge variant="outline">{{ $activeCount }}/5 terpakai</x-ui.badge>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @for ($i = 0; $i < 5; $i++)
                @php($request = $activeSlots[$i] ?? null)
                @if ($request && in_array($request->status, ['pending', 'pending_vehicle_approval'], true))
                    @include('requester.partials.slot-pending', ['request' => $request, 'slotNum' => $i + 1])
                @elseif ($request && $request->status === 'approved')
                    @include('requester.partials.slot-approved', ['request' => $request, 'slotNum' => $i + 1])
                @else
                    <x-ui.empty-state icon="bi bi-inbox" title="Slot {{ $i + 1 }} kosong" description="Siap dipakai untuk permohonan baru." class="min-h-64 bg-card" />
                @endif
            @endfor
        </div>
    </section>

    <script type="application/json" id="page-data">@json(['vehicles' => $vehicleOptions, 'fallbackOptions' => $fallbackOptions])</script>

    @push('scripts')
        @vite('resources/js/pages/bbm/request-create.js')
    @endpush
</x-layouts.app>
