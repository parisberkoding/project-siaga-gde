{{-- Slot permohonan yang menunggu persetujuan + dialog pembatalan. --}}
<x-ui.card class="gap-4 overflow-hidden py-0">
    <div class="h-1 bg-warning"></div>

    <x-ui.card-content class="grid gap-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Slot {{ $slotNum }}</p>
                <a href="{{ route('requester-show', $request->id) }}" class="mt-1 block truncate font-mono text-xs text-muted-foreground hover:text-foreground hover:underline">{{ $request->request_number }}</a>
            </div>
            <x-bbm.status-badge :status="$request->status" />
        </div>

        <div>
            <p class="text-2xl font-bold tabular-nums tracking-tight">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</p>
            <p class="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground">
                <i class="bi bi-fuel-pump"></i> {{ $request->gasoline_type }}
            </p>
        </div>

        <div class="flex items-center gap-3 rounded-lg bg-muted/60 p-3 text-sm">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-md bg-card text-muted-foreground shadow-xs">
                <i class="bi {{ strtolower($request->vehicle->consumerial_type ?? '') === 'kendaraan' ? 'bi-car-front' : 'bi-tools' }}"></i>
            </span>
            <div class="min-w-0">
                <p class="truncate font-medium">{{ $request->vehicle->consumerial_code ?? 'N/A' }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ $request->vehicle->consumerial_name ?? '-' }}</p>
            </div>
        </div>

        @if ($request->vehicle && ! $request->vehicle->is_approved)
            <x-ui.badge variant="danger"><i class="bi bi-exclamation-circle"></i> Kendaraan belum disetujui</x-ui.badge>
        @endif

        <p class="flex items-start gap-2 text-xs text-muted-foreground">
            <i class="bi bi-clock-history mt-px"></i>
            <span>
                Diajukan {{ $request->request_date->diffForHumans() }}.
                {{ $request->status === 'pending_vehicle_approval'
                    ? 'Kendaraan rental menunggu persetujuan Superadmin, setelah itu diproses Admin GA.'
                    : 'Menunggu persetujuan Admin GA.' }}
            </span>
        </p>
    </x-ui.card-content>

    <div class="mt-auto flex items-center gap-2 border-t px-4 py-2.5">
        <x-ui.button variant="ghost" size="sm" :href="route('requester-show', $request->id)">
            <i class="bi bi-eye"></i> Detail
        </x-ui.button>
        @can('permission', 'bbm.request')
            <x-ui.button variant="ghost" size="sm" class="ml-auto text-destructive hover:bg-destructive-soft hover:text-destructive" data-dialog-open="cancel-request-{{ $request->id }}">
                <i class="bi bi-x-circle"></i> Batalkan
            </x-ui.button>
        @endcan
    </div>
</x-ui.card>

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
