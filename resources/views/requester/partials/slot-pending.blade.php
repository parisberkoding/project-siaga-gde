{{-- Slot permohonan yang menunggu persetujuan + dialog pembatalan. --}}
<x-ui.card class="gap-4 border-warning/40">
    <x-ui.card-header>
        <div class="font-semibold leading-none">Slot {{ $slotNum }}</div>
        <div class="text-sm text-muted-foreground"><code>{{ $request->request_number }}</code></div>
        <x-slot:action><x-bbm.status-badge :status="$request->status" /></x-slot:action>
    </x-ui.card-header>

    <x-ui.card-content class="grid gap-3 text-sm">
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5">
            <dt class="text-muted-foreground">Kendaraan</dt>
            <dd>
                {{ $request->vehicle->consumerial_code ?? 'N/A' }}
                <span class="block text-xs text-muted-foreground">{{ $request->vehicle->consumerial_name ?? '' }} ({{ $request->vehicle->consumerial_type ?? '-' }})</span>
                @if ($request->vehicle && ! $request->vehicle->is_approved)
                    <x-ui.badge variant="danger" class="mt-1"><i class="bi bi-exclamation-circle"></i> Kendaraan belum disetujui</x-ui.badge>
                @endif
            </dd>
            <dt class="text-muted-foreground">BBM</dt>
            <dd>{{ $request->gasoline_type }}</dd>
            <dt class="text-muted-foreground">Nominal</dt>
            <dd class="font-medium">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</dd>
            @if ($request->requester_notes)
                <dt class="text-muted-foreground">Catatan</dt>
                <dd>{{ Str::limit($request->requester_notes, 80) }}</dd>
            @endif
            <dt class="text-muted-foreground">Diajukan</dt>
            <dd>{{ $request->request_date->diffForHumans() }}</dd>
        </dl>

        @if ($request->status === 'pending_vehicle_approval')
            <x-ui.alert variant="warning">
                Kendaraan rental sedang menunggu persetujuan Superadmin. Setelah disetujui, permohonan diproses oleh Admin.
            </x-ui.alert>
        @else
            <p class="text-xs text-muted-foreground"><i class="bi bi-clock-history"></i> Menunggu persetujuan Admin GA.</p>
        @endif
    </x-ui.card-content>

    @can('permission', 'bbm.request')
        <x-ui.card-footer class="mt-auto">
            <x-ui.button variant="outline" class="w-full text-destructive hover:text-destructive" data-dialog-open="cancel-request-{{ $request->id }}">
                <i class="bi bi-x-circle"></i> Batalkan Permohonan
            </x-ui.button>
        </x-ui.card-footer>
    @endcan
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
