{{--
  Daftar permohonan menunggu dengan keputusan massal. Pilih Setujui/Tolak per baris,
  isi komentar bila perlu, lalu Simpan Keputusan (resources/js/pages/bbm/admin-approval.js).
--}}
<form method="POST" action="{{ route('admin-bulk-process') }}" id="bulkApprovalForm" class="grid gap-3">
    @csrf
    <input type="hidden" name="decisions" id="decisionsInput">

    @forelse ($pendingRequests as $req)
        <div class="grid items-center gap-4 rounded-lg border bg-card p-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_auto]" data-request-row="{{ $req->id }}">
            <div class="grid gap-1 text-sm">
                <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                    <code>{{ $req->request_number }}</code>
                    <span>{{ $req->request_date->format('d/m/Y H:i') }}</span>
                    <x-ui.badge variant="secondary">{{ $req->gasoline_type }}</x-ui.badge>
                </div>
                <p class="font-medium">{{ $req->requester->nama_lengkap }}</p>
                <p class="text-muted-foreground">{{ $req->vehicle->consumerial_code }} · {{ $req->vehicle->consumerial_name }}</p>
                <p class="font-semibold text-primary">Rp {{ number_format($req->bill_amounts, 0, ',', '.') }}</p>
                @if ($req->requester_notes)
                    <p class="text-xs text-muted-foreground"><i class="bi bi-chat-dots"></i> {{ $req->requester_notes }}</p>
                @endif
            </div>

            <x-ui.field label="Komentar admin" for="catatan_admin_{{ $req->id }}">
                <x-ui.input id="catatan_admin_{{ $req->id }}" data-note="{{ $req->id }}" autocomplete="off" placeholder="Opsional" />
            </x-ui.field>

            <div class="flex gap-2 lg:justify-end" role="group" aria-label="Keputusan">
                <x-ui.button variant="outline" size="sm" aria-pressed="false" data-decision="approve" data-id="{{ $req->id }}"
                             class="aria-pressed:border-success aria-pressed:bg-success aria-pressed:text-white">
                    <i class="bi bi-check-circle"></i> Setujui
                </x-ui.button>
                <x-ui.button variant="outline" size="sm" aria-pressed="false" data-decision="reject" data-id="{{ $req->id }}"
                             class="aria-pressed:border-destructive aria-pressed:bg-destructive aria-pressed:text-white">
                    <i class="bi bi-x-circle"></i> Tolak
                </x-ui.button>
            </div>
        </div>
    @empty
        <x-ui.empty-state icon="bi bi-inbox" title="Tidak ada permohonan menunggu" description="Semua permohonan sudah diproses." />
    @endforelse

    {{-- Bar keputusan, muncul setelah ada pilihan --}}
    <div id="bulkActionBar" hidden
         class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-card p-3 shadow-lg">
        <p class="text-sm">
            <strong id="selectedCount">0</strong> permohonan dipilih
            <span class="text-muted-foreground">(<span id="approveCount" class="text-success">0</span> disetujui, <span id="rejectCount" class="text-destructive">0</span> ditolak)</span>
        </p>
        <x-ui.button id="saveDecisions"><i class="bi bi-save"></i> Simpan Keputusan</x-ui.button>
    </div>
</form>
