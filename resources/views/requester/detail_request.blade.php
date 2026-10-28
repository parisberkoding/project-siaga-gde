@php($proof = $request->transactionProof)

<x-layouts.app title="Detail {{ $request->request_number }}" :breadcrumbs="['Permohonan BBM' => route('requester-index'), 'Riwayat' => route('requester-history'), $request->request_number => null]">
    <x-ui.page-header :title="'Permohonan ' . $request->request_number" description="Rincian permohonan, persetujuan, dan laporan pembelian.">
        <x-slot:actions>
            <x-bbm.status-badge :status="$request->status" class="text-sm" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="grid content-start gap-6 lg:col-span-2">
            <x-ui.card>
                <x-ui.card-header title="Permohonan" icon="bi bi-fuel-pump" />
                <x-ui.card-content>
                    <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-muted-foreground">Tanggal permohonan</dt><dd class="mt-1">{{ $request->request_date->locale('id')->translatedFormat('l, d F Y') }}</dd></div>
                        <div>
                            <dt class="text-muted-foreground">Kendaraan</dt>
                            <dd class="mt-1">{{ $request->vehicle->consumerial_code ?? 'N/A' }} <span class="text-muted-foreground">{{ $request->vehicle->consumerial_name ?? '' }} ({{ $request->vehicle->consumerial_type ?? '-' }})</span></dd>
                        </div>
                        <div><dt class="text-muted-foreground">Jenis BBM</dt><dd class="mt-1">{{ $request->gasoline_type }}</dd></div>
                        <div><dt class="text-muted-foreground">Nominal</dt><dd class="mt-1 font-medium">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-muted-foreground">Catatan pemohon</dt><dd class="mt-1">{{ $request->requester_notes ?? '-' }}</dd></div>
                    </dl>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card>
                <x-ui.card-header title="Laporan Pembelian" icon="bi bi-receipt" />
                <x-ui.card-content class="grid gap-4">
                    @if ($proof)
                        <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                            <div><dt class="text-muted-foreground">Tanggal beli</dt><dd class="mt-1">{{ $proof->purchase_datetime->translatedFormat('d M Y H:i') }}</dd></div>
                            <div><dt class="text-muted-foreground">Volume BBM</dt><dd class="mt-1">{{ number_format((float) $proof->fuel_volume, 2, ',', '.') }} liter</dd></div>
                            @if ($proof->km_terakhir)
                                <div><dt class="text-muted-foreground">KM akhir</dt><dd class="mt-1">{{ number_format((float) $proof->km_terakhir, 0, ',', '.') }} km</dd></div>
                            @endif
                            <div><dt class="text-muted-foreground">ID transaksi</dt><dd class="mt-1"><code class="text-xs">{{ $proof->transaction_id }}</code></dd></div>
                        </dl>

                        <div class="grid gap-4 sm:grid-cols-2">
                            {{-- Foto ada di disk privat; URL dari accessor model (rute media yang memeriksa hak akses). --}}
                            @foreach (['struk_url' => 'Foto struk BBM', 'odometer_url' => 'Foto odometer'] as $field => $label)
                                @if ($proof->$field)
                                    <figure class="grid gap-2">
                                        <figcaption class="text-xs text-muted-foreground">{{ $label }}</figcaption>
                                        <a href="{{ $proof->$field }}" target="_blank" class="overflow-hidden rounded-lg border">
                                            <img src="{{ $proof->$field }}" alt="{{ $label }}" class="h-48 w-full object-cover transition hover:opacity-90">
                                        </a>
                                    </figure>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state icon="bi bi-receipt" title="Belum ada laporan" description="Laporan pembelian untuk permohonan ini belum dikirim." />
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <x-ui.card class="content-start self-start">
            <x-ui.card-header title="Persetujuan" icon="bi bi-person-check" />
            <x-ui.card-content>
                <dl class="grid gap-4 text-sm">
                    <div><dt class="text-muted-foreground">Status</dt><dd class="mt-1"><x-bbm.status-badge :status="$request->status" /></dd></div>
                    <div><dt class="text-muted-foreground">Diproses oleh</dt><dd class="mt-1">{{ $request->authorizer->nama_lengkap ?? $request->authorizer->username ?? '-' }}</dd></div>
                    <div><dt class="text-muted-foreground">Catatan admin</dt><dd class="mt-1">{{ $request->authorizer_notes ?? '-' }}</dd></div>
                </dl>
            </x-ui.card-content>
        </x-ui.card>
    </div>
</x-layouts.app>
