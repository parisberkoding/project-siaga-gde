@php
    $isPending = $surat->status === 'pending';
    $isApproved = in_array($surat->status, ['approved', 'completed'], true);
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $jenisLabels = ['dalam_kota' => 'SPD Dalam Kota', 'luar_kota' => 'SPD Luar Kota', 'lainnya' => $surat->jenis_spd_lainnya ?: 'Lainnya'];
@endphp

<x-layouts.app title="Detail Surat Tugas" :breadcrumbs="['Validasi Surat Tugas' => route('surat.superadmin.index'), $surat->nomor_surat => null]">
    <x-ui.page-header kicker="Detail & Validasi" :title="$surat->nomor_surat" :description="$surat->pengemudi_nama . ' · ' . $surat->tempat_tujuan">
        <x-slot:actions>
            <x-spd.status-badge :status="$surat->status" class="px-3 py-1 text-sm" />
            <x-ui.button variant="outline" :href="route('surat.superadmin.index')"><i class="bi bi-arrow-left"></i> Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($surat->is_extension && $surat->parentSurat)
        <x-ui.alert variant="info">
            Ini adalah <strong>Surat Perpanjangan</strong> dari
            <a href="{{ route('surat.superadmin.show', $surat->parentSurat) }}" class="font-medium underline underline-offset-4">{{ $surat->parentSurat->nomor_surat }}</a>.
        </x-ui.alert>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="grid min-w-0 gap-6 lg:col-span-2">
            <form action="{{ route('surat.superadmin.approve', $surat) }}" method="POST" id="approveForm" class="grid gap-6"
                  data-confirm="Menyetujui surat tugas akan mengunci data perjalanan dan menetapkan tenggat LPJ." data-confirm-title="Setujui surat {{ $surat->nomor_surat }}?" data-confirm-text="Ya, setujui" data-confirm-variant="success">
                @csrf

                <x-ui.card>
                    <x-ui.card-header title="Pemberi Tugas" icon="bi bi-person-badge" />
                    <x-ui.card-content>
                        @if ($isPending && empty($surat->pemberi_tugas_nama))
                            <div class="grid gap-4">
                                <x-ui.alert variant="warning">Data pemberi tugas belum diisi. Silakan isi sebelum menyetujui.</x-ui.alert>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <x-ui.field label="Nama" for="pemberi_tugas_nama" required>
                                        <x-ui.input id="pemberi_tugas_nama" name="pemberi_tugas_nama" :value="old('pemberi_tugas_nama')" required placeholder="Nama lengkap pemberi tugas" />
                                    </x-ui.field>
                                    <x-ui.field label="Jabatan" for="pemberi_tugas_jabatan" required>
                                        <x-ui.input id="pemberi_tugas_jabatan" name="pemberi_tugas_jabatan" :value="old('pemberi_tugas_jabatan')" required placeholder="Contoh: HC, GA, & Finance Manager" />
                                    </x-ui.field>
                                </div>
                            </div>
                        @else
                            <dl class="divide-y">
                                <x-ui.detail-row label="Nama"><span class="font-medium">{{ $surat->pemberi_tugas_nama }}</span></x-ui.detail-row>
                                <x-ui.detail-row label="Jabatan">{{ $surat->pemberi_tugas_jabatan }}</x-ui.detail-row>
                            </dl>
                        @endif
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.card-header title="Informasi Perjalanan" icon="bi bi-geo-alt" />
                    <x-ui.card-content class="grid gap-6 md:grid-cols-2">
                        <dl class="divide-y">
                            <x-ui.detail-row label="Pengemudi"><span class="font-medium">{{ $surat->pengemudi_nama }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Tujuan">{{ $surat->tempat_tujuan }}</x-ui.detail-row>
                            <x-ui.detail-row label="Melayani bidang">{{ $surat->melayani_bidang }}</x-ui.detail-row>
                            <x-ui.detail-row label="Kendaraan">
                                <x-ui.badge variant="outline"><i class="bi bi-truck"></i> {{ $surat->vehicle->consumerial_code ?? $surat->vehicle->plat_nomor }}</x-ui.badge>
                                <span class="block text-xs text-muted-foreground">{{ $surat->vehicle->consumerial_name ?? '' }}</span>
                            </x-ui.detail-row>
                        </dl>
                        <dl class="divide-y">
                            <x-ui.detail-row label="Berangkat"><span class="font-medium">{{ $surat->tanggal_berangkat->locale('id')->translatedFormat('l, d F Y') }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Kembali"><span class="font-medium">{{ $surat->tanggal_kembali->locale('id')->translatedFormat('l, d F Y') }}</span></x-ui.detail-row>
                            <x-ui.detail-row label="Lama"><x-ui.badge variant="info">{{ $surat->lama_perjalanan_hari }} hari</x-ui.badge></x-ui.detail-row>
                            <x-ui.detail-row label="Pemakai">{{ $surat->jumlah_pemakai }} orang</x-ui.detail-row>
                        </dl>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.card-header title="Daftar Nama Pemakai" icon="bi bi-people" />
                    <x-ui.card-content>
                        @if ($surat->details->isNotEmpty())
                            <ol class="grid gap-2 sm:grid-cols-2">
                                @foreach ($surat->details as $detail)
                                    <li class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">{{ $loop->iteration }}</span>
                                        {{ $detail->nama_lengkap }}
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <p class="text-sm text-muted-foreground">Tidak ada data penumpang.</p>
                        @endif
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.card-header title="Kode GL & Nominal Pemberian" icon="bi bi-credit-card" />
                    <x-ui.card-content class="grid gap-4">
                        @if ($isPending)
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-ui.field label="Kode GL (SAP)" for="kode_gl">
                                    <x-ui.input id="kode_gl" name="kode_gl" placeholder="Contoh: 5121000" :value="old('kode_gl', $surat->kode_gl)" />
                                </x-ui.field>
                                <x-ui.field label="Uang yang diberikan (Rp)" for="uang_diberikan" required>
                                    <x-ui.input type="number" id="uang_diberikan" name="uang_diberikan" placeholder="Nominal diberikan ke driver" min="0" step="0.01" :value="old('uang_diberikan', $surat->uang_diberikan)" />
                                </x-ui.field>
                                <x-ui.field label="Jenis SPD (override)" for="jenis_spd">
                                    <x-ui.select id="jenis_spd" name="jenis_spd">
                                        <option value="">Ikuti pilihan driver</option>
                                        <option value="dalam_kota" @selected($surat->jenis_spd === 'dalam_kota')>SPD Dalam Kota</option>
                                        <option value="luar_kota" @selected($surat->jenis_spd === 'luar_kota')>SPD Luar Kota</option>
                                        <option value="lainnya" @selected($surat->jenis_spd === 'lainnya')>Lainnya</option>
                                    </x-ui.select>
                                </x-ui.field>
                                <x-ui.field label="Jenis SPD Lainnya" for="jenis_spd_lainnya">
                                    <x-ui.input id="jenis_spd_lainnya" name="jenis_spd_lainnya" placeholder="Isi jika Lainnya" :value="old('jenis_spd_lainnya', $surat->jenis_spd_lainnya)" />
                                </x-ui.field>
                            </div>
                            <x-ui.alert variant="info">Menyetujui surat tugas akan mengunci data perjalanan dan menetapkan tenggat LPJ.</x-ui.alert>
                        @else
                            <dl class="divide-y">
                                <x-ui.detail-row label="Kode GL"><span class="font-medium">{{ $surat->kode_gl ?? '-' }}</span></x-ui.detail-row>
                                <x-ui.detail-row label="Uang diberikan" numeric><span class="font-medium tabular-nums">{{ $rp($surat->uang_diberikan ?? 0) }}</span></x-ui.detail-row>
                                <x-ui.detail-row label="Jenis SPD"><x-ui.badge variant="secondary">{{ $jenisLabels[$surat->jenis_spd] ?? '-' }}</x-ui.badge></x-ui.detail-row>
                            </dl>
                        @endif
                    </x-ui.card-content>
                </x-ui.card>

                @if ($isPending)
                    {{-- Pemberian dana saat validasi: satu baris = satu pemberian/transfer. --}}
                    <x-ui.card>
                        <x-ui.card-header title="Pemberian Dana (Pencairan)" icon="bi bi-wallet2" description="Boleh dikosongkan sekarang dan diisi belakangan dari halaman detail LPJ (Lampiran Pemberian Dana)." />
                        <x-ui.card-content>
                            <x-spd.pencairan-input :rows="$surat->pencairans" :tanggal-default="$surat->tanggal_berangkat?->format('Y-m-d')" />
                        </x-ui.card-content>
                    </x-ui.card>
                @endif
            </form>

            @if ($isApproved)
                @if ($surat->pencairans->isNotEmpty())
                    <x-ui.card>
                        <x-ui.card-header title="Rincian Pemberian Dana" icon="bi bi-cash-stack" />
                        <x-ui.card-content>
                            <x-ui.table>
                                <thead>
                                    <tr><th>No</th><th>Tanggal</th><th class="text-right">Nominal</th><th>Bentuk</th><th>No. Referensi / Rek. Tujuan</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($surat->pencairans as $index => $budget)
                                        <tr>
                                            <td class="text-muted-foreground">{{ $index + 1 }}</td>
                                            <td class="whitespace-nowrap">{{ $budget->tanggal?->translatedFormat('d F Y') ?? '-' }}</td>
                                            <td class="whitespace-nowrap text-right tabular-nums">{{ $rp($budget->nominal) }}</td>
                                            <td><x-ui.badge :variant="$budget->isTransfer() ? 'default' : 'secondary'">{{ $budget->bentuk_pencairan_label }}</x-ui.badge></td>
                                            <td class="text-xs">
                                                @if ($budget->isTransfer())
                                                    {{ $budget->bank_reference ?: '-' }}
                                                    <span class="block text-muted-foreground">{{ $budget->rek_tujuan ?: '-' }}</span>
                                                @else
                                                    <span class="text-muted-foreground">-</span>
                                                @endif
                                                @if ($budget->catatan)
                                                    <span class="block italic text-muted-foreground">{{ $budget->catatan }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t bg-muted/50 font-semibold">
                                        <td colspan="2" class="text-right">Total Dana Diberikan</td>
                                        <td class="whitespace-nowrap text-right tabular-nums">{{ $rp($surat->pencairans->sum('nominal')) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </x-ui.table>
                        </x-ui.card-content>
                    </x-ui.card>
                @elseif ($surat->uang_diberikan > 0)
                    <x-ui.alert variant="info" title="Uang diberikan {{ $rp($surat->uang_diberikan) }}">
                        Belum ada rincian per pencairan. Lengkapi lewat halaman LPJ (Lampiran Pemberian Dana).
                    </x-ui.alert>
                @endif
            @endif

            @if ($surat->catatan)
                <x-ui.card>
                    <x-ui.card-header title="Catatan" icon="bi bi-journal-text" />
                    <x-ui.card-content><p class="whitespace-pre-line text-sm text-muted-foreground">{{ $surat->catatan }}</p></x-ui.card-content>
                </x-ui.card>
            @endif

            @if ($isApproved)
                <x-ui.alert variant="success" title="Disetujui oleh {{ $surat->approver->nama_lengkap ?? '-' }}">
                    <p>{{ \Carbon\Carbon::parse($surat->approved_at)->locale('id')->translatedFormat('l, d F Y') }}</p>
                    @if ($surat->lpj_deadline)
                        <p>Batas LPJ: {{ \Carbon\Carbon::parse($surat->lpj_deadline)->locale('id')->translatedFormat('l, d F Y') }}</p>
                    @endif
                </x-ui.alert>
            @elseif ($surat->status === 'rejected')
                <x-ui.alert variant="destructive" title="Ditolak oleh {{ $surat->rejector->nama_lengkap ?? '-' }}">
                    <p>{{ \Carbon\Carbon::parse($surat->rejected_at)->locale('id')->translatedFormat('l, d F Y') }}</p>
                    <p><strong>Alasan:</strong> {{ $surat->rejection_reason }}</p>
                </x-ui.alert>
            @endif

            @if ($surat->childExtensions?->isNotEmpty())
                <x-ui.card>
                    <x-ui.card-header title="Surat Perpanjangan" icon="bi bi-diagram-3" />
                    <x-ui.card-content>
                        <x-ui.table>
                            <thead><tr><th>Nomor Surat</th><th>Tanggal Kembali Baru</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($surat->childExtensions as $ext)
                                    <tr>
                                        <td class="font-medium">{{ $ext->nomor_surat }}</td>
                                        <td>{{ $ext->tanggal_kembali->translatedFormat('d M Y') }}</td>
                                        <td><x-spd.status-badge :status="$ext->status" /></td>
                                        <td class="text-right"><x-ui.button variant="ghost" size="icon-sm" :href="route('surat.superadmin.show', $ext)" aria-label="Lihat"><i class="bi bi-eye"></i></x-ui.button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    </x-ui.card-content>
                </x-ui.card>
            @endif
        </div>

        <div class="grid gap-6">
            @if ($isPending)
                <x-ui.card class="overflow-hidden pt-0">
                    <div class="bg-primary px-6 py-4 text-primary-foreground">
                        <p class="flex items-center gap-2 font-semibold"><i class="bi bi-shield-check"></i> Aksi Validasi</p>
                    </div>
                    <x-ui.card-content class="grid gap-2">
                        <p class="mb-1 text-sm text-muted-foreground">Setujui atau tolak surat tugas ini.</p>
                        <x-ui.button type="submit" form="approveForm" variant="success"><i class="bi bi-check-circle"></i> Setujui Surat Tugas</x-ui.button>
                        <x-ui.button variant="outline" class="text-destructive hover:bg-destructive-soft hover:text-destructive" data-dialog-open="rejectModal"><i class="bi bi-x-circle"></i> Tolak Surat Tugas</x-ui.button>
                    </x-ui.card-content>
                </x-ui.card>
            @endif

            @if ($isApproved)
                <x-ui.card class="gap-4">
                    <x-ui.card-header title="Dokumen & Dana" icon="bi bi-printer" />
                    <x-ui.card-content class="grid gap-4">
                        <x-ui.button :href="route('surat.superadmin.print', $surat)" target="_blank"><i class="bi bi-printer"></i> Cetak Surat Tugas</x-ui.button>

                        <div class="grid gap-2 border-t pt-4">
                            <p class="flex items-center gap-2 text-sm font-semibold"><i class="bi bi-cash-coin text-warning"></i> Penambahan Dana</p>
                            @forelse ($surat->fundAdditions ?? [] as $fa)
                                <div class="grid gap-0.5 rounded-md border p-2.5 text-sm">
                                    <div class="flex justify-between gap-2"><strong class="tabular-nums">{{ $rp($fa->nominal_tambahan) }}</strong><span class="text-xs text-muted-foreground">{{ $fa->created_at->translatedFormat('d M Y') }}</span></div>
                                    <span class="text-muted-foreground">{{ $fa->alasan }}</span>
                                    @if ($fa->bank_reference)<span class="text-xs text-muted-foreground">Ref: {{ $fa->bank_reference }}</span>@endif
                                    @if ($fa->bukti_transfer_path)<a href="{{ asset('storage/' . $fa->bukti_transfer_path) }}" target="_blank" class="text-xs font-medium text-primary underline-offset-4 hover:underline">Lihat bukti transfer</a>@endif
                                </div>
                            @empty
                                <p class="text-sm text-muted-foreground">Belum ada penambahan dana.</p>
                            @endforelse
                            <x-ui.button variant="outline" data-dialog-open="fundAdditionModal"><i class="bi bi-plus-circle"></i> Tambah Dana</x-ui.button>
                        </div>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card class="gap-4">
                    <x-ui.card-header title="Status LPJ" icon="bi bi-file-earmark-check" />
                    <x-ui.card-content>
                        @if ($surat->lpjPenggantian)
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <span>LPJ sudah disubmit</span>
                                <x-spd.status-badge :status="$surat->lpjPenggantian->status" />
                            </div>
                        @else
                            <x-ui.alert :variant="$surat->isLpjDeadlineOverdue() ? 'destructive' : 'warning'" :title="$surat->isLpjDeadlineOverdue() ? 'LPJ belum disubmit (terlambat)' : 'LPJ belum disubmit'">
                                @if ($surat->lpj_deadline) Batas: {{ $surat->lpj_deadline->translatedFormat('d M Y') }} @endif
                            </x-ui.alert>
                        @endif
                    </x-ui.card-content>
                </x-ui.card>
            @endif

            <x-ui.card class="gap-4">
                <x-ui.card-header title="Informasi Pengajuan" icon="bi bi-info-circle" />
                <x-ui.card-content>
                    <dl class="divide-y">
                        <x-ui.detail-row label="Diajukan oleh">{{ $surat->user->nama_lengkap ?? '-' }}</x-ui.detail-row>
                        <x-ui.detail-row label="Tanggal">{{ $surat->created_at->translatedFormat('d M Y H:i') }}</x-ui.detail-row>
                        <x-ui.detail-row label="Diperbarui">{{ $surat->updated_at->translatedFormat('d M Y H:i') }}</x-ui.detail-row>
                    </dl>
                </x-ui.card-content>
            </x-ui.card>
        </div>
    </div>

    <x-ui.dialog id="rejectModal" title="Tolak Surat Tugas" description="Surat yang ditolak dikembalikan ke pengaju beserta alasannya.">
        <form action="{{ route('surat.superadmin.reject', $surat) }}" method="POST" data-loading class="grid gap-4">
            @csrf
            <div class="rounded-lg border bg-muted/40 p-3 text-sm">
                <p class="font-medium">{{ $surat->nomor_surat }}</p>
                <p class="text-muted-foreground">{{ $surat->pengemudi_nama }} &middot; {{ $surat->tempat_tujuan }}</p>
            </div>
            <x-ui.field label="Alasan Penolakan" for="rejection_reason" required hint="Minimal 10 karakter">
                <x-ui.textarea id="rejection_reason" name="rejection_reason" rows="4" required minlength="10" placeholder="Jelaskan alasan penolakan" />
            </x-ui.field>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                <x-ui.button type="submit" variant="destructive"><i class="bi bi-x-circle"></i> Ya, Tolak Surat</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>

    @if ($isApproved)
        <x-ui.dialog id="fundAdditionModal" title="Tambah Dana">
            <form action="{{ route('surat.superadmin.fund-additions.store', $surat) }}" method="POST" enctype="multipart/form-data" data-loading class="grid gap-4">
                @csrf
                <x-ui.field label="Nominal Tambahan (Rp)" for="nominal_tambahan" required>
                    <x-ui.input type="number" id="nominal_tambahan" name="nominal_tambahan" min="0" step="0.01" required />
                </x-ui.field>
                <x-ui.field label="Alasan" for="alasan" required>
                    <x-ui.textarea id="alasan" name="alasan" rows="3" required minlength="5" placeholder="Alasan penambahan dana" />
                </x-ui.field>
                <x-ui.field label="Referensi Transaksi Bank" for="bank_reference">
                    <x-ui.input id="bank_reference" name="bank_reference" placeholder="Nomor referensi / bukti transfer" />
                </x-ui.field>
                <x-ui.field label="Bukti Transfer (foto, opsional)" for="bukti_transfer">
                    <x-ui.input type="file" id="bukti_transfer" name="bukti_transfer" accept="image/*" />
                </x-ui.field>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                    <x-ui.button type="submit"><i class="bi bi-check-circle"></i> Simpan</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endif

    @if ($isPending)
        @push('scripts')
            @vite('resources/js/pages/spd/surat-detail.js')
        @endpush
    @endif
</x-layouts.app>
