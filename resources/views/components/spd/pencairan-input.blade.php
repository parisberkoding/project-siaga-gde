{{--
  Input PEMBERIAN DANA (pencairan) untuk sebuah surat SPD. Satu baris = SATU kali pemberian/transfer
  (bukan per hari), tersimpan ke surat_spd_budgets. Interaksi: resources/js/ui/pencairan-input.js.
  (Template cetak masih memakai partials/pencairan-input versi Bootstrap.)
--}}
@props(['name' => 'pencairans', 'rows' => collect(), 'tanggalDefault' => null, 'totalTarget' => null])

@php
    $existingRows = $rows->map(fn ($b) => [
        'tanggal' => $b->tanggal?->format('Y-m-d'),
        'nominal' => (float) $b->nominal,
        'bentuk' => $b->bentuk_pencairan_label,
        'bank_reference' => $b->bank_reference,
        'rek_tujuan' => $b->rek_tujuan,
        'catatan' => $b->catatan,
    ])->values()->all();
@endphp

<div data-pencairan-root data-name="{{ $name }}" data-tanggal-default="{{ $tanggalDefault ?? now()->toDateString() }}"
     data-total-target="{{ $totalTarget }}" data-rows="{{ json_encode($existingRows) }}" {{ $attributes->class('grid gap-3') }}>
    <div data-pencairan-body class="grid gap-3"></div>

    <div class="flex flex-wrap items-center justify-between gap-2">
        <x-ui.button variant="outline" size="sm" data-pencairan-add><i class="bi bi-plus-circle"></i> Tambah Pemberian Dana</x-ui.button>
        <span class="text-sm text-muted-foreground">Total dana diberikan: <strong class="tabular-nums text-success" data-pencairan-total>Rp 0</strong></span>
    </div>

    <template data-pencairan-template>
        <div data-pencairan-row class="grid gap-3 rounded-lg border bg-card p-3">
            <div class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_0.8fr_1.2fr_auto]">
                <x-ui.field label="Tanggal"><x-ui.input type="date" data-name="tanggal" /></x-ui.field>
                <x-ui.field label="Nominal (Rp)"><x-ui.input type="number" data-name="nominal" min="0" step="1000" placeholder="0" /></x-ui.field>
                <x-ui.field label="Bentuk">
                    <x-ui.select data-name="bentuk_pencairan">
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer">Transfer</option>
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="No. Referensi Perbankan" data-transfer-only>
                    <x-ui.input data-name="bank_reference" maxlength="255" placeholder="cth: TRF/2026/09/00123" />
                </x-ui.field>
                <x-ui.button variant="ghost" size="icon" class="text-destructive hover:bg-destructive-soft" data-pencairan-remove title="Hapus baris" aria-label="Hapus baris"><i class="bi bi-trash"></i></x-ui.button>
            </div>
            <div class="grid gap-3 sm:grid-cols-[5fr_7fr]">
                <x-ui.field label="Rekening Tujuan" data-transfer-only>
                    <x-ui.input data-name="rek_tujuan" maxlength="255" placeholder="cth: BCA 1234567890 a.n. Nama" />
                </x-ui.field>
                <x-ui.field label="Catatan (opsional)">
                    <x-ui.input data-name="catatan" maxlength="255" placeholder="cth: uang muka perjalanan" />
                </x-ui.field>
            </div>
        </div>
    </template>
</div>
