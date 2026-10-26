@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Data Forecast Makan Siang</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Lunch Management</li>
            <li class="breadcrumb-item active">Data Forecast</li>
        </ol>
    </nav>
</div>

<section class="section">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($pendingEmployees > 0)
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-person-exclamation me-1"></i>
                {{ $pendingEmployees }} karyawan hasil scan belum diverifikasi admin unit.
            </div>
            @can('permission', 'lunch.manage')
            <a href="{{ route('lunch.employee-defaults.index', ['verification' => 'pending']) }}"
               class="btn btn-sm btn-outline-dark">Verifikasi</a>
            @endcan
        </div>
    @endif

    {{-- ── Filter & aksi utama ─────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('lunch.forecasts.index') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm">
                </div>

                @if($units->count() > 1)
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Unit</label>
                    <select name="unit_id" class="form-select form-select-sm">
                        <option value="">Semua unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected((int) $unitFilter === (int) $unit->id)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Site</label>
                    <select name="site_id" class="form-select form-select-sm">
                        <option value="">Semua site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected((int) $siteFilter === (int) $site->id)>
                                {{ $site->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Cari karyawan</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Nama…">
                </div>

                <div class="col-12 col-md-2">
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                </div>
            </form>

            <hr class="my-3">

            <div class="d-flex flex-wrap gap-2">
                @can('permission', 'lunch.manage')
                <form method="POST" action="{{ route('lunch.forecasts.generate') }}" onsubmit="return confirm('Generate forecast {{ $date }}? Jawaban manual, booking, dan konfirmasi besok tidak akan ditimpa.')">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-magic me-1"></i>Generate Forecast</button>
                </form>

                <form method="POST" action="{{ route('lunch.distributions.lock') }}" onsubmit="return confirm('Kunci distribusi {{ $date }}? Angka tidak berubah otomatis setelah dikirim ke vendor.')">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <button class="btn btn-sm btn-warning"><i class="bi bi-lock me-1"></i>Kunci Angka Vendor</button>
                </form>

                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#guestModal">
                    <i class="bi bi-person-plus me-1"></i>Tambah Tamu
                </button>
                @endcan

                @can('permission', 'lunch.manage')
                <a href="{{ route('lunch.employee-defaults.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-people me-1"></i>Master Karyawan
                </a>
                @endcan
                <a href="{{ route('lunch.guests.calendar') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-calendar3 me-1"></i>Kalender Tamu
                </a>
                @can('permission', 'lunch.manage')
                <a href="{{ route('lunch.self-booking.index') }}" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-calendar-check me-1"></i>Booking Mandiri
                </a>
                @endcan
                <a href="{{ route('lunch.qr-codes') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-qr-code me-1"></i>QR Lokasi
                </a>
                @can('permission', 'lunch.export')
                <a href="{{ route('lunch.export-form') }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Vendor
                </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- ── Ringkasan per site ──────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        @forelse($sites as $site)
            @php
                $forecastCount = (int) ($totalsBySite[$site->id] ?? 0);
                $attendanceCount = (int) ($attendanceBySite[$site->id] ?? 0);
                $guestCount = (int) $guests->where('site_id', $site->id)->sum('guest_count');
                $dist = $distributions->get($site->id);
                $custom = $customBySite->get($site->id);
            @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 {{ $custom ? 'border-warning' : '' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-0">{{ $site->name }}</h6>
                                <small class="text-muted">{{ $site->unit?->name }} · {{ $site->code }}</small>
                            </div>
                            @if($custom)
                                <span class="badge bg-warning text-dark">Custom Purchase</span>
                            @elseif($dist?->is_locked)
                                <span class="badge bg-success"><i class="bi bi-lock-fill me-1"></i>Terkunci</span>
                            @else
                                <span class="badge bg-secondary">Draft</span>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between text-center mt-3">
                            <div>
                                <h5 class="fw-bold text-primary mb-0">{{ $custom ? 0 : $forecastCount }}</h5>
                                <small class="text-muted">Forecast</small>
                            </div>
                            <div>
                                <h5 class="fw-bold text-success mb-0">{{ $attendanceCount }}</h5>
                                <small class="text-muted">Hadir</small>
                            </div>
                            <div>
                                <h5 class="fw-bold text-info mb-0">{{ $custom ? 0 : $guestCount }}</h5>
                                <small class="text-muted">Tamu</small>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">{{ $dist?->total_count ?? 0 }}</h5>
                                <small class="text-muted">Porsi</small>
                            </div>
                        </div>

                        @if($custom)
                            <div class="alert alert-warning py-1 px-2 small mt-2 mb-0">
                                Katering diganti: {{ $custom->title }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">Tidak ada site dalam kewenangan Anda.</div>
            </div>
        @endforelse
    </div>

    {{-- ── Akurasi & sumber ────────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold {{ ($accuracy['accuracy'] ?? 0) >= 80 ? 'text-success' : 'text-warning' }}">
                        {{ $accuracy['accuracy'] !== null ? $accuracy['accuracy'] . '%' : '—' }}
                    </h3>
                    <p class="mb-0 small">Akurasi Forecast</p>
                    <small class="text-muted">{{ $accuracy['correct'] }}/{{ $accuracy['evaluated'] }} tepat</small>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($totalsBySource as $source => $total)
                            <span class="badge bg-light text-dark border">{{ $source }}: {{ $total }}</span>
                        @empty
                            <span class="text-muted small">Belum ada forecast pada tanggal ini.</span>
                        @endforelse
                    </div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Jawaban "besok makan dimana" dan booking mandiri langsung masuk forecast.
                        Generate hanya mengisi yang belum menjawab (default site).
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabel forecast + override inline ────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-table me-1"></i>Forecast {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</strong>
            <small class="text-muted">Ubah site langsung dari dropdown — tersimpan otomatis.</small>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Unit</th>
                            <th>Site Forecast</th>
                            <th>Sumber</th>
                            <th class="text-center">Confidence</th>
                            <th>Site Aktual (QR)</th>
                            <th class="text-center">Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forecasts as $forecast)
                            @php
                                $employeeUnitId = $forecast->employee?->resolvedUnitId();
                                $employeeSites = $sites->where('unit_id', $employeeUnitId);
                                $currentSiteInUnit = $employeeSites->contains('id', $forecast->predicted_site_id);
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    {{ $forecast->employee?->employee_name ?? '-' }}
                                    <br><small class="text-muted">{{ $forecast->employee?->position }}</small>
                                </td>
                                <td><small>{{ $forecast->employee?->resolvedUnit()?->name ?? '—' }}</small></td>
                                <td style="min-width: 190px;">
                                    @if($forecast->employee && $employeeSites->isNotEmpty())
                                        @can('permission', 'lunch.manage')
                                        <form method="POST" action="{{ route('lunch.forecasts.override') }}">
                                            @csrf
                                            <input type="hidden" name="slug" value="{{ $forecast->employee->slug }}">
                                            <input type="hidden" name="date" value="{{ $date }}">
                                            <select name="site_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                @if(! $currentSiteInUnit && $forecast->predictedSite)
                                                    <option value="{{ $forecast->predicted_site_id }}" selected>
                                                        {{ $forecast->predictedSite->name }} (luar unit)
                                                    </option>
                                                @endif
                                                @foreach($employeeSites as $site)
                                                    <option value="{{ $site->id }}" @selected((int) $forecast->predicted_site_id === (int) $site->id)>
                                                        {{ $site->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                        @else
                                            <span class="small">{{ $forecast->predictedSite?->name ?? '—' }}</span>
                                        @endcan
                                    @else
                                        <span class="text-danger small">Unit karyawan belum diisi</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $forecast->sourceBadgeClass() }}">{{ $forecast->sourceLabel() }}</span></td>
                                <td class="text-center">{{ $forecast->confidence !== null ? $forecast->confidence . '%' : '—' }}</td>
                                <td>
                                    @if($forecast->actualSite)
                                        <span class="badge bg-success">{{ $forecast->actualSite->name }}</span>
                                    @else
                                        <span class="text-muted small">belum scan</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($forecast->is_prediction_correct === true)
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @elseif($forecast->is_prediction_correct === false)
                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                    @else — @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">
                                Belum ada forecast untuk tanggal ini.
                                @can('permission', 'lunch.manage')
                                    Klik <strong>Generate Forecast</strong> untuk mengisi default.
                                @endcan
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $forecasts->links('pagination::bootstrap-5') }}
        </div>
    </div>

    {{-- ── Tamu & custom purchase ─────────────────────────────── --}}
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><strong><i class="bi bi-people me-1"></i>Reservasi Tamu</strong></div>
                <div class="card-body pt-2">
                    @if($guests->isEmpty())
                        <p class="text-muted small mb-0">Tidak ada reservasi tamu pada tanggal ini.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Instansi</th><th>Site</th><th class="text-center">Jumlah</th><th>Status</th></tr></thead>
                                <tbody>
                                    @foreach($guests as $guest)
                                        <tr>
                                            <td>{{ $guest->institution }}</td>
                                            <td><small>{{ $guest->site?->name }}</small></td>
                                            <td class="text-center">{{ $guest->guest_count }}</td>
                                            <td><span class="badge bg-{{ $guest->statusBadgeClass() }}">{{ $guest->statusLabel() }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><strong><i class="bi bi-bag-heart me-1"></i>Custom Purchase</strong></div>
                <div class="card-body pt-2">
                    @if($customPurchases->isEmpty())
                        <p class="text-muted small mb-0">Tidak ada pembelian manual pada tanggal ini.</p>
                    @else
                        @foreach($customPurchases as $purchase)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $purchase->title }}</strong>
                                    <span class="badge bg-{{ $purchase->statusBadgeClass() }}">{{ $purchase->statusLabel() }}</span>
                                </div>
                                <small class="text-muted">{{ $purchase->site?->name }} · Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</small>
                            </div>
                        @endforeach
                    @endif
                    <a href="{{ route('lunch.custom-purchases.index', ['month' => \Carbon\Carbon::parse($date)->format('Y-m')]) }}"
                       class="btn btn-sm btn-outline-primary mt-1">
                        <i class="bi bi-arrow-right me-1"></i>Kelola Custom Purchase
                    </a>
                </div>
            </div>
        </div>
    </div>

</section>

{{-- ── Modal tambah tamu ───────────────────────────────────────── --}}
@can('permission', 'lunch.manage')
<div class="modal fade" id="guestModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('lunch.guests.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Reservasi Tamu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ $date }}" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Instansi</label>
                    <input type="text" name="instansi" class="form-control" required maxlength="200">
                </div>
                <div class="mb-2">
                    <label class="form-label">Jumlah tamu</label>
                    <input type="number" name="jumlah" class="form-control" min="1" max="9999" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Site</label>
                    <select name="site_id" class="form-select" required>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-1">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection
