{{-- Slot permohonan yang menunggu persetujuan + dialog pembatalan. --}}
@php
    $isVehicle = strtolower($request->vehicle->consumerial_type ?? '') === 'kendaraan';
@endphp

<article class="flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg motion-reduce:transition-none motion-reduce:hover:translate-y-0">
    <div class="relative overflow-hidden border-b border-amber-100 bg-linear-to-br from-amber-50 via-orange-50 to-card px-5 pb-5 pt-4">
        <i class="bi bi-hourglass-split pointer-events-none absolute -right-2 bottom-0 text-7xl leading-none text-amber-500/10" aria-hidden="true"></i>
        <div class="relative flex items-center justify-between gap-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-amber-800">Slot {{ $slotNum }}</span>
            <x-bbm.status-badge :status="$request->status" />
        </div>
        <p class="relative mt-3 flex items-center gap-2 text-sm font-medium text-amber-900">
            <span class="relative flex size-2.5">
                <span class="absolute inline-flex size-full rounded-full bg-amber-400 opacity-75 motion-safe:animate-ping"></span>
                <span class="relative inline-flex size-2.5 rounded-full bg-amber-500"></span>
            </span>
            {{ $request->status === 'pending_vehicle_approval' ? 'Menunggu persetujuan kendaraan' : 'Menunggu persetujuan Admin GA' }}
        </p>
        <p class="relative mt-2 text-2xl font-bold tabular-nums tracking-tight">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</p>
        <p class="relative mt-0.5 flex items-center gap-1.5 text-sm text-muted-foreground"><i class="bi bi-fuel-pump text-orange-600"></i> {{ $request->gasoline_type }}</p>
    </div>

    <div class="grid gap-3 px-5 py-4 text-sm">
        <div class="flex items-center gap-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                <i class="bi {{ $isVehicle ? 'bi-car-front' : 'bi-tools' }}"></i>
            </span>
            <div class="min-w-0">
                <p class="truncate font-medium">{{ $request->vehicle->consumerial_code ?? 'N/A' }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ $request->vehicle->consumerial_name ?? '-' }}</p>
            </div>
        </div>

        @if ($request->vehicle && ! $request->vehicle->is_approved)
            <x-ui.badge variant="danger"><i class="bi bi-exclamation-circle"></i> Kendaraan belum disetujui</x-ui.badge>
        @endif

        <div class="flex items-center justify-between gap-3 text-xs text-muted-foreground">
            <a href="{{ route('requester-show', $request->id) }}" class="truncate font-mono hover:text-foreground hover:underline">{{ $request->request_number }}</a>
            <span class="shrink-0"><i class="bi bi-clock"></i> {{ $request->request_date->diffForHumans() }}</span>
        </div>
    </div>

    <div class="mt-auto flex items-center gap-2 border-t bg-muted/30 px-4 py-2.5">
        <x-ui.button variant="ghost" size="sm" :href="route('requester-show', $request->id)">
            <i class="bi bi-eye"></i> Detail
        </x-ui.button>
        @can('permission', 'bbm.request')
            <x-ui.button variant="ghost" size="sm" class="ml-auto text-destructive hover:bg-destructive-soft hover:text-destructive" data-dialog-open="cancel-request-{{ $request->id }}">
                <i class="bi bi-x-circle"></i> Batalkan
            </x-ui.button>
        @endcan
    </div>
</article>

@can('permission', 'bbm.request')
    <x-ui.dialog id="cancel-request-{{ $request->id }}" title="Batalkan permohonan?" description="Permohonan yang dibatalkan tidak bisa dikembalikan.">
        <form action="{{ route('requester.cancel-request', $request->id) }}" method="POST" id="cancel-form-{{ $request->id }}" data-loading class="grid gap-4">
            @csrf
            @method('PUT')

            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 rounded-lg bg-muted/60 p-3 text-sm">
                <dt class="text-muted-foreground">Nomor</dt>
                <dd><code>{{ $request->request_number }}</code></dd>
                <dt class="text-muted-foreground">Kendaraan</dt>
                <dd>{{ $request->vehicle->consumerial_code ?? 'N/A' }}</dd>
                <dt class="text-muted-foreground">Nominal</dt>
                <dd>Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</dd>
            </dl>

            <x-ui.field label="Alasan pembatalan" for="cancellation_reason_{{ $request->id }}" required hint="Minimal 10 karakter." error="cancellation_reason">
                <x-ui.textarea id="cancellation_reason_{{ $request->id }}" name="cancellation_reason" rows="4" required minlength="10"
                               placeholder="Contoh: salah input nominal atau salah pilih kendaraan">{{ old('cancellation_reason') }}</x-ui.textarea>
            </x-ui.field>
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" data-dialog-close>Kembali</x-ui.button>
            <x-ui.button type="submit" form="cancel-form-{{ $request->id }}" variant="destructive" data-loading-text="Membatalkan...">
                Ya, batalkan permohonan
            </x-ui.button>
        </x-slot:footer>
    </x-ui.dialog>
@endcan
