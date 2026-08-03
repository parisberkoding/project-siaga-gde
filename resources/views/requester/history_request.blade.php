@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Riwayat Permohonan BBM</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('requester-index') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Riwayat</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Permohonan</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Nomor Request</th>
                                    <th>Tanggal</th>
                                    <th>Kendaraan/Alat</th>
                                    <th>Jenis BBM</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $item)
                                <tr>
                                    <td><code>{{ $item->request_number }}</code></td>
                                    <td>{{ $item->request_date->format('d M Y H:i') }}</td>
                                    <td>
                                        {{ $item->vehicle->consumerial_code ?? 'N/A' }}<br>
                                        <small class="text-muted">{{ $item->vehicle->consumerial_name ?? '' }} ({{ $item->vehicle->consumerial_type ?? '' }})</small>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $item->gasoline_type }}</span></td>
                                    <td><strong>Rp {{ number_format($item->bill_amounts, 0, ',', '.') }}</strong></td>
                                    <td>
                                        @if($item->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @elseif($item->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                        @elseif($item->status === 'completed')
                                        <span class="badge bg-info">Completed</span>
                                        @elseif($item->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('requester-show', $item->id) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada riwayat permohonan</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $history->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
