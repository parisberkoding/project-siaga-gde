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

            </div>
        </div>
    </div>
</div>
@endsection
