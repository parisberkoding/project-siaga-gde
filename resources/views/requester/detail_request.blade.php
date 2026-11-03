@php
    $proof = $request->transactionProof;
    $voucher = $request->approvedRequest;
    $validUntil = $voucher?->valid_until ? \Carbon\Carbon::parse($voucher->valid_until) : null;
    $status = $request->status;
    $isCancelled = in_array($status, ['cancelled', 'cancelled_by_driver'], true);
    $fmt = fn ($date) => $date?->locale('id')->translatedFormat('d M Y, H:i');
    $authorizer = $request->authorizer->nama_lengkap ?? $request->authorizer->username ?? null;

    // Alasan pembatalan disimpan di requester_notes dengan penanda [USER CANCELLATION].
    [$requesterNotes, $cancelReason] = array_pad(explode('[USER CANCELLATION] ', (string) $request->requester_notes, 2), 2, null);
    $requesterNotes = trim($requesterNotes) ?: null;

    // Alur permohonan: tiap langkah [judul, keterangan, state]. state: done | current | failed | upcoming.
    $steps = [['Permohonan diajukan', $fmt($request->request_date), 'done']];

    if (in_array($status, ['pending', 'pending_vehicle_approval'], true)) {
        $steps[] = [$status === 'pending' ? 'Menunggu persetujuan Admin GA' : 'Menunggu persetujuan kendaraan', 'Sedang diproses', 'current'];
    } elseif ($status === 'rejected') {
        $steps[] = ['Ditolak', trim($fmt($request->authorization_date) . ($authorizer ? ' oleh ' . $authorizer : '')), 'failed'];
    } elseif ($voucher || $request->authorization_date) {
        $steps[] = ['Disetujui, voucher terbit', trim($fmt($request->authorization_date) . ($authorizer ? ' oleh ' . $authorizer : '')), 'done'];
    }

    if ($voucher && ! $isCancelled && $status !== 'rejected') {
        if ($proof) {
            $steps[] = ['Laporan pembelian dikirim', $fmt($proof->created_at), 'done'];
        } elseif ($status === 'expired' || ($validUntil && $validUntil->isPast())) {
            $steps[] = ['Voucher kedaluwarsa', 'Berakhir ' . $fmt($validUntil), 'failed'];
        } else {
            $steps[] = ['Menunggu laporan pembelian', 'Voucher berlaku sampai ' . $fmt($validUntil), 'current'];
        }
    }

    if ($isCancelled) {
        $steps[] = ['Dibatalkan', $fmt($request->updated_at), 'failed'];
    } elseif ($status === 'completed') {
        $steps[] = ['Selesai', null, 'done'];
    } elseif (! in_array($status, ['rejected', 'expired'], true)) {
        $steps[] = ['Selesai', null, 'upcoming'];
    }
@endphp

<x-layouts.app title="Detail {{ $request->request_number }}" :breadcrumbs="['Permohonan BBM' => route('requester-index'), 'Riwayat' => route('requester-history'), $request->request_number => null]">
    <x-ui.page-header kicker="Detail Permohonan" :title="$request->request_number" description="Rincian permohonan, persetujuan, dan laporan pembelian.">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('requester-history')">
                <i class="bi bi-arrow-left"></i> Riwayat
            </x-ui.button>
            @if ($status === 'approved')
                <x-ui.button variant="outline" data-dialog-open="voucher-{{ $request->id }}">
                    <i class="bi bi-printer"></i> Cetak Voucher
                </x-ui.button>
                @can('permission', 'pelaporan.create')
                    <x-ui.button :href="route('pelaporan.index', ['mode' => 'operational', 'permohonan_id' => $request->id])">
                        <i class="bi bi-send"></i> Lapor Pembelian
                    </x-ui.button>
                @endcan
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="grid gap-6 lg:col-span-2">
            {{-- Ringkasan: nominal, kendaraan, voucher. --}}
            <x-ui.card class="gap-0 overflow-hidden py-0">
                <div class="flex flex-wrap items-end justify-between gap-4 bg-sidebar-gradient px-6 py-5 text-white">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-white/70">Nominal permohonan</p>
                        <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</p>
                        <p class="mt-1 text-sm text-white/80">{{ $request->gasoline_type }}</p>
                    </div>
                    <x-bbm.status-badge :status="$status" class="text-sm" />
                </div>

                <dl class="grid divide-y text-sm sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    <div class="px-6 py-4">
                        <dt class="text-xs text-muted-foreground">Kendaraan / Alat</dt>
                        <dd class="mt-1 font-medium">{{ $request->vehicle->consumerial_code ?? 'N/A' }}</dd>
                        <dd class="text-xs text-muted-foreground">{{ $request->vehicle->consumerial_name ?? '-' }} ({{ $request->vehicle->consumerial_type ?? '-' }})</dd>
                    </div>
                    <div class="px-6 py-4">
                        <dt class="text-xs text-muted-foreground">Kode voucher</dt>
                        <dd class="mt-1 select-all font-mono font-semibold tracking-wider">{{ $voucher->voucher_code ?? '-' }}</dd>
                        @if ($voucher && $voucher->approved_amount && (float) $voucher->approved_amount !== (float) $request->bill_amounts)
                            <dd class="text-xs text-muted-foreground">Disetujui Rp {{ number_format($voucher->approved_amount, 0, ',', '.') }}</dd>
                        @endif
                    </div>
                    <div class="px-6 py-4">
                        <dt class="text-xs text-muted-foreground">Berlaku sampai</dt>
                        <dd @class(['mt-1 font-medium', 'text-destructive' => $validUntil?->isPast() && ! $proof])>{{ $fmt($validUntil) ?? '-' }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Laporan Pembelian" icon="bi bi-receipt"
                                  :description="$proof ? 'Dikirim ' . $fmt($proof->created_at) : null" />
                <x-ui.card-content class="grid gap-5">
                    @if ($proof)
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm sm:grid-cols-4">
                            <div><dt class="text-xs text-muted-foreground">Tanggal beli</dt><dd class="mt-1 font-medium">{{ $fmt($proof->purchase_datetime) }}</dd></div>
                            <div><dt class="text-xs text-muted-foreground">Volume</dt><dd class="mt-1 font-medium tabular-nums">{{ number_format((float) $proof->fuel_volume, 2, ',', '.') }} liter</dd></div>
                            <div><dt class="text-xs text-muted-foreground">KM akhir</dt><dd class="mt-1 font-medium tabular-nums">{{ $proof->km_terakhir ? number_format((float) $proof->km_terakhir, 0, ',', '.') . ' km' : '-' }}</dd></div>
                            <div class="min-w-0"><dt class="text-xs text-muted-foreground">ID transaksi</dt><dd class="mt-1 truncate font-mono text-xs" title="{{ $proof->transaction_id }}">{{ $proof->transaction_id }}</dd></div>
                        </dl>

                        <div class="grid gap-4 sm:grid-cols-2">
                            {{-- Foto ada di disk privat; URL dari accessor model (rute media yang memeriksa hak akses). --}}
                            @foreach (['struk_url' => 'Foto struk BBM', 'odometer_url' => 'Foto odometer'] as $field => $label)
                                @if ($proof->$field)
                                    <figure class="grid gap-2">
                                        <figcaption class="text-xs text-muted-foreground">{{ $label }}</figcaption>
                                        <a href="{{ $proof->$field }}" target="_blank" rel="noopener" class="group relative block overflow-hidden rounded-lg border bg-muted">
                                            <img src="{{ $proof->$field }}" alt="{{ $label }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition group-hover:opacity-90">
                                            <span class="absolute bottom-2 right-2 rounded-md bg-black/60 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
                                                <i class="bi bi-arrows-fullscreen"></i> Buka
                                            </span>
                                        </a>
                                    </figure>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state icon="bi bi-receipt" title="Belum ada laporan"
                                          :description="$status === 'approved' ? 'Laporkan pembelian sebelum voucher habis masa berlakunya.' : 'Tidak ada laporan pembelian untuk permohonan ini.'" />
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <div class="grid gap-6">
            <x-ui.card>
                <x-ui.card-header title="Alur Permohonan" icon="bi bi-signpost-split" />
                <x-ui.card-content>
                    <ol class="grid">
                        @foreach ($steps as $step)
                            <li class="relative flex gap-3 pb-5 last:pb-0">
                                @unless ($loop->last)
                                    <span @class(['absolute left-[11px] top-7 h-[calc(100%-1.75rem)] w-px', 'bg-success/50' => $step[2] === 'done', 'bg-border' => $step[2] !== 'done'])></span>
                                @endunless
                                <span @class([
                                    'relative flex size-6 shrink-0 items-center justify-center rounded-full text-xs',
                                    'bg-success text-white' => $step[2] === 'done',
                                    'bg-primary text-primary-foreground ring-4 ring-primary/15' => $step[2] === 'current',
                                    'bg-destructive text-white' => $step[2] === 'failed',
                                    'border border-dashed bg-card text-muted-foreground' => $step[2] === 'upcoming',
                                ])>
                                    <i @class([
                                        'bi',
                                        'bi-check-lg' => $step[2] === 'done',
                                        'bi-three-dots' => $step[2] === 'current',
                                        'bi-x-lg' => $step[2] === 'failed',
                                        'bi-flag' => $step[2] === 'upcoming',
                                    ])></i>
                                </span>
                                <div class="min-w-0 pt-0.5">
                                    <p @class(['text-sm font-medium leading-tight', 'text-muted-foreground' => $step[2] === 'upcoming'])>{{ $step[0] }}</p>
                                    @if ($step[1])
                                        <p class="mt-1 text-xs text-muted-foreground">{{ $step[1] }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="gap-4">
                <x-ui.card-header title="Catatan" icon="bi bi-chat-left-text" />
                <x-ui.card-content>
                    <dl class="grid gap-4 text-sm">
                        <div>
                            <dt class="text-xs text-muted-foreground">Catatan pemohon</dt>
                            <dd class="mt-1 whitespace-pre-line">{{ $requesterNotes ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">Catatan admin{{ $authorizer ? ' (' . $authorizer . ')' : '' }}</dt>
                            <dd class="mt-1 whitespace-pre-line">{{ $request->authorizer_notes ?: ($voucher?->approval_notes ?: '-') }}</dd>
                        </div>
                        @if ($cancelReason)
                            <div class="rounded-lg bg-destructive-soft p-3">
                                <dt class="text-xs font-medium text-destructive">Alasan pembatalan</dt>
                                <dd class="mt-1 whitespace-pre-line">{{ trim($cancelReason) }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-ui.card-content>
            </x-ui.card>
        </div>
    </div>

    @if ($status === 'approved')
        @include('requester.partials.voucher-print-modal', ['request' => $request])
        @push('scripts')
            @vite('resources/js/pages/bbm/voucher-print.js')
        @endpush
    @endif
</x-layouts.app>
