{{-- Tab voucher terbit per tanggal + tracking approval. Filter tanggal/status memuat ulang lewat AJAX. --}}
@php
    $voucherDate ??= now()->subDay()->toDateString();
@endphp

<div class="grid gap-3" id="voucherPanel" data-list-url="{{ route('admin.vouchers.list') }}">
    {{-- Kartu filter (judul, tanggal, status, export) terpisah dari kartu tabel. --}}
    <div class="grid gap-3 rounded-xl border bg-card p-3 shadow-xs xl:flex xl:items-end xl:justify-between">
        <div>
            <h3 class="font-medium">Voucher terbit dan tracking approval</h3>
            <p class="text-sm text-muted-foreground">Bawaan menampilkan voucher kemarin. Data berubah otomatis saat filter diganti.</p>
        </div>
        {{-- HP: tanggal & status masing-masing selebar layar (label tanggal panjang), dua tombol export sama lebar. --}}
        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
            <x-ui.date-picker id="voucherDate" :value="$voucherDate" :max="now()->toDateString()" class="col-span-2 sm:w-64" aria-label="Tanggal voucher terbit" />
            <x-ui.select-menu id="voucherStatus" name="voucher_status" value="all" icon="bi bi-funnel" class="col-span-2 sm:w-44" aria-label="Status voucher"
                :options="[['value' => 'all', 'label' => 'Semua status'], ['value' => 'active', 'label' => 'Aktif'], ['value' => 'expired', 'label' => 'Kedaluwarsa']]" />
            <x-ui.button variant="outline" data-voucher-export="selected"><i class="bi bi-file-earmark-spreadsheet"></i> <span>Export <span class="max-sm:hidden">terpilih</span><span class="sm:hidden">pilihan</span></span></x-ui.button>
            <x-ui.button variant="success" data-voucher-export="all"><i class="bi bi-file-earmark-spreadsheet-fill"></i> Export semua</x-ui.button>
        </div>
    </div>

    <form id="voucherExportForm" method="POST" action="{{ route('admin.vouchers.export') }}" hidden>
        @csrf
        <input type="hidden" name="date" id="exportDate">
        <input type="hidden" name="status" id="exportStatus">
        <div id="exportIdsContainer"></div>
    </form>

    <x-ui.card data-table-card class="gap-0 overflow-hidden py-0">
    <x-ui.table id="voucherTable" class="max-sm:[&_td]:whitespace-nowrap">
        <thead>
            <tr>
                <th class="w-10"><x-ui.checkbox id="voucherSelectAll" aria-label="Pilih semua" /></th>
                <th>Pemohon</th>
                <th>Kendaraan / Alat</th>
                <th class="text-right">Nominal</th>
                <th>Kode voucher</th>
                <th>Status</th>
                <th>Tracking approval</th>
            </tr>
        </thead>
        <tbody id="voucherBody">
            @forelse ($approvedVouchers as $voucher)
                @php
                    $perm = $voucher->permohonan;
                    $vehicle = $perm?->vehicle;
                    $isExpired = $voucher->valid_until && \Carbon\Carbon::parse($voucher->valid_until)->isPast();
                @endphp
                <tr>
                    <td><x-ui.checkbox class="voucher-check" value="{{ $voucher->id }}" aria-label="Pilih voucher" /></td>
                    <td>
                        <span class="font-medium">{{ $perm?->requester?->nama_lengkap ?? 'Unknown' }}</span>
                        <span class="block text-xs text-muted-foreground">{{ $perm?->request_number }}</span>
                    </td>
                    <td>
                        @if ($vehicle)
                            <span class="font-medium text-primary">{{ $vehicle->consumerial_code }}</span>
                            <span class="block text-xs text-muted-foreground">{{ $vehicle->consumerial_name }} ({{ $vehicle->consumerial_type }})</span>
                        @else
                            <x-ui.badge variant="muted">Kendaraan dihapus</x-ui.badge>
                        @endif
                    </td>
                    <td class="whitespace-nowrap text-right tabular-nums">Rp {{ number_format($voucher->approved_amount, 0, ',', '.') }}</td>
                    <td>
                        <code class="font-semibold tracking-wide">{{ $voucher->voucher_code }}</code>
                        <span class="block text-xs text-muted-foreground">Berlaku sampai {{ $voucher->valid_until ? \Carbon\Carbon::parse($voucher->valid_until)->format('d/m/Y H:i') : '-' }}</span>
                    </td>
                    <td><x-bbm.status-badge :status="$isExpired ? 'expired' : 'approved'" /></td>
                    <td class="text-xs">
                        <span class="block">Approver: <strong>{{ $voucher->authorizer?->nama_lengkap ?? '-' }}</strong></span>
                        <span class="block text-muted-foreground">Approval: {{ $voucher->approval_date ? \Carbon\Carbon::parse($voucher->approval_date)->format('d/m/Y H:i') : '-' }}</span>
                        <span class="block text-muted-foreground">Permohonan: {{ strtoupper($perm?->status ?? '-') }}</span>
                        @if ($voucher->approval_notes || $perm?->authorizer_notes)
                            <span class="block text-muted-foreground">{{ $voucher->approval_notes ?? $perm?->authorizer_notes }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-muted-foreground">Tidak ada voucher terbit pada tanggal ini.</td></tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="flex justify-between border-t px-4 py-3 text-sm text-muted-foreground">
        <span><span id="voucherCount">{{ $approvedVouchers->count() }}</span> voucher</span>
        <span><span id="voucherSelectedCount">0</span> dipilih</span>
    </div>
    </x-ui.card>
</div>
