@php
    $pageData = [
        'analyzeUrl' => route('pelaporan.analyze-struk'),
        'csrfToken' => csrf_token(),
        'totalBudget' => (float) ($surat->total_budget ?? 0),
        'budgets' => $surat ? ($budgetsPerSurat[$surat->id] ?? []) : [],
        'items' => $laporan->details->map(fn ($d) => [
            'tanggal' => $d->tanggal?->format('Y-m-d') ?? '',
            'tipe_penggunaan' => $d->tipe_penggunaan,
            'nominal' => $d->nominal,
            'keterangan' => $d->keterangan,
        ])->values(),
    ];
    $kategori = ['Makan', 'Hotel', 'BBM', 'Parkir', 'Tol', 'Lain-lain'];
@endphp

<x-layouts.app title="Revisi LPJ" :breadcrumbs="['Laporan LPJ' => route('requester.lpj.index'), 'Revisi LPJ' => null]">
    <x-ui.page-header kicker="Laporan Pertanggungjawaban" title="Revisi Laporan Pertanggungjawaban" :description="$surat->nomor_surat . ' · ' . $surat->tempat_tujuan" />

    <x-ui.alert variant="destructive" title="LPJ ditolak">
        <p>{{ $laporan->rejection_reason }}</p>
        <p class="text-xs">Silakan perbaiki laporan di bawah lalu ajukan ulang.</p>
    </x-ui.alert>

    <form action="{{ route('requester.lpj.update', $laporan) }}" method="POST" id="formLpj" enctype="multipart/form-data" class="grid items-start gap-6 lg:grid-cols-3">
        @csrf
        @method('PUT')

        <div class="grid gap-6 lg:col-span-2">
            <x-ui.card>
                <x-ui.card-header title="Surat Tugas" icon="bi bi-file-earmark-text" />
                <x-ui.card-content>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border bg-muted/40 p-3 text-sm">
                        <span class="font-medium">{{ $surat->nomor_surat }}</span>
                        <span class="text-muted-foreground">{{ $surat->tanggal_berangkat->translatedFormat('d M') }} - {{ $surat->tanggal_kembali->translatedFormat('d M Y') }}</span>
                        <x-ui.badge variant="outline"><i class="bi bi-truck"></i> {{ $surat->vehicle->consumerial_code ?? '' }}</x-ui.badge>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Informasi Pelapor" icon="bi bi-person" />
                <x-ui.card-content class="grid gap-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Nama Pelapor" for="nama" required error="nama">
                            <x-ui.input id="nama" name="nama" :value="old('nama', $laporan->nama)" required />
                        </x-ui.field>
                        <x-ui.field label="Jabatan" for="jabatan" required error="jabatan">
                            <x-ui.input id="jabatan" name="jabatan" :value="old('jabatan', $laporan->jabatan)" required />
                        </x-ui.field>
                    </div>
                    <x-ui.field label="Uraian Perjalanan" for="uraian" required error="uraian">
                        <x-ui.textarea id="uraian" name="uraian" rows="3" required placeholder="Jelaskan secara singkat kegiatan perjalanan dinas">{{ old('uraian', $laporan->uraian) }}</x-ui.textarea>
                    </x-ui.field>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Informasi Tempuh Kendaraan" icon="bi bi-speedometer2" description="Data odometer & kebutuhan BBM untuk dokumen cetak LPJ (opsional)." />
                <x-ui.card-content class="grid gap-4">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-ui.field label="Odometer sebelum (km)" for="odoBefore">
                            <x-ui.input type="number" id="odoBefore" name="odo_before" :value="old('odo_before', $laporan->odo_before)" min="0" step="0.01" />
                        </x-ui.field>
                        <x-ui.field label="Odometer setelah (km)" for="odoAfter">
                            <x-ui.input type="number" id="odoAfter" name="odo_after" :value="old('odo_after', $laporan->odo_after)" min="0" step="0.01" />
                        </x-ui.field>
                        <x-ui.field label="Kebutuhan BBM (liter)" for="kebutuhanBbm">
                            <x-ui.input type="number" id="kebutuhanBbm" name="kebutuhan_bbm_liter" :value="old('kebutuhan_bbm_liter', $laporan->kebutuhan_bbm_liter)" min="0" step="0.01" />
                        </x-ui.field>
                    </div>
                    <p class="text-sm text-muted-foreground">Jarak tempuh otomatis: <strong id="jarakTempuh" class="text-foreground">-</strong></p>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Rincian Pengeluaran" icon="bi bi-list-check">
                    <x-slot:action>
                        <x-ui.button size="sm" id="btnAddItem"><i class="bi bi-plus-lg"></i> Tambah Item</x-ui.button>
                    </x-slot:action>
                </x-ui.card-header>
                <x-ui.card-content class="grid gap-3">
                    <div id="itemsContainer" class="grid gap-3"></div>
                    <x-ui.empty-state id="itemsEmpty" icon="bi bi-inbox" title="Belum ada item pengeluaran" description="Klik Tambah Item untuk mulai." />

                    <div class="flex items-center justify-between border-t pt-3">
                        <span class="font-semibold">Total Pengeluaran</span>
                        <span id="totalPengeluaran" class="text-lg font-bold tabular-nums text-primary">Rp 0</span>
                    </div>
                    <x-ui.alert variant="warning" id="alertOverBudget" hidden><span data-message></span></x-ui.alert>
                    <x-ui.alert variant="destructive" id="alertOverBudgetDetail" title="Over budget per tanggal" hidden>
                        <ul class="list-inside list-disc" id="overBudgetList"></ul>
                    </x-ui.alert>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Informasi Keuangan" icon="bi bi-cash-stack" />
                <x-ui.card-content class="grid gap-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Uang Dari" for="uang_dari">
                            <x-ui.input id="uang_dari" name="uang_dari" :value="old('uang_dari', $laporan->uang_dari ?? 'KAS')" placeholder="Contoh: KAS, Bon Sementara" />
                        </x-ui.field>
                        <x-ui.field label="Jumlah Diberikan (Rp)" for="jumlahDiberikan" hint="Terisi otomatis dari pencairan dana SPD bila belum pernah diisi.">
                            <x-ui.input type="number" id="jumlahDiberikan" name="jumlah_diberikan" :value="old('jumlah_diberikan', $laporan->jumlah_diberikan_efektif)" min="0" />
                        </x-ui.field>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg bg-success-soft p-3 text-success"><span class="block text-xs">Pengembalian</span><strong id="pengembalian" class="tabular-nums">Rp 0</strong></div>
                        <div class="rounded-lg bg-warning-soft p-3 text-warning"><span class="block text-xs">Kekurangan</span><strong id="kekurangan" class="tabular-nums">Rp 0</strong></div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Catatan" icon="bi bi-journal-text" description="Opsional" />
                <x-ui.card-content>
                    <x-ui.textarea name="catatan" rows="2" placeholder="Catatan tambahan jika ada">{{ old('catatan', $laporan->catatan) }}</x-ui.textarea>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <div class="grid gap-6">
            <x-ui.card class="overflow-hidden pt-0">
                <div class="bg-primary px-6 py-4 text-primary-foreground">
                    <p class="flex items-center gap-2 font-semibold"><i class="bi bi-calculator"></i> Ringkasan</p>
                </div>
                <x-ui.card-content class="grid gap-4 text-sm">
                    <dl class="grid gap-2">
                        <div class="flex justify-between"><dt class="text-muted-foreground">Total budget</dt><dd id="summaryBudget" class="font-semibold tabular-nums">-</dd></div>
                        <div class="flex justify-between"><dt class="text-muted-foreground">Total pengeluaran</dt><dd id="summaryPengeluaran" class="font-semibold tabular-nums">Rp 0</dd></div>
                        <div class="flex justify-between" id="rowSelisih" hidden><dt class="text-muted-foreground">Selisih</dt><dd id="summarySelisih" class="font-semibold tabular-nums">-</dd></div>
                    </dl>

                    <div id="summaryBudgetPerTanggal" hidden class="grid gap-2 border-t pt-4">
                        <span class="text-xs font-semibold text-muted-foreground">Status per tanggal</span>
                        <div id="summaryDateStatus" class="grid gap-3"></div>
                    </div>

                    <dl class="grid gap-2 border-t pt-4">
                        @foreach (['Makan' => 'summaryMakan', 'Hotel' => 'summaryHotel', 'BBM' => 'summaryBBM', 'Parkir' => 'summaryParkir', 'Tol' => 'summaryTol', 'Lain-lain' => 'summaryLain'] as $label => $id)
                            <div class="flex justify-between"><dt class="text-muted-foreground">{{ $label }}</dt><dd id="{{ $id }}" class="tabular-nums">Rp 0</dd></div>
                        @endforeach
                    </dl>

                    <div class="grid gap-2">
                        <x-ui.button type="submit" id="btnSubmit"><i class="bi bi-send"></i> Ajukan Ulang LPJ</x-ui.button>
                        <x-ui.button variant="outline" :href="route('requester.lpj.show', $laporan)"><i class="bi bi-arrow-left"></i> Batal</x-ui.button>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="gap-3">
                <x-ui.card-header title="Informasi" icon="bi bi-info-circle" />
                <x-ui.card-content>
                    <ul class="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                        <li>Perbaiki data sesuai alasan penolakan</li>
                        <li>Pastikan semua pengeluaran sudah dicatat</li>
                        <li>Pengeluaran dicocokkan dengan budget per tanggal</li>
                        <li>Item yang melebihi budget harian akan ditandai</li>
                    </ul>
                </x-ui.card-content>
            </x-ui.card>
        </div>
    </form>

    {{-- Kartu satu item pengeluaran; __I__ diganti nomor urut oleh resources/js/pages/spd/lpj-edit.js --}}
    <template id="itemTemplate">
        <div data-item class="grid gap-3 rounded-lg border p-4 shadow-xs transition-colors data-[over]:border-destructive data-[over]:bg-destructive-soft/40">
            <div class="flex items-center justify-between">
                <x-ui.badge class="rounded-full" data-item-number>#1</x-ui.badge>
                <x-ui.button variant="ghost" size="sm" class="text-destructive" data-remove-item><i class="bi bi-trash"></i> Hapus</x-ui.button>
            </div>

            <div class="grid gap-3 sm:grid-cols-4">
                <x-ui.field label="Tanggal">
                    <x-ui.input type="date" name="items[__I__][tanggal]" data-field="tanggal" required />
                </x-ui.field>
                <x-ui.field label="Kategori">
                    <x-ui.select name="items[__I__][tipe_penggunaan]" data-field="tipe" required>
                        @foreach ($kategori as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="Nominal (Rp)">
                    <x-ui.input type="number" name="items[__I__][nominal]" data-field="nominal" min="0" required placeholder="0" />
                </x-ui.field>
                <div class="grid content-end text-right text-sm">
                    <span class="text-xs text-muted-foreground">Subtotal</span>
                    <strong data-subtotal class="tabular-nums">Rp 0</strong>
                </div>
            </div>

            <div class="grid items-end gap-3 sm:grid-cols-[1fr_auto]">
                <x-ui.field label="Foto Struk" hint="Kosongkan bila struk lama tetap dipakai.">
                    <x-ui.input type="file" name="items[__I__][struk]" data-field="struk" accept="image/jpeg,image/png,image/webp" />
                </x-ui.field>
                <x-ui.button variant="outline" data-analyze disabled class="sm:mb-6"><i class="bi bi-stars"></i> Analisis AI</x-ui.button>
            </div>
            <p data-ai-status class="text-xs text-muted-foreground empty:hidden"></p>
            <img data-preview hidden alt="Pratinjau struk" class="max-h-24 w-fit rounded-md border">

            <input type="hidden" name="items[__I__][struk_kategori]" data-field="struk_kategori">
            <input type="hidden" name="items[__I__][kategori_source]" data-field="kategori_source" value="manual">
            <input type="hidden" name="items[__I__][metadata_json]" data-field="metadata_json">

            <x-ui.field label="Keterangan">
                <x-ui.input name="items[__I__][keterangan]" data-field="keterangan" placeholder="Contoh: SPBU Pertamina, hotel menginap 1 malam, tukang parkir" />
            </x-ui.field>
        </div>
    </template>

    @push('scripts')
        <script type="application/json" id="page-data">@json($pageData)</script>
        @vite('resources/js/pages/spd/lpj-edit.js')
    @endpush
</x-layouts.app>
