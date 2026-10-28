{{-- Riwayat approval yang diproses user yang sedang login. --}}
<x-ui.table id="myApprovalTable" :datatable="['columns' => [['select' => 3, 'sort' => 'desc']]]">
    <thead>
        <tr>
            <th>Nomor</th>
            <th>Pemohon</th>
            <th>Diajukan</th>
            <th>Diproses</th>
            <th>BBM</th>
            <th class="text-right">Nominal</th>
            <th>Status</th>
            <th>Voucher</th>
            <th>Catatan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($myApprovals as $req)
            <tr>
                <td><code class="text-xs">{{ $req->request_number }}</code></td>
                <td class="font-medium">{{ $req->requester->nama_lengkap }}</td>
                <td data-order="{{ $req->request_date->timestamp }}" class="whitespace-nowrap">{{ $req->request_date->format('d/m/Y H:i') }}</td>
                <td data-order="{{ $req->authorization_date?->timestamp ?? 0 }}" class="whitespace-nowrap">{{ $req->authorization_date?->format('d/m/Y H:i') ?? '-' }}</td>
                <td><x-ui.badge variant="secondary">{{ $req->gasoline_type }}</x-ui.badge></td>
                <td data-order="{{ $req->bill_amounts }}" class="whitespace-nowrap text-right tabular-nums">Rp {{ number_format($req->bill_amounts, 0, ',', '.') }}</td>
                <td><x-bbm.status-badge :status="$req->status" /></td>
                <td>@if ($req->approvedRequest)<code class="text-xs">{{ $req->approvedRequest->voucher_code }}</code>@else - @endif</td>
                <td class="text-muted-foreground">{{ $req->authorizer_notes ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="py-10 text-center text-muted-foreground">Belum ada approval yang Anda proses.</td></tr>
        @endforelse
    </tbody>
</x-ui.table>
