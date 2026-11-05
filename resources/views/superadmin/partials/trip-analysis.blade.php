{{--
  Analisis Trip & Efisiensi BBM (SuperadminController::indexTripAnalysis). Satu baris = jarak antara dua pengisian BBM
  kendaraan yang sama. Interaksi (pilih massal, dialog validasi, detail transaksi): resources/js/pages/vehicle/trip-analysis.js.
  Foto struk dimuat lewat struk_url (rute ber-autentikasi) saat detail dibuka, bukan base64 di setiap baris.
--}}
@php
    $variant = fn ($badge) => match ($badge) {
        'primary' => 'default',
        'secondary' => 'muted',
        default => $badge,
    };
    $avgDev = (float) $stats['avgDeviation'];
    $devTone = $avgDev < -20 ? 'danger' : ($avgDev < -10 ? 'warning' : 'success');
    $num = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
    $filtered = $alertLevel || $vehicleId || $requesterId || $validated || $search;
@endphp

<x-layouts.app title="Analisis Efisiensi Trip" :breadcrumbs="['BBM Operasional' => route('modules.dashboard.bbm'), 'Analisis Efisiensi Trip' => null]">
    <x-ui.page-header kicker="Laporan & Analisis" icon="bi bi-signpost-2" title="Analisis Efisiensi Trip"
        description="Jarak, konsumsi BBM, dan deviasi efisiensi antar pengisian. Trip dengan jarak di atas 300 km, isi ulang terlalu cepat, atau deviasi di bawah minus 20 persen perlu divalidasi.">
        <x-slot:actions>
            <form action="{{ route('superadmin.trip-analysis.superadmin.trip-validations.recalculate-all') }}" method="POST" data-loading
                  data-confirm="Semua metrik trip dihitung ulang dari data pengisian. Proses ini bisa memakan waktu." data-confirm-title="Hitung ulang semua metrik?" data-confirm-text="Ya, hitung ulang">
                @csrf
                <x-ui.button type="submit" variant="outline" data-loading-text="Menghitung..."><i class="bi bi-arrow-repeat"></i> Hitung Ulang Metrik</x-ui.button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($errors->any())
        <x-ui.alert variant="destructive" title="Validasi trip gagal disimpan">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.stat-card label="Trip Teranalisis" :value="$num($stats['total'])" icon="bi bi-map" hint="Sudah dihitung efisiensinya" />
        <x-ui.stat-card label="Perlu Validasi" :value="$num($stats['needValidation'])" icon="bi bi-clipboard-check" :tone="$stats['needValidation'] ? 'warning' : 'muted'"
            hint="Jarak 300 km atau lebih, isi ulang cepat, atau boros" :href="route('superadmin.trip-analysis.index', ['validated' => 'false'])" />
        <x-ui.stat-card label="Perlu Perhatian" :value="$num($stats['warning'])" icon="bi bi-flag" :tone="$stats['warning'] ? 'warning' : 'muted'"
            hint="Level peringatan" :href="route('superadmin.trip-analysis.index', ['alert_level' => 'warning'])" />
        <x-ui.stat-card label="Kritis" :value="$num($stats['critical'])" icon="bi bi-shield-exclamation" :tone="$stats['critical'] ? 'danger' : 'muted'"
            hint="Level kritis" :href="route('superadmin.trip-analysis.index', ['alert_level' => 'critical'])" />
        <x-ui.stat-card label="Rata-rata Efisiensi" :value="$num($stats['avgEfficiency'], 2) . ' km/L'" icon="bi bi-speedometer2" tone="success" hint="Efisiensi aktual seluruh trip" />
        <x-ui.stat-card label="Rata-rata Deviasi" :value="$num($avgDev, 2) . '%'" icon="bi bi-graph-down" :tone="$devTone" hint="Terhadap patokan efisiensi" />
    </div>

    <x-ui.card class="gap-4 py-4">
        <x-ui.card-content class="px-4 sm:px-6">
            {{-- Filter langsung terkirim saat diubah (data-autosubmit); isian kosong tidak ikut ke URL. --}}
            <form method="GET" action="{{ route('superadmin.trip-analysis.index') }}" data-autosubmit data-clean-query class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto] lg:items-end">
                <x-ui.field label="Cari" for="tripSearch">
                    <div class="relative">
                        <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i>
                        <x-ui.input type="search" id="tripSearch" name="search" :value="$search" placeholder="Kode, nama kendaraan, atau driver" class="w-full pl-9" />
                    </div>
                </x-ui.field>
                <x-ui.field label="Level" for="alert_level">
                    <x-ui.select id="alert_level" name="alert_level">
                        <option value="">Semua level</option>
                        <option value="normal" @selected($alertLevel === 'normal')>Normal</option>
                        <option value="warning" @selected($alertLevel === 'warning')>Perlu Perhatian</option>
                        <option value="critical" @selected($alertLevel === 'critical')>Kritis</option>
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="Kendaraan" for="vehicle_id">
                    <x-ui.select id="vehicle_id" name="vehicle_id">
                        <option value="">Semua kendaraan</option>
                        @foreach ($vehicles as $v)
                            <option value="{{ $v->id }}" @selected($vehicleId == $v->id)>{{ $v->consumerial_code }} · {{ $v->consumerial_name }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="Driver" for="requester_id">
                    <x-ui.select id="requester_id" name="requester_id">
                        <option value="">Semua driver</option>
                        @foreach ($drivers as $d)
                            <option value="{{ $d->id }}" @selected($requesterId == $d->id)>{{ $d->nama_lengkap }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="Validasi" for="validated">
                    <x-ui.select id="validated" name="validated">
                        <option value="">Semua status</option>
                        <option value="false" @selected($validated === 'false')>Belum divalidasi</option>
                        <option value="true" @selected($validated === 'true')>Sudah divalidasi</option>
                    </x-ui.select>
                </x-ui.field>
                <x-ui.button variant="outline" :href="route('superadmin.trip-analysis.index')" :aria-disabled="$filtered ? null : 'true'"><i class="bi bi-arrow-counterclockwise"></i> Reset</x-ui.button>
            </form>
        </x-ui.card-content>
    </x-ui.card>

    <x-ui.card class="gap-4">
        <x-ui.card-header title="Data Trip" icon="bi bi-table" :description="$num($tripAnalyses->total()) . ' trip, urut dari jeda isi ulang tersingkat'" />
        <x-ui.card-content class="grid gap-4">
            {{-- Validasi massal: id terpilih dikirim sebagai validation_ids[], jenis perjalanan dari tombol yang diklik. --}}
            <form method="POST" action="{{ route('superadmin.trip-validations.bulk-validate') }}" id="bulkForm" data-loading
                  data-confirm="Trip terpilih akan divalidasi sekaligus dengan jenis perjalanan yang dipilih." data-confirm-title="Validasi trip terpilih?" data-confirm-text="Ya, validasi">
                @csrf
            </form>

            <x-ui.table>
                <thead>
                    <tr>
                        <th class="w-10"><x-ui.checkbox id="checkAll" aria-label="Pilih semua trip yang belum divalidasi" /></th>
                        <th>Driver & Kendaraan</th>
                        <th>Periode</th>
                        <th class="text-right">Jarak</th>
                        <th class="text-right">BBM</th>
                        <th class="text-right">Efisiensi</th>
                        <th class="text-right">Deviasi</th>
                        <th class="min-w-32">Pemakaian Jangkauan</th>
                        <th>Level</th>
                        <th class="text-right">Validasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tripAnalyses as $trip)
                        @php
                            $prev = $trip->transactionProofPrevious;
                            $last = $trip->transactionProofLatest;
                            $vehicle = $prev?->permohonan?->consumerialTool;
                            $range = $trip->range_usage_percent;
                            $proof = fn ($p, $odo, $pos) => json_encode([
                                'position' => $pos,
                                'request' => $p?->permohonan?->request_number,
                                'transaction' => $p?->transaction_id,
                                'datetime' => $p?->purchase_datetime?->translatedFormat('d M Y H:i'),
                                'odo' => $num($odo) . ' km',
                                'volume' => $num($p?->fuel_volume, 2) . ' L',
                                'fuel' => $trip->fuel_type,
                                'struk' => $p?->struk_url,
                            ]);
                        @endphp
                        <tr>
                            <td>
                                @unless ($trip->validated_at)
                                    <x-ui.checkbox class="trip-checkbox" value="{{ $trip->id }}" aria-label="Pilih trip {{ $vehicle->consumerial_code ?? $trip->id }}" />
                                @endunless
                            </td>
                            <td>
                                <span class="block font-medium">{{ $prev?->permohonan?->requester?->nama_lengkap ?? 'Tidak diketahui' }}</span>
                                <span class="block text-xs"><span class="font-medium text-primary">{{ $vehicle->consumerial_code ?? '-' }}</span> <span class="text-muted-foreground">{{ $vehicle->consumerial_name ?? '' }}</span></span>
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                <button type="button" data-proof="{{ $proof($prev, $trip->previous_odo, 'previous') }}" class="cursor-pointer font-medium text-primary underline-offset-4 hover:underline">{{ $prev?->purchase_datetime?->translatedFormat('d M y') ?? '-' }}</button>
                                <i class="bi bi-arrow-right text-muted-foreground"></i>
                                <button type="button" data-proof="{{ $proof($last, $trip->latest_odo, 'latest') }}" class="cursor-pointer font-medium text-success underline-offset-4 hover:underline">{{ $last?->purchase_datetime?->translatedFormat('d M y') ?? '-' }}</button>
                                <span class="block text-muted-foreground">{{ $trip->days_between_refuel }} hari</span>
                            </td>
                            <td class="text-right">
                                <x-ui.badge :variant="$trip->diff_odo >= 300 ? 'danger' : 'success'" class="tabular-nums">{{ $num($trip->diff_odo) }} km</x-ui.badge>
                            </td>
                            <td class="whitespace-nowrap text-right tabular-nums">{{ $trip->fuel_volume !== null ? $num($trip->fuel_volume, 2) . ' L' : '-' }}</td>
                            <td class="whitespace-nowrap text-right">
                                @if ($trip->actual_efficiency)
                                    <span @class(['font-semibold tabular-nums', 'text-success' => $trip->efficiency_status === 'good', 'text-warning' => $trip->efficiency_status === 'fair', 'text-destructive' => $trip->efficiency_status === 'poor'])>{{ $num($trip->actual_efficiency, 2) }} km/L</span>
                                    <span class="block text-xs text-muted-foreground">patokan {{ $trip->expected_efficiency !== null ? $num($trip->expected_efficiency, 1) : '-' }}@if ($trip->baseline_override) <span title="Patokan disesuaikan admin">*</span>@endif</span>
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($trip->efficiency_deviation_percent !== null)
                                    <x-ui.badge :variant="$variant($trip->efficiency_status_badge)" class="tabular-nums">{{ $num($trip->efficiency_deviation_percent, 1) }}%</x-ui.badge>
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($range !== null)
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted" role="progressbar" aria-label="Pemakaian jangkauan tangki" aria-valuenow="{{ round($range) }}" aria-valuemin="0" aria-valuemax="100">
                                            <div @class(['h-full rounded-full', 'bg-destructive' => $trip->is_premature_refuel, 'bg-warning' => ! $trip->is_premature_refuel && $range < 80, 'bg-success' => ! $trip->is_premature_refuel && $range >= 80]) style="width: {{ min($range, 100) }}%"></div>
                                        </div>
                                        <span class="w-10 text-right text-xs tabular-nums">{{ round($range) }}%</span>
                                    </div>
                                    @if ($trip->is_premature_refuel)<span class="text-xs font-medium text-destructive">Isi ulang terlalu cepat</span>@endif
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td><x-ui.badge :variant="$variant($trip->alert_level_badge)">{{ $trip->alert_level_label }}</x-ui.badge></td>
                            <td class="text-right">
                                @if ($trip->validated_at)
                                    <x-ui.badge :variant="$variant($trip->status_badge)">{{ $trip->status_label }}</x-ui.badge>
                                    @if ($trip->long_trip_notes)
                                        <span class="mt-1 block max-w-40 truncate text-xs text-muted-foreground" title="{{ $trip->long_trip_notes }}">{{ $trip->long_trip_notes }}</span>
                                    @endif
                                @else
                                    <x-ui.button size="sm" data-validate-trip
                                        :data-url="route('superadmin.trip-validations.validate', $trip->id)"
                                        :data-info="($vehicle->consumerial_code ?? '-') . ' · ' . $num($trip->diff_odo) . ' km · ' . $trip->fuel_type"
                                        :data-fuel="$trip->fuel_type">
                                        <i class="bi bi-check2-circle"></i> Validasi
                                    </x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10">
                                <x-ui.empty-state icon="bi bi-inbox" title="Belum ada data trip"
                                    :description="$filtered ? 'Tidak ada trip yang cocok dengan filter. Ubah atau reset filter.' : 'Data muncul setelah ada dua pengisian BBM pada kendaraan yang sama. Jalankan Hitung Ulang Metrik bila data lama belum terhitung.'" class="border-0" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>

            {{ $tripAnalyses->withQueryString()->links('vendor.pagination.shadcn') }}

            <div id="bulkBar" hidden class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-card/95 p-3 shadow-lg backdrop-blur">
                <p class="flex items-center gap-2 text-sm">
                    <span id="selectedCount" class="flex h-6 min-w-6 items-center justify-center rounded-full bg-primary px-2 text-xs font-semibold text-primary-foreground">0</span>
                    trip dipilih
                    <button type="button" data-clear-selection class="text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">Batal pilih</button>
                </p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button type="submit" form="bulkForm" name="is_long_trip" value="0" variant="outline"><i class="bi bi-house"></i> Perjalanan Normal</x-ui.button>
                    <x-ui.button type="submit" form="bulkForm" name="is_long_trip" value="1" variant="success"><i class="bi bi-briefcase"></i> Perjalanan Dinas</x-ui.button>
                </div>
            </div>
        </x-ui.card-content>
    </x-ui.card>

    {{-- Dialog validasi satu trip: action diisi JS dari tombol Validasi. --}}
    <x-ui.dialog id="validationDialog" title="Validasi Trip" size="lg">
        <form method="POST" id="validationForm" data-loading class="grid gap-5">
            @csrf
            <div class="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/40 p-3 text-sm">
                <i class="bi bi-truck text-primary"></i><span class="font-medium" data-trip-info>-</span>
            </div>

            <fieldset class="grid gap-2">
                <legend class="mb-2 text-sm font-medium">Jenis perjalanan <span class="text-destructive">*</span></legend>
                @foreach ([['1', 'bi-briefcase', 'Perjalanan Dinas', 'Luar kota, misalnya Jakarta atau Dieng'], ['0', 'bi-house', 'Perjalanan Normal', 'Dalam kota atau sekitar area kerja']] as [$val, $icon, $label, $hint])
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-accent has-checked:border-primary has-checked:bg-accent">
                        <input type="radio" name="is_long_trip" value="{{ $val }}" required class="mt-1 accent-primary">
                        <span><span class="flex items-center gap-1.5 text-sm font-medium"><i class="bi {{ $icon }}"></i> {{ $label }}</span><span class="text-xs text-muted-foreground">{{ $hint }}</span></span>
                    </label>
                @endforeach
            </fieldset>

            <x-ui.field label="Tujuan / keperluan (opsional)" for="long_trip_notes" hint="Isi bila perjalanan dinas. Maksimal 500 karakter.">
                <x-ui.textarea id="long_trip_notes" name="long_trip_notes" rows="2" maxlength="500" placeholder="Contoh: inspeksi lapangan Dieng" />
            </x-ui.field>

            <fieldset class="grid gap-4 rounded-lg border border-warning/40 bg-warning-soft/40 p-4">
                <legend class="px-1 text-sm font-semibold"><i class="bi bi-speedometer2 text-warning"></i> Patokan efisiensi BBM</legend>
                <x-ui.field label="Patokan khusus trip ini (km/L)" for="baselineOverride">
                    <x-ui.input type="number" id="baselineOverride" name="baseline_override" step="0.1" min="1" max="30" placeholder="Kosongkan untuk patokan standar" />
                    <p class="text-xs text-muted-foreground" data-baseline-default></p>
                </x-ui.field>
                <x-ui.field label="Alasan perubahan patokan" for="baselineReason" hint="Wajib diisi bila patokan diubah, sebagai jejak audit." error="baseline_override_reason">
                    <x-ui.textarea id="baselineReason" name="baseline_override_reason" rows="2" maxlength="500" placeholder="Contoh: jalan pegunungan, macet parah, membawa beban berat" />
                </x-ui.field>
            </fieldset>
        </form>
        <x-slot:footer>
            <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
            <x-ui.button type="submit" form="validationForm" data-loading-text="Menyimpan..."><i class="bi bi-check2-circle"></i> Simpan Validasi</x-ui.button>
        </x-slot:footer>
    </x-ui.dialog>

    {{-- Dialog detail transaksi pengisian: isi dari atribut data-proof tombol tanggal. --}}
    <x-ui.dialog id="proofDialog" title="Detail Transaksi" size="lg">
        <div class="grid gap-4">
            <x-ui.badge variant="info" data-proof-position class="w-fit">-</x-ui.badge>
            <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                @foreach (['request' => 'Nomor permohonan', 'transaction' => 'ID transaksi', 'datetime' => 'Waktu pengisian', 'fuel' => 'Jenis BBM', 'odo' => 'Odometer', 'volume' => 'Volume BBM'] as $key => $label)
                    <div><dt class="text-xs text-muted-foreground">{{ $label }}</dt><dd class="break-all font-medium" data-proof-field="{{ $key }}">-</dd></div>
                @endforeach
            </dl>
            <div class="grid gap-2">
                <p class="text-sm font-medium">Foto struk</p>
                <a data-proof-link target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border bg-muted/40" title="Buka ukuran penuh di tab baru">
                    <img data-proof-img alt="Foto struk BBM" loading="lazy" class="mx-auto max-h-96 object-contain">
                </a>
                <p data-proof-empty hidden class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"><i class="bi bi-image-alt"></i> Tidak ada foto struk</p>
            </div>
        </div>
    </x-ui.dialog>

    @push('scripts')
        @vite('resources/js/pages/vehicle/trip-analysis.js')
    @endpush
</x-layouts.app>
