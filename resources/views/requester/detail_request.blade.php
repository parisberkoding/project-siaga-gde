@extends('templates.main')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Detail Permohonan {{ $request->request_number }}</h5>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <h6 class="mb-0">Tanggal Permohonan</h6>
                    </div>
                    <div class="col-sm-9 text-secondary">
                        {{ $request->request_date->locale('id')->translatedFormat('l, d F Y') }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-3">
                        <h6 class="mb-0">Kendaraan</h6>
                    </div>
                    <div class="col-sm-9 text-secondary">
                        {{ $request->vehicle->consumerial_code ?? 'N/A' }} -
                        {{ $request->vehicle->consumerial_name ?? '' }} ({{ $request->vehicle->consumerial_type ?? '' }})
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-3">
                        <h6 class="mb-0">Jenis Bahan Bakar</h6>
                    </div>
                    <div class="col-sm-9 text-secondary">
                        {{ $request->gasoline_type }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-3">
                        <h6 class="mb-0">Nominal Permohonan</h6>
                    </div>
                    <div class="col-sm-9 text-secondary">
                        Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-3">
                        <h6 class="mb-0">Status</h6>
                    </div>
                    <div class="col-sm-9 text-secondary">
                        <span class="badge bg-{{ $request->status === 'pending' ? 'warning' : 'success' }}">
                            {{ ucfirst($request->status) }}
                        </span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-3">
                        <h6 class="mb-0">Catatan Pemohon </h6>
                    </div>
                    <div class="col-sm-9 text-secondary">
                        {{ $request->requester_notes ?? 'N/A' }}
                    </div>
                </div>

                <hr>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <h6 class="mb-0">disetujui oleh :</h6>
                    </div>
                    <div class="col-sm-9 text-secondary">
                        {{ $request->authorizer->username ?? 'N/A' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <h6 class="mb-0">Catatan Admin</h6>
                    </div>
                    <div class="col-sm-9 text-secondary">
                        {{ $request->authorizer_notes ?? 'N/A' }}
                    </div>
                </div>

                <hr>

                @if($request->transactionProof)
                <div class="row mb-3">
                    <div class="col-sm-3">
                        <h6 class="mb-0"><i class="bi bi-check2-circle text-success"></i> Laporan Terkirim</h6>
                    </div>
                    <div class="col-sm-9 text-secondary">
                        <span class="badge bg-info">Completed</span>
                        <div class="mt-1 small">
                            <div>Tanggal Beli: {{ $request->transactionProof->purchase_datetime->format('d M Y H:i') }}</div>
                            <div>Volume BBM: {{ number_format((float) $request->transactionProof->fuel_volume, 2, ',', '.') }} liter</div>
                            @if($request->transactionProof->km_terakhir)
                                <div>KM Akhir: {{ number_format((float) $request->transactionProof->km_terakhir, 0, ',', '.') }} km</div>
                            @endif
                            <div class="text-muted">ID Transaksi: {{ $request->transactionProof->transaction_id }}</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    @if($request->transactionProof->struk_bbm_path)
                    <div class="col-md-6 mb-3">
                        <label class="form-label small text-muted">Foto Struk BBM</label>
                        <a href="{{ asset('storage/' . $request->transactionProof->struk_bbm_path) }}" target="_blank">
                            <img src="{{ asset('storage/' . $request->transactionProof->struk_bbm_path) }}" alt="Struk BBM"
                                 style="max-height:180px; max-width:100%; border-radius:6px; object-fit:cover;" class="border">
                        </a>
                    </div>
                    @endif
                    @if($request->transactionProof->odometer_photo_path)
                    <div class="col-md-6 mb-3">
                        <label class="form-label small text-muted">Foto Odometer</label>
                        <a href="{{ asset('storage/' . $request->transactionProof->odometer_photo_path) }}" target="_blank">
                            <img src="{{ asset('storage/' . $request->transactionProof->odometer_photo_path) }}" alt="Odometer"
                                 style="max-height:180px; max-width:100%; border-radius:6px; object-fit:cover;" class="border">
                        </a>
                    </div>
                    @endif
                </div>
                @else
                <div class="row mb-3">
                    <div class="col-sm-3">
                        <h6 class="mb-0">Laporan</h6>
                    </div>
                    <div class="col-sm-9 text-secondary">
                        <span class="text-muted">Belum ada laporan yang dikirim untuk permohonan ini.</span>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection
