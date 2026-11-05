{{-- Riwayat approval yang diproses user yang sedang login: tabel (cari/urut/halaman: ui/datatable.js). --}}
@php
    $initials = fn ($name) => collect(explode(' ', trim($name ?? '')))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('') ?: '?';
@endphp

{{-- Kartu filter (cari + per halaman, dipindah ui/datatable.js lewat controlsTarget) terpisah dari kartu tabel. --}}
<div class="grid gap-3">
    <div id="myApprovalControls" data-table-controls class="rounded-xl border bg-card p-3 shadow-xs"></div>
    <x-ui.card data-table-card class="gap-0 overflow-hidden py-0">
    <x-ui.table id="myApprovalTable" class="max-sm:[&_td]:whitespace-nowrap" :datatable="['controlsTarget' => '#myApprovalControls', 'columns' => [['select' => 2, 'sort' => 'desc']]]">
        <thead>
            <tr>
                <th>Permohonan</th>
                <th>Pemohon</th>
                <th>Diproses</th>
                <th class="text-right">Nominal</th>
                <th>Status</th>
                <th>Voucher</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($myApprovals as $req)
                <tr>
                    <td data-order="{{ $req->request_date->timestamp }}">
                        <code class="text-xs font-medium">{{ $req->request_number }}</code>
                        <span class="block text-xs text-muted-foreground">Diajukan {{ $req->request_date->format('d/m/Y H:i') }}</span>
                    </td>
                    <td>
                        <span class="flex items-center gap-2.5">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-primary to-module-spd text-[11px] font-semibold text-white">{{ $initials($req->requester->nama_lengkap) }}</span>
                            <span class="font-medium">{{ $req->requester->nama_lengkap }}</span>
                        </span>
                    </td>
                    <td data-order="{{ $req->authorization_date?->timestamp ?? 0 }}" class="whitespace-nowrap">
                        {{ $req->authorization_date?->format('d/m/Y H:i') ?? '-' }}
                        @if ($req->authorization_date)<span class="block text-xs text-muted-foreground">{{ $req->authorization_date->diffForHumans() }}</span>@endif
                    </td>
                    <td data-order="{{ $req->bill_amounts }}" class="whitespace-nowrap text-right">
                        <span class="font-semibold tabular-nums">Rp {{ number_format($req->bill_amounts, 0, ',', '.') }}</span>
                        <span class="block text-xs text-muted-foreground"><i class="bi bi-fuel-pump"></i> {{ $req->gasoline_type }}</span>
                    </td>
                    <td><x-bbm.status-badge :status="$req->status" /></td>
                    <td>
                        @if ($req->approvedRequest)
                            <span class="inline-flex items-center gap-1.5 rounded-md border border-dashed bg-muted/40 px-2 py-1 font-mono text-xs"><i class="bi bi-ticket-perforated text-primary"></i>{{ $req->approvedRequest->voucher_code }}</span>
                        @else
                            <span class="text-muted-foreground">-</span>
                        @endif
                    </td>
                    <td class="text-muted-foreground">
                        @if ($req->authorizer_notes)
                            <span class="line-clamp-2 max-w-60 whitespace-normal" title="{{ $req->authorizer_notes }}">{{ $req->authorizer_notes }}</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-muted-foreground">Belum ada approval yang Anda proses.</td></tr>
            @endforelse
        </tbody>
    </x-ui.table>
    </x-ui.card>
</div>
