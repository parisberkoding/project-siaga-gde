{{--
  Dialog voucher BBM. Tombol Print membuka aplikasi Bluetooth Print (Thermer) lewat URL scheme;
  di desktop, resources/js/pages/bbm/request-create.js menawarkan pratinjau JSON-nya.
  Endpoint JSON publik (app Thermer mengunduhnya tanpa cookie session), jadi diamankan dengan
  signed URL berumur 1 jam. Jangan ubah https ke http: itu merusak signature.
--}}
@php($responseUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('voucher.thermer.response', now()->addHour(), ['id' => $request->id]))

<x-ui.dialog id="voucher-{{ $request->id }}" title="Voucher BBM">
    <div class="grid gap-4">
        <div class="flex flex-col items-center gap-1 text-center">
            <img src="https://jojo-static-files-klfm7hjb.s3.ap-southeast-3.amazonaws.com/logo-geodipa-officeless.png" alt="Logo Geodipa" class="w-20">
            <p class="font-semibold">PT. Geo Dipa Energi (Persero)</p>
            <p class="text-sm text-muted-foreground">Unit Patuha</p>
        </div>

        <div class="rounded-lg bg-primary p-4 text-center text-primary-foreground">
            <p class="text-xs opacity-80"><i class="bi bi-ticket-perforated"></i> KODE VOUCHER BBM</p>
            <p class="mt-1 font-mono text-2xl font-semibold tracking-widest">{{ $request->approvedRequest->voucher_code ?? 'N/A' }}</p>
        </div>

        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 rounded-lg border p-3 text-sm">
            <dt class="text-muted-foreground">No. Permohonan</dt>
            <dd><code>{{ $request->request_number }}</code></dd>
            <dt class="text-muted-foreground">Pemohon</dt>
            <dd>{{ $request->requester->nama_lengkap ?? 'N/A' }}</dd>
            <dt class="text-muted-foreground">Kendaraan/Alat</dt>
            <dd>
                {{ $request->vehicle->consumerial_code ?? 'N/A' }}
                <span class="block text-xs text-muted-foreground">{{ $request->vehicle->consumerial_name ?? '' }} ({{ $request->vehicle->consumerial_type ?? '-' }})</span>
            </dd>
            <dt class="text-muted-foreground">Jenis BBM</dt>
            <dd>{{ $request->gasoline_type }}</dd>
            <dt class="text-muted-foreground">Nominal</dt>
            <dd class="font-medium text-success">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</dd>
            <dt class="text-muted-foreground">Disetujui</dt>
            <dd>{{ $request->authorization_date?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
        </dl>

        <x-ui.alert variant="info">Tekan Print untuk mencetak voucher lewat printer thermal Bluetooth.</x-ui.alert>
    </div>

    <x-slot:footer>
        <x-ui.button variant="outline" data-dialog-close>Tutup</x-ui.button>
        <x-ui.button variant="success"
                     :href="'my.bluetoothprint.scheme://' . $responseUrl"
                     data-thermer-print
                     data-response-url="{{ $responseUrl }}"
                     data-voucher-code="{{ $request->approvedRequest->voucher_code ?? '' }}">
            <i class="bi bi-phone"></i> Print
        </x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
