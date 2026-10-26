@extends('templates.main')

@section('title', 'Riwayat Pengecekan')

@section('content')

<div class="pagetitle">
    <h1>Riwayat Pengecekan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Riwayat</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 pb-2">
                        <h5 class="card-title mb-0 pb-0">Riwayat Semua Pengecekan</h5>
                        <div class="d-flex gap-1 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalExportChecklist">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Vehicle Checklist
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalExportRoutine">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Routine Check Report
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExportBody">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Body Check Report
                            </button>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════ --}}
                    {{-- FILTER                              --}}
                    {{-- ═══════════════════════════════════ --}}
                    <form method="GET" action="{{ route('dispatcher.history') }}" class="mb-3">
                        <div class="row g-2 align-items-end">

                            {{-- Kendaraan --}}
                            <div class="col-md-3 col-6">
                                <label class="form-label small text-muted">Kendaraan</label>
                                <select name="vehicle_id" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->consumerial_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Jenis --}}
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Jenis</label>
                                <select name="type" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    <option value="routine" {{ request('type') == 'routine' ? 'selected' : '' }}>Rutin</option>
                                    <option value="body" {{ request('type') == 'body' ? 'selected' : '' }}>Body</option>
                                </select>
                            </div>

                            {{-- Driver --}}
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Driver</label>
                                <select name="driver_id" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->nama_lengkap }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Dispatcher --}}
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Dispatcher</label>
                                <select name="dispatcher_id" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    @foreach($dispatchers as $dispatcher)
                                        <option value="{{ $dispatcher->id }}" {{ request('dispatcher_id') == $dispatcher->id ? 'selected' : '' }}>
                                            {{ $dispatcher->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Dari --}}
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Dari</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>

                            {{-- Sampai --}}
                            <div class="col-md-2 col-6">
                                <label class="form-label small text-muted">Sampai</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>

                            {{-- Buttons --}}
                            <div class="col-md-2 col-12">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="{{ route('dispatcher.history') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </form>

                    {{-- ═══════════════════════════════════ --}}
                    {{-- TABLE                               --}}
                    {{-- ═══════════════════════════════════ --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Kendaraan</th>
                                    <th>Jenis</th>
                                    <th>Dispatcher</th>
                                    <th>Driver</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Hasil</th>
                                    <th class="text-center">Ack</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($checkings as $checking)
                                <tr>
                                    {{-- Kode --}}
                                    <td>
                                        @php
                                            // Detail pengecekan rutin punya halaman per frekuensi
                                            // (harian/mingguan/bulanan). Tidak ada route
                                            // `dispatcher.routine-check.*` — memakai nama itu
                                            // membuat halaman riwayat melempar RouteNotFoundException.
                                            $detailRoute = match (true) {
                                                $checking->type === 'body' => route('dispatcher.body-check.show', $checking->checking_detail_code),
                                                $checking->sub_type === 'weekly' => route('dispatcher.weekly-check.show', $checking->checking_detail_code),
                                                $checking->sub_type === 'monthly' => route('dispatcher.monthly-check.show', $checking->checking_detail_code),
                                                default => route('dispatcher.daily-check.show', $checking->checking_detail_code),
                                            };
                                        @endphp
                                        <a href="{{ $detailRoute }}" class="fw-semibold text-primary text-decoration-none">
                                            {{ $checking->checking_detail_code }}
                                        </a>
                                    </td>

                                    {{-- Kendaraan --}}
                                    <td>
                                        <strong>{{ $checking->vehicleChecking->nopol ?? '-' }}</strong>
                                        <br><small class="text-muted">{{ $checking->vehicleChecking->vehicle->consumerial_name ?? '' }}</small>
                                    </td>

                                    {{-- Jenis + Sub Type --}}
                                    <td>
                                        @if($checking->type === 'routine')
                                            @php
                                                $subColors = ['daily' => 'bg-primary', 'weekly' => 'bg-info', 'monthly' => 'bg-secondary'];
                                                $subLabels = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'];
                                            @endphp
                                            <span class="badge {{ $subColors[$checking->sub_type] ?? 'bg-secondary' }}">
                                                Rutin — {{ $subLabels[$checking->sub_type] ?? $checking->sub_type }}
                                            </span>
                                        @else
                                            <span class="badge bg-dark">Body</span>
                                        @endif
                                    </td>

                                    {{-- Dispatcher --}}
                                    <td>{{ $checking->dispatcher->nama_lengkap ?? '-' }}</td>

                                    {{-- Driver --}}
                                    <td>{{ $checking->currentDriver->nama_lengkap ?? '-' }}</td>

                                    {{-- Tanggal --}}
                                    <td>
                                        {{ $checking->checked_at->format('d/m/Y') }}
                                        <br><small class="text-muted">{{ $checking->checked_at->format('H:i') }}</small>
                                    </td>

                                    {{-- Hasil --}}
                                    <td class="text-center">
                                        @if($checking->type === 'routine')
                                            @php
                                                $ok = $checking->itemResults->where('status', 'ok')->count();
                                                $ng = $checking->itemResults->where('status', 'ng')->count();
                                            @endphp
                                            <span class="text-success fw-bold">{{ $ok }}</span><span class="text-muted">/</span><span class="text-danger fw-bold">{{ $ng }}</span>
                                            <br><small class="text-muted">OK / NG</small>
                                        @else
                                            @php
                                                $b = $checking->itemResults->where('status', 'baik')->count();
                                                $c = $checking->itemResults->where('status', 'cukup')->count();
                                                $k = $checking->itemResults->where('status', 'kurang')->count();
                                                $t = $checking->itemResults->where('status', 'tidak')->count();
                                            @endphp
                                            <span class="badge bg-success">{{ $b }}</span>
                                            <span class="badge bg-info">{{ $c }}</span>
                                            <span class="badge bg-warning text-dark">{{ $k }}</span>
                                            <span class="badge bg-danger">{{ $t }}</span>
                                            <br><small class="text-muted" style="font-size: 10px;">B/C/K/T</small>
                                        @endif
                                    </td>

                                    {{-- Driver Ack --}}
                                    <td class="text-center">
                                        @if($checking->driver_acknowledged)
                                            <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i></span>
                                        @endif
                                    </td>

                                    {{-- Detail --}}
                                    <td>
                                        <a href="{{ $detailRoute }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="bi bi-inbox text-muted fs-1"></i>
                                        <p class="text-muted mt-2 mb-0">Belum ada riwayat pengecekan</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($checkings->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $checkings->links() }}
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MODAL: Export Vehicle Checklist                           --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalExportChecklist" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('dispatcher.export.vehicle-checklist') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Download Vehicle Checklist</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Pilih rentang waktu untuk mengunduh checklist kendaraan (summary + detail).</p>
                    <div class="mb-3">
                        <label class="form-label small">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-download me-1"></i> Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MODAL: Export Routine Check Report                         --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalExportRoutine" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('dispatcher.export.routine-check') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-file-earmark-pdf me-1"></i> Download Routine Check Report</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Pilih rentang waktu untuk mengunduh laporan pengecekan rutin (harian, mingguan, bulanan).</p>
                    <div class="mb-3">
                        <label class="form-label small">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-download me-1"></i> Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MODAL: Export Body Check Report                            --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalExportBody" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('dispatcher.export.body-check') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-file-earmark-pdf me-1"></i> Download Body Check Report</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Pilih rentang waktu untuk mengunduh laporan pengecekan body kendaraan (termasuk foto kerusakan).</p>
                    <div class="mb-3">
                        <label class="form-label small">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="bi bi-download me-1"></i> Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
