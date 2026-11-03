{{-- Slot permohonan yang sudah disetujui: tiket voucher, sisa masa berlaku, cetak voucher, lapor pembelian. --}}
@php
    $voucher = $request->approvedRequest;
    $validUntil = $voucher?->valid_until ? \Carbon\Carbon::parse($voucher->valid_until) : null;
    $approvedAt = $voucher?->approval_date ? \Carbon\Carbon::parse($voucher->approval_date) : $request->authorization_date;
    $totalMinutes = $validUntil && $approvedAt ? max(1, $approvedAt->diffInMinutes($validUntil)) : 50 * 60;
    $leftMinutes = $validUntil ? max(0, (int) now()->diffInMinutes($validUntil, false)) : 0;
    $percent = (int) round(min(100, $leftMinutes / $totalMinutes * 100));
    $tone = match (true) {
        $leftMinutes === 0 => 'destructive',
        $leftMinutes <= 12 * 60 => 'warning',
        default => 'success',
    };
    $leftLabel = $leftMinutes === 0
        ? 'Masa berlaku habis'
        : 'Sisa ' . ($leftMinutes >= 60 ? intdiv($leftMinutes, 60) . ' jam ' : '') . ($leftMinutes % 60) . ' menit';
@endphp

<x-ui.card class="gap-4 overflow-hidden py-0">
    <div class="h-1 bg-success"></div>

    <x-ui.card-content class="grid gap-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Slot {{ $slotNum }}</p>
                <a href="{{ route('requester-show', $request->id) }}" class="mt-1 block truncate font-mono text-xs text-muted-foreground hover:text-foreground hover:underline">{{ $request->request_number }}</a>
            </div>
            <x-bbm.status-badge :status="$request->status" />
        </div>

        {{-- Tiket voucher: lubang di kiri-kanan dan garis putus sebagai perforasi. --}}
        <div class="relative rounded-lg border border-success/30 bg-success-soft">
            <span class="absolute -left-2 top-1/2 size-4 -translate-y-1/2 rounded-full border border-success/30 bg-card [clip-path:inset(0_0_0_50%)]"></span>
            <span class="absolute -right-2 top-1/2 size-4 -translate-y-1/2 rounded-full border border-success/30 bg-card [clip-path:inset(0_50%_0_0)]"></span>
            <div class="px-4 pb-3 pt-3 text-center">
                <p class="text-xs font-medium text-success"><i class="bi bi-ticket-perforated"></i> Kode voucher</p>
                <p class="mt-1 select-all font-mono text-xl font-semibold tracking-widest">{{ $voucher->voucher_code ?? 'N/A' }}</p>
            </div>
            <div class="flex items-center justify-between gap-3 border-t border-dashed border-success/40 px-4 py-2.5">
                <span class="text-lg font-bold tabular-nums">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</span>
                <span class="text-xs text-muted-foreground">{{ $request->gasoline_type }}</span>
            </div>
        </div>

        <div class="grid gap-1.5">
            <div class="flex items-center justify-between gap-3 text-xs">
                <span @class(['font-medium', 'text-success' => $tone === 'success', 'text-warning' => $tone === 'warning', 'text-destructive' => $tone === 'destructive'])>
                    <i class="bi bi-hourglass-split"></i> {{ $leftLabel }}
                </span>
                <span class="text-muted-foreground">sampai {{ $validUntil?->translatedFormat('d M, H:i') ?? '-' }}</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-muted" role="progressbar" aria-label="Sisa masa berlaku voucher" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                <div @class(['h-full rounded-full', 'bg-success' => $tone === 'success', 'bg-warning' => $tone === 'warning', 'bg-destructive' => $tone === 'destructive']) style="width: {{ $percent }}%"></div>
            </div>
        </div>

        <div class="flex items-center gap-3 text-sm">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                <i class="bi {{ strtolower($request->vehicle->consumerial_type ?? '') === 'kendaraan' ? 'bi-car-front' : 'bi-tools' }}"></i>
            </span>
            <div class="min-w-0">
                <p class="truncate font-medium">{{ $request->vehicle->consumerial_code ?? 'N/A' }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ $request->vehicle->consumerial_name ?? '-' }}</p>
            </div>
        </div>

        @if ($request->authorizer_notes)
            <p class="rounded-lg bg-muted/60 p-3 text-xs"><span class="text-muted-foreground">Catatan admin:</span> {{ $request->authorizer_notes }}</p>
        @endif
    </x-ui.card-content>

    <div class="mt-auto grid gap-2 border-t p-4">
        @can('permission', 'pelaporan.create')
            <x-ui.button :href="route('pelaporan.index', ['mode' => 'operational', 'permohonan_id' => $request->id])">
                <i class="bi bi-send"></i> Lapor Pembelian
            </x-ui.button>
        @endcan
        <div class="grid grid-cols-2 gap-2">
            <x-ui.button variant="outline" size="sm" data-dialog-open="voucher-{{ $request->id }}">
                <i class="bi bi-printer"></i> Cetak
            </x-ui.button>
            <x-ui.button variant="outline" size="sm" :href="route('requester-show', $request->id)">
                <i class="bi bi-eye"></i> Detail
            </x-ui.button>
        </div>
    </div>
</x-ui.card>

@include('requester.partials.voucher-print-modal', ['request' => $request])
