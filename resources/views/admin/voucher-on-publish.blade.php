{{-- Tab voucher terbit per tanggal + tracking approval. Filter tanggal/status memuat ulang lewat AJAX. --}}
@php
    $voucherDate ??= now()->subDay()->toDateString();
@endphp

<div class="grid gap-4" id="voucherPanel" data-list-url="{{ route('admin.vouchers.list') }}">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h3 class="font-medium">Voucher terbit dan tracking approval</h3>
            <p class="text-sm text-muted-foreground">Bawaan menampilkan voucher kemarin. Data berubah otomatis saat filter diganti.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.input type="date" id="voucherDate" :value="$voucherDate" inline aria-label="Tanggal" />
            <x-ui.select id="voucherStatus" inline aria-label="Status">
                <option value="all">Semua status</option>
                <option value="active">Aktif</option>
                <option value="expired">Kedaluwarsa</option>
            </x-ui.select>
            <x-ui.button variant="outline" data-voucher-export="selected"><i class="bi bi-file-earmark-spreadsheet"></i> Export terpilih</x-ui.button>
            <x-ui.button variant="success" data-voucher-export="all"><i class="bi bi-file-earmark-spreadsheet-fill"></i> Export semua</x-ui.button>
        </div>
    </div>

    <form id="voucherExportForm" method="POST" action="{{ route('admin.vouchers.export') }}" hidden>
        @csrf
        <input type="hidden" name="date" id="exportDate">
        <input type="hidden" name="status" id="exportStatus">
        <div id="exportIdsContainer"></div>
    </form>

    <x-ui.table id="voucherTable">
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

    <div class="flex justify-between text-sm text-muted-foreground">
        <span><span id="voucherCount">{{ $approvedVouchers->count() }}</span> voucher</span>
        <span><span id="voucherSelectedCount">0</span> dipilih</span>
    </div>
</div>
