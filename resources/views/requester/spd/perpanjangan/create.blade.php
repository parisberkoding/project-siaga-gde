<x-layouts.app title="Ajukan Perpanjangan" :breadcrumbs="['Perpanjangan' => route('requester.perpanjangan.index'), 'Ajukan' => null]">
    <x-ui.page-header kicker="Surat Tugas" title="Ajukan Perpanjangan Perjalanan Dinas" description="Perpanjangan membuat surat baru dengan LPJ sendiri, surat lama tetap berlaku." />

    @if ($suratList->isEmpty())
        <x-ui.card>
            <x-ui.card-content>
                <x-ui.empty-state icon="bi bi-file-earmark-x" title="Tidak ada surat yang bisa diperpanjang" description="Perpanjangan hanya bisa diajukan untuk surat yang sudah disetujui.">
                    <x-ui.button variant="outline" class="mt-2" :href="route('requester.surat.index')"><i class="bi bi-arrow-left"></i> Kembali</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card-content>
        </x-ui.card>
    @else
        <form action="{{ route('requester.perpanjangan.store') }}" method="POST" id="formPerpanjangan" data-loading class="grid items-start gap-6 lg:grid-cols-3">
            @csrf

            <x-ui.card class="lg:col-span-2">
                <x-ui.card-header title="Form Perpanjangan" icon="bi bi-clock-history" />
                <x-ui.card-content class="grid gap-5">
                    <x-ui.field label="Surat yang akan diperpanjang" for="selectSurat" required error="surat_spd_id">
                        <x-ui.select name="surat_spd_id" id="selectSurat" required>
                            <option value="">Pilih surat tugas</option>
                            @foreach ($suratList as $surat)
                                <option value="{{ $surat->id }}"
                                    data-tujuan="{{ $surat->tempat_tujuan }}"
                                    data-berangkat="{{ $surat->tanggal_berangkat->translatedFormat('d M Y') }}"
                                    data-kembali="{{ $surat->tanggal_kembali->format('Y-m-d') }}"
                                    data-kembali-formatted="{{ $surat->tanggal_kembali->translatedFormat('d M Y') }}"
                                    data-lama="{{ $surat->lama_perjalanan_hari }}"
                                    data-budget="{{ $surat->budget_per_hari }}"
                                    @selected(old('surat_spd_id', request('surat_id')) == $surat->id)>
                                    {{ $surat->nomor_surat }} - {{ Str::limit($surat->tempat_tujuan, 30) }}
                                    @if ($surat->getExtensionCount() > 0) (sudah {{ $surat->getExtensionCount() }}x perpanjangan) @endif
                                </option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <div id="infoSurat" hidden class="grid gap-3 rounded-lg border bg-muted/40 p-4 text-sm sm:grid-cols-2">
                        <div><span class="block text-xs text-muted-foreground">Tujuan</span><span id="infoTujuan">-</span></div>
                        <div><span class="block text-xs text-muted-foreground">Tanggal perjalanan</span><span id="infoTanggal">-</span></div>
                        <div><span class="block text-xs text-muted-foreground">Tanggal kembali sekarang</span><strong id="infoKembali" class="text-primary">-</strong></div>
                        <div><span class="block text-xs text-muted-foreground">Budget per hari</span><strong id="infoBudget">-</strong></div>
                    </div>

                    <x-ui.field label="Tanggal Kembali Baru" for="tanggalKembaliBaru" required error="tanggal_kembali_baru" hint="Harus setelah tanggal kembali sebelumnya">
                        <x-ui.date-picker name="tanggal_kembali_baru" id="tanggalKembaliBaru" :value="old('tanggal_kembali_baru')" required />
                    </x-ui.field>

                    <div id="infoPerpanjangan" hidden class="grid divide-y rounded-lg border border-success/30 bg-success-soft text-center sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                        <div class="p-3"><span class="block text-xs text-muted-foreground">Durasi tambahan</span><span class="text-xl font-bold text-success" id="durasiTambahan">-</span> <span class="text-xs">hari</span></div>
                        <div class="p-3"><span class="block text-xs text-muted-foreground">Budget tambahan</span><span class="text-xl font-bold text-success" id="budgetTambahan">-</span></div>
                        <div class="p-3"><span class="block text-xs text-muted-foreground">Batas LPJ baru</span><span class="text-xl font-bold text-success" id="deadlineBaru">-</span></div>
                    </div>

                    <x-ui.field label="Alasan Perpanjangan" for="alasan_perpanjangan" required error="alasan_perpanjangan" hint="Minimal 10 karakter">
                        <x-ui.textarea id="alasan_perpanjangan" name="alasan_perpanjangan" rows="4" required minlength="10" placeholder="Jelaskan alasan perjalanan perlu diperpanjang">{{ old('alasan_perpanjangan') }}</x-ui.textarea>
                    </x-ui.field>
                </x-ui.card-content>
            </x-ui.card>

            <div class="grid gap-6">
                <x-ui.card class="gap-4">
                    <x-ui.card-header title="Kirim Perpanjangan" icon="bi bi-send" description="Pengajuan akan menunggu persetujuan admin." />
                    <x-ui.card-content class="grid gap-2">
                        <x-ui.button type="submit" variant="success" data-loading-text="Mengirim..."><i class="bi bi-send"></i> Ajukan Perpanjangan</x-ui.button>
                        <x-ui.button variant="outline" :href="route('requester.surat.index')"><i class="bi bi-arrow-left"></i> Batal</x-ui.button>
                    </x-ui.card-content>
                </x-ui.card>

                <x-ui.card class="gap-3">
                    <x-ui.card-header title="Informasi" icon="bi bi-info-circle" />
                    <x-ui.card-content>
                        <ul class="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                            <li>Perpanjangan akan membuat surat baru</li>
                            <li>Surat lama tetap valid</li>
                            <li>Setiap surat memiliki LPJ masing-masing</li>
                            <li>Batas LPJ baru = tanggal kembali baru + 3 hari</li>
                        </ul>
                    </x-ui.card-content>
                </x-ui.card>
            </div>
        </form>

        @push('scripts')
            @vite('resources/js/pages/spd/perpanjangan-create.js')
        @endpush
    @endif
</x-layouts.app>
