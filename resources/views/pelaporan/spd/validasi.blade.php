{{--
  SATU HALAMAN PELAPORAN SPD (driver):
    - Input struk BULK (maks 5 gambar per kirim, kirim lagi 5 dst), dianalisis AI.
    - Item tersegmentasi: DRAFT (masih bisa diubah) dan TERKIRIM (laporan harian flow lama).
    - Edit / hapus item (autosave) + "Submit sebagai LPJ" yang menggabungkan semuanya.
  Interaksi di resources/js/pages/spd/validasi.js.
--}}
@php
    $draftGrouped = $draftItems->groupBy(fn ($i) => $i->tanggal ? $i->tanggal->format('Y-m-d') : 'tanpa-tanggal');
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $updateItemUrlBase = rtrim(str_replace('/__SLUG__', '', route('pelaporan.spd.daily.update-item', ['surat' => $surat, 'item' => '__SLUG__'])), '/');
@endphp

<x-layouts.app title="Pelaporan & Validasi SPD" :breadcrumbs="['SPD Berjalan' => route('requester.spd.berjalan'), 'Pelaporan & Validasi' => null]">
    <x-ui.page-header kicker="Pelaporan SPD" title="Validasi Laporan untuk Membuat LPJ" description="Upload struk, periksa hasil AI, lalu gabungkan menjadi satu LPJ." />

    @if ($surat->lpj_terlambat)
        {{-- Lewat batas 3 hari BUKAN alasan menutup form: driver tetap boleh menyelesaikan LPJ. --}}
        <x-ui.alert variant="destructive" :title="'LPJ sudah melewati batas submit ' . $surat->lpj_overdue_days . ' hari.'">
            Batas submit <strong>{{ $surat->lpj_deadline_efektif?->translatedFormat('d F Y') }}</strong>. Form ini <strong>tetap terbuka</strong>, silakan lanjutkan submit.
            LPJ yang masuk akan tercatat <em>terlambat</em> dan terlihat oleh Divisi GA serta pengguna yang berangkat.
        </x-ui.alert>
    @elseif ($surat->menunggu_lpj && $surat->lpj_deadline_efektif)
        <x-ui.alert variant="warning" icon="bi bi-clock-history">
            Batas submit LPJ <strong>{{ $surat->lpj_deadline_efektif->translatedFormat('d F Y') }}</strong> ({{ $surat->getDaysUntilLpjDeadline() }} hari lagi). Laporan otomatis tersimpan sebagai draft.
        </x-ui.alert>
    @endif

    <x-ui.card class="py-4">
        <x-ui.card-content class="flex flex-wrap items-center justify-between gap-3 px-4">
            <div class="flex min-w-0 items-start gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-module-spd/10 text-lg text-module-spd"><i class="bi bi-file-earmark-text"></i></span>
                <div class="min-w-0">
                    <p class="break-words font-semibold">{{ $surat->nomor_surat }}</p>
                    <p class="text-sm text-muted-foreground">
                        <i class="bi bi-geo-alt"></i> {{ $surat->tempat_tujuan }}
                        @if ($surat->is_extension) <x-ui.badge variant="info">Perpanjangan</x-ui.badge> @endif
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-ui.badge variant="outline" class="py-1"><i class="bi bi-calendar3"></i> {{ $surat->tanggal_berangkat->translatedFormat('d F Y') }} s/d {{ $surat->tanggal_kembali->translatedFormat('d F Y') }}</x-ui.badge>
                <x-ui.badge variant="outline" class="py-1"><i class="bi bi-truck"></i> {{ $surat->vehicle?->consumerial_code ?? '-' }}</x-ui.badge>
            </div>
        </x-ui.card-content>
    </x-ui.card>

    @if ($spdActive)
        <x-ui.alert variant="info" icon="bi bi-clock-history" title="Periode SPD masih berjalan.">
            Semua struk yang di-upload tersimpan sebagai <strong>draft</strong>. Finalisasi menjadi LPJ baru bisa dilakukan setelah tanggal kembali lewat.
        </x-ui.alert>
    @else
        <x-ui.alert variant="success" icon="bi bi-check2-circle" title="SPD sudah selesai.">
            Upload struk sisa (maks 5 gambar per kirim), lalu <strong>Submit sebagai LPJ</strong> untuk finalisasi. Bila belum mau final, biarkan sebagai draft.
        </x-ui.alert>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="grid min-w-0 gap-6 lg:col-span-2">
            {{-- Input struk: bulk, maks 5 per kirim --}}
            <x-ui.card class="overflow-hidden pt-0">
                <div class="bg-primary px-6 py-4 text-primary-foreground">
                    <p class="flex items-center gap-2 font-semibold"><i class="bi bi-cloud-upload"></i> Input Struk / Bukti Pengeluaran</p>
                </div>
                <x-ui.card-content class="grid gap-3">
                    <x-ui.field label="Pilih gambar struk (maksimal 5 per kirim)" for="bulkFile"
                        hint="Setiap foto dianalisis AI: kategori, nominal, dan tanggal transaksi dibaca otomatis lalu dikelompokkan per tanggal. Tersimpan sebagai draft, aman dari refresh.">
                        <x-ui.input type="file" id="bulkFile" multiple accept="image/jpeg,image/png,image/webp" />
                    </x-ui.field>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button id="btnBulkUpload"><i class="bi bi-upload"></i> Upload Struk</x-ui.button>
                        <span id="bulkFileCount" class="text-sm text-muted-foreground"></span>
                        <span id="uploadStatus" class="text-sm sm:ml-auto"></span>
                    </div>

                    {{-- Foto gagal dianalisis AI (mis. kuota Gemini habis): isi manual --}}
                    <div id="manualPendingWrap" hidden class="grid gap-2">
                        <x-ui.alert variant="warning" title="Beberapa foto gagal dianalisis AI.">
                            Lengkapi datanya secara manual di bawah lalu klik <em>Simpan Manual</em>, foto tetap masuk draft.
                        </x-ui.alert>
                        <div id="manualPendingList" class="grid gap-2"></div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            {{-- Draft: belum final --}}
            <x-ui.card class="border-t-4 border-t-warning">
                <x-ui.card-header title="Draft" icon="bi bi-pencil-square" description="Disimpan sementara, belum final.">
                    <x-slot:action>
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <span class="text-sm text-muted-foreground" id="draftSummary"></span>
                            @if ($draftItems->isNotEmpty())
                                {{-- Hasil OCR bisa salah (terutama tanggal): buka form lengkap untuk semua item sekaligus. --}}
                                <x-ui.button variant="outline" size="sm" id="btnBulkEdit" title="Ubah tanggal, kategori, nominal, keterangan semua item"><i class="bi bi-pencil-square"></i> Ubah Semua Isian</x-ui.button>
                            @endif
                        </div>
                    </x-slot:action>
                </x-ui.card-header>
                <x-ui.card-content class="grid gap-4">
                    <x-ui.alert variant="info" id="bulkEditHint" hidden>
                        Mode ubah semua aktif. Perbaiki <strong>tanggal</strong>, kategori, nominal, dan keterangan tiap item, lalu tekan <strong>Simpan Semua Perubahan</strong>.
                        Item otomatis dikelompokkan ulang berdasarkan tanggal barunya.
                    </x-ui.alert>

                    <div id="draftItemsContainer" class="grid gap-4">
                        @foreach ($draftGrouped as $dateKey => $groupItems)
                            <section data-date-group="{{ $dateKey }}" class="grid gap-3 rounded-lg border bg-muted/30 p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="flex items-center gap-2 text-sm font-semibold"><i class="bi bi-calendar3 text-muted-foreground"></i>
                                        {{ $dateKey === 'tanpa-tanggal' ? 'Tanpa tanggal' : \Illuminate\Support\Carbon::parse($dateKey)->translatedFormat('l, d F Y') }}
                                    </span>
                                    <span data-group-meta class="text-xs text-muted-foreground">
                                        {{ $groupItems->count() }} item &middot; Total {{ $rp($groupItems->where('struk_kategori', '!=', 'Tambahan')->sum('nominal')) }}
                                    </span>
                                </div>
                                <div data-group-items class="grid gap-3">
                                    @foreach ($groupItems as $item)
                                        <x-spd.struk-card :item="$item" />
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

                    <div id="bulkEditBar" hidden class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-primary bg-card p-3 shadow-lg">
                        <span class="text-sm"><i class="bi bi-pencil-square"></i> <span id="bulkEditCount">0</span> item akan disimpan sekaligus.</span>
                        <div class="flex gap-2">
                            <x-ui.button variant="outline" size="sm" id="btnBulkCancel">Batal</x-ui.button>
                            <x-ui.button variant="success" size="sm" id="btnBulkSave"><i class="bi bi-check2-all"></i> Simpan Semua Perubahan</x-ui.button>
                        </div>
                    </div>

                    <x-ui.empty-state id="draftEmpty" icon="bi bi-inbox" title="Belum ada item draft" description="Upload struk di atas, item langsung tersimpan sebagai draft." :hidden="$draftItems->isNotEmpty()" />
                </x-ui.card-content>
            </x-ui.card>

            {{-- Terkirim: laporan harian flow lama, siap digabung --}}
            @if ($submitted->isNotEmpty())
                <x-ui.card class="border-t-4 border-t-success">
                    <x-ui.card-header title="Terkirim" icon="bi bi-check2-all" description="Siap digabung menjadi LPJ.">
                        <x-slot:action><x-ui.badge variant="success">{{ $submittedItems->count() }} item</x-ui.badge></x-slot:action>
                    </x-ui.card-header>
                    <x-ui.card-content class="grid gap-4">
                        @foreach ($submitted as $report)
                            <section data-report class="grid gap-3 rounded-lg border bg-muted/30 p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="flex items-center gap-2 text-sm font-semibold"><i class="bi bi-calendar3 text-muted-foreground"></i> {{ $report->tanggal->translatedFormat('l, d F Y') }}</span>
                                    <span class="text-xs text-muted-foreground">{{ $report->item_count }} item &middot; Total <strong data-report-total>{{ $rp($report->total_nominal) }}</strong></span>
                                </div>
                                <div class="grid gap-3">
                                    @foreach ($report->strukItems as $item)
                                        <x-spd.struk-card :item="$item" />
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </x-ui.card-content>
                </x-ui.card>
            @endif
        </div>

        <div>
            @if (! $spdActive)
                <form action="{{ route('pelaporan.spd.finalize', $surat) }}" method="POST" id="formFinalize" data-loading
                      data-confirm="Seluruh item (draft dan terkirim) akan digabung menjadi satu LPJ dan dikunci." data-confirm-title="Submit sebagai LPJ?" data-confirm-text="Ya, submit LPJ" data-confirm-variant="success">
                    @csrf
                    <x-ui.card class="overflow-hidden pt-0">
                        <div class="bg-success px-6 py-4 text-white">
                            <p class="flex items-center gap-2 font-semibold"><i class="bi bi-journal-text"></i> Finalisasi LPJ</p>
                        </div>
                        <x-ui.card-content class="grid gap-4">
                            <p class="text-sm text-muted-foreground">Semua item <strong class="text-foreground">draft</strong> dan <strong class="text-foreground">terkirim</strong> akan digabung menjadi satu LPJ dan dikunci untuk direview admin GA.</p>
                            <x-ui.field label="Nama Pelapor" for="nama" required>
                                <x-ui.input id="nama" name="nama" :value="old('nama', Auth::user()->nama_lengkap ?? Auth::user()->name)" required />
                            </x-ui.field>
                            <x-ui.field label="Jabatan" for="jabatan">
                                <x-ui.input id="jabatan" name="jabatan" :value="old('jabatan', 'Pengemudi')" />
                            </x-ui.field>
                            <x-ui.field label="Uraian Perjalanan" for="uraian">
                                <x-ui.textarea id="uraian" name="uraian" rows="2" placeholder="Ringkasan keseluruhan perjalanan dinas">{{ old('uraian') }}</x-ui.textarea>
                            </x-ui.field>

                            <div class="grid gap-3 rounded-lg border bg-muted/30 p-3">
                                <p class="text-xs text-muted-foreground"><i class="bi bi-speedometer2"></i> Data tempuh kendaraan untuk dokumen cetak LPJ (opsional, boleh diisi admin GA nanti).</p>
                                <x-ui.field label="Odometer sebelum SPD (km)" for="odo_before">
                                    <x-ui.input type="number" id="odo_before" name="odo_before" :value="old('odo_before')" min="0" step="0.01" />
                                </x-ui.field>
                                <x-ui.field label="Odometer setelah SPD (km)" for="odo_after">
                                    <x-ui.input type="number" id="odo_after" name="odo_after" :value="old('odo_after')" min="0" step="0.01" />
                                </x-ui.field>
                                <x-ui.field label="Kebutuhan BBM (liter)" for="kebutuhan_bbm_liter">
                                    <x-ui.input type="number" id="kebutuhan_bbm_liter" name="kebutuhan_bbm_liter" :value="old('kebutuhan_bbm_liter')" min="0" step="0.01" />
                                </x-ui.field>
                            </div>

                            <div class="flex items-center justify-between border-t pt-4">
                                <span class="text-sm text-muted-foreground">Total LPJ</span>
                                <strong class="text-xl tabular-nums text-success" id="totalFinal">Rp 0</strong>
                            </div>
                            <x-ui.button type="submit" variant="success" id="btnSubmitLpj"><i class="bi bi-check2-circle"></i> Submit sebagai LPJ</x-ui.button>
                        </x-ui.card-content>
                    </x-ui.card>
                </form>
            @else
                <x-ui.card>
                    <x-ui.card-content>
                        <x-ui.empty-state icon="bi bi-lock" title="Finalisasi LPJ terkunci"
                            :description="'SPD masih berjalan. Laporan otomatis tersimpan sebagai draft, finalisasi bisa dilakukan setelah ' . $surat->tanggal_kembali->translatedFormat('d M Y') . '.'" />
                    </x-ui.card-content>
                </x-ui.card>
            @endif
        </div>
    </div>

    <template id="strukCardTemplate"><x-spd.struk-card /></template>

    <template id="dateGroupTemplate">
        <section data-date-group class="grid gap-3 rounded-lg border bg-muted/30 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="flex items-center gap-2 text-sm font-semibold"><i class="bi bi-calendar3 text-muted-foreground"></i> <span data-group-label></span></span>
                <span data-group-meta class="text-xs text-muted-foreground"></span>
            </div>
            <div data-group-items class="grid gap-3"></div>
        </section>
    </template>

    <template id="manualEntryTemplate">
        <div class="flex gap-3 rounded-lg border border-warning/40 bg-card p-3 shadow-xs">
            <img data-mp-preview alt="Pratinjau" class="h-20 w-24 shrink-0 rounded-md bg-muted object-cover">
            <div class="grid min-w-0 flex-1 gap-2 text-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="warning" data-mp-reason></x-ui.badge>
                    <span data-mp-message class="text-xs text-muted-foreground"></span>
                </div>
                <div class="grid gap-2 sm:grid-cols-4">
                    <x-ui.select data-mp="kategori">
                        <option value="">Kategori</option>
                        @foreach (\App\Models\StrukItem::KATEGORI as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.input type="number" min="0" step="1" data-mp="nominal" placeholder="Nominal (Rp)" />
                    <x-ui.input type="date" data-mp="tanggal" />
                    <x-ui.input maxlength="500" data-mp="keterangan" placeholder="Keterangan (opsional)" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" data-mp-save><i class="bi bi-check2"></i> Simpan Manual</x-ui.button>
                    <x-ui.button variant="outline" size="sm" data-mp-retry><i class="bi bi-arrow-clockwise"></i> Coba Analisis Ulang</x-ui.button>
                    <x-ui.button variant="ghost" size="sm" data-mp-discard>Buang</x-ui.button>
                </div>
            </div>
        </div>
    </template>

    @push('scripts')
        <script type="application/json" id="page-data">@json([
            'bulkUploadUrl' => route('pelaporan.spd.daily.upload-items', $surat),
            'bulkUpdateUrl' => route('pelaporan.spd.daily.update-items', $surat),
            'updateItemUrlBase' => $updateItemUrlBase,
            'csrfToken' => csrf_token(),
            'maxBulkUpload' => \App\Http\Controllers\SpdDailyReportController::MAX_BULK_UPLOAD,
        ])</script>
        @vite('resources/js/pages/spd/validasi.js')
    @endpush
</x-layouts.app>
