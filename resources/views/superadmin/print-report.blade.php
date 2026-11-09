{{-- Cetak Laporan BBM. Isian: x-bbm.print-report-form (sama dengan dialog di Dashboard BBM). JS: pages/bbm/print-report.js. --}}
<x-layouts.app title="Cetak Laporan BBM" :breadcrumbs="['BBM Operasional' => route('modules.dashboard.bbm'), 'Cetak Laporan' => null]">
    <x-ui.page-header kicker="Laporan & Analisis" icon="bi bi-printer" title="Cetak Laporan BBM" description="Buat laporan transaksi BBM dalam format PDF sesuai periode dan filter." />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <x-ui.card-header title="Filter laporan" icon="bi bi-funnel" />
            <x-ui.card-content>
                <x-bbm.print-report-form :approvers="$approvers" id="printReportForm">
                    <div class="flex flex-col-reverse gap-2 border-t pt-5 sm:flex-row sm:justify-end">
                        <x-ui.button variant="outline" :href="route('superadmin.print-report')"><i class="bi bi-arrow-counterclockwise"></i> Reset filter</x-ui.button>
                        <x-ui.button type="submit" id="btnGenerate"><i class="bi bi-file-earmark-pdf"></i> Buat PDF</x-ui.button>
                    </div>
                </x-bbm.print-report-form>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.alert variant="info" title="Tentang laporan" class="h-fit">
            <ul class="list-inside list-disc text-xs">
                <li>PDF dibuka di tab baru dan dapat diunduh atau dicetak.</li>
                <li>Laporan berisi pemohon, kendaraan, jenis BBM, nominal, dan struk.</li>
                <li>Hanya memuat transaksi unit Anda, kecuali akun Pusat.</li>
            </ul>
        </x-ui.alert>
    </div>

    <div id="loadingOverlay" hidden class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <x-ui.card class="w-full max-w-72 items-center gap-2 px-8 text-center">
            <i class="bi bi-hourglass-split animate-pulse text-3xl text-primary"></i>
            <p class="font-medium">Membuat PDF</p>
            <p class="text-sm text-muted-foreground">Mohon tunggu, laporan sedang diproses.</p>
        </x-ui.card>
    </div>

    @push('scripts')
        @vite('resources/js/pages/bbm/print-report.js')
    @endpush
</x-layouts.app>
