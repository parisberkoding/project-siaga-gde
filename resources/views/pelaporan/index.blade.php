{{--
  Pelaporan (pelaporan.index): satu halaman untuk dua alur, BBM operasional (lapor pembelian voucher) dan SPD (LPJ).
  Bila mode sudah dipilih (?mode= atau ?permohonan_id= dari tombol Lapor Pembelian), judul, breadcrumb, dan isi
  halaman mengikuti mode itu saja. Interaksi + analisis struk AI: resources/js/pages/bbm/pelaporan.js.
--}}
@php
    $mode = request('mode') ?? (request()->filled('permohonan_id') ? 'operational' : null);
    $isOperational = ($modeLocked ?? false) && $mode !== 'spd';
    $isSpd = ($modeLocked ?? false) && $mode === 'spd';
    [$pageTitle, $kicker, $description, $crumbs] = match (true) {
        $isOperational => ['Lapor Pembelian BBM', 'Pengajuan Saya', 'Laporkan pengisian BBM dari voucher yang sudah disetujui.',
            ['BBM Operasional' => route('requester-index'), 'Permohonan Aktif' => route('requester-index'), 'Lapor Pembelian' => null]],
        $isSpd => ['Laporan SPD (LPJ)', 'Perjalanan Dinas', 'Laporkan pengeluaran surat tugas SPD.', ['Pelaporan' => null]],
        default => ['Pelaporan BBM & SPD', 'Pelaporan', 'Laporkan pengisian BBM operasional atau pengeluaran surat tugas SPD.', ['Pelaporan' => null]],
    };

    // Data voucher per permohonan untuk kartu voucher di Langkah 1 (dibaca JS).
    $activePermohonan->loadMissing('approvedRequest');
    $voucherData = $activePermohonan->mapWithKeys(fn ($p) => [$p->id => [
        'number' => $p->request_number,
        'voucher' => $p->approvedRequest?->voucher_code,
        'validUntil' => $p->approvedRequest?->valid_until ? \Illuminate\Support\Carbon::parse($p->approvedRequest->valid_until)->toIso8601String() : null,
        'vehicle' => $p->vehicle?->consumerial_code,
        'vehicleName' => $p->vehicle?->consumerial_name,
        'gasoline' => $p->gasoline_type,
        'amount' => (float) $p->bill_amounts,
        'budget' => $p->vehicle?->detail?->nominal_full_tank,
    ]]);
    $voucherOptions = $activePermohonan->map(fn ($p) => [
        'value' => (string) $p->id,
        'label' => ($p->approvedRequest?->voucher_code ?? $p->request_number) . ' · ' . ($p->vehicle?->consumerial_code ?? '-') . ' · Rp ' . number_format($p->bill_amounts, 0, ',', '.'),
    ])->values()->all();
    $presetId = (string) request('permohonan_id', $activePermohonan->count() === 1 ? $activePermohonan->first()->id : '');
    $presetValid = $presetId !== '' && $voucherData->has((int) $presetId);
    $step = 'flex size-7 shrink-0 items-center justify-center rounded-lg bg-linear-to-br text-xs font-bold text-white shadow-sm';
@endphp

<x-layouts.app :title="$pageTitle" :breadcrumbs="$crumbs">
    <x-ui.page-header :kicker="$kicker" :title="$pageTitle" :description="$description">
        @if ($isOperational)
            <x-slot:actions>
                <x-ui.button variant="outline" :href="route('requester-index')"><i class="bi bi-arrow-left"></i> Kembali</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    {{-- Pilihan mode, tersimpan di URL (?mode=spd|operational). Disembunyikan bila mode sudah dipilih. --}}
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

    {{-- Peringatan SPD hanya relevan di alur SPD, bukan saat melapor pembelian BBM. --}}
    @if (! $spdMode && ! $isOperational)
        <x-ui.alert variant="warning" dismissible>
            Tidak ada surat tugas SPD yang perlu dilaporkan. LPJ hanya bisa dibuat untuk surat yang sudah disetujui dan belum ada laporannya.
        </x-ui.alert>
    @endif

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

    {{-- grid-cols-1 = minmax(0,1fr): label voucher panjang tidak memaksa halaman melebar di HP. --}}
    <section id="panel-operational" hidden class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        @if ($activePermohonan->isEmpty())
            <x-ui.card class="lg:col-span-3">
                <x-ui.card-content>
                    <x-ui.empty-state icon="bi bi-ticket-perforated" title="Tidak ada voucher untuk dilaporkan"
                                      description="Laporan bisa dibuat untuk voucher yang sudah disetujui dan belum dilaporkan.">
                        <x-ui.button class="mt-2" variant="outline" :href="route('requester-index')"><i class="bi bi-arrow-left"></i> Ke Permohonan Aktif</x-ui.button>
                    </x-ui.empty-state>
                </x-ui.card-content>
            </x-ui.card>
        @else
            <form action="{{ route('pelaporan.operational.store') }}" method="POST" id="formOperational" enctype="multipart/form-data" class="grid min-w-0 grid-cols-1 gap-6 lg:col-span-2">
                @csrf

                {{-- Langkah 1: voucher yang dilaporkan --}}
                <x-ui.card class="ornament-corner gap-4" style="--ornament: var(--primary)">
                    <div class="flex items-start gap-3 px-6">
                        <span class="{{ $step }} from-primary to-indigo-500">1</span>
                        <div>
                            <h2 class="font-semibold leading-7">Voucher yang dilaporkan</h2>
                            <p class="text-sm text-muted-foreground">Pilih voucher yang dipakai saat mengisi BBM.</p>
                        </div>
                    </div>
                    <x-ui.card-content class="grid gap-3">
                        <x-ui.field label="Voucher" for="selectPermohonan" required>
                            <x-ui.select-menu id="selectPermohonan" name="permohonan_id" :options="$voucherOptions" :value="$presetValid ? $presetId : ''"
                                              placeholder="Pilih voucher" icon="bi bi-ticket-perforated" required class="[&_[data-select-trigger]]:h-11" />
                            <p id="infoPermohonan" class="text-xs text-muted-foreground empty:hidden">
                                @if ($presetId !== '' && ! $presetValid)
                                    <span class="text-warning">Voucher ini sudah dilaporkan atau belum disetujui. Pilih voucher lain.</span>
                                @endif
                            </p>
                        </x-ui.field>

                        {{-- Kartu voucher terpilih (diisi JS dari data voucher) --}}
                        <div id="voucherCard" hidden class="overflow-hidden rounded-xl border bg-linear-to-br from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-600/20">
                            {{-- HP: kode dan nominal bertumpuk supaya kode voucher tidak terpotong. --}}
                            <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 p-4">
                                <div class="min-w-0">
                                    <p class="text-xs text-white/75">Kode voucher</p>
                                    <p data-v="voucher" class="break-all font-mono text-base font-bold tracking-wider sm:text-lg"></p>
                                    <p data-v="number" class="text-xs text-white/75"></p>
                                </div>
                                <div class="shrink-0 sm:text-right">
                                    <p class="text-xs text-white/75">Nominal</p>
                                    <p data-v="amount" class="text-lg font-bold tabular-nums"></p>
                                </div>
                            </div>
                            <dl class="grid grid-cols-2 gap-px bg-white/15 text-xs sm:grid-cols-3">
                                <div class="bg-emerald-700/60 px-4 py-2.5"><dt class="text-white/70">Kendaraan</dt><dd data-v="vehicle" class="truncate font-semibold"></dd></div>
                                <div class="bg-emerald-700/60 px-4 py-2.5"><dt class="text-white/70">BBM</dt><dd data-v="gasoline" class="truncate font-semibold"></dd></div>
                                <div class="col-span-2 bg-emerald-700/60 px-4 py-2.5 sm:col-span-1"><dt class="text-white/70">Berlaku</dt><dd data-v="validity" class="truncate font-semibold"></dd></div>
                            </dl>
                        </div>
                    </x-ui.card-content>
                </x-ui.card>

                {{-- Langkah 2: foto struk & tambahan --}}
                <x-ui.card class="ornament-corner gap-4" style="--ornament: var(--module-bbm)">
                    <div class="flex items-start gap-3 px-6">
                        <span class="{{ $step }} from-orange-500 to-amber-500">2</span>
                        <div>
                            <h2 class="font-semibold leading-7">Foto struk dan tambahan</h2>
                            <p class="text-sm text-muted-foreground">Struk BBM wajib. Foto odometer atau dashboard boleh ditambahkan. AI membaca setiap foto.</p>
                        </div>
                    </div>
                    <x-ui.card-content class="grid gap-4">
                        {{-- Area unggah: input file asli menutupi area (ketuk atau seret foto ke sini). --}}
                        <label for="fotoFilesOp" data-dropzone
                               class="relative grid cursor-pointer place-items-center gap-2 rounded-xl border-2 border-dashed border-border bg-muted/30 px-4 py-8 text-center transition-colors hover:border-primary/50 hover:bg-primary/5 has-[:focus-visible]:border-ring has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-ring/50 data-[drag=true]:border-primary data-[drag=true]:bg-primary/5">
                            <span class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-xl text-primary"><i class="bi bi-cloud-arrow-up"></i></span>
                            <span class="text-sm font-medium">Ketuk untuk pilih foto, atau seret ke sini</span>
                            <span data-dropzone-label class="text-xs text-muted-foreground">JPG, PNG, atau WEBP. Bisa beberapa foto sekaligus.</span>
                            <input type="file" name="foto_files[]" id="fotoFilesOp" accept="image/jpeg,image/png,image/webp" multiple required
                                   class="absolute inset-0 size-full cursor-pointer opacity-0" aria-describedby="aiStatusOp">
                        </label>

                        <div id="aiResultsOp" class="grid gap-2 empty:hidden"></div>
                        <p id="aiStatusOp" class="text-sm empty:hidden" aria-live="polite"></p>

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
                    </x-ui.card-content>
                </x-ui.card>
            </form>

            {{-- Ringkasan + kirim (HP: di bawah form). Angka ikut hasil AI / isian manual. --}}
            <aside class="grid min-w-0 gap-4 lg:sticky lg:top-4">
                <x-ui.card class="gap-0 overflow-hidden py-0">
                    <div class="relative overflow-hidden border-b border-emerald-100 bg-linear-to-br from-emerald-50 via-teal-50 to-card px-6 py-5">
                        <i class="bi bi-receipt pointer-events-none absolute -right-3 -top-2 text-7xl text-emerald-600/10" aria-hidden="true"></i>
                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Ringkasan laporan</p>
                        <p data-sum="nominal" class="mt-2 text-3xl font-bold tabular-nums tracking-tight">Rp 0</p>
                        <p class="mt-1 text-xs text-muted-foreground">Total nominal dari struk</p>
                    </div>
                    <dl class="grid gap-3 p-6 text-sm">
                        <div class="flex items-center justify-between gap-3"><dt class="text-muted-foreground"><i class="bi bi-droplet-half"></i> Volume</dt><dd data-sum="volume" class="font-semibold tabular-nums">-</dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-muted-foreground"><i class="bi bi-calendar3"></i> Tanggal isi</dt><dd data-sum="date" class="font-semibold">-</dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-muted-foreground"><i class="bi bi-images"></i> Foto</dt><dd data-sum="files" class="font-semibold tabular-nums">0</dd></div>
                    </dl>
                    <div class="border-t p-6 pt-4">
                        <x-ui.button type="submit" form="formOperational" variant="success" size="lg" class="w-full shadow-lg shadow-success/25">
                            <i class="bi bi-send"></i> Kirim Laporan BBM
                        </x-ui.button>
                        <p class="mt-2 text-center text-xs text-muted-foreground">Satu voucher hanya punya satu laporan.</p>
                    </div>
                </x-ui.card>

                <x-ui.card class="gap-3 py-4">
                    <x-ui.card-content class="px-4">
                        <ul class="grid gap-2.5 text-xs text-muted-foreground">
                            <li class="flex gap-2.5"><span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-blue-50 text-blue-700"><i class="bi bi-stars"></i></span><span class="pt-0.5">AI membaca nominal, volume, dan tanggal dari struk.</span></li>
                            <li class="flex gap-2.5"><span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-amber-50 text-amber-700"><i class="bi bi-pencil-square"></i></span><span class="pt-0.5">Bila AI gagal atau mencapai batas, isi data pada foto yang ditandai kuning.</span></li>
                            <li class="flex gap-2.5"><span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-rose-50 text-rose-700"><i class="bi bi-shield-exclamation"></i></span><span class="pt-0.5">Struk harus sesuai voucher (nominal dan plat nomor).</span></li>
                        </ul>
                    </x-ui.card-content>
                </x-ui.card>
            </aside>
        @endif
    </section>

    <script type="application/json" id="page-data">@json(['analyzeUrl' => route('pelaporan.analyze-struk'), 'csrfToken' => csrf_token(), 'vouchers' => $voucherData])</script>

    @push('scripts')
        @vite('resources/js/pages/bbm/pelaporan.js')
    @endpush
</x-layouts.app>
