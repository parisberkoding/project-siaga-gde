{{--
  Isian cetak laporan transaksi BBM (PDF di tab baru). Dipakai halaman Cetak Laporan (superadmin/print-report)
  dan dialog cetak di Dashboard BBM, supaya isiannya sama. Tombol kirim dari pemanggil: slot (di dalam form)
  atau tombol ber-atribut form="id" (footer dialog). Layar tunggu PDF memakai input download_token.
--}}
@props(['approvers', 'idPrefix' => 'print'])

@php
    $today = now();
    $fmt = fn ($d) => $d->format('Y-m-d');
    $presets = [
        'Hari ini' => [$fmt($today), $fmt($today)],
        'Minggu ini' => [$fmt($today->copy()->startOfWeek()), $fmt($today)],
        'Bulan ini' => [$fmt($today->copy()->startOfMonth()), $fmt($today)],
        'Bulan lalu' => [$fmt($today->copy()->subMonthNoOverflow()->startOfMonth()), $fmt($today->copy()->subMonthNoOverflow()->endOfMonth())],
        'Tahun ini' => [$fmt($today->copy()->startOfYear()), $fmt($today)],
    ];
    $roleLabels = ['superadmin' => 'Super Admin', 'admin' => 'Admin'];
    $gasolineOptions = [['value' => '', 'label' => 'Semua jenis BBM'], ...collect(['Pertalite', 'Pertamax', 'Pertamina Dex'])->map(fn ($g) => ['value' => $g, 'label' => $g])->all()];
    $approverOptions = [['value' => '', 'label' => 'Semua approver'], ...collect($approvers)->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->nama_lengkap . ' · ' . ($roleLabels[$a->role] ?? $a->role)])->all()];
    $statuses = old('status', ['approved', 'completed']);
@endphp

<form action="{{ route('superadmin.print-report') }}" method="GET" target="_blank" {{ $attributes->class('grid gap-5') }}>
    <input type="hidden" name="download_token">

    <x-ui.field label="Periode transaksi" :for="$idPrefix . '_range'" required error="end_date" hint="Pilih di kalender atau pakai rentang cepat. Rentang panjang membuat PDF lebih lama dibuat.">
        <x-ui.date-range-picker :id="$idPrefix . '_range'" :from="old('start_date', $presets['Bulan ini'][0])" :to="old('end_date', $presets['Bulan ini'][1])"
            :max="$fmt($today)" :submit="false" :presets="$presets" />
    </x-ui.field>

    <fieldset class="grid gap-2">
        <legend class="mb-2 text-sm font-medium">Status permohonan</legend>
        <div class="grid grid-cols-2 gap-2 lg:grid-cols-4">
            @foreach (['approved', 'completed', 'pending', 'rejected'] as $status)
                <label class="flex cursor-pointer items-center gap-2 rounded-md border p-3 transition-colors hover:bg-accent/50 has-checked:border-primary has-checked:bg-accent">
                    <x-ui.checkbox name="status[]" value="{{ $status }}" :checked="in_array($status, $statuses)" />
                    <x-bbm.status-badge :status="$status" />
                </label>
            @endforeach
        </div>
        <p class="text-xs text-muted-foreground">Bila tidak ada yang dipilih, laporan memuat Disetujui dan Selesai.</p>
    </fieldset>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field label="Jenis BBM" :for="$idPrefix . '_gasoline'">
            <x-ui.select-menu :id="$idPrefix . '_gasoline'" name="gasoline_type" :value="old('gasoline_type', '')" :options="$gasolineOptions" icon="bi bi-fuel-pump" />
        </x-ui.field>
        <x-ui.field label="Diproses oleh" :for="$idPrefix . '_authorizer'">
            <x-ui.select-menu :id="$idPrefix . '_authorizer'" name="authorizer_id" :value="(string) old('authorizer_id', '')" :options="$approverOptions" icon="bi bi-person-check" searchable />
        </x-ui.field>
    </div>

    {{ $slot }}
</form>
