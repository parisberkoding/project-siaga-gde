<x-layouts.app title="Buat Surat Tugas" :breadcrumbs="['Validasi Surat Tugas' => route('surat.superadmin.index'), 'Buat Surat Tugas' => null]">
    <x-ui.page-header kicker="Admin SPD" title="Buat Surat Tugas" description="Surat yang dibuat dari sini langsung disetujui tanpa alur validasi." />

    <form action="{{ route('surat.superadmin.store-shortcut') }}" method="POST" id="formSurat" data-loading class="grid items-start gap-6 lg:grid-cols-3">
        @csrf

        <div class="grid min-w-0 gap-6 lg:col-span-2">
            {{-- Pemberi tugas: dari Master Pekerja atau manual, level AM/Manager --}}
            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Pemberi Tugas" step="1" />
                <x-ui.card-content class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Level Pemberi Tugas" for="pemberiLevel" required error="pemberi_tugas_level">
                        <x-ui.select name="pemberi_tugas_level" id="pemberiLevel" required>
                            <option value="">Pilih level</option>
                            <option value="manager" @selected(old('pemberi_tugas_level') === 'manager')>Manager</option>
                            <option value="assistant_manager" @selected(old('pemberi_tugas_level') === 'assistant_manager')>Assistant Manager</option>
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Ambil dari Master Pekerja (opsional)" for="pemberiEmployee" hint="Pilih pejabat atau biarkan untuk input manual.">
                        <x-ui.select name="pemberi_employee_id" id="pemberiEmployee">
                            <option value="">Input manual</option>
                            @foreach ($pejabatOptions as $pejabat)
                                <option value="{{ $pejabat->id }}" data-nama="{{ $pejabat->employee_name }}" data-jabatan="{{ $pejabat->position ?? '' }}" @selected(old('pemberi_employee_id') == $pejabat->id)>
                                    {{ $pejabat->employee_name }} · {{ $pejabat->position ?? 'Tanpa jabatan' }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Nama Pemberi Tugas" for="pemberiNama" required error="pemberi_tugas_nama">
                        <x-ui.input name="pemberi_tugas_nama" id="pemberiNama" :value="old('pemberi_tugas_nama')" required placeholder="Nama lengkap pemberi tugas" />
                    </x-ui.field>
                    <x-ui.field label="Jabatan" for="pemberiJabatan" required error="pemberi_tugas_jabatan">
                        <x-ui.input name="pemberi_tugas_jabatan" id="pemberiJabatan" :value="old('pemberi_tugas_jabatan')" required placeholder="Contoh: HC, GA, & Finance Manager" />
                    </x-ui.field>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Pengemudi" step="2" />
                <x-ui.card-content class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Ambil dari Master Pekerja (opsional)" for="pengemudiEmployee" hint="Pilih dari Master Pekerja atau biarkan untuk input manual.">
                        <x-ui.select name="pengemudi_employee_id" id="pengemudiEmployee">
                            <option value="">Input manual</option>
                            @foreach ($pengemudiOptions as $peng)
                                <option value="{{ $peng->id }}" data-nama="{{ $peng->employee_name }}" @selected(old('pengemudi_employee_id') == $peng->id)>
                                    {{ $peng->employee_name }} · {{ $peng->position ?? 'Tanpa jabatan' }} ({{ $peng->defaultSite?->name ?? '-' }})
                                </option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Nama Pengemudi" for="pengemudiNama" required error="pengemudi_nama">
                        <x-ui.input name="pengemudi_nama" id="pengemudiNama" :value="old('pengemudi_nama')" required placeholder="Nama lengkap pengemudi" />
                    </x-ui.field>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Informasi Perjalanan" step="3" />
                <x-ui.card-content class="grid gap-4">
                    <x-ui.field label="Tempat Tujuan" for="tempat_tujuan" required error="tempat_tujuan">
                        <x-ui.textarea id="tempat_tujuan" name="tempat_tujuan" rows="2" required>{{ old('tempat_tujuan') }}</x-ui.textarea>
                    </x-ui.field>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-ui.field label="Melayani Bidang" for="melayani_bidang" required error="melayani_bidang">
                            <x-ui.input id="melayani_bidang" name="melayani_bidang" :value="old('melayani_bidang')" required />
                        </x-ui.field>
                        <x-ui.field label="Jumlah Pemakai" for="jumlahPemakai" required error="jumlah_pemakai">
                            <x-ui.input type="number" id="jumlahPemakai" name="jumlah_pemakai" :value="old('jumlah_pemakai', 1)" min="1" max="7" required />
                        </x-ui.field>
                        <x-ui.field label="Tipe SPD" for="tipeSpd" required error="type">
                            <x-ui.select name="type" id="tipeSpd" required>
                                <option value="">Pilih tipe</option>
                                <option value="biasa" @selected(old('type') === 'biasa')>Biasa</option>
                                <option value="mendadak" @selected(old('type') === 'mendadak')>Mendadak</option>
                            </x-ui.select>
                        </x-ui.field>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Jenis SPD" for="jenis_spd" error="jenis_spd">
                            <x-ui.select name="jenis_spd" id="jenis_spd">
                                <option value="">Pilih jenis SPD</option>
                                <option value="luar_kota" @selected(old('jenis_spd') === 'luar_kota')>SPD Luar Kota</option>
                                <option value="dalam_kota" @selected(old('jenis_spd') === 'dalam_kota')>SPD Dalam Kota</option>
                                <option value="lainnya" @selected(old('jenis_spd') === 'lainnya')>Lainnya</option>
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Jenis SPD Lainnya" for="jenis_spd_lainnya" id="jenis_spd_lainnya_wrapper" hidden>
                            <x-ui.input id="jenis_spd_lainnya" name="jenis_spd_lainnya" placeholder="Isi jika Lainnya" :value="old('jenis_spd_lainnya')" />
                        </x-ui.field>
                    </div>
                    <x-ui.field label="Kendaraan" for="vehicle_id" required error="vehicle_id">
                        <x-ui.select name="vehicle_id" id="vehicle_id" required>
                            <option value="">Pilih kendaraan</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>{{ $vehicle->consumerial_code }} - {{ $vehicle->consumerial_name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Kode GL & Nominal Pemberian" step="4" description="Diisi saat penerbitan surat tugas, dapat ditambah kemudian bila ada penambahan dana." />
                <x-ui.card-content class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Kode GL (SAP)" for="kode_gl" error="kode_gl">
                        <x-ui.input id="kode_gl" name="kode_gl" placeholder="Contoh: 5121000" :value="old('kode_gl')" />
                    </x-ui.field>
                    <x-ui.field label="Uang yang Diberikan (Rp)" for="uang_diberikan" error="uang_diberikan">
                        <x-ui.input type="number" id="uang_diberikan" name="uang_diberikan" min="0" step="0.01" placeholder="Nominal uang diberikan ke driver" :value="old('uang_diberikan')" />
                    </x-ui.field>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Jadwal Perjalanan" step="5" />
                <x-ui.card-content class="grid gap-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Tanggal Berangkat" for="tanggalBerangkat" required error="tanggal_berangkat">
                            <x-ui.input type="date" id="tanggalBerangkat" name="tanggal_berangkat" :value="old('tanggal_berangkat')" required />
                        </x-ui.field>
                        <x-ui.field label="Tanggal Kembali" for="tanggalKembali" required error="tanggal_kembali">
                            <x-ui.input type="date" id="tanggalKembali" name="tanggal_kembali" :value="old('tanggal_kembali')" required />
                        </x-ui.field>
                    </div>
                    <x-ui.alert variant="info" id="infoLama" hidden>Lama perjalanan: <strong id="lamaHari">0</strong> hari</x-ui.alert>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Daftar Nama Pemakai" step="6" description="Ketik minimal 2 huruf untuk mencari dari data pekerja, atau tulis nama manual." />
                <x-ui.card-content class="grid gap-2">
                    @for ($i = 1; $i <= 7; $i++)
                        <div data-row-nama="{{ $i }}" class="flex items-center gap-2" @if ($i > 1) hidden @endif>
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-sm font-medium text-muted-foreground">{{ $i }}</span>
                            <x-ui.input name="nama_lengkap_{{ $i }}" data-pemakai-input data-index="{{ $i }}" :data-lookup-url="route('spd.passenger.lookup')"
                                autocomplete="off" placeholder="Ketik nama pemakai ke-{{ $i }}" :value="old('nama_lengkap_' . $i)" />
                            <input type="hidden" name="user_id_{{ $i }}" id="userId{{ $i }}" value="{{ old('user_id_' . $i) }}">
                        </div>
                    @endfor
                </x-ui.card-content>
            </x-ui.card>

            {{-- Pemberian dana: per transfer/pemberian, bukan per hari --}}
            <x-ui.card id="budgetCard" class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Pemberian Dana" step="7" description="Catat setiap kali dana diberikan ke driver (uang muka, transfer susulan, dst). Satu baris = satu pemberian." />
                <x-ui.card-content>
                    <x-spd.pencairan-input :tanggal-default="old('tanggal_berangkat', now()->toDateString())" total-target="summaryBudget" />
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.alert variant="warning" id="mendadakNotice" title="SPD Mendadak" hidden>Pemberian dana akan diisi saat pencetakan surat.</x-ui.alert>

            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Catatan" step="8" description="Opsional" />
                <x-ui.card-content>
                    <x-ui.textarea name="catatan" rows="3" placeholder="Catatan tambahan">{{ old('catatan') }}</x-ui.textarea>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        {{-- Kolom kanan setinggi form: alur ikut tergulir, ringkasan + tombol kirim tetap terlihat. --}}
        <div class="flex flex-col gap-6 lg:self-stretch">
            <x-spd.flow-steps title="Alur penerbitan" :steps="[
                ['bi-lightning-charge', 'Terbitkan surat', 'Surat langsung berstatus disetujui.'],
                ['bi-printer', 'Cetak surat tugas', 'Untuk SPD mendadak, dana diisi saat cetak.'],
                ['bi-car-front', 'Perjalanan dinas', 'Driver upload struk selama perjalanan.'],
                ['bi-receipt', 'LPJ driver', 'Paling lambat <strong>3 hari</strong> setelah kembali.'],
            ]" />

            <div class="lg:sticky lg:top-20">
                <x-spd.surat-summary title="Ringkasan Surat" badge="Langsung disetujui" badge-icon="bi bi-lightning-charge-fill">
                    <x-slot:rows>
                        <div class="flex items-center justify-between gap-3 border-t pt-3">
                            <dt class="flex items-center gap-2 font-semibold">
                                <span class="flex size-7 items-center justify-center rounded-md bg-success-soft text-success"><i class="bi bi-wallet2"></i></span>Total dana diberikan
                            </dt>
                            <dd id="summaryBudget" class="font-bold tabular-nums text-success">Rp 0</dd>
                        </div>
                    </x-slot:rows>
                    <x-ui.alert variant="success">Surat dari form ini langsung <strong>disetujui</strong> tanpa antrian validasi.</x-ui.alert>
                    <div class="grid gap-2">
                        <x-ui.button type="submit" variant="success" data-loading-text="Menyimpan..."><i class="bi bi-send"></i> Buat & Setujui Langsung</x-ui.button>
                        <x-ui.button variant="ghost" :href="route('surat.superadmin.index')"><i class="bi bi-arrow-left"></i> Batal</x-ui.button>
                    </div>
                </x-spd.surat-summary>
            </div>
        </div>
    </form>

    @push('scripts')
        @vite('resources/js/pages/spd/surat-shortcut.js')
    @endpush
</x-layouts.app>
