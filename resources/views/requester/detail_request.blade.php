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
    {{-- Hero: nomor, status, nominal, dan aksi di atas gradien brand. --}}
    <section class="bg-brand-gradient ornament-corner relative overflow-hidden rounded-xl px-6 py-6 text-white shadow-lg shadow-primary/20 md:px-8" style="--ornament: #fff; --ornament-dot: #fff">
        <i class="bi bi-fuel-pump pointer-events-none absolute -bottom-8 right-4 text-[9rem] leading-none text-white/[0.07]" aria-hidden="true"></i>
        <div class="relative flex flex-wrap items-end justify-between gap-6">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-orange-200">Detail Permohonan</p>
                    <x-bbm.status-badge :status="$status" class="ring-1 ring-white/30" />
                </div>
                <h1 class="mt-1 truncate font-mono text-xl font-bold tracking-tight md:text-2xl">{{ $request->request_number }}</h1>
                <p class="mt-4 text-xs text-white/70">Nominal permohonan</p>
                <p class="text-3xl font-bold tabular-nums tracking-tight md:text-4xl">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</p>
                <p class="mt-1 inline-flex items-center gap-1.5 text-sm text-white/85"><i class="bi bi-fuel-pump text-orange-200"></i> {{ $request->gasoline_type }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('requester-history') }}" class="inline-flex h-9 items-center gap-2 rounded-md bg-white/10 px-4 text-sm font-medium ring-1 ring-white/25 transition hover:bg-white/20 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-white/50">
                    <i class="bi bi-arrow-left"></i> Riwayat
                </a>
                @if ($status === 'approved')
                    <button type="button" data-dialog-open="voucher-{{ $request->id }}" class="inline-flex h-9 cursor-pointer items-center gap-2 rounded-md bg-white/10 px-4 text-sm font-medium ring-1 ring-white/25 transition hover:bg-white/20 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-white/50">
                        <i class="bi bi-printer"></i> Cetak Voucher
                    </button>
                    @can('permission', 'pelaporan.create')
                        <a href="{{ route('pelaporan.index', ['mode' => 'operational', 'permohonan_id' => $request->id]) }}" class="inline-flex h-9 items-center gap-2 rounded-md bg-white px-4 text-sm font-medium text-primary shadow-sm transition hover:bg-white/90 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-white/50">
                            <i class="bi bi-send"></i> Lapor Pembelian
                        </a>
                    @endcan
                @endif
            </div>
        </div>
    </section>

    {{-- Info cepat: kendaraan, voucher, masa berlaku. --}}
    @php
        $expiredUnreported = $validUntil?->isPast() && ! $proof;
        $tiles = [
            ['Kendaraan / Alat', $request->vehicle->consumerial_code ?? 'N/A', ($request->vehicle->consumerial_name ?? '-') . ' (' . ($request->vehicle->consumerial_type ?? '-') . ')', 'bi-car-front', 'bg-blue-50 text-blue-700', false],
            ['Kode voucher', $voucher->voucher_code ?? '-', $voucher && $voucher->approved_amount && (float) $voucher->approved_amount !== (float) $request->bill_amounts ? 'Disetujui Rp ' . number_format($voucher->approved_amount, 0, ',', '.') : ($voucher ? 'Voucher terbit' : 'Belum terbit'), 'bi-ticket-perforated', 'bg-emerald-50 text-emerald-700', true],
            ['Berlaku sampai', $fmt($validUntil) ?? '-', $expiredUnreported ? 'Kedaluwarsa sebelum dilaporkan' : ($proof ? 'Sudah dilaporkan' : ($validUntil ? 'Batas 50 jam' : '-')), 'bi-hourglass-split', $expiredUnreported ? 'bg-destructive-soft text-destructive' : 'bg-amber-50 text-amber-700', false],
        ];
    @endphp
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ($tiles as [$label, $value, $sub, $icon, $tint, $mono])
            <div class="flex items-center gap-4 rounded-xl border bg-card p-4 shadow-sm">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl text-lg {{ $tint }}"><i class="bi {{ $icon }}"></i></span>
                <div class="min-w-0">
                    <p class="text-xs text-muted-foreground">{{ $label }}</p>
                    <p @class(['truncate font-semibold', 'select-all font-mono tracking-wider' => $mono])>{{ $value }}</p>
                    <p class="truncate text-xs text-muted-foreground">{{ $sub }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <x-ui.card class="ornament-corner lg:col-span-2" style="--ornament: #15803d">
            <div class="flex items-start gap-3 px-6">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/25"><i class="bi bi-receipt"></i></span>
                <div>
                    <h2 class="font-semibold">Laporan Pembelian</h2>
                    <p class="text-sm text-muted-foreground">{{ $proof ? 'Dikirim ' . $fmt($proof->created_at) : 'Bukti pembelian BBM dan odometer' }}</p>
                </div>
            </div>
            <x-ui.card-content class="grid gap-5">
                @if ($proof)
                    <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                        @foreach ([
                            ['Tanggal beli', $fmt($proof->purchase_datetime), false],
                            ['Volume', number_format((float) $proof->fuel_volume, 2, ',', '.') . ' liter', false],
                            ['KM akhir', $proof->km_terakhir ? number_format((float) $proof->km_terakhir, 0, ',', '.') . ' km' : '-', false],
                            ['ID transaksi', $proof->transaction_id, true],
                        ] as [$label, $value, $mono])
                            <div class="min-w-0 rounded-lg bg-muted/50 p-3">
                                <dt class="text-xs text-muted-foreground">{{ $label }}</dt>
                                <dd @class(['mt-1 truncate font-semibold tabular-nums', 'font-mono text-xs' => $mono]) title="{{ $value }}">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="grid gap-4 sm:grid-cols-2">
                        {{-- Foto ada di disk privat; URL dari accessor model (rute media yang memeriksa hak akses). --}}
                        @foreach (['struk_url' => 'Foto struk BBM', 'odometer_url' => 'Foto odometer'] as $field => $label)
                            @if ($proof->$field)
                                <figure class="grid gap-2">
                                    <figcaption class="text-xs font-medium text-muted-foreground">{{ $label }}</figcaption>
                                    <a href="{{ $proof->$field }}" target="_blank" rel="noopener" class="group relative block overflow-hidden rounded-xl border bg-muted">
                                        <img src="{{ $proof->$field }}" alt="{{ $label }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition group-hover:scale-[1.02] motion-reduce:transition-none">
                                        <span class="absolute inset-x-0 bottom-0 flex justify-end bg-linear-to-t from-black/60 to-transparent p-3 text-xs text-white opacity-0 transition group-hover:opacity-100">
                                            <i class="bi bi-arrows-fullscreen"></i>&nbsp;Buka
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

        <div class="grid gap-6">
            <x-ui.card class="ornament-corner" style="--ornament: #4154f1">
                <div class="flex items-center gap-3 px-6">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-indigo-500 to-primary text-white shadow-md shadow-indigo-500/25"><i class="bi bi-signpost-split"></i></span>
                    <h2 class="font-semibold">Alur Permohonan</h2>
                </div>
                <x-ui.card-content>
                    <ol class="grid">
                        @foreach ($steps as $step)
                            <li class="relative flex gap-3 pb-5 last:pb-0">
                                @unless ($loop->last)
                                    <span @class(['absolute left-[13px] top-8 h-[calc(100%-2rem)] w-0.5 rounded-full', 'bg-emerald-300' => $step[2] === 'done', 'bg-border' => $step[2] !== 'done'])></span>
                                @endunless
                                <span @class([
                                    'relative flex size-7 shrink-0 items-center justify-center rounded-full text-xs',
                                    'bg-linear-to-br from-emerald-500 to-teal-600 text-white shadow-sm shadow-emerald-500/30' => $step[2] === 'done',
                                    'bg-linear-to-br from-primary to-indigo-500 text-white ring-4 ring-primary/15' => $step[2] === 'current',
                                    'bg-linear-to-br from-red-500 to-destructive text-white shadow-sm shadow-red-500/30' => $step[2] === 'failed',
                                    'border-2 border-dashed bg-card text-muted-foreground' => $step[2] === 'upcoming',
                                ])>
                                    <i @class([
                                        'bi',
                                        'bi-check-lg' => $step[2] === 'done',
                                        'bi-three-dots' => $step[2] === 'current',
                                        'bi-x-lg' => $step[2] === 'failed',
                                        'bi-flag' => $step[2] === 'upcoming',
                                    ])></i>
                                </span>
                                <div class="min-w-0 pt-1">
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

            <x-ui.card class="ornament-corner gap-4" style="--ornament: var(--module-bbm)">
                <div class="flex items-center gap-3 px-6">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-orange-500 to-amber-500 text-white shadow-md shadow-orange-500/25"><i class="bi bi-chat-left-text"></i></span>
                    <h2 class="font-semibold">Catatan</h2>
                </div>
                <x-ui.card-content>
                    <dl class="grid gap-3 text-sm">
                        <div class="rounded-lg bg-muted/50 p-3">
                            <dt class="text-xs text-muted-foreground"><i class="bi bi-person"></i> Catatan pemohon</dt>
                            <dd class="mt-1 whitespace-pre-line">{{ $requesterNotes ?? '-' }}</dd>
                        </div>
                        <div class="rounded-lg bg-blue-50/70 p-3">
                            <dt class="text-xs text-blue-800"><i class="bi bi-person-check"></i> Catatan admin{{ $authorizer ? ' (' . $authorizer . ')' : '' }}</dt>
                            <dd class="mt-1 whitespace-pre-line">{{ $request->authorizer_notes ?: ($voucher?->approval_notes ?: '-') }}</dd>
                        </div>
                        @if ($cancelReason)
                            <div class="rounded-lg bg-destructive-soft p-3">
                                <dt class="text-xs font-medium text-destructive"><i class="bi bi-x-circle"></i> Alasan pembatalan</dt>
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
