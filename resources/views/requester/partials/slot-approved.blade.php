{{-- Slot permohonan yang sudah disetujui: tiket voucher, sisa masa berlaku, cetak voucher, lapor pembelian. --}}
@php
    $voucher = $request->approvedRequest;
    $isVehicle = strtolower($request->vehicle->consumerial_type ?? '') === 'kendaraan';
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

<article class="flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg motion-reduce:transition-none motion-reduce:hover:translate-y-0">
    {{-- Bagian atas tiket: kode voucher dan nominal di atas gradien hijau. --}}
    <div class="bg-voucher-gradient relative overflow-hidden px-5 pb-5 pt-4 text-white">
        <i class="bi bi-ticket-perforated pointer-events-none absolute -right-3 -top-1 text-8xl leading-none text-white/10" aria-hidden="true"></i>
        <div class="relative flex items-center justify-between gap-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-white/80">Slot {{ $slotNum }}</span>
            <span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2 py-0.5 text-xs font-medium ring-1 ring-white/25"><i class="bi bi-check-circle"></i> Disetujui</span>
        </div>
        <p class="relative mt-3 text-xs text-white/75">Kode voucher</p>
        <p class="relative select-all font-mono text-xl font-bold tracking-widest">{{ $voucher->voucher_code ?? 'N/A' }}</p>
        <div class="relative mt-3 flex items-end justify-between gap-3">
            <span class="text-2xl font-bold tabular-nums tracking-tight">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</span>
            <span class="rounded-md bg-white/15 px-2 py-0.5 text-xs font-medium">{{ $request->gasoline_type }}</span>
        </div>
    </div>

    {{-- Perforasi: garis putus dengan lekukan setengah lingkaran di kiri dan kanan. --}}
    <div class="relative h-0 border-t-2 border-dashed border-border" aria-hidden="true">
        <span class="absolute -left-3 -top-3 size-6 rounded-full border bg-background"></span>
        <span class="absolute -right-3 -top-3 size-6 rounded-full border bg-background"></span>
    </div>

    <div class="grid gap-4 px-5 pb-4 pt-5 text-sm">
        <div class="grid gap-1.5">
            <div class="flex items-center justify-between gap-3 text-xs">
                <span @class(['font-semibold', 'text-emerald-700' => $tone === 'success', 'text-amber-700' => $tone === 'warning', 'text-destructive' => $tone === 'destructive'])>
                    <i class="bi bi-hourglass-split"></i> {{ $leftLabel }}
                </span>
                <span class="text-muted-foreground">sampai {{ $validUntil?->translatedFormat('d M, H:i') ?? '-' }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-muted" role="progressbar" aria-label="Sisa masa berlaku voucher" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                <div @class([
                    'h-full rounded-full bg-linear-to-r',
                    'from-emerald-400 to-teal-600' => $tone === 'success',
                    'from-amber-300 to-orange-500' => $tone === 'warning',
                    'from-red-400 to-destructive' => $tone === 'destructive',
                ]) style="width: {{ $percent }}%"></div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                <i class="bi {{ $isVehicle ? 'bi-car-front' : 'bi-tools' }}"></i>
            </span>
            <div class="min-w-0">
                <p class="truncate font-medium">{{ $request->vehicle->consumerial_code ?? 'N/A' }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ $request->vehicle->consumerial_name ?? '-' }}</p>
            </div>
        </div>

        @if ($request->authorizer_notes)
            <p class="flex gap-2 rounded-lg bg-emerald-50 p-3 text-xs text-emerald-900">
                <i class="bi bi-chat-left-quote text-emerald-600"></i>
                <span><span class="font-medium">Catatan admin:</span> {{ $request->authorizer_notes }}</span>
            </p>
        @endif

        <a href="{{ route('requester-show', $request->id) }}" class="truncate font-mono text-xs text-muted-foreground hover:text-foreground hover:underline">{{ $request->request_number }}</a>
    </div>

    <div class="mt-auto grid gap-2 border-t bg-muted/30 p-4">
        @can('permission', 'pelaporan.create')
            <x-ui.button :href="route('pelaporan.index', ['mode' => 'operational', 'permohonan_id' => $request->id])"
                         class="bg-linear-to-r from-primary to-indigo-600 shadow-md shadow-primary/20 hover:brightness-110">
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
</article>

@include('requester.partials.voucher-print-modal', ['request' => $request])
