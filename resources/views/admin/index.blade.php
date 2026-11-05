<x-layouts.app title="Permohonan Masuk" :breadcrumbs="['BBM Operasional' => route('admin-dashboard'), 'Permohonan Masuk' => null]">
    <x-ui.page-header kicker="Persetujuan" title="Permohonan Masuk" description="Proses permohonan BBM, pantau riwayat, laporan transaksi, dan voucher yang terbit.">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="request()->fullUrl()"><i class="bi bi-arrow-clockwise"></i> Muat ulang</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('admin.partials.stats')

    <x-ui.tabs default="pending">
        {{-- HP: grid 2x2 (tanpa geser ke samping); layar lebar: satu baris. --}}
        <x-ui.tabs-list class="max-sm:grid max-sm:w-full max-sm:grid-cols-2">
            <x-ui.tabs-trigger value="pending"><i class="bi bi-hourglass-split"></i> Menunggu
                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-warning px-1.5 text-[11px] font-semibold tabular-nums text-white">{{ $stats['pending'] }}</span>
            </x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="my-approvals"><i class="bi bi-person-check"></i> Riwayat Saya</x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="history"><i class="bi bi-clock-history"></i> Semua Riwayat</x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="vouchers"><i class="bi bi-ticket-perforated"></i> Voucher Terbit</x-ui.tabs-trigger>
        </x-ui.tabs-list>

        <x-ui.tabs-content value="pending">
            @include('admin.partials.pending-list')
        </x-ui.tabs-content>

        <x-ui.tabs-content value="my-approvals">
            @include('admin.myapproval')
        </x-ui.tabs-content>

        <x-ui.tabs-content value="history">
            @include('admin.requester_reports')
        </x-ui.tabs-content>

        <x-ui.tabs-content value="vouchers">
            @include('admin.voucher-on-publish')
        </x-ui.tabs-content>
    </x-ui.tabs>

    @push('scripts')
        @vite('resources/js/pages/bbm/admin-approval.js')
    @endpush
</x-layouts.app>
