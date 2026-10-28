<x-layouts.app title="Cetak Laporan BBM" :breadcrumbs="['Permohonan BBM' => route('admin-dashboard'), 'Cetak Laporan' => null]">
    <x-ui.page-header title="Cetak Laporan BBM" description="Buat laporan transaksi BBM dalam format PDF sesuai filter." />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <x-ui.card-header title="Filter laporan" icon="bi bi-funnel" />
            <x-ui.card-content>
                <form id="printReportForm" action="{{ route('superadmin.print-report') }}" method="GET" target="_blank" class="grid gap-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Tanggal mulai" for="startDate" required error="start_date">
                            <x-ui.input type="date" name="start_date" id="startDate" required
                                        :value="old('start_date', now()->startOfMonth()->format('Y-m-d'))" />
                        </x-ui.field>
                        <x-ui.field label="Tanggal akhir" for="endDate" required error="end_date">
                            <x-ui.input type="date" name="end_date" id="endDate" required :value="old('end_date', now()->format('Y-m-d'))" />
                        </x-ui.field>
                    </div>

                    <fieldset class="grid gap-2">
                        <legend class="mb-2 text-sm font-medium">Status permohonan</legend>
                        <div class="grid gap-2 sm:grid-cols-4">
                            @foreach (['approved' => true, 'completed' => true, 'pending' => false, 'rejected' => false] as $status => $checked)
                                <label class="flex cursor-pointer items-center gap-2 rounded-md border p-3 has-[:checked]:border-primary has-[:checked]:bg-accent">
                                    <x-ui.checkbox name="status[]" value="{{ $status }}" :checked="$checked" data-default="{{ $checked ? '1' : '0' }}" />
                                    <x-bbm.status-badge :status="$status" />
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-muted-foreground">Bawaan: Disetujui dan Selesai.</p>
                    </fieldset>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Jenis BBM" for="gasolineType" hint="Kosongkan untuk semua jenis BBM.">
                            <x-ui.select name="gasoline_type" id="gasolineType">
                                <option value="">Semua jenis BBM</option>
                                @foreach (['Pertalite', 'Pertamax', 'Pertamina Dex'] as $gt)
                                    <option value="{{ $gt }}" @selected(old('gasoline_type') == $gt)>{{ $gt }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Diproses oleh" for="authorizerId" hint="Filter berdasarkan approver.">
                            <x-ui.select name="authorizer_id" id="authorizerId">
                                <option value="">Semua approver</option>
                                @foreach ($approvers as $approver)
                                    <option value="{{ $approver->id }}" @selected(old('authorizer_id') == $approver->id)>{{ $approver->nama_lengkap }} ({{ ucfirst($approver->role) }})</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                    </div>

                    <input type="hidden" name="download_token" id="downloadToken">

                    <div class="flex flex-wrap gap-2 border-t pt-5">
                        <x-ui.button type="submit" id="btnGenerate"><i class="bi bi-file-earmark-pdf"></i> Buat PDF</x-ui.button>
                        <x-ui.button type="reset" variant="outline"><i class="bi bi-arrow-counterclockwise"></i> Reset filter</x-ui.button>
                    </div>
                </form>
            </x-ui.card-content>
        </x-ui.card>

        <div class="grid content-start gap-6">
            <x-ui.card>
                <x-ui.card-header title="Rentang cepat" icon="bi bi-lightning" />
                <x-ui.card-content class="grid gap-2">
                    @foreach (['today' => 'Hari ini', 'week' => 'Minggu ini', 'month' => 'Bulan ini', 'lastmonth' => 'Bulan lalu', 'year' => 'Tahun ini'] as $range => $label)
                        <x-ui.button variant="outline" data-range="{{ $range }}" class="justify-start">{{ $label }}</x-ui.button>
                    @endforeach
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.alert variant="info" title="Informasi">
                <ul class="list-inside list-disc text-xs">
                    <li>PDF dibuka di tab baru dan dapat diunduh atau dicetak.</li>
                    <li>Laporan berisi pemohon, kendaraan, jenis BBM, nominal, dan struk.</li>
                    <li>Rentang maksimal 1 tahun supaya pembuatan PDF tetap cepat.</li>
                </ul>
            </x-ui.alert>
        </div>
    </div>

    <div id="loadingOverlay" hidden class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <x-ui.card class="min-w-64 items-center gap-2 px-8 text-center">
            <i class="bi bi-hourglass-split animate-pulse text-3xl text-primary"></i>
            <p class="font-medium">Membuat PDF</p>
            <p class="text-sm text-muted-foreground">Mohon tunggu, laporan sedang diproses.</p>
        </x-ui.card>
    </div>

    @push('scripts')
        @vite('resources/js/pages/bbm/print-report.js')
    @endpush
</x-layouts.app>
