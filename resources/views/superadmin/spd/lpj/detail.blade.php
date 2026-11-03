@php
    $surat = $laporan->suratSpd;
    $user = Auth::user();
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $canValidate = $user->hasPermission('lpj.validate');
    $canPencairan = $user->hasPermission('lpj.pencairan');
    // Halaman ini dipakai antrean GA dan antrean Management; tautan kembali mengikuti konteks
    // supaya Management tanpa permission lpj.validate tidak mendapat 403.
    $indexRoute = request()->routeIs('lpj.management.*') ? 'lpj.management.index' : 'lpj.superadmin.index';
    // BUG-LPJ-002: jumlah efektif = nilai tersimpan, atau total pencairan dana SPD bila belum diisi.
    // Tanpa data dana sama sekali, pakai nilai tersimpan supaya tidak tampil kekurangan penuh.
    $jumlahDiberikan = $laporan->jumlah_diberikan_efektif;
    $selisih = $jumlahDiberikan - (float) $laporan->total_pengeluaran;
    $pengembalian = $jumlahDiberikan > 0 ? max(0, $selisih) : (float) $laporan->pengembalian;
    $kekurangan = $jumlahDiberikan > 0 ? abs(min(0, $selisih)) : (float) $laporan->kekurangan;
@endphp

<x-layouts.app title="Detail LPJ" :breadcrumbs="[(request()->routeIs('lpj.management.*') ? 'Persetujuan Akhir LPJ' : 'Pemeriksaan LPJ (GA)') => route($indexRoute), 'Detail' => null]">
    <x-ui.page-header kicker="Laporan Pertanggungjawaban" :title="$surat->nomor_surat ?? 'Detail LPJ'" :description="$laporan->nama . ' · ' . ($surat->tempat_tujuan ?? '-')">
        <x-slot:actions>
            <x-spd.status-badge :status="$laporan->status" class="px-3 py-1 text-sm" />
            @if ($laporan->is_late)<x-ui.badge variant="danger" class="px-3 py-1 text-sm"><i class="bi bi-clock"></i> Terlambat {{ $laporan->days_late }} hari</x-ui.badge>@endif
            @if ($laporan->is_over_budget)<x-ui.badge variant="warning" class="px-3 py-1 text-sm"><i class="bi bi-exclamation-triangle"></i> Over Budget</x-ui.badge>@endif
            @if ($surat?->is_extension)<x-ui.badge variant="info" class="px-3 py-1 text-sm"><i class="bi bi-diagram-3"></i> Perpanjangan</x-ui.badge>@endif
            <x-ui.button variant="outline" :href="route($indexRoute)"><i class="bi bi-arrow-left"></i> Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="grid min-w-0 gap-6 lg:col-span-2">
            <div class="grid gap-6 md:grid-cols-2">
                <x-ui.card>
                    <x-ui.card-header title="Surat Tugas" icon="bi bi-file-earmark-text" />
                    <x-ui.card-content>
                        <dl class="divide-y">
                            <x-ui.detail-row label="Nomor">
                                <span class="font-medium">{{ $surat->nomor_surat ?? '-' }}</span>
                                @if ($surat?->is_extension && $surat->parentSurat)
                                    <span class="block text-xs text-muted-foreground">Perpanjangan dari {{ $surat->parentSurat->nomor_surat }}</span>
                                @endif
                            </x-ui.detail-row>
                            <x-ui.detail-row label="Tujuan">{{ $surat->tempat_tujuan ?? '-' }}</x-ui.detail-row>
                            <x-ui.detail-row label="Tanggal">
                                @if ($surat)
                                    {{ $surat->tanggal_berangkat->translatedFormat('d M Y') }} - {{ $surat->tanggal_kembali->translatedFormat('d M Y') }}
                                    <span class="block text-xs text-muted-foreground">{{ $surat->lama_perjalanan_hari }} hari</span>
                                @else
                                    -
                                @endif
                            </x-ui.detail-row>
                        </dl>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.card-header title="Pelapor & Tanggal" icon="bi bi-person" />
                    <x-ui.card-content>
                        <dl class="divide-y">
                            <x-ui.detail-row label="Nama">
                                <span class="font-medium">{{ $laporan->nama }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $laporan->jabatan }}</span>
                            </x-ui.detail-row>
                            <x-ui.detail-row label="Kendaraan">
                                <x-ui.badge variant="outline"><i class="bi bi-truck"></i> {{ $laporan->nomor_polisi }}</x-ui.badge>
                                <span class="block text-xs text-muted-foreground">{{ $laporan->vehicle->consumerial_name ?? '' }}</span>
                            </x-ui.detail-row>
                            <x-ui.detail-row label="Tanggal LPJ">{{ $laporan->tanggal_lpj?->translatedFormat('d M Y') ?? '-' }}</x-ui.detail-row>
                            <x-ui.detail-row label="Batas">
                                @if ($laporan->due_date)
                                    <span @class(['font-medium text-destructive' => $laporan->is_late])>{{ $laporan->due_date->translatedFormat('d M Y') }}</span>
                                @else
                                    -
                                @endif
                            </x-ui.detail-row>
                        </dl>
                    </x-ui.card-content>
                </x-ui.card>
            </div>

            @if ($laporan->uraian)
                <x-ui.card>
                    <x-ui.card-header title="Uraian Perjalanan" icon="bi bi-journal-text" />
                    <x-ui.card-content><p class="whitespace-pre-line text-sm">{{ $laporan->uraian }}</p></x-ui.card-content>
                </x-ui.card>
            @endif

            <x-ui.card>
                <x-ui.card-header title="Rincian Pengeluaran" icon="bi bi-list-check" :description="$laporan->details->count() . ' item'" />
                <x-ui.card-content>
                    <x-ui.table>
                        <thead>
                            <tr><th class="w-12">No</th><th>Tanggal</th><th>Tipe</th><th class="text-right">Nominal</th><th>Struk</th><th>Keterangan</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($laporan->details as $index => $detail)
                                @php $struk = $detail->strukItem; @endphp
                                <tr>
                                    <td class="text-muted-foreground">{{ $index + 1 }}</td>
                                    <td class="whitespace-nowrap">{{ $detail->tanggal?->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            <x-spd.tipe-badge :tipe="$detail->tipe_penggunaan" />
                                            @if ($struk?->kategori_source === 'ai')
                                                <x-ui.badge variant="info" title="Dianalisis AI/OCR"><i class="bi bi-stars"></i> AI</x-ui.badge>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap text-right tabular-nums">{{ $rp($detail->nominal) }}</td>
                                    <td>
                                        @if ($struk?->path_image)
                                            <a href="{{ $struk->path_url }}" target="_blank" title="Klik untuk perbesar" class="grid w-fit gap-1">
                                                <img src="{{ $struk->path_url }}" alt="{{ $struk->item_code }}"
                                                     class="h-14 w-auto max-w-28 cursor-zoom-in rounded-md border object-cover"
                                                     onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                                                <span hidden class="inline-flex items-center gap-1 text-xs text-primary"><i class="bi bi-image"></i> Lihat struk</span>
                                            </a>
                                            <code class="mt-1 block max-w-32 truncate text-[10px] text-muted-foreground" title="{{ $struk->item_code }}">{{ $struk->item_code }}</code>
                                        @else
                                            <span class="text-muted-foreground">-</span>
                                        @endif
                                    </td>
                                    <td class="text-muted-foreground">{{ $detail->keterangan ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-8 text-center text-muted-foreground">Tidak ada data pengeluaran.</td></tr>
                            @endforelse
                        </tbody>
                    </x-ui.table>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Ringkasan Keuangan" icon="bi bi-calculator" />
                <x-ui.card-content class="grid gap-6 md:grid-cols-2">
                    <dl class="divide-y">
                        <x-ui.detail-row label="Biaya BBM" numeric><span class="tabular-nums">{{ $rp($laporan->total_biaya_bbm) }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Biaya Tol" numeric><span class="tabular-nums">{{ $rp($laporan->total_biaya_tol) }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Biaya Portir" numeric><span class="tabular-nums">{{ $rp($laporan->total_biaya_portir) }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Biaya Lain-lain" numeric><span class="tabular-nums">{{ $rp($laporan->total_biaya_lain) }}</span></x-ui.detail-row>
                        <x-ui.detail-row label="Total" numeric><span class="font-bold tabular-nums">{{ $rp($laporan->total_pengeluaran) }}</span></x-ui.detail-row>
                    </dl>
                    <div class="grid content-start gap-4">
                        <dl class="divide-y">
                            <x-ui.detail-row label="Uang dari" numeric>{{ $laporan->uang_dari ?? 'KAS' }}</x-ui.detail-row>
                            <x-ui.detail-row label="Jumlah diberikan" numeric><span class="tabular-nums">{{ $rp($jumlahDiberikan) }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Pengembalian" numeric><span @class(['tabular-nums', 'font-semibold text-success' => $pengembalian > 0])>{{ $rp($pengembalian) }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Kekurangan" numeric><span @class(['tabular-nums', 'font-semibold text-warning' => $kekurangan > 0])>{{ $rp($kekurangan) }}</span></x-ui.detail-row>
                        </dl>
                        @if ($surat)
                            <x-ui.alert :variant="$laporan->is_over_budget ? 'warning' : 'info'">
                                <p>Budget per hari <strong>{{ $rp($laporan->budget_per_hari) }}</strong>, rata-rata per hari <strong>{{ $rp($laporan->total_pengeluaran / max(1, $surat->lama_perjalanan_hari)) }}</strong>@if ($laporan->is_over_budget) (over budget)@endif.</p>
                            </x-ui.alert>
                        @endif
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            {{-- Data keuangan: diubah oleh admin GA / keuangan --}}
            <x-ui.card>
                <x-ui.card-header title="Data Keuangan" icon="bi bi-pencil-square" description="Untuk keuangan / GA. Pengembalian dan kekurangan dihitung otomatis dari jumlah diberikan dikurangi total pengeluaran." />
                <x-ui.card-content class="grid gap-4">
                    <form action="{{ route('lpj.superadmin.keuangan', $laporan) }}" method="POST" data-loading class="grid items-end gap-3 sm:grid-cols-[1fr_1fr_auto]">
                        @csrf
                        @method('PUT')
                        <x-ui.field label="Uang Dari" for="uang_dari">
                            <x-ui.input id="uang_dari" name="uang_dari" :value="$laporan->uang_dari ?? 'KAS'" />
                        </x-ui.field>
                        <x-ui.field label="Jumlah Diberikan (Rp)" for="jumlah_diberikan"
                            :hint="(float) $laporan->jumlah_diberikan > 0
                                ? 'Total pencairan dana SPD: ' . $rp($laporan->total_pencairan) . '.'
                                : ($laporan->total_pencairan > 0
                                    ? 'Terisi otomatis dari total pencairan dana SPD (' . $rp($laporan->total_pencairan) . '). Simpan untuk menerapkan.'
                                    : 'Belum ada pencairan dana SPD untuk surat ini.')">
                            <x-ui.input type="number" id="jumlah_diberikan" name="jumlah_diberikan" :value="$jumlahDiberikan" min="0" step="0.01" />
                        </x-ui.field>
                        <x-ui.button type="submit" variant="outline"><i class="bi bi-check2"></i> Simpan</x-ui.button>
                    </form>

                    @if ($canPencairan)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-muted/40 p-3">
                            <div class="text-sm">
                                <p class="font-medium">Pencairan Dana (Tunai / Transfer)</p>
                                <p class="text-muted-foreground">Kelola rincian pencairan, totalnya otomatis menjadi Jumlah Diberikan.</p>
                            </div>
                            <x-ui.button size="sm" :href="route('lpj.superadmin.pencairan.index', $laporan)"><i class="bi bi-cash-stack"></i> Kelola Pencairan</x-ui.button>
                        </div>
                    @endif
                </x-ui.card-content>
            </x-ui.card>

            @if ($laporan->status === 'approved' && $laporan->approverManagement)
                <x-ui.alert variant="success" title="Disetujui oleh {{ $laporan->approverManagement->nama_lengkap ?? $laporan->approverManagement->name ?? '-' }} (Management)">
                    {{ $laporan->approved_management_at?->translatedFormat('d F Y H:i') ?? '-' }}
                </x-ui.alert>
            @elseif ($laporan->status === 'approved')
                {{-- LPJ lama yang sudah final sebelum alur 3 tahap --}}
                <x-ui.alert variant="success" title="Disetujui oleh {{ $laporan->approverPengguna->nama_lengkap ?? $laporan->approverPengguna->name ?? '-' }}">
                    {{ $laporan->approved_pengguna_at?->translatedFormat('d F Y H:i') ?? '-' }}
                </x-ui.alert>
            @elseif ($laporan->status === 'rejected')
                <x-ui.alert variant="destructive" title="Ditolak oleh {{ $laporan->rejector->nama_lengkap ?? $laporan->rejector->name ?? '-' }}">
                    <p>{{ $laporan->rejected_at?->translatedFormat('d F Y H:i') ?? '-' }}</p>
                    <p><strong>Alasan:</strong> {{ $laporan->rejection_reason }}</p>
                </x-ui.alert>
            @endif
        </div>

        <div class="grid gap-6">
            {{-- Tiga tanda tangan dicatat terpisah. User dan GA tidak harus berurutan; Management final setelah keduanya. --}}
            <x-ui.card class="gap-4">
                <x-ui.card-header title="Progress Validasi" icon="bi bi-diagram-3" />
                <x-ui.card-content class="grid gap-3">
                    <ol class="grid gap-2">
                        @foreach ([
                            ['User (Penumpang)', 'bi-person-check', $laporan->approved_by_pengguna],
                            ['Divisi GA', 'bi-shield-check', $laporan->approved_by_divisi_ga],
                            ['Management (Final)', 'bi-person-badge', $laporan->approved_by_management],
                        ] as [$label, $icon, $done])
                            <li class="flex items-center gap-3 rounded-lg border p-2.5 text-sm">
                                <span @class(['flex size-8 items-center justify-center rounded-full', 'bg-success-soft text-success' => $done, 'bg-muted text-muted-foreground' => ! $done])><i class="bi {{ $done ? 'bi-check-lg' : $icon }}"></i></span>
                                <span class="flex-1 font-medium">{{ $label }}</span>
                                <x-ui.badge :variant="$done ? 'success' : 'muted'">{{ $done ? 'Disetujui' : 'Menunggu' }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ol>
                    <p class="text-xs text-muted-foreground">LPJ baru berstatus Disetujui setelah User, Divisi GA, dan Management menyelesaikan validasinya.</p>
                </x-ui.card-content>
            </x-ui.card>

            @if ($laporan->status === 'pending')
                <x-ui.card class="overflow-hidden pt-0">
                    <div class="bg-primary px-6 py-4 text-primary-foreground">
                        <p class="flex items-center gap-2 font-semibold"><i class="bi bi-shield-check"></i> Aksi Validasi</p>
                    </div>
                    <x-ui.card-content class="grid gap-2">
                        @if ($laporan->is_late || $laporan->is_over_budget)
                            <x-ui.alert variant="warning" title="Perhatian" class="mb-1">
                                <ul class="list-inside list-disc">
                                    @if ($laporan->is_late)<li>LPJ diajukan terlambat {{ $laporan->days_late }} hari</li>@endif
                                    @if ($laporan->is_over_budget)<li>Pengeluaran melebihi budget harian</li>@endif
                                </ul>
                            </x-ui.alert>
                        @endif

                        {{-- Tahap User (penumpang): tidak menunggu tahap lain --}}
                        @if ($user->hasPermission('spd.validate_trip') && ! $laporan->approved_by_pengguna)
                            <form action="{{ route('lpj.pengguna.approve', $laporan) }}" method="POST" class="grid"
                                  data-confirm="Saya mengetahui bahwa perjalanan dinas ini benar adanya." data-confirm-title="Validasi sebagai User (penumpang)?" data-confirm-text="Ya, validasi" data-confirm-variant="success">
                                @csrf
                                <input type="hidden" name="pernyataan" value="1">
                                <x-ui.button type="submit" variant="success"><i class="bi bi-person-check"></i> Validasi sebagai User</x-ui.button>
                            </form>
                        @endif

                        {{-- Tahap Divisi GA: boleh lebih dulu --}}
                        @if ($canValidate && ! $laporan->approved_by_divisi_ga)
                            <form action="{{ route('lpj.superadmin.approve-ga', $laporan) }}" method="POST" class="grid"
                                  data-confirm="Validasi LPJ ini sebagai Divisi GA (admin/supervisor/manager)." data-confirm-title="Validasi sebagai Divisi GA?" data-confirm-text="Ya, validasi">
                                @csrf
                                <x-ui.button type="submit"><i class="bi bi-shield-check"></i> Validasi sebagai Divisi GA</x-ui.button>
                            </form>
                        @endif

                        {{-- Tahap Management: final, setelah User & Divisi GA --}}
                        @if ($user->hasPermission('lpj.validate_management') && ! $laporan->approved_by_management)
                            @if ($laporan->approved_by_pengguna && $laporan->approved_by_divisi_ga)
                                <form action="{{ route('lpj.management.approve', $laporan) }}" method="POST" class="grid"
                                      data-confirm="Dokumen akan siap dicetak setelah validasi final ini." data-confirm-title="Validasi final sebagai Management?" data-confirm-text="Ya, validasi" data-confirm-variant="success">
                                    @csrf
                                    <x-ui.button type="submit" variant="success"><i class="bi bi-person-badge"></i> Validasi Final sebagai Management</x-ui.button>
                                </form>
                            @else
                                <x-ui.alert variant="info">Validasi final Management menunggu validasi User dan Divisi GA selesai.</x-ui.alert>
                            @endif
                        @endif

                        @if ($canValidate)
                            <x-ui.button variant="outline" class="text-destructive hover:bg-destructive-soft hover:text-destructive" data-dialog-open="rejectModal"><i class="bi bi-x-circle"></i> Tolak LPJ</x-ui.button>
                        @endif
                    </x-ui.card-content>
                </x-ui.card>
            @endif

            @if ($laporan->status === 'approved')
                <x-ui.card class="gap-4">
                    <x-ui.card-header title="Cetak Dokumen" icon="bi bi-printer" />
                    <x-ui.card-content class="grid gap-2">
                        <x-ui.button :href="route('spd.laporan.export', $laporan)" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Cetak SPD + LPJ</x-ui.button>
                        @if ($canPencairan)
                            <x-ui.button variant="outline" :href="route('spd.laporan.pemberian-dana', $laporan)" target="_blank"><i class="bi bi-cash-stack"></i> Cetak Lembar Pemberian Dana (GA)</x-ui.button>
                        @endif
                    </x-ui.card-content>
                </x-ui.card>
            @endif

            <x-ui.card class="gap-4">
                <x-ui.card-header title="Informasi Pengajuan" icon="bi bi-info-circle" />
                <x-ui.card-content>
                    <dl class="divide-y">
                        <x-ui.detail-row label="Diajukan oleh">{{ $laporan->user->nama_lengkap ?? $laporan->user->name ?? '-' }}</x-ui.detail-row>
                        <x-ui.detail-row label="Tanggal">{{ $laporan->created_at->translatedFormat('d M Y H:i') }}</x-ui.detail-row>
                        <x-ui.detail-row label="Diperbarui">{{ $laporan->updated_at->translatedFormat('d M Y H:i') }}</x-ui.detail-row>
                    </dl>
                </x-ui.card-content>
            </x-ui.card>

            @if ($laporan->catatan)
                <x-ui.card class="gap-4">
                    <x-ui.card-header title="Catatan" icon="bi bi-journal-text" />
                    <x-ui.card-content><p class="whitespace-pre-line text-sm">{{ $laporan->catatan }}</p></x-ui.card-content>
                </x-ui.card>
            @endif
        </div>
    </div>

    @if ($canValidate)
        <x-ui.dialog id="rejectModal" title="Tolak LPJ" description="LPJ yang ditolak dikembalikan ke driver untuk direvisi.">
            <form action="{{ route('lpj.superadmin.reject', $laporan) }}" method="POST" data-loading class="grid gap-4">
                @csrf
                <div class="rounded-lg border bg-muted/40 p-3 text-sm">
                    <p class="font-medium">{{ $surat->nomor_surat ?? '-' }}</p>
                    <p class="text-muted-foreground">{{ $laporan->nama }} &middot; {{ $rp($laporan->total_pengeluaran) }}</p>
                </div>
                <x-ui.field label="Alasan Penolakan" for="rejection_reason" required hint="Minimal 10 karakter">
                    <x-ui.textarea id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Jelaskan alasan penolakan" />
                </x-ui.field>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                    <x-ui.button type="submit" variant="destructive"><i class="bi bi-x-circle"></i> Ya, Tolak LPJ</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endif
</x-layouts.app>
