@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Lunch Transactions</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Transactions</li>
        </ol>
    </nav>
</div>

<section class="section">

    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" action="{{ route('lunch.transactions.index') }}" class="row g-2">
                <div class="col-auto">
                    <input type="date" name="date" value="{{ $date }}" class="form-control">
                </div>
                <div class="col-auto">
                    <select name="site_id" class="form-select">
                        <option value="">Semua Site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected(request('site_id') == $site->id)>{{ $site->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="resolved" @selected(request('status') === 'resolved')>Dikenali</option>
                        <option value="unresolved" @selected(request('status') === 'unresolved')>Belum Dikenali</option>
                    </select>
                </div>
                <div class="col">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Cari nama…">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end gap-2">
            <span class="badge bg-success" id="live-total">Hadir: {{ $transactions->total() }}</span>
            @if($unresolvedCount > 0)
                <span class="badge bg-danger" id="live-unresolved">Belum dikenali: {{ $unresolvedCount }}</span>
            @endif
            <span class="text-muted small"><i class="bi bi-broadcast text-success"></i> live</span>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach($sites as $site)
        <div class="col-md-2 col-6">
            <div class="card"><div class="card-body text-center py-3">
                <h4 class="fw-bold text-success mb-0">{{ $totalsBySite[$site->id] ?? 0 }}</h4>
                <small class="text-muted">Hadir di {{ $site->code }}</small>
            </div></div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body pt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Jam</th>
                            <th>Nama (Hasil Lookup)</th>
                            <th>Input Asli</th>
                            <th>Site</th>
                            <th>Metode</th>
                            <th class="text-center">Confidence</th>
                            <th>Besok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $t)
                        <tr class="{{ $t->isResolved() ? '' : 'table-warning' }}">
                            <td>{{ $t->scanned_at?->format('H:i') ?? '-' }}</td>
                            <td class="fw-semibold">
                                {{ $t->employee_name ?? '(belum dikenali)' }}
                                @if($t->position)<br><small class="text-muted">{{ $t->position }}</small>@endif
                            </td>
                            <td class="text-muted"><code>{{ $t->raw_alias }}</code></td>
                            <td><span class="badge bg-primary">{{ $t->site?->code }}</span></td>
                            <td><span class="badge bg-{{ $t->resolutionBadgeClass() }}">{{ $t->resolution_method }}</span></td>
                            <td class="text-center">{{ $t->confidence_score }}%</td>
                            <td>
                                @if($t->tomorrowSite)
                                    <span class="badge bg-info">{{ $t->tomorrowSite->code }}</span>
                                @else <span class="text-muted small">—</span> @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            Belum ada kehadiran tercatat untuk filter ini.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $transactions->links('pagination::bootstrap-5') }}
        </div>
    </div>

</section>
@endsection

@push('scripts')
<script>
// Polling ringan: refresh halaman bila jumlah kehadiran hari ini berubah.
const feedUrl   = @json(route('lunch.transactions.feed', ['date' => $date]));
let currentTotal = {{ $transactions->total() }};

setInterval(async () => {
    try {
        const res  = await fetch(feedUrl, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (data.total !== currentTotal) window.location.reload();
    } catch (e) { /* offline sesaat — coba lagi interval berikutnya */ }
}, 30000);
</script>
@endpush
