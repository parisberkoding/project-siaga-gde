{{-- Riwayat permohonan BBM driver. Server mengirim 100 baris per batch (controller requester_history);
     resources/js/pages/bbm/history.js menampilkan 10 per halaman dan meminta batch berikutnya lewat ?json=1&offset=. --}}
<x-layouts.app title="Riwayat Permohonan BBM" :breadcrumbs="['Permohonan BBM' => route('requester-index'), 'Riwayat' => null]">
    <x-ui.page-header kicker="Permohonan BBM" title="Riwayat Permohonan" description="Dimuat 100 data per batch, 10 per halaman. Saring dengan rentang tanggal maksimal 30 hari." />

    <x-ui.card class="gap-4">
        <x-ui.card-content class="grid gap-4">
            <form method="GET" action="{{ route('requester-history') }}" id="historyFilter" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <x-ui.field label="Dari tanggal" for="historyFrom">
                    <x-ui.date-picker id="historyFrom" name="from" :value="$from" />
                </x-ui.field>
                <x-ui.field label="Sampai tanggal" for="historyTo">
                    <x-ui.date-picker id="historyTo" name="to" :value="$to" />
                </x-ui.field>
                <div class="flex gap-2">
                    <x-ui.button type="submit" class="flex-1 sm:flex-none"><i class="bi bi-funnel"></i> Terapkan Filter</x-ui.button>
                    <x-ui.button variant="outline" :href="route('requester-history')" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i> Reset</x-ui.button>
                </div>
                <p class="text-xs text-muted-foreground sm:col-span-3">Rentang maksimal 30 hari. Bila tanggal terbalik, sistem otomatis membacanya dari tanggal awal ke tanggal akhir.</p>
            </form>

            @if (! empty($rangeNotice))
                <x-ui.alert variant="warning">{{ $rangeNotice }}</x-ui.alert>
            @endif

            {{-- HP: daftar kartu. Layar lebar: tabel. Keduanya diisi history.js. --}}
            <ul id="historyCards" class="grid gap-3 md:hidden" aria-live="polite"></ul>
            <div class="hidden md:block">
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Tanggal</th>
                            <th>Kendaraan/Alat</th>
                            <th>Jenis BBM</th>
                            <th class="text-right">Nominal</th>
                            <th>Status</th>
                            <th class="text-right"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody id="historyBody">
                        <tr><td colspan="7" class="py-10 text-center text-muted-foreground">Memuat riwayat...</td></tr>
                    </tbody>
                </x-ui.table>
            </div>

            <nav aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground" id="historyCount">&nbsp;</p>
                <div class="flex items-center gap-1" id="historyPager" hidden>
                    <x-ui.button variant="outline" size="icon" id="historyPrev" aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></x-ui.button>
                    <span class="min-w-24 text-center text-sm tabular-nums" id="historyPageInfo">Halaman 1 / 1</span>
                    <x-ui.button variant="outline" size="icon" id="historyNext" aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></x-ui.button>
                </div>
            </nav>
        </x-ui.card-content>
    </x-ui.card>

    <script type="application/json" id="page-data">@json(['url' => route('requester-history'), 'rows' => $historyRows, 'hasMore' => (bool) $hasMore])</script>
    @push('scripts')
        @vite('resources/js/pages/bbm/history.js')
    @endpush
</x-layouts.app>
