{{-- Slot permohonan yang sudah disetujui: kode voucher, cetak voucher, lapor pembelian. --}}
<x-ui.card class="gap-4 border-success/40">
    <x-ui.card-header>
        <div class="font-semibold leading-none">Slot {{ $slotNum }}</div>
        <div class="text-sm text-muted-foreground"><code>{{ $request->request_number }}</code></div>
        <x-slot:action><x-bbm.status-badge :status="$request->status" /></x-slot:action>
    </x-ui.card-header>

    <x-ui.card-content class="grid gap-3 text-sm">
        <div class="rounded-lg border border-dashed border-success/50 bg-success-soft p-3 text-center">
            <p class="text-xs text-success"><i class="bi bi-ticket-perforated"></i> Kode voucher BBM</p>
            <p class="mt-1 font-mono text-xl font-semibold tracking-widest">{{ $request->approvedRequest->voucher_code ?? 'N/A' }}</p>
        </div>

        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5">
            <dt class="text-muted-foreground">Disetujui</dt>
            <dd>{{ $request->authorization_date?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
            <dt class="text-muted-foreground">Kendaraan</dt>
            <dd>
                {{ $request->vehicle->consumerial_code ?? 'N/A' }}
                <span class="block text-xs text-muted-foreground">{{ $request->vehicle->consumerial_name ?? '' }} ({{ $request->vehicle->consumerial_type ?? '-' }})</span>
            </dd>
            <dt class="text-muted-foreground">Nominal</dt>
            <dd class="font-medium text-primary">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</dd>
            @if ($request->authorizer_notes)
                <dt class="text-muted-foreground">Catatan admin</dt>
                <dd class="italic">{{ $request->authorizer_notes }}</dd>
            @endif
        </dl>
    </x-ui.card-content>

    <x-ui.card-footer class="mt-auto flex-col items-stretch">
        <x-ui.button variant="outline" data-dialog-open="voucher-{{ $request->id }}">
            <i class="bi bi-printer"></i> Cetak Voucher
        </x-ui.button>
        @can('permission', 'pelaporan.create')
            <x-ui.button :href="route('pelaporan.index', ['mode' => 'operational', 'permohonan_id' => $request->id])">
                <i class="bi bi-send"></i> Lapor Pembelian
            </x-ui.button>
        @endcan
    </x-ui.card-footer>
</x-ui.card>

@include('requester.partials.voucher-print-modal', ['request' => $request])
