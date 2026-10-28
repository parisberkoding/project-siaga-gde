<x-layouts.app title="Approval Permohonan BBM" :breadcrumbs="['Permohonan BBM' => route('admin-dashboard'), 'Approval' => null]">
    <x-ui.page-header title="Approval Permohonan BBM" description="Setujui atau tolak permohonan BBM yang menunggu.">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="request()->fullUrl()"><i class="bi bi-arrow-clockwise"></i> Muat ulang</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @include('admin.partials.stats')

    <x-ui.tabs default="pending">
        <x-ui.tabs-list>
            <x-ui.tabs-trigger value="pending"><i class="bi bi-hourglass-split"></i> Menunggu ({{ $stats['pending'] }})</x-ui.tabs-trigger>
            <x-ui.tabs-trigger value="my-approvals"><i class="bi bi-person-check"></i> Riwayat Approval Saya</x-ui.tabs-trigger>
        </x-ui.tabs-list>

        <x-ui.tabs-content value="pending">
            @include('admin.partials.pending-list')
        </x-ui.tabs-content>

        <x-ui.tabs-content value="my-approvals">
            <x-ui.card class="py-4"><x-ui.card-content class="px-4">@include('admin.myapproval')</x-ui.card-content></x-ui.card>
        </x-ui.tabs-content>
    </x-ui.tabs>

    @push('scripts')
        @vite('resources/js/pages/bbm/admin-approval.js')
    @endpush
</x-layouts.app>
