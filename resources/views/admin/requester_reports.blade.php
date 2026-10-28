{{-- Tab "Semua History & Laporan": semua permohonan, laporan transaksi, prognosa voucher. --}}
<x-ui.tabs default="all-requests">
    <x-ui.tabs-list>
        <x-ui.tabs-trigger value="all-requests"><i class="bi bi-list-check"></i> Semua Permohonan</x-ui.tabs-trigger>
        <x-ui.tabs-trigger value="reports"><i class="bi bi-file-earmark-check"></i> Laporan Transaksi</x-ui.tabs-trigger>
        <x-ui.tabs-trigger value="prognosis"><i class="bi bi-journal-check"></i> Prognosa Transaksi</x-ui.tabs-trigger>
    </x-ui.tabs-list>

    <x-ui.tabs-content value="all-requests">
        <x-ui.table id="allRequestsTable" :datatable="['columns' => [['select' => 2, 'sort' => 'desc']]]">
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Pemohon</th>
                    <th>Tanggal</th>
                    <th>Kendaraan</th>
                    <th>BBM</th>
                    <th class="text-right">Nominal</th>
                    <th>Status</th>
                    <th>Voucher</th>
                    <th>Admin</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($allRequests as $req)
                    <tr>
                        <td><code class="text-xs">{{ $req->request_number }}</code></td>
                        <td class="font-medium">{{ $req->requester->nama_lengkap ?? 'Unknown User' }}</td>
                        <td data-order="{{ $req->request_date->timestamp }}" class="whitespace-nowrap">{{ $req->request_date->format('d/m/Y H:i') }}</td>
                        <td>
                            @if ($req->vehicle)
                                <span class="font-medium text-primary">{{ $req->vehicle->consumerial_code }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $req->vehicle->consumerial_name }} ({{ $req->vehicle->consumerial_type }})</span>
                            @else
                                <x-ui.badge variant="muted">Kendaraan dihapus</x-ui.badge>
                            @endif
                        </td>
                        <td><x-ui.badge variant="secondary">{{ $req->gasoline_type }}</x-ui.badge></td>
                        <td data-order="{{ $req->bill_amounts }}" class="whitespace-nowrap text-right tabular-nums">Rp {{ number_format($req->bill_amounts, 0, ',', '.') }}</td>
                        <td><x-bbm.status-badge :status="$req->status" /></td>
                        <td>@if ($req->approvedRequest)<code class="text-xs">{{ $req->approvedRequest->voucher_code }}</code>@else - @endif</td>
                        <td class="text-muted-foreground">{{ $req->authorizer->nama_lengkap ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-10 text-center text-muted-foreground">Belum ada permohonan.</td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.tabs-content>

    <x-ui.tabs-content value="reports">
        <x-ui.table id="reportsTable" :datatable="['columns' => [['select' => 9, 'sort' => 'desc'], ['select' => 8, 'sortable' => false]]]">
            <thead>
                <tr>
                    <th>ID Transaksi</th>
                    <th>Nomor</th>
                    <th>Pemohon</th>
                    <th>Kendaraan</th>
                    <th>Voucher</th>
                    <th>Tanggal isi</th>
                    <th class="text-right">Volume</th>
                    <th class="text-right">KM akhir</th>
                    <th>Foto</th>
                    <th>Dilaporkan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td><code class="text-xs">{{ $report->transaction_id }}</code></td>
                        <td><code class="text-xs">{{ $report->permohonan->request_number }}</code></td>
                        <td class="font-medium">{{ $report->permohonan->requester->nama_lengkap }}</td>
                        <td>
                            @if ($report->permohonan?->vehicle)
                                <span class="font-medium">{{ $report->permohonan->vehicle->consumerial_code }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $report->permohonan->vehicle->consumerial_name }}</span>
                            @else
                                <x-ui.badge variant="muted">Kendaraan dihapus</x-ui.badge>
                            @endif
                        </td>
                        <td><code class="text-xs">{{ $report->voucher_number }}</code></td>
                        <td data-order="{{ $report->purchase_datetime ? strtotime($report->purchase_datetime) : 0 }}" class="whitespace-nowrap">
                            {{ $report->purchase_datetime ? date('d/m/Y H:i', strtotime($report->purchase_datetime)) : '-' }}
                        </td>
                        <td class="text-right tabular-nums">{{ $report->fuel_volume }} L</td>
                        <td class="text-right tabular-nums">{{ number_format($report->km_terakhir, 0, ',', '.') }}</td>
                        <td>
                            <div class="flex gap-1">
                                @if ($report->odometer_photo_path)
                                    <x-ui.button variant="outline" size="icon-sm" title="Foto odometer"
                                                 data-photo-url="{{ route('transaction.getPhoto', [$report->id, 'odometer']) }}" data-photo-title="Foto Odometer">
                                        <i class="bi bi-speedometer2"></i>
                                    </x-ui.button>
                                @endif
                                @if ($report->struk_bbm_path)
                                    <x-ui.button variant="outline" size="icon-sm" title="Foto struk"
                                                 data-photo-url="{{ route('transaction.getPhoto', [$report->id, 'struk']) }}" data-photo-title="Foto Struk BBM">
                                        <i class="bi bi-receipt"></i>
                                    </x-ui.button>
                                @endif
                            </div>
                        </td>
                        <td data-order="{{ $report->created_at->timestamp }}" class="whitespace-nowrap">{{ $report->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="py-10 text-center text-muted-foreground">Belum ada laporan transaksi.</td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.tabs-content>

    <x-ui.tabs-content value="prognosis" class="grid gap-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h3 class="font-medium">Daftar voucher yang sudah terbit</h3>
                <p class="text-sm text-muted-foreground">Voucher aktif dan kedaluwarsa untuk dicocokkan dengan transaksi SPBU.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-md border bg-card p-0.5 shadow-xs" role="group" aria-label="Filter status">
                    @foreach (['all' => 'Semua', 'approved' => 'Aktif', 'cancelled' => 'Kedaluwarsa'] as $value => $label)
                        <button type="button" data-prognosis-filter="{{ $value }}" aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}"
                                class="rounded-sm px-3 py-1 text-sm text-muted-foreground aria-pressed:bg-accent aria-pressed:font-medium aria-pressed:text-accent-foreground">{{ $label }}</button>
                    @endforeach
                </div>
                <x-ui.button variant="outline" size="sm" id="exportPrognosis"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</x-ui.button>
            </div>
        </div>

        <x-ui.table id="prognosisTable">
            <thead>
                <tr>
                    <th>Pemohon</th>
                    <th>Kendaraan / Alat</th>
                    <th class="text-right">Nominal</th>
                    <th>Kode voucher</th>
                    <th>Status</th>
                    <th class="text-center">Checklist</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($approvedVouchers as $voucher)
                    @php
                        $perm = $voucher->permohonan;
                        $isExpired = \Carbon\Carbon::parse($voucher->valid_until)->isPast();
                        $csvRow = [
                            $perm?->requester?->nama_lengkap ?? 'Unknown',
                            $perm?->vehicle?->consumerial_name ?? '-',
                            $perm?->vehicle?->consumerial_code ?? '-',
                            $perm?->vehicle?->consumerial_type ?? '-',
                            (string) (int) $voucher->approved_amount,
                            $voucher->voucher_code,
                            $isExpired ? 'EXPIRED' : 'AKTIF',
                            \Carbon\Carbon::parse($voucher->valid_until)->format('d/m/Y H:i'),
                        ];
                    @endphp
                    <tr data-status="{{ $isExpired ? 'cancelled' : 'approved' }}" data-csv="{{ json_encode($csvRow) }}">
                        <td class="font-medium">{{ $perm?->requester?->nama_lengkap ?? 'Unknown' }}</td>
                        <td>
                            @if ($perm?->vehicle)
                                <span class="font-medium text-primary">{{ $perm->vehicle->consumerial_code }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $perm->vehicle->consumerial_name }} ({{ $perm->vehicle->consumerial_type }})</span>
                            @else
                                <x-ui.badge variant="muted">Kendaraan dihapus</x-ui.badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-right tabular-nums">Rp {{ number_format($voucher->approved_amount, 0, ',', '.') }}</td>
                        <td>
                            <code class="font-semibold tracking-wide">{{ $voucher->voucher_code }}</code>
                            <span class="block text-xs text-muted-foreground">Berlaku sampai {{ \Carbon\Carbon::parse($voucher->valid_until)->format('d/m/Y H:i') }}</span>
                        </td>
                        <td><x-bbm.status-badge :status="$isExpired ? 'expired' : 'approved'" /></td>
                        <td><span class="mx-auto block size-6 rounded border-2 border-muted-foreground/60"></span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-muted-foreground">Belum ada voucher yang terbit.</td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.tabs-content>
</x-ui.tabs>

<x-ui.dialog id="photoDialog" title="Foto" size="lg">
    <div id="photoDialogBody" class="flex min-h-48 items-center justify-center"></div>
</x-ui.dialog>
