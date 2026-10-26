@extends('templates.main')

@section('title', 'Assign Dispatcher')

@section('content')

<div class="pagetitle">
    <h1>Assign Dispatcher</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Assign Dispatcher</li>
        </ol>
    </nav>
</div>

{{-- Alert --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 pb-2">
                        <div>
                            <h5 class="card-title mb-0 pb-0">Penugasan PIC per Kendaraan</h5>
                            <p class="text-muted small mb-0">Tentukan dispatcher penanggung jawab untuk setiap jadwal pengecekan</p>
                        </div>
                    </div>

                    <form action="{{ route('dispatcher.assign.update') }}" method="POST" id="assignForm">
                        @csrf
                        @method('PUT')

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kendaraan</th>
                                        <th class="text-center">Harian</th>
                                        <th class="text-center">Mingguan</th>
                                        <th class="text-center">Bulanan</th>
                                        <th class="text-center">Body</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $idx = 0; @endphp
                                    @foreach($vehicles as $vehicle)
                                    @php $vehicleSchedules = $schedules[$vehicle->id] ?? collect(); @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $vehicle->consumerial_code }}</strong>
                                            <br><small class="text-muted">{{ $vehicle->consumerial_name }}</small>
                                        </td>

                                        @foreach(['daily', 'weekly', 'monthly', 'body'] as $freq)
                                        @php $schedule = $vehicleSchedules->firstWhere('check_frequency', $freq); @endphp
                                        <td class="text-center">
                                            @if($schedule)
                                                <input type="hidden" name="assignments[{{ $idx }}][schedule_id]" value="{{ $schedule->id }}">
                                                <select name="assignments[{{ $idx }}][dispatcher_id]" class="form-select form-select-sm" style="min-width: 130px;">
                                                    <option value="">— Belum —</option>
                                                    @foreach($dispatchers as $dispatcher)
                                                        <option value="{{ $dispatcher->id }}"
                                                            {{ $schedule->assigned_dispatcher_id == $dispatcher->id ? 'selected' : '' }}>
                                                            {{ $dispatcher->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                {{-- Status badge --}}
                                                @if($schedule->status === 'overdue')
                                                    <span class="badge bg-danger mt-1" style="font-size: 10px;">Overdue</span>
                                                @elseif($schedule->status === 'due')
                                                    <span class="badge bg-warning text-dark mt-1" style="font-size: 10px;">Due</span>
                                                @endif
                                                @php $idx++; @endphp
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($vehicles->count() > 0)
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary" id="btnSave">
                                <i class="bi bi-check-lg me-1"></i> Simpan Assignment
                            </button>
                        </div>
                        @else
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted fs-1"></i>
                            <p class="text-muted mt-2 mb-0">Belum ada kendaraan aktif. Generate periode terlebih dahulu.</p>
                            <a href="{{ route('dispatcher.dispatcher-dashboard') }}" class="btn btn-primary btn-sm mt-2">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                            </a>
                        </div>
                        @endif

                    </form>

                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('assignForm').addEventListener('submit', function() {
    var btn = document.getElementById('btnSave');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
});
</script>

@endsection
