{{--
  Lonceng notifikasi di header: daftar hal yang menunggu tindakan pengguna, sesuai permission.
  Hanya MEMBACA data (hitungan) dengan scope model yang sama seperti halaman tujuannya, tanpa
  tabel notifikasi baru. Item modul lain dibuka lewat aktivasi modul (POST modules.activate),
  karena rute modul lain dikunci oleh EnsureModuleContext.
--}}
@php
    $Permohonan = \App\Models\Permohonan::class;
    $SuratSpd = \App\Models\SuratSpd::class;
    $Extension = \App\Models\SuratSpdExtension::class;
    $Lpj = \App\Models\LpjPenggantian::class;

    $user = Auth::user();
    $unitId = \App\Services\UnitScope::for($user)->unitId();
    $activeCode = app(\App\Services\ModuleContext::class)->current()?->code;
    $can = fn ($perm) => $user->hasPermission($perm);

    // [modul, permission, judul, keterangan, ikon, nada, rute, hitungan (closure, dihitung bila berhak)]
    $definitions = [
        ['bbm', 'bbm.approve', 'Permohonan BBM menunggu approval', 'Setujui atau tolak permohonan BBM', 'bi-fuel-pump', 'warning', 'admin-dashboard',
            fn () => $Permohonan::inUnit($unitId)->where('status', 'pending')->count()],
        ['spd', 'spd.manage', 'Surat tugas menunggu validasi', 'Tentukan dana lalu setujui atau tolak', 'bi-envelope-paper', 'warning', 'surat.superadmin.index',
            fn () => $SuratSpd::pending()->inUnit($unitId)->count()],
        ['spd', 'spd.update', 'Perpanjangan perlu approval', 'Pengajuan perpanjangan SPD', 'bi-clock-history', 'warning', 'perpanjangan.superadmin.index',
            fn () => $Extension::pending()->inUnit($unitId)->count()],
        ['spd', 'spd.update', 'Perpanjangan perlu checklist', 'Sudah disetujui, surat perlu dicetak', 'bi-check2-square', 'info', 'perpanjangan.superadmin.index',
            fn () => $Extension::needAcknowledge()->inUnit($unitId)->count()],
        // Tiga tahap LPJ dihitung terpisah: satu LPJ hanya muncul di tahap yang sedang menunggu orang itu,
        // jadi tidak terhitung dua kali di lonceng.
        ['spd', 'spd.validate_trip', 'Perjalanan perlu konfirmasi Anda', 'Tahap 1: pastikan rincian perjalanan benar', 'bi-person-check', 'info', 'lpj.pengguna.index',
            fn () => $Lpj::pending()->whereNull('approved_by_pengguna')
                ->whereHas('suratSpd', fn ($q) => $q->where(fn ($s) => $s->where('user_id', $user->id)->orWhereHas('details', fn ($d) => $d->where('user_id', $user->id))))
                ->count()],
        ['spd', 'lpj.validate', 'LPJ perlu diperiksa GA', 'Tahap 2: periksa struk dan biaya', 'bi-receipt', 'warning', 'lpj.superadmin.index',
            fn () => $Lpj::pending()->inUnit($unitId)->whereNull('approved_by_divisi_ga')->count()],
        ['spd', 'lpj.validate_management', 'LPJ menunggu persetujuan akhir', 'Tahap 3: penumpang dan GA sudah selesai', 'bi-patch-check', 'warning', 'lpj.management.index',
            fn () => $Lpj::pending()->inUnit($unitId)->whereNotNull('approved_by_pengguna')->whereNotNull('approved_by_divisi_ga')->whereNull('approved_by_management')->count()],
        ['spd', 'lpj.create', 'SPD selesai, LPJ belum dibuat', 'Laporkan struk dan buat LPJ', 'bi-car-front', 'danger', 'requester.spd.berjalan',
            fn () => $SuratSpd::forUser($user->id)->where('status', 'approved')->whereDoesntHave('lpjPenggantian')->whereDate('tanggal_kembali', '<', today())->count()],
        ['spd', 'lpj.update', 'LPJ ditolak, perlu revisi', 'Perbaiki lalu ajukan ulang', 'bi-arrow-counterclockwise', 'danger', 'requester.lpj.index',
            fn () => $Lpj::rejected()->where('user_id', $user->id)->count()],
    ];

    $items = collect($definitions)
        ->filter(fn ($d) => $can($d[1]))
        ->map(fn ($d) => ['module' => $d[0], 'title' => $d[2], 'description' => $d[3], 'icon' => $d[4], 'tone' => $d[5], 'route' => $d[6], 'count' => ($d[7])()])
        ->filter(fn ($item) => $item['count'] > 0)
        // Item modul aktif di atas.
        ->sortBy(fn ($item) => $item['module'] === $activeCode ? 0 : 1)
        ->values();
    $total = $items->sum('count');
    $moduleNames = ['bbm' => 'BBM & Permohonan', 'spd' => 'Perjalanan Dinas'];
    $tones = [
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-destructive-soft text-destructive',
        'info' => 'bg-info-soft text-info',
    ];
    $row = 'flex w-full items-start gap-3 rounded-md p-2 text-left outline-none transition-colors hover:bg-accent focus-visible:bg-accent';
@endphp

<details data-menu class="relative">
    <summary class="relative flex size-8 cursor-pointer list-none items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground [&::-webkit-details-marker]:hidden"
             aria-label="{{ $total ? "Notifikasi, $total menunggu tindakan" : 'Notifikasi' }}" title="Notifikasi">
        <i class="bi bi-bell text-base"></i>
        @if ($total)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-semibold leading-none text-white ring-2 ring-card">{{ $total > 99 ? '99+' : $total }}</span>
        @endif
    </summary>

    <div class="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-lg border bg-popover text-popover-foreground shadow-lg">
        <div class="flex items-center justify-between border-b px-4 py-3">
            <p class="font-semibold">Notifikasi</p>
            @if ($total)
                <x-ui.badge variant="danger" class="rounded-full">{{ $total }} menunggu</x-ui.badge>
            @endif
        </div>

        @if ($items->isEmpty())
            <div class="grid justify-items-center gap-1 px-4 py-8 text-center">
                <span class="mb-1 flex size-10 items-center justify-center rounded-full bg-success-soft text-lg text-success"><i class="bi bi-check2-all"></i></span>
                <p class="text-sm font-medium">Semua beres</p>
                <p class="text-xs text-muted-foreground">Tidak ada yang perlu ditindaklanjuti.</p>
            </div>
        @else
            <ul class="grid max-h-96 gap-0.5 overflow-y-auto p-1.5">
                @foreach ($items as $item)
                    @php
                        $body = '<span class="flex size-9 shrink-0 items-center justify-center rounded-lg ' . $tones[$item['tone']] . '"><i class="bi ' . $item['icon'] . '"></i></span>';
                    @endphp
                    <li>
                        @if ($item['module'] === $activeCode)
                            <a href="{{ route($item['route']) }}" class="{{ $row }}">
                                {!! $body !!}
                                <span class="grid min-w-0 flex-1 gap-0.5">
                                    <span class="text-sm font-medium leading-snug">{{ $item['title'] }}</span>
                                    <span class="text-xs text-muted-foreground">{{ $item['description'] }}</span>
                                </span>
                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-semibold tabular-nums">{{ $item['count'] }}</span>
                            </a>
                        @else
                            <form action="{{ route('modules.activate', $item['module']) }}" method="POST">
                                @csrf
                                <button type="submit" class="{{ $row }}">
                                    {!! $body !!}
                                    <span class="grid min-w-0 flex-1 gap-0.5">
                                        <span class="text-sm font-medium leading-snug">{{ $item['title'] }}</span>
                                        <span class="text-xs text-muted-foreground"><i class="bi bi-box-arrow-up-right"></i> Buka modul {{ $moduleNames[$item['module']] ?? $item['module'] }}</span>
                                    </span>
                                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-semibold tabular-nums">{{ $item['count'] }}</span>
                                </button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</details>
