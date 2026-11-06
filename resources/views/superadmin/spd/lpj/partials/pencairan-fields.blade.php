{{-- Isian satu baris pencairan (form tambah & dialog ubah). Field transfer ditampilkan oleh resources/js/pages/spd/pencairan.js. --}}
<x-ui.field label="Tanggal" required>
    <x-ui.date-picker name="tanggal" :value="$values['tanggal']" required />
</x-ui.field>
<x-ui.field label="Nominal (Rp)" required>
    <x-ui.input type="number" name="nominal" :value="$values['nominal']" min="0" step="0.01" required />
</x-ui.field>
<x-ui.field label="Bentuk Pencairan" required>
    <x-ui.select name="bentuk_pencairan" data-bentuk required>
        <option value="Tunai" @selected($values['bentuk'] === 'Tunai')>Tunai</option>
        <option value="Transfer" @selected($values['bentuk'] === 'Transfer')>Transfer</option>
    </x-ui.select>
</x-ui.field>
<x-ui.field label="No. Referensi Perbankan" data-transfer-only>
    <x-ui.input name="bank_reference" :value="$values['bank_reference']" maxlength="255" />
</x-ui.field>
<x-ui.field label="Rekening Tujuan" data-transfer-only>
    <x-ui.input name="rek_tujuan" :value="$values['rek_tujuan']" maxlength="255" />
</x-ui.field>
<x-ui.field label="Catatan">
    <x-ui.input name="catatan" :value="$values['catatan']" maxlength="255" />
</x-ui.field>
