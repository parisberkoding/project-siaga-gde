<x-layouts.app title="Buat Surat Tugas" :breadcrumbs="['Daftar Surat Tugas' => route('requester.surat.index'), 'Buat Surat' => null]">
    <x-ui.page-header kicker="Surat Tugas" title="Buat Surat Tugas Perjalanan Dinas" description="Isi data perjalanan, budget ditentukan admin saat persetujuan." />

    <form action="{{ route('requester.surat.store') }}" method="POST" id="formSurat" data-loading class="grid items-start gap-6 lg:grid-cols-3">
        @csrf

        <div class="grid gap-6 lg:col-span-2">
            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Informasi Perjalanan" step="1" />
                <x-ui.card-content class="grid gap-4">
                    <x-ui.field label="Tempat Tujuan" for="tempat_tujuan" required error="tempat_tujuan">
                        <x-ui.textarea id="tempat_tujuan" name="tempat_tujuan" rows="2" required placeholder="Contoh: Kantor PLN Bandung, Jl. Asia Afrika No. 123">{{ old('tempat_tujuan') }}</x-ui.textarea>
                    </x-ui.field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Jenis SPD" for="jenis_spd" required error="jenis_spd">
                            <x-ui.select id="jenis_spd" name="jenis_spd">
                                <option value="">Pilih jenis SPD</option>
                                <option value="luar_kota" @selected(old('jenis_spd') === 'luar_kota')>SPD Luar Kota</option>
                                <option value="dalam_kota" @selected(old('jenis_spd') === 'dalam_kota')>SPD Dalam Kota</option>
                                <option value="lainnya" @selected(old('jenis_spd') === 'lainnya')>Lainnya</option>
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Jenis SPD Lainnya" for="jenis_spd_lainnya" id="jenis_spd_lainnya_wrapper" hidden>
                            <x-ui.input id="jenis_spd_lainnya" name="jenis_spd_lainnya" placeholder="Contoh: Dinas Internasional" :value="old('jenis_spd_lainnya')" />
                        </x-ui.field>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Melayani Bidang" for="melayani_bidang" required error="melayani_bidang">
                            <x-ui.input id="melayani_bidang" name="melayani_bidang" :value="old('melayani_bidang')" required placeholder="Contoh: Operasional, Teknik" />
                        </x-ui.field>
                        <x-ui.field label="Jumlah Pemakai" for="jumlahPemakai" required error="jumlah_pemakai" hint="Maksimal 7 orang">
                            <x-ui.input type="number" id="jumlahPemakai" name="jumlah_pemakai" :value="old('jumlah_pemakai', 1)" min="1" max="7" required />
                        </x-ui.field>
                    </div>

                    <x-ui.field label="Kendaraan" for="vehicle_id" required error="vehicle_id">
                        <x-ui.select id="vehicle_id" name="vehicle_id" required>
                            <option value="">Pilih kendaraan</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                    {{ $vehicle->consumerial_code ?? $vehicle->plat_nomor }} - {{ $vehicle->consumerial_name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Jadwal Perjalanan" step="2" />
                <x-ui.card-content class="grid gap-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Tanggal Berangkat" for="tanggalBerangkat" required error="tanggal_berangkat">
                            <x-ui.input type="date" id="tanggalBerangkat" name="tanggal_berangkat" :value="old('tanggal_berangkat')" required :min="date('Y-m-d')" />
                        </x-ui.field>
                        <x-ui.field label="Tanggal Kembali" for="tanggalKembali" required error="tanggal_kembali">
                            <x-ui.input type="date" id="tanggalKembali" name="tanggal_kembali" :value="old('tanggal_kembali')" required />
                        </x-ui.field>
                    </div>
                    <x-ui.alert variant="info" id="infoLama" hidden>Lama perjalanan: <strong id="lamaHari">0</strong> hari</x-ui.alert>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Daftar Nama Pemakai" step="3" description="Ketik minimal 2 huruf untuk mencari dari data pekerja, atau tulis nama manual." />
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

            <x-ui.card class="transition-shadow focus-within:border-primary/40 focus-within:shadow-md">
                <x-ui.card-header title="Catatan" step="4" description="Opsional" />
                <x-ui.card-content>
                    <x-ui.textarea name="catatan" rows="3" placeholder="Catatan tambahan jika diperlukan">{{ old('catatan') }}</x-ui.textarea>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        {{-- Kolom kanan setinggi form: alur ikut tergulir, ringkasan + tombol kirim tetap terlihat. --}}
        <div class="flex flex-col gap-6 lg:self-stretch">
            <x-spd.flow-steps :steps="[
                ['bi-send', 'Ajukan surat tugas', 'Isi form ini lalu kirim.'],
                ['bi-shield-check', 'Validasi admin GA', 'Pemberi tugas dan dana ditetapkan.'],
                ['bi-car-front', 'Perjalanan dinas', 'Upload struk selama perjalanan.'],
                ['bi-receipt', 'Kirim LPJ', 'Paling lambat <strong>3 hari</strong> setelah kembali.'],
            ]" />

            <div class="lg:sticky lg:top-20">
                <x-spd.surat-summary badge="Menunggu validasi GA">
                    <x-ui.alert variant="info">Budget ditentukan admin saat persetujuan.</x-ui.alert>
                    <div class="grid gap-2">
                        <x-ui.button type="submit" variant="success" data-loading-text="Mengirim..."><i class="bi bi-send"></i> Ajukan Surat Tugas</x-ui.button>
                        <x-ui.button variant="ghost" :href="route('requester.surat.index')"><i class="bi bi-arrow-left"></i> Batal</x-ui.button>
                    </div>
                </x-spd.surat-summary>
            </div>
        </div>
    </form>

    @push('scripts')
        @vite('resources/js/pages/spd/surat-create.js')
    @endpush
</x-layouts.app>
