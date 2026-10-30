<x-layouts.app title="Pelaporan BBM & SPD" :breadcrumbs="['Pelaporan' => null]">
    <x-ui.page-header kicker="Pelaporan" title="Pelaporan BBM & SPD" description="Laporkan pengisian BBM operasional atau pengeluaran surat tugas SPD." />

    {{-- Pilihan mode, tersimpan di URL (?mode=spd|operational). Disembunyikan bila mode sudah dipilih
         (mis. dari kartu modul BBM atau tautan voucher "Lapor"), supaya tidak muncul lagi di tengah alur. --}}
    @unless ($modeLocked ?? false)
    <div class="grid gap-4 md:grid-cols-2">
        @if ($spdMode)
            <button type="button" data-mode="spd" aria-pressed="false"
                    class="flex items-center gap-4 rounded-xl border bg-card p-5 text-left shadow-sm transition hover:border-primary/50 aria-pressed:border-primary aria-pressed:ring-[3px] aria-pressed:ring-ring/20">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-module-spd/10 text-2xl text-module-spd"><i class="bi bi-file-earmark-text"></i></span>
                <span class="grid gap-1">
                    <span class="font-semibold">Laporan SPD (LPJ)</span>
                    <span class="text-sm text-muted-foreground">Input struk dalam satu halaman validasi, simpan sebagai draft, lalu kirim sebagai LPJ.</span>
                </span>
                <i class="bi bi-chevron-right ml-auto text-muted-foreground"></i>
            </button>
        @endif
        <button type="button" data-mode="operational" aria-pressed="false"
                class="flex items-center gap-4 rounded-xl border bg-card p-5 text-left shadow-sm transition hover:border-primary/50 aria-pressed:border-primary aria-pressed:ring-[3px] aria-pressed:ring-ring/20">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-module-bbm/10 text-2xl text-module-bbm"><i class="bi bi-fuel-pump"></i></span>
            <span class="grid gap-1">
                <span class="font-semibold">BBM Operasional</span>
                <span class="text-sm text-muted-foreground">Laporkan pengisian BBM harian dengan unggah struk dan analisis AI.</span>
            </span>
            <i class="bi bi-chevron-right ml-auto text-muted-foreground"></i>
        </button>
    </div>
    @endunless

    @unless ($spdMode)
        <x-ui.alert variant="warning" dismissible>
            Tidak ada surat tugas SPD yang perlu dilaporkan. LPJ hanya bisa dibuat untuk surat yang sudah disetujui dan belum ada laporannya.
        </x-ui.alert>
    @endunless

    @if ($spdMode)
        <section id="panel-spd" hidden>
            <x-ui.card class="gap-4">
                <x-ui.card-header title="Surat Tugas SPD" icon="bi bi-file-earmark-text"
                                  description="Struk selama perjalanan tersimpan sebagai draft. Setelah SPD selesai, unggah sisa struk (maksimal 5 gambar per kirim) lalu pilih Submit sebagai LPJ." />
                <x-ui.card-content>
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th>Surat tugas</th>
                                <th>Jadwal</th>
                                <th>Kendaraan</th>
                                <th>Laporan harian</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($suratList as $surat)
                                <tr>
                                    <td>
                                        <span class="font-medium">{{ $surat->nomor_surat }}</span>
                                        @if ($surat->is_extension)<x-ui.badge variant="secondary">Perpanjangan</x-ui.badge>@endif
                                        <span class="block text-xs text-muted-foreground">{{ $surat->tempat_tujuan }}</span>
                                    </td>
                                    <td class="text-xs">
                                        {{ $surat->tanggal_berangkat->translatedFormat('l, d F Y') }} sampai<br>
                                        {{ $surat->tanggal_kembali->translatedFormat('l, d F Y') }}
                                    </td>
                                    <td>{{ $surat->vehicle?->consumerial_code ?? '-' }}</td>
                                    <td>
                                        @if ($surat->daily_reports_available)
                                            <div class="flex flex-wrap gap-1">
                                                <x-ui.badge variant="success">{{ $surat->daily_submitted_count }} hari terkirim</x-ui.badge>
                                                @if ($surat->has_draft_daily)<x-ui.badge variant="warning">ada draft</x-ui.badge>@endif
                                            </div>
                                            <span class="text-xs text-muted-foreground">{{ $surat->daily_item_count }} item</span>
                                        @else
                                            <x-ui.badge variant="muted">belum ada</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <x-ui.button variant="outline" size="sm" :href="route('pelaporan.spd.validasi', $surat)">
                                            <i class="bi bi-calendar3"></i> Lapor & Validasi
                                        </x-ui.button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-10 text-center text-muted-foreground">Tidak ada surat SPD yang perlu dilaporkan.</td></tr>
                            @endforelse
                        </tbody>
                    </x-ui.table>
                </x-ui.card-content>
            </x-ui.card>
        </section>
    @endif

    <section id="panel-operational" hidden class="grid gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <x-ui.card-header title="Laporan BBM Operasional" icon="bi bi-fuel-pump" />
            <x-ui.card-content>
                @if ($activePermohonan->isEmpty())
                    <x-ui.empty-state icon="bi bi-inbox" title="Tidak ada permohonan untuk dilaporkan" description="Belum ada permohonan BBM yang disetujui dan belum dilaporkan." />
                @else
                    <form action="{{ route('pelaporan.operational.store') }}" method="POST" id="formOperational" enctype="multipart/form-data" class="grid gap-5">
                        @csrf

                        <x-ui.field label="Permohonan BBM" for="selectPermohonan" required>
                            <x-ui.select name="permohonan_id" id="selectPermohonan" required>
                                <option value="">Pilih permohonan</option>
                                @foreach ($activePermohonan as $p)
                                    <option value="{{ $p->id }}"
                                            data-vehicle="{{ $p->vehicle?->consumerial_code }}"
                                            data-gasoline="{{ $p->gasoline_type }}"
                                            data-budget="{{ $p->vehicle?->detail?->nominal_full_tank }}">
                                        {{ $p->request_number }} - {{ $p->vehicle?->consumerial_code }} ({{ $p->gasoline_type }}) - Rp {{ number_format($p->bill_amounts, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                            <p id="infoPermohonan" class="text-xs text-muted-foreground"></p>
                        </x-ui.field>

                        <x-ui.field label="Foto struk dan tambahan" for="fotoFilesOp" required
                                    hint="Unggah satu atau beberapa foto sekaligus (struk BBM, odometer, dashboard). AI menganalisis setiap foto.">
                            <x-ui.input type="file" name="foto_files[]" id="fotoFilesOp" accept="image/jpeg,image/png,image/webp" multiple required />
                        </x-ui.field>

                        <div id="aiResultsOp" class="grid gap-2"></div>

                        <x-ui.alert id="strukValidationAlertOp" variant="destructive" title="Struk berbeda" hidden>
                            <span id="strukValidationMsgOp"></span>
                        </x-ui.alert>
                        <x-ui.alert id="aiLimitAlertOp" variant="warning" hidden><span id="aiLimitMsgOp"></span></x-ui.alert>

                        {{-- Rekap manual, muncul bila ada foto yang harus diisi manual --}}
                        <div id="manualRekapOp" hidden class="grid gap-3 rounded-lg border border-warning/40 bg-warning-soft p-4">
                            <p class="text-sm font-medium text-warning"><i class="bi bi-pencil-square"></i> Rekap laporan, koreksi atau isi manual</p>
                            <div id="manualRekapFieldsOp" class="grid gap-3 sm:grid-cols-3"></div>
                        </div>

                        {{-- Diisi otomatis dari hasil AI (atau dari rekap manual) --}}
                        <input type="hidden" name="purchase_date" id="purchaseDateOp" value="{{ old('purchase_date', now()->format('Y-m-d')) }}">
                        <input type="hidden" name="volume_bbm" id="volumeOp" value="{{ old('volume_bbm') }}" required>
                        <input type="hidden" name="nominal" id="nominalOp" value="{{ old('nominal') }}">
                        <div id="fileFieldsWrap"></div>

                        <p id="aiStatusOp" class="text-sm" aria-live="polite"></p>

                        <div>
                            <x-ui.button type="submit" variant="success"><i class="bi bi-send"></i> Kirim Laporan BBM</x-ui.button>
                        </div>
                    </form>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card class="self-start">
            <x-ui.card-header title="Cara melapor" icon="bi bi-info-circle" />
            <x-ui.card-content>
                <ol class="grid list-inside list-decimal gap-2 text-sm text-muted-foreground">
                    <li>Pilih permohonan yang sudah disetujui.</li>
                    <li>Unggah foto struk dan foto tambahan. Foto odometer atau dashboard otomatis masuk kategori Tambahan.</li>
                    <li>Periksa hasil analisis AI.</li>
                    <li>Bila AI mencapai batas atau gagal, isi data manual pada foto yang ditandai kuning.</li>
                    <li>Klik Kirim Laporan. Satu permohonan hanya punya satu laporan BBM.</li>
                </ol>
            </x-ui.card-content>
        </x-ui.card>
    </section>

    <script type="application/json" id="page-data">@json(['analyzeUrl' => route('pelaporan.analyze-struk'), 'csrfToken' => csrf_token()])</script>

    @push('scripts')
        @vite('resources/js/pages/bbm/pelaporan.js')
    @endpush
</x-layouts.app>
