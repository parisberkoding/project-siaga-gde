<x-layouts.app title="Approval BBM" :breadcrumbs="['Permohonan BBM' => route('admin-dashboard'), 'Approval BBM' => null]">
    <x-ui.page-header kicker="Permohonan BBM" title="Approval BBM" description="Proses permohonan BBM, pantau riwayat, laporan transaksi, dan voucher yang terbit.">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="request()->fullUrl()"><i class="bi bi-arrow-clockwise"></i> Muat ulang</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('admin.partials.stats')

    <x-ui.tabs default="pending">
        <x-ui.tabs-list>
            <x-ui.tabs-trigger value="pending"><i class="bi bi-hourglass-split"></i> Menunggu ({{ $stats['pending'] }})</x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="my-approvals"><i class="bi bi-person-check"></i> Riwayat Approval Saya</x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="history"><i class="bi bi-clock-history"></i> Semua History & Laporan</x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="vouchers"><i class="bi bi-ticket-perforated"></i> Voucher Terbit</x-ui.tabs-trigger>
        </x-ui.tabs-list>

        <x-ui.tabs-content value="pending">
            @include('admin.partials.pending-list')
        </x-ui.tabs-content>

        <x-ui.tabs-content value="my-approvals">
            <x-ui.card class="py-4"><x-ui.card-content class="px-4">@include('admin.myapproval')</x-ui.card-content></x-ui.card>
        </x-ui.tabs-content>

        <x-ui.tabs-content value="history">
            <x-ui.card class="py-4"><x-ui.card-content class="px-4">@include('admin.requester_reports')</x-ui.card-content></x-ui.card>
        </x-ui.tabs-content>

        <x-ui.tabs-content value="vouchers">
            <x-ui.card class="py-4"><x-ui.card-content class="px-4">@include('admin.voucher-on-publish')</x-ui.card-content></x-ui.card>
        </x-ui.tabs-content>
    </x-ui.tabs>

    @push('scripts')
        @vite('resources/js/pages/bbm/admin-approval.js')
    @endpush
</x-layouts.app>
