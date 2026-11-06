{{--
  Koreksi Transaksi (superadmin.permohonan): ubah / batalkan / hapus permohonan dan laporan BBM. Filter lewat GET
  (paginate 20 di server); Select shadcn mengirim form saat dipilih (ui/forms.js data-autosubmit). JS: pages/bbm/kelola-permohonan.js.
--}}
@php
    $statusLabels = ['pending' => 'Menunggu', 'pending_vehicle_approval' => 'Menunggu kendaraan', 'approved' => 'Disetujui', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled_by_driver' => 'Dibatalkan'];
    $statusOptions = [['value' => '', 'label' => 'Semua status'], ...collect($statusLabels)->map(fn ($l, $v) => ['value' => $v, 'label' => $l])->values()->all()];
    $initials = fn ($name) => collect(explode(' ', trim($name ?? '')))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('') ?: '?';
    $fuelTones = ['Pertamax' => 'bg-blue-50 text-blue-700 ring-blue-200', 'Pertalite' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'Pertamina Dex' => 'bg-orange-50 text-orange-700 ring-orange-200', 'Dexlite' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'Pertamax Turbo' => 'bg-rose-50 text-rose-700 ring-rose-200'];
    $filtered = filled($search) || filled($status ?? null);
@endphp

<x-layouts.app title="Koreksi Transaksi" :breadcrumbs="['BBM Operasional' => route('admin-dashboard'), 'Koreksi Transaksi' => null]">
    <x-ui.page-header kicker="Persetujuan" title="Koreksi Transaksi" description="Ubah, batalkan, atau hapus permohonan dan laporan pembelian BBM yang salah input." />

    <x-ui.tabs-list>
        <x-ui.tabs-link :href="route('superadmin.permohonan', ['tab' => 'permohonan'])" :active="$tab == 'permohonan'">
            <i class="bi bi-file-earmark-text"></i> Permohonan
        </x-ui.tabs-link>
        <x-ui.tabs-link :href="route('superadmin.permohonan', ['tab' => 'laporan'])" :active="$tab == 'laporan'">
            <i class="bi bi-receipt"></i> Laporan Transaksi
        </x-ui.tabs-link>
    </x-ui.tabs-list>

    @if ($tab == 'permohonan')
        {{-- Kartu filter terpisah dari tabel (pola tab Menunggu di Permohonan Masuk). --}}
        <div class="grid gap-2 rounded-xl border bg-card p-3 shadow-xs">
                <form method="GET" action="{{ route('superadmin.permohonan') }}" data-autosubmit
                      class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_14rem]">
                    <input type="hidden" name="tab" value="permohonan">
                    <div class="relative"><i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i><x-ui.input type="search" name="search" :value="$search" class="w-full pl-9" placeholder="Cari nomor, pemohon, atau kendaraan" aria-label="Cari permohonan" /></div>
                    <x-ui.select-menu id="filterStatus" name="status" :value="$status ?? ''" :options="$statusOptions" icon="bi bi-funnel" aria-label="Filter status" />
                </form>
                <div class="flex flex-wrap items-center justify-between gap-2 px-1 text-sm text-muted-foreground">
                    <p aria-live="polite"><span class="font-medium tabular-nums text-foreground">{{ $permohonans->total() }}</span> permohonan @if ($filtered) sesuai filter @endif</p>
                    @if ($filtered)
                        <a href="{{ route('superadmin.permohonan', ['tab' => 'permohonan']) }}" class="inline-flex items-center gap-1 font-medium text-primary hover:underline"><i class="bi bi-x-circle"></i> Reset filter</a>
                    @endif
                </div>
        </div>

                {{-- Kartu tabel: tabel rapat ke tepi, halaman jadi footer. Bar keputusan massal di luar kartu supaya sticky tetap jalan. --}}
                <form method="POST" action="{{ route('superadmin.permohonan.bulk-process') }}" id="bulkApprovalForm" class="grid gap-3">
                    @csrf
                    <input type="hidden" name="decisions" id="decisionsInput">

                    <x-ui.card class="gap-0 overflow-hidden py-0">
                    <x-ui.table dense class="table-sticky-actions max-sm:[&_td]:whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="w-10">
                                    @if ($permohonans->contains('status', 'pending'))
                                        <x-ui.checkbox data-select-all aria-label="Pilih semua permohonan menunggu" />
                                    @endif
                                </th>
                                <th>Permohonan</th>
                                <th>Pemohon</th>
                                <th>Kendaraan/Alat</th>
                                <th class="text-right">Nominal</th>
                                <th>Status</th>
                                <th>Catatan / Voucher</th>
                                <th class="w-20 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($permohonans as $p)
                                @php
                                    $isPending = $p->status === 'pending';
                                    $summary = [
                                        'number' => $p->request_number,
                                        'name' => $p->requester->nama_lengkap ?? 'Unknown',
                                        'vehicle' => trim(($p->vehicle->consumerial_code ?? 'N/A') . ' · ' . ($p->vehicle->consumerial_name ?? ''), ' ·'),
                                        'amount' => 'Rp ' . number_format($p->bill_amounts, 0, ',', '.'),
                                    ];
                                @endphp
                                {{-- Baris menunggu: aksen kuning di kiri; diputuskan dari menu Aksi atau dipilih untuk keputusan massal. --}}
                                <tr @if ($isPending) data-decision-item="{{ $p->id }}" data-summary="{{ json_encode($summary) }}" class="has-[[data-select]:checked]:bg-primary/5" @endif>
                                    <td @class(['shadow-[inset_3px_0_0_var(--color-warning)]' => $isPending])>
                                        @if ($isPending)
                                            <x-ui.checkbox data-select aria-label="Pilih permohonan {{ $p->request_number }}" />
                                        @endif
                                    </td>
                                    <td>
                                        <code class="whitespace-nowrap text-xs">{{ $p->request_number }}</code>
                                        <span class="block text-xs text-muted-foreground">{{ $p->request_date->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td>
                                        <span class="flex items-center gap-2.5">
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-primary to-module-spd text-[11px] font-semibold text-white">{{ $initials($p->requester->nama_lengkap ?? '') }}</span>
                                            <span class="min-w-0">
                                                <span class="block whitespace-nowrap font-medium">{{ $p->requester->nama_lengkap ?? 'Unknown' }}</span>
                                                <span class="block text-xs text-muted-foreground">{{ $p->requester->username ?? '-' }}</span>
                                            </span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="whitespace-nowrap font-medium">{{ $p->vehicle->consumerial_code ?? 'N/A' }}</span>
                                        <span class="block whitespace-nowrap text-xs text-muted-foreground">{{ $p->vehicle->consumerial_name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap text-right">
                                        <span class="font-semibold tabular-nums">Rp {{ number_format($p->bill_amounts, 0, ',', '.') }}</span>
                                        <span class="mt-0.5 flex justify-end"><span class="inline-flex items-center rounded-md px-1.5 py-px text-[11px] font-medium ring-1 {{ $fuelTones[$p->gasoline_type] ?? 'bg-muted text-muted-foreground ring-border' }}">{{ $p->gasoline_type }}</span></span>
                                    </td>
                                    <td><x-bbm.status-badge :status="$p->status" /></td>
                                    <td class="max-w-60">
                                        @if ($p->approvedRequest?->voucher_code)
                                            <span class="inline-flex items-center gap-1.5 rounded-md border border-dashed bg-muted/40 px-2 py-0.5 font-mono text-xs"><i class="bi bi-ticket-perforated text-primary"></i>{{ $p->approvedRequest->voucher_code }}</span>
                                        @endif
                                        @if ($p->authorizer_notes)
                                            <span class="block truncate text-xs text-muted-foreground" title="{{ $p->authorizer_notes }}"><i class="bi bi-chat-left-text"></i> {{ $p->authorizer_notes }}</span>
                                        @elseif (! $p->approvedRequest?->voucher_code)
                                            <span class="text-xs text-muted-foreground">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{-- Tombol batal/hapus memakai atribut form= karena formnya berada di luar form keputusan (form tidak boleh bersarang). --}}
                                        <x-ui.dropdown-menu :id="'aksi-permohonan-' . $p->id" :label="'Aksi permohonan ' . $p->request_number">
                                            <div class="px-2.5 pb-1.5 pt-1 text-xs text-muted-foreground"><code>{{ $p->request_number }}</code></div>
                                            @if ($isPending)
                                                <x-ui.dropdown-item icon="bi bi-check-circle" variant="success" data-decide="approve">Setujui</x-ui.dropdown-item>
                                                <x-ui.dropdown-item icon="bi bi-x-circle" variant="destructive" data-decide="reject">Tolak</x-ui.dropdown-item>
                                                <x-ui.dropdown-separator />
                                            @endif
                                            <x-ui.dropdown-item icon="bi bi-pencil" data-edit-permohonan
                                                data-update-url="{{ route('superadmin.permohonan.update', $p->id) }}"
                                                data-request-number="{{ $p->request_number }}"
                                                data-vehicle-id="{{ $p->consumerial_tools_id }}"
                                                data-gasoline-type="{{ $p->gasoline_type }}"
                                                data-bill-amounts="{{ $p->bill_amounts }}"
                                                data-requester-notes="{{ $p->requester_notes }}"
                                                data-authorizer-notes="{{ $p->authorizer_notes }}">Ubah permohonan</x-ui.dropdown-item>
                                            @unless (in_array($p->status, ['completed', 'cancelled_by_driver']))
                                                <x-ui.dropdown-item icon="bi bi-slash-circle" type="submit" :form="'cancel-permohonan-' . $p->id">Batalkan</x-ui.dropdown-item>
                                            @endunless
                                            @if ($p->status != 'completed')
                                                <x-ui.dropdown-separator />
                                                <x-ui.dropdown-item icon="bi bi-trash" variant="destructive" type="submit" :form="'delete-permohonan-' . $p->id">Hapus permanen</x-ui.dropdown-item>
                                            @endif
                                        </x-ui.dropdown-menu>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-12 text-center text-muted-foreground">
                                    <i class="bi bi-inbox mb-2 block text-2xl"></i>
                                    {{ $filtered ? 'Tidak ada permohonan yang cocok dengan filter.' : 'Belum ada data permohonan.' }}
                                </td></tr>
                            @endforelse
                        </tbody>
                    </x-ui.table>
                    @if ($permohonans->hasPages())
                        <div class="border-t px-4 py-3">{{ $permohonans->appends(['tab' => 'permohonan', 'status' => $status, 'search' => $search])->onEachSide(1)->links('vendor.pagination.shadcn') }}</div>
                    @endif
                    </x-ui.card>

                    <div id="bulkActionBar" hidden class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-card/95 p-3 shadow-lg backdrop-blur">
                        <p class="flex items-center gap-2 text-sm">
                            <span class="flex h-6 min-w-6 items-center justify-center rounded-full bg-primary px-2 text-xs font-semibold text-primary-foreground" id="selectedCount">0</span>
                            permohonan dipilih
                            <button type="button" data-clear-selection class="text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">Batal pilih</button>
                        </p>
                        <div class="flex gap-2">
                            <x-ui.button variant="outline" data-decide-selected="reject" class="text-destructive hover:bg-destructive-soft hover:text-destructive"><i class="bi bi-x-circle"></i> Tolak terpilih</x-ui.button>
                            <x-ui.button variant="success" data-decide-selected="approve"><i class="bi bi-check-circle"></i> Setujui terpilih</x-ui.button>
                        </div>
                    </div>
                </form>

                <x-bbm.decision-dialog />

                @foreach ($permohonans as $p)
                    <form id="cancel-permohonan-{{ $p->id }}" action="{{ route('superadmin.permohonan.cancel', $p->id) }}" method="POST" hidden
                          data-confirm="Permohonan {{ $p->request_number }} akan dibatalkan." data-confirm-title="Batalkan permohonan?" data-confirm-text="Ya, batalkan" data-confirm-variant="destructive">
                        @csrf
                        <input type="hidden" name="cancel_reason" value="cancel_by_superadmin">
                    </form>
                    <form id="delete-permohonan-{{ $p->id }}" action="{{ route('superadmin.permohonan.delete', $p->id) }}" method="POST" hidden
                          data-confirm="Permohonan {{ $p->request_number }} akan dihapus permanen." data-confirm-title="Hapus permohonan?" data-confirm-text="Ya, hapus" data-confirm-variant="destructive">
                        @csrf
                    </form>
                @endforeach

        <x-ui.dialog id="editPermohonanDialog" title="Ubah permohonan" size="lg" static>
            <form id="editPermohonanForm" method="POST" class="grid gap-4">
                @csrf
                <x-ui.alert variant="info">Nomor permohonan <strong id="editRequestNumber"></strong></x-ui.alert>

                @php
                    $vehicleOptions = $vehicles->map(fn ($v) => ['value' => (string) $v->id, 'label' => "{$v->dropdown_label} ({$v->consumerial_type})"])->values()->all();
                    $vehicleBudgets = $vehicles->mapWithKeys(fn ($v) => [$v->id => (float) ($v->detail->nominal_full_tank ?? 0)]);
                    $fuelOptions = collect($gasolineTypes)->pluck('gasoline_name')->whenEmpty(fn () => collect(['Pertamax', 'Pertamina Dex', 'Pertalite']))
                        ->map(fn ($n) => ['value' => $n, 'label' => $n])->values()->all();
                @endphp
                <script type="application/json" id="vehicle-budgets">@json($vehicleBudgets)</script>
                <x-ui.field label="Kendaraan/Alat" for="editVehicleId" required>
                    <x-ui.select-menu id="editVehicleId" name="consumerial_tools_id" :options="$vehicleOptions" placeholder="Pilih kendaraan/alat" icon="bi bi-truck" searchable required />
                    <p id="editVehicleBudgetInfo" class="text-xs text-muted-foreground"></p>
                </x-ui.field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Jenis BBM" for="editGasolineType" required>
                        <x-ui.select-menu id="editGasolineType" name="gasoline_type" :options="$fuelOptions" placeholder="Pilih jenis BBM" icon="bi bi-fuel-pump" required />
                    </x-ui.field>
                    <x-ui.field label="Nominal (Rp)" for="editBillAmounts" required>
                        {{-- Opsi nominal disusun JS sesuai budget kendaraan; satu opsi awal jadi templat gaya. --}}
                        <x-ui.select-menu id="editBillAmounts" name="bill_amounts" :options="[['value' => '', 'label' => 'Pilih nominal']]" placeholder="Pilih nominal" icon="bi bi-cash-coin" required />
                    </x-ui.field>
                </div>

                <x-ui.field label="Catatan pemohon" for="editRequesterNotes">
                    <x-ui.textarea name="requester_notes" id="editRequesterNotes" rows="2" />
                </x-ui.field>
                <x-ui.field label="Catatan admin" for="editAuthorizerNotes">
                    <x-ui.textarea name="authorizer_notes" id="editAuthorizerNotes" rows="2" />
                </x-ui.field>
            </form>
            <x-slot:footer>
                <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                <x-ui.button type="submit" form="editPermohonanForm"><i class="bi bi-save"></i> Simpan perubahan</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($tab == 'laporan')
        {{-- Kartu filter terpisah dari tabel, sama seperti tab Permohonan. --}}
        <div class="grid gap-2 rounded-xl border bg-card p-3 shadow-xs">
                <form method="GET" action="{{ route('superadmin.permohonan') }}" data-autosubmit>
                    <input type="hidden" name="tab" value="laporan">
                    <div class="relative"><i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i><x-ui.input type="search" name="search" :value="$search" class="w-full pl-9" placeholder="Cari ID transaksi atau nomor permohonan" aria-label="Cari laporan" /></div>
                </form>
                <div class="flex flex-wrap items-center justify-between gap-2 px-1 text-sm text-muted-foreground">
                    <p aria-live="polite"><span class="font-medium tabular-nums text-foreground">{{ $laporans->total() }}</span> laporan @if (filled($search)) sesuai pencarian @endif</p>
                    @if (filled($search))
                        <a href="{{ route('superadmin.permohonan', ['tab' => 'laporan']) }}" class="inline-flex items-center gap-1 font-medium text-primary hover:underline"><i class="bi bi-x-circle"></i> Reset</a>
                    @endif
                </div>

        </div>

        <x-ui.card class="gap-0 overflow-hidden py-0">
                <x-ui.table dense class="table-sticky-actions max-sm:[&_td]:whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Transaksi</th>
                            <th>Permohonan</th>
                            <th>Pemohon</th>
                            <th>Kendaraan</th>
                            <th class="text-right">Nominal</th>
                            <th class="text-right">Pengisian</th>
                            <th class="w-20 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporans as $lap)
                            @php
                                $perm = $lap->permohonan;
                                $struk = $lap->struk_url;
                                $odometer = $lap->odometer_url;
                            @endphp
                            <tr>
                                <td>
                                    <code class="whitespace-nowrap text-xs">{{ $lap->transaction_id }}</code>
                                    <span class="block text-xs text-muted-foreground">Diisi {{ $lap->purchase_datetime->format('d/m/Y H:i') }}</span>
                                </td>
                                <td>
                                    <code class="whitespace-nowrap text-xs">{{ $perm->request_number ?? 'N/A' }}</code>
                                    @if ($lap->voucher_number)
                                        <span class="mt-0.5 flex"><span class="inline-flex items-center gap-1.5 rounded-md border border-dashed bg-muted/40 px-1.5 py-px font-mono text-[11px]"><i class="bi bi-ticket-perforated text-primary"></i>{{ $lap->voucher_number }}</span></span>
                                    @endif
                                </td>
                                <td>
                                    <span class="flex items-center gap-2.5">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-primary to-module-spd text-[11px] font-semibold text-white">{{ $initials($perm->requester->nama_lengkap ?? '') }}</span>
                                        <span class="min-w-0">
                                            <span class="block whitespace-nowrap font-medium">{{ $perm->requester->nama_lengkap ?? 'Unknown' }}</span>
                                            <span class="block text-xs text-muted-foreground">{{ $perm->requester->username ?? '-' }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    <span class="whitespace-nowrap font-medium">{{ $perm->vehicle->consumerial_code ?? 'N/A' }}</span>
                                    <span class="block whitespace-nowrap text-xs text-muted-foreground">{{ $perm->vehicle->consumerial_name ?? 'N/A' }}</span>
                                </td>
                                <td class="whitespace-nowrap text-right">
                                    <span class="font-semibold tabular-nums">Rp {{ number_format($perm->bill_amounts ?? 0, 0, ',', '.') }}</span>
                                    @if ($perm?->gasoline_type)
                                        <span class="mt-0.5 flex justify-end"><span class="inline-flex items-center rounded-md px-1.5 py-px text-[11px] font-medium ring-1 {{ $fuelTones[$perm->gasoline_type] ?? 'bg-muted text-muted-foreground ring-border' }}">{{ $perm->gasoline_type }}</span></span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-right">
                                    <span class="inline-flex items-center gap-1 font-semibold tabular-nums"><i class="bi bi-droplet-half text-cyan-600"></i>{{ number_format($lap->fuel_volume, 2, ',', '.') }} L</span>
                                    <span class="block text-xs tabular-nums text-muted-foreground"><i class="bi bi-speedometer2"></i> {{ $lap->km_terakhir ? number_format($lap->km_terakhir, 0, ',', '.') . ' km' : 'KM tidak diisi' }}</span>
                                </td>
                                <td class="text-center">
                                    {{-- Hapus memakai atribut form= (form hapus di luar tabel), sama seperti tab Permohonan. --}}
                                    <x-ui.dropdown-menu :id="'aksi-laporan-' . $lap->id" :label="'Aksi laporan ' . $lap->transaction_id">
                                        <div class="px-2.5 pb-1.5 pt-1 text-xs text-muted-foreground"><code>{{ $lap->transaction_id }}</code></div>
                                        @if ($struk)
                                            <x-ui.dropdown-item icon="bi bi-receipt" data-view-struk data-image-url="{{ $struk }}" data-transaction-id="{{ $lap->transaction_id }}">Lihat struk</x-ui.dropdown-item>
                                        @endif
                                        @if ($odometer)
                                            <x-ui.dropdown-item icon="bi bi-speedometer2" data-view-struk data-image-url="{{ $odometer }}" data-transaction-id="{{ $lap->transaction_id }}" data-view-title="Foto odometer">Lihat foto odometer</x-ui.dropdown-item>
                                        @endif
                                        <x-ui.dropdown-item icon="bi bi-pencil" data-edit-laporan
                                            data-update-url="{{ route('superadmin.laporan.update', $lap->id) }}"
                                            data-transaction-id="{{ $lap->transaction_id }}"
                                            data-purchase-date="{{ $lap->purchase_datetime->format('Y-m-d') }}"
                                            data-fuel-volume="{{ $lap->fuel_volume }}"
                                            data-km-terakhir="{{ $lap->km_terakhir }}">Ubah laporan</x-ui.dropdown-item>
                                        <x-ui.dropdown-separator />
                                        <x-ui.dropdown-item icon="bi bi-trash" variant="destructive" type="submit" :form="'delete-laporan-' . $lap->id">Hapus laporan</x-ui.dropdown-item>
                                    </x-ui.dropdown-menu>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-12 text-center text-muted-foreground">
                                <i class="bi bi-receipt mb-2 block text-2xl"></i>
                                {{ filled($search) ? 'Tidak ada laporan yang cocok dengan pencarian.' : 'Belum ada laporan transaksi.' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
                @if ($laporans->hasPages())
                    <div class="border-t px-4 py-3">{{ $laporans->appends(['tab' => 'laporan', 'search' => $search])->onEachSide(1)->links('vendor.pagination.shadcn') }}</div>
                @endif
        </x-ui.card>

                @foreach ($laporans as $lap)
                    <form id="delete-laporan-{{ $lap->id }}" action="{{ route('superadmin.laporan.delete', $lap->id) }}" method="POST" hidden
                          data-confirm="Laporan {{ $lap->transaction_id }} akan dihapus dan permohonan dikembalikan ke status disetujui."
                          data-confirm-title="Hapus laporan?" data-confirm-text="Ya, hapus" data-confirm-variant="destructive">
                        @csrf
                    </form>
                @endforeach

        <x-ui.dialog id="editLaporanDialog" title="Ubah laporan transaksi" size="lg" static>
            <form id="editLaporanForm" method="POST" enctype="multipart/form-data" class="grid gap-4">
                @csrf
                <x-ui.alert variant="info">ID transaksi <strong id="editTransactionId"></strong></x-ui.alert>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Tanggal pengisian" for="editPurchaseDate" required>
                        <x-ui.date-picker name="purchase_date" id="editPurchaseDate" :max="now()->toDateString()" required />
                    </x-ui.field>
                    <x-ui.field label="Volume BBM (liter)" for="editFuelVolume" required>
                        <x-ui.input type="number" step="0.01" name="fuel_volume" id="editFuelVolume" required />
                    </x-ui.field>
                </div>
                <x-ui.field label="KM terakhir" for="editKmTerakhir" hint="Opsional untuk alat.">
                    <x-ui.input type="number" name="km_terakhir" id="editKmTerakhir" />
                </x-ui.field>
                <x-ui.field label="Unggah ulang foto struk" for="editFotoStruk" hint="Kosongkan bila foto tidak diganti.">
                    <x-ui.input type="file" name="foto_struk" id="editFotoStruk" accept="image/*" />
                </x-ui.field>
            </form>
            <x-slot:footer>
                <x-ui.button variant="outline" data-dialog-close>Batal</x-ui.button>
                <x-ui.button type="submit" form="editLaporanForm"><i class="bi bi-save"></i> Simpan perubahan</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>

        <x-ui.dialog id="viewStrukDialog" title="Struk BBM" size="lg">
            <img id="strukImage" alt="Struk BBM" class="mx-auto max-h-[70vh] rounded-md">
            <x-slot:footer>
                <x-ui.button variant="outline" data-dialog-close>Tutup</x-ui.button>
                <x-ui.button id="downloadStruk" href="#" download><i class="bi bi-download"></i> Unduh foto</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @push('scripts')
        @vite('resources/js/pages/bbm/kelola-permohonan.js')
    @endpush
</x-layouts.app>
