{{--
  Permohonan menunggu persetujuan. Setujui/Tolak membuka dialog berisi ringkasan + komentar admin;
  centang Pilih (atau Pilih semua) untuk keputusan massal (resources/js/lib/decisions.js).
--}}
@php
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $initials = fn ($name) => collect(explode(' ', trim($name ?? '')))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('') ?: '?';
@endphp

<form method="POST" action="{{ route('admin-bulk-process') }}" id="bulkApprovalForm" class="grid gap-4">
    @csrf
    <input type="hidden" name="decisions" id="decisionsInput">

    @if ($pendingRequests->isEmpty())
        <x-ui.empty-state icon="bi bi-check2-all" title="Tidak ada permohonan menunggu" description="Semua permohonan sudah diproses." class="bg-card" />
    @else
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-card px-4 py-3 shadow-xs">
            <label class="inline-flex cursor-pointer items-center gap-2.5 text-sm font-medium">
                <x-ui.checkbox data-select-all class="size-[18px]" aria-label="Pilih semua permohonan" /> Pilih semua
            </label>
            <span class="flex items-center gap-2 text-sm text-muted-foreground">
                <i class="bi bi-info-circle"></i> Centang Pilih pada kartu untuk memutuskan beberapa permohonan sekaligus. Terlama di atas.
            </span>
        </div>

        {{-- Satu kolom: tiap permohonan satu kartu memanjang (lg ke atas isinya berderet). --}}
        <div class="grid gap-3">
            @foreach ($pendingRequests->sortBy('request_date') as $req)
                @php
                    $vehicleCode = $req->vehicle->consumerial_code ?? '-';
                    $vehicleName = $req->vehicle->consumerial_name ?? '';
                    $summary = ['number' => $req->request_number, 'name' => $req->requester->nama_lengkap, 'vehicle' => trim("$vehicleCode · $vehicleName", ' ·'), 'amount' => $rp($req->bill_amounts)];
                    $waitingDays = $req->request_date->diffInDays(now());
                @endphp
                <article data-decision-item="{{ $req->id }}" data-summary="{{ json_encode($summary) }}"
                         class="flex flex-col gap-4 rounded-xl border bg-card p-4 shadow-xs transition-shadow duration-200 hover:shadow-md lg:flex-row lg:items-center lg:gap-6 has-[[data-select]:checked]:border-primary has-[[data-select]:checked]:ring-2 has-[[data-select]:checked]:ring-primary/30">
                    {{-- Pemohon, status, waktu tunggu --}}
                    <div class="flex min-w-0 items-center gap-3 lg:w-64 lg:shrink-0">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-primary to-module-spd text-sm font-semibold text-white shadow-sm">
                            {{ $initials($req->requester->nama_lengkap) }}
                        </span>
                        <div class="grid min-w-0 flex-1 gap-1">
                            <p class="truncate text-base font-semibold leading-tight">{{ $req->requester->nama_lengkap }}</p>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                                <span class="inline-flex items-center gap-1 rounded-full bg-warning-soft px-2 py-0.5 font-semibold text-warning">
                                    <span class="relative flex size-1.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-warning opacity-60 motion-reduce:animate-none"></span><span class="relative inline-flex size-1.5 rounded-full bg-warning"></span></span>
                                    Menunggu
                                </span>
                                <span @class(['inline-flex items-center gap-1', 'font-semibold text-destructive' => $waitingDays >= 2, 'text-muted-foreground' => $waitingDays < 2])
                                      title="Diajukan {{ $req->request_date->format('d/m/Y H:i') }}">
                                    <i class="bi bi-clock-history"></i> {{ $req->request_date->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Rincian: kendaraan, BBM, tanggal, nomor, catatan --}}
                    <div class="grid min-w-0 flex-1 gap-1.5 text-sm">
                        <p class="flex min-w-0 items-center gap-2">
                            <i class="bi bi-truck text-muted-foreground"></i>
                            <span class="truncate"><span class="font-semibold">{{ $vehicleCode }}</span> <span class="text-muted-foreground">{{ $vehicleName }}</span></span>
                        </p>
                        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-muted-foreground">
                            <span class="inline-flex items-center gap-1.5"><i class="bi bi-fuel-pump"></i> <span class="text-foreground">{{ $req->gasoline_type }}</span></span>
                            <span class="size-1 rounded-full bg-border"></span>
                            <span class="inline-flex items-center gap-1.5"><i class="bi bi-calendar3"></i> {{ $req->request_date->translatedFormat('d M Y, H:i') }}</span>
                            <span class="size-1 rounded-full bg-border"></span>
                            <span class="font-mono text-xs">{{ $req->request_number }}</span>
                        </p>
                        @if ($req->requester_notes)
                            <p class="flex min-w-0 items-start gap-2 text-foreground/80">
                                <i class="bi bi-chat-left-quote text-primary/50"></i>
                                <span class="line-clamp-2">{{ $req->requester_notes }}</span>
                            </p>
                        @endif
                    </div>

                    {{-- Nominal --}}
                    <div class="flex items-baseline justify-between gap-2 rounded-lg bg-primary/5 px-4 py-2.5 ring-1 ring-primary/10 lg:grid lg:min-w-40 lg:shrink-0 lg:justify-items-end lg:gap-1">
                        <p class="text-[10px] font-semibold uppercase leading-none tracking-wider text-muted-foreground">Nominal</p>
                        <p class="whitespace-nowrap text-xl leading-none font-bold tracking-tight tabular-nums text-primary"><span class="mr-0.5 text-sm font-semibold text-primary/70">Rp</span>{{ number_format((float) $req->bill_amounts, 0, ",", ".") }}</p>
                    </div>

                    {{-- Pilih (keputusan massal) + aksi; keputusan dikonfirmasi lewat dialog --}}
                    <div class="flex items-center justify-between gap-2 border-t pt-3 lg:shrink-0 lg:border-t-0 lg:pt-0">
                        <label class="flex cursor-pointer items-center gap-2 rounded-md border px-2.5 py-1.5 text-xs font-medium text-muted-foreground shadow-xs transition-colors hover:border-primary/50 hover:text-foreground has-checked:border-primary has-checked:bg-primary/10 has-checked:text-primary">
                            <x-ui.checkbox data-select aria-label="Pilih permohonan {{ $req->request_number }}" />
                            Pilih
                        </label>
                        <div class="flex gap-2">
                            <x-ui.button variant="outline" size="sm" data-decide="reject" class="text-destructive hover:bg-destructive-soft hover:text-destructive">
                                <i class="bi bi-x-circle"></i> Tolak
                            </x-ui.button>
                            <x-ui.button variant="success" size="sm" data-decide="approve">
                                <i class="bi bi-check-circle"></i> Setujui
                            </x-ui.button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- Bar keputusan massal, muncul setelah ada kartu yang dipilih --}}
    <div id="bulkActionBar" hidden
         class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-primary/30 bg-card/95 p-3 pl-4 shadow-xl backdrop-blur">
        <p class="flex items-center gap-2 text-sm">
            <span class="flex h-7 min-w-7 items-center justify-center rounded-full bg-primary px-2 text-xs font-semibold text-primary-foreground" id="selectedCount">0</span>
            <span class="font-medium">permohonan dipilih</span>
            <button type="button" data-clear-selection class="text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">Batal pilih</button>
        </p>
        <div class="flex gap-2">
            <x-ui.button variant="outline" data-decide-selected="reject" class="text-destructive hover:bg-destructive-soft hover:text-destructive"><i class="bi bi-x-circle"></i> Tolak terpilih</x-ui.button>
            <x-ui.button variant="success" data-decide-selected="approve"><i class="bi bi-check-circle"></i> Setujui terpilih</x-ui.button>
        </div>
    </div>
</form>

<x-bbm.decision-dialog />
