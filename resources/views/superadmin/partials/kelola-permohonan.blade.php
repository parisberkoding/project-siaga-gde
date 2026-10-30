<x-layouts.app title="Kelola Permohonan & Pelaporan" :breadcrumbs="['Permohonan BBM' => route('admin-dashboard'), 'Kelola Permohonan' => null]">
    <x-ui.page-header kicker="Admin BBM" title="Kelola Permohonan & Pelaporan" description="Ubah, batalkan, atau hapus permohonan dan laporan transaksi BBM." />

    <x-ui.tabs-list>
        <x-ui.tabs-link :href="route('superadmin.permohonan', ['tab' => 'permohonan'])" :active="$tab == 'permohonan'">
            <i class="bi bi-file-earmark-text"></i> Permohonan
        </x-ui.tabs-link>
        <x-ui.tabs-link :href="route('superadmin.permohonan', ['tab' => 'laporan'])" :active="$tab == 'laporan'">
            <i class="bi bi-receipt"></i> Laporan Transaksi
        </x-ui.tabs-link>
    </x-ui.tabs-list>

    @if ($tab == 'permohonan')
        <x-ui.card class="gap-4">
            <x-ui.card-content class="grid gap-4">
                <form method="GET" action="{{ route('superadmin.permohonan') }}" data-autosubmit class="grid gap-2 md:grid-cols-[1fr_16rem]">
                    <input type="hidden" name="tab" value="permohonan">
                    <div class="relative"><i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i><x-ui.input type="search" name="search" :value="$search" class="w-full pl-9" placeholder="Cari nomor permohonan, pemohon, atau kendaraan" aria-label="Cari" /></div>
                    <x-ui.select name="status" aria-label="Status">
                        <option value="">Semua status</option>
                        @foreach (['pending' => 'Menunggu', 'pending_vehicle_approval' => 'Menunggu kendaraan', 'approved' => 'Disetujui', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled_by_driver' => 'Dibatalkan'] as $value => $label)
                            <option value="{{ $value }}" @selected($status == $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </form>

                <form method="POST" action="{{ route('superadmin.permohonan.bulk-process') }}" id="bulkApprovalForm" class="grid gap-4">
                    @csrf
                    <input type="hidden" name="decisions" id="decisionsInput">

                    <x-ui.table>
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
                                <tr @if ($isPending) data-decision-item="{{ $p->id }}" data-summary="{{ json_encode($summary) }}" class="has-[[data-select]:checked]:bg-accent/60" @endif>
                                    <td>
                                        @if ($isPending)
                                            <x-ui.checkbox data-select aria-label="Pilih permohonan {{ $p->request_number }}" />
                                        @endif
                                    </td>
                                    <td>
                                        <code class="whitespace-nowrap text-xs">{{ $p->request_number }}</code>
                                        <span class="block text-xs text-muted-foreground">{{ $p->request_date->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td>
                                        <span class="whitespace-nowrap font-medium">{{ $p->requester->nama_lengkap ?? 'Unknown' }}</span>
                                        <span class="block text-xs text-muted-foreground">{{ $p->requester->username ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="whitespace-nowrap font-medium">{{ $p->vehicle->consumerial_code ?? 'N/A' }}</span>
                                        <span class="block whitespace-nowrap text-xs text-muted-foreground">{{ $p->vehicle->consumerial_name ?? 'N/A' }} &middot; {{ $p->gasoline_type }}</span>
                                    </td>
                                    <td class="whitespace-nowrap text-right font-semibold tabular-nums">Rp {{ number_format($p->bill_amounts, 0, ',', '.') }}</td>
                                    <td><x-bbm.status-badge :status="$p->status" /></td>
                                    <td class="max-w-56">
                                        @if ($p->approvedRequest?->voucher_code)
                                            <code class="text-xs font-semibold tracking-wide">{{ $p->approvedRequest->voucher_code }}</code>
                                        @endif
                                        @if ($p->authorizer_notes)
                                            <span class="block truncate text-xs text-muted-foreground" title="{{ $p->authorizer_notes }}"><i class="bi bi-chat-left-text"></i> {{ $p->authorizer_notes }}</span>
                                        @elseif (! $p->approvedRequest?->voucher_code)
                                            <span class="text-xs text-muted-foreground">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{-- Tombol batal/hapus memakai atribut form= karena formnya berada di luar form keputusan (form tidak boleh bersarang). --}}
                                        <x-ui.dropdown-menu :id="'aksi-permohonan-' . $p->id" label="Aksi permohonan"
                                            @class(['border-warning/50 text-warning' => $isPending])>
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
                                <tr><td colspan="8" class="py-10 text-center text-muted-foreground">Tidak ada data permohonan.</td></tr>
                            @endforelse
                        </tbody>
                    </x-ui.table>

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

                {{ $permohonans->appends(['tab' => 'permohonan', 'status' => $status, 'search' => $search])->links('vendor.pagination.shadcn') }}
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.dialog id="editPermohonanDialog" title="Ubah permohonan" size="lg" static>
            <form id="editPermohonanForm" method="POST" class="grid gap-4">
                @csrf
                <x-ui.alert variant="info">Nomor permohonan <strong id="editRequestNumber"></strong></x-ui.alert>

                <x-ui.field label="Kendaraan/Alat" for="editVehicleId" required>
                    <x-ui.select name="consumerial_tools_id" id="editVehicleId" required>
                        <option value="">Pilih kendaraan/alat</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" data-budget="{{ $vehicle->detail->nominal_full_tank ?? '' }}">
                                {{ $vehicle->dropdown_label }} ({{ $vehicle->consumerial_type }})
                            </option>
                        @endforeach
                    </x-ui.select>
                    <p id="editVehicleBudgetInfo" class="text-xs text-muted-foreground"></p>
                </x-ui.field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Jenis BBM" for="editGasolineType" required>
                        <x-ui.select name="gasoline_type" id="editGasolineType" required>
                            @forelse ($gasolineTypes as $gas)
                                <option value="{{ $gas->gasoline_name }}">{{ $gas->gasoline_name }}</option>
                            @empty
                                <option value="Pertamax">Pertamax</option>
                                <option value="Pertamina Dex">Pertamina Dex</option>
                                <option value="Pertalite">Pertalite</option>
                            @endforelse
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Nominal (Rp)" for="editBillAmounts" required>
                        <x-ui.select name="bill_amounts" id="editBillAmounts" required></x-ui.select>
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
        <x-ui.card class="gap-4">
            <x-ui.card-content class="grid gap-4">
                <form method="GET" action="{{ route('superadmin.permohonan') }}" data-autosubmit>
                    <input type="hidden" name="tab" value="laporan">
                    <div class="relative"><i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i><x-ui.input type="search" name="search" :value="$search" class="w-full pl-9" placeholder="Cari ID transaksi atau nomor permohonan" aria-label="Cari" /></div>
                </form>

                <x-ui.table>
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Nomor</th>
                            <th>Pemohon</th>
                            <th>Kendaraan</th>
                            <th>Tanggal isi</th>
                            <th class="text-right">Volume</th>
                            <th class="text-right">KM terakhir</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporans as $lap)
                            <tr>
                                <td><code class="text-xs">{{ $lap->transaction_id }}</code></td>
                                <td><code class="text-xs">{{ $lap->permohonan->request_number ?? 'N/A' }}</code></td>
                                <td>{{ $lap->permohonan->requester->nama_lengkap ?? 'Unknown' }}</td>
                                <td>
                                    <span class="font-medium">{{ $lap->permohonan->vehicle->consumerial_code ?? 'N/A' }}</span>
                                    <span class="block text-xs text-muted-foreground">{{ $lap->permohonan->vehicle->consumerial_name ?? 'N/A' }}</span>
                                </td>
                                <td class="whitespace-nowrap">{{ $lap->purchase_datetime->format('d/m/Y H:i') }}</td>
                                <td class="text-right tabular-nums">{{ number_format($lap->fuel_volume, 2) }} L</td>
                                <td class="text-right tabular-nums">{{ number_format($lap->km_terakhir, 0, ',', '.') }} km</td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <x-ui.button variant="outline" size="icon-sm" title="Ubah" data-edit-laporan
                                                     data-update-url="{{ route('superadmin.laporan.update', $lap->id) }}"
                                                     data-transaction-id="{{ $lap->transaction_id }}"
                                                     data-purchase-date="{{ $lap->purchase_datetime->format('Y-m-d') }}"
                                                     data-fuel-volume="{{ $lap->fuel_volume }}"
                                                     data-km-terakhir="{{ $lap->km_terakhir }}">
                                            <i class="bi bi-pencil"></i>
                                        </x-ui.button>
                                        <x-ui.button variant="outline" size="icon-sm" title="Lihat struk" data-view-struk
                                                     data-image-url="{{ asset('storage/' . $lap->struk_bbm_path) }}"
                                                     data-transaction-id="{{ $lap->transaction_id }}">
                                            <i class="bi bi-receipt"></i>
                                        </x-ui.button>
                                        <form action="{{ route('superadmin.laporan.delete', $lap->id) }}" method="POST"
                                              data-confirm="Laporan {{ $lap->transaction_id }} akan dihapus dan permohonan dikembalikan ke status disetujui."
                                              data-confirm-title="Hapus laporan?" data-confirm-text="Ya, hapus" data-confirm-variant="destructive">
                                            @csrf
                                            <x-ui.button type="submit" variant="outline" size="icon-sm" title="Hapus" class="text-destructive hover:text-destructive">
                                                <i class="bi bi-trash"></i>
                                            </x-ui.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-10 text-center text-muted-foreground">Tidak ada data laporan transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </x-ui.table>

                {{ $laporans->appends(['tab' => 'laporan', 'search' => $search])->links('vendor.pagination.shadcn') }}
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.dialog id="editLaporanDialog" title="Ubah laporan transaksi" size="lg" static>
            <form id="editLaporanForm" method="POST" enctype="multipart/form-data" class="grid gap-4">
                @csrf
                <x-ui.alert variant="info">ID transaksi <strong id="editTransactionId"></strong></x-ui.alert>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Tanggal pengisian" for="editPurchaseDate" required>
                        <x-ui.input type="date" name="purchase_date" id="editPurchaseDate" required />
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
