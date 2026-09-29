@extends('templates.main')

@section('title', 'Kalender Tamu Catering')

@section('content')
<style>
    /* ── Calendar Grid ── */
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #E4E9F0;
        border: 1px solid #E4E9F0;
        border-radius: 8px;
        overflow: hidden;
    }
    .calendar-day-header {
        background: #F8FAFC;
        text-align: center;
        padding: 8px 4px;
        font-size: 12px;
        font-weight: 700;
        color: #64748B;
        text-transform: uppercase;
    }
    .calendar-cell {
        background: #fff;
        min-height: 100px;
        padding: 6px;
        position: relative;
        cursor: pointer;
        transition: background 0.15s;
    }
    .calendar-cell:hover { background: #f1f5ff; }
    .calendar-cell.empty { background: #F8FAFC; cursor: default; }
    .calendar-cell.today { background: #fffbf0; }
    .calendar-cell.past  { background: #fafafa; cursor: default; }
    .calendar-cell.past:hover { background: #fafafa; }

    .cell-date {
        font-size: 13px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 4px;
    }
    .calendar-cell.today .cell-date {
        background: #0B50A1;
        color: #fff;
        border-radius: 50%;
        width: 24px; height: 24px;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px;
    }

    .guest-pill {
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 10px;
        display: block;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
    }
    .guest-pill.pending   { background: #fff3cd; color: #856404; }
    .guest-pill.confirmed { background: #d1e7dd; color: #0f5132; }
    .guest-pill.cancelled { background: #f8d7da; color: #842029; text-decoration: line-through; }

    .cell-add-btn { font-size: 18px; color: #ced4da; line-height: 1; }
    .calendar-cell:hover .cell-add-btn { color: #0B50A1; }

    .month-nav {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 16px;
    }
    .month-nav h5 { margin: 0; color: #093D7D; font-weight: 700; font-size: 1.1rem; }
</style>

<div class="pagetitle">
    <h1>Kalender Tamu Catering</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">SIAGA</li>
            <li class="breadcrumb-item">Catering</li>
            <li class="breadcrumb-item active">Kalender Tamu</li>
        </ol>
    </nav>
</div>

{{-- Session Alerts --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── H-2 Confirmation Banner ── --}}
@if($pendingH2->isNotEmpty())
<div class="alert alert-warning" id="h2Banner">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-bell-fill fs-5 mt-1 text-warning"></i>
        <div class="flex-grow-1">
            <strong>Konfirmasi Tamu H-2</strong>
            <p class="mb-2 mt-1 text-muted small">Tamu berikut akan datang dalam 2 hari ke depan. Konfirmasikan apakah mereka jadi datang agar bisa dimasukkan ke order catering.</p>

            <div class="row g-2">
                @foreach($pendingH2 as $guest)
                <div class="col-md-6">
                    <div class="card border-warning shadow-sm" id="h2card-{{ $guest->id }}">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">{{ $guest->institution }}</div>
                                    <small class="text-muted">
                                        {{ $guest->guest_count }} orang &bull; Site {{ $guest->site->code }} &bull;
                                        {{ $guest->reservation_date->translatedFormat('d F Y') }}
                                    </small>
                                    @if($guest->notes)
                                    <div class="text-muted" style="font-size:11px;">{{ $guest->notes }}</div>
                                    @endif
                                </div>
                                <div class="d-flex flex-column gap-1 ms-2">
                                    @can('permission', 'lunch.manage')
                                    <button class="btn btn-success btn-sm py-0"
                                            onclick="h2Confirm({{ $guest->id }})">
                                        <i class="bi bi-check-circle"></i> Jadi
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm py-0"
                                            onclick="h2Cancel({{ $guest->id }})">
                                        <i class="bi bi-x-circle"></i> Batal
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<section class="section">
<div class="row">
<div class="col-12">
<div class="card">
<div class="card-body">

    {{-- Month Navigation --}}
    <div class="month-nav">
        <a href="{{ route('lunch.guests.calendar', ['month' => $prevMonth]) }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chevron-left"></i>
            {{ \Carbon\Carbon::createFromFormat('Y-m', $prevMonth)->translatedFormat('M Y') }}
        </a>
        <h5>{{ $monthStart->translatedFormat('F Y') }}</h5>
        <a href="{{ route('lunch.guests.calendar', ['month' => $nextMonth]) }}"
           class="btn btn-outline-secondary btn-sm">
            {{ \Carbon\Carbon::createFromFormat('Y-m', $nextMonth)->translatedFormat('M Y') }}
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    {{-- Legend --}}
    <div class="d-flex gap-3 flex-wrap mb-3" style="font-size:12px;">
        <span><span class="badge" style="background:#fff3cd;color:#856404;">●</span> Menunggu Konfirmasi</span>
        <span><span class="badge" style="background:#d1e7dd;color:#0f5132;">●</span> Terkonfirmasi</span>
        <span><span class="badge" style="background:#f8d7da;color:#842029;">●</span> Dibatalkan</span>
    </div>

    {{-- Calendar Grid --}}
    @php
        $today    = \Carbon\Carbon::today();
        $cellDate = $monthStart->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $monthEnd = $monthStart->copy()->endOfMonth();
        $gridEnd  = $monthEnd->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
        $dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    @endphp

    <div class="calendar-grid">
        @foreach($dayNames as $dayName)
            <div class="calendar-day-header">{{ $dayName }}</div>
        @endforeach

        @while($cellDate->lte($gridEnd))
        @php
            $dateStr  = $cellDate->toDateString();
            $inMonth  = $cellDate->month === $monthStart->month;
            $isPast   = $cellDate->lt($today);
            $isToday  = $cellDate->isSameDay($today);
            $dayGuests = $inMonth ? ($guests->get($dateStr) ?? collect()) : collect();
            $canAdd   = $inMonth && !$isPast && auth()->user()->can('permission', 'lunch.manage');
        @endphp

        <div class="calendar-cell
                    {{ !$inMonth ? 'empty' : '' }}
                    {{ $isToday ? 'today' : '' }}
                    {{ ($inMonth && $isPast && !$isToday) ? 'past' : '' }}"
             @if($canAdd)
                 onclick="openAddModal('{{ $dateStr }}', '{{ $cellDate->translatedFormat('l, d F Y') }}')"
             @endif
        >
            @if($inMonth)
                <div class="cell-date">{{ $cellDate->day }}</div>

                @foreach($dayGuests as $guest)
                    <span class="guest-pill {{ $guest->status }}"
                          onclick="event.stopPropagation(); openDetailModal(
                              {{ $guest->id }},
                              '{{ $cellDate->translatedFormat('l, d F Y') }}',
                              '{{ addslashes($guest->institution) }}',
                              {{ $guest->guest_count }},
                              '{{ $guest->site->code }}',
                              '{{ addslashes($guest->notes ?? '') }}',
                              '{{ $guest->statusLabel() }}',
                              '{{ $guest->statusBadgeClass() }}',
                              '{{ $guest->status }}'
                          )">
                        <i class="bi bi-people-fill"></i>
                        {{ $guest->institution }} ({{ $guest->guest_count }} org)
                    </span>
                @endforeach

                @if($canAdd)
                    <div class="cell-add-btn text-center mt-1">+</div>
                @endif
            @endif
        </div>
        @php $cellDate->addDay(); @endphp
        @endwhile
    </div>

</div>{{-- card-body --}}
</div>{{-- card --}}
</div>{{-- col --}}
</div>{{-- row --}}
</section>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- MODAL: Tambah Tamu --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('lunch.guests.store') }}" id="addForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-people me-2"></i>Tambah Tamu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tanggal" id="addTanggal">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="text" class="form-control bg-light" id="addTanggalDisplay" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Tamu / Instansi <span class="text-danger">*</span></label>
                        <input type="text" name="instansi" class="form-control" required maxlength="200"
                               placeholder="Contoh: PT. XYZ, Delegasi Kementerian...">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Jumlah Orang <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah" class="form-control" required min="1" max="9999"
                                   placeholder="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Site <span class="text-danger">*</span></label>
                            <select name="site" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="PMU">PMU</option>
                                <option value="Patuha">Patuha</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label fw-semibold">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                        <textarea name="catatan" class="form-control" rows="2" maxlength="500"
                                  placeholder="Keperluan kunjungan, acara, dll."></textarea>
                    </div>
                    <div class="alert alert-info py-2 mb-0" style="font-size:13px;">
                        <i class="bi bi-info-circle me-1"></i>
                        Tamu akan berstatus <strong>Menunggu Konfirmasi</strong>. Di H-2 Anda akan mendapat pengingat untuk mengkonfirmasi apakah tamu jadi datang.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambahkan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- MODAL: Detail Tamu --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-check me-2"></i>Detail Tamu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-borderless mb-0">
                    <tr><th width="35%">Tanggal</th><td id="detailTanggal">—</td></tr>
                    <tr><th>Instansi</th><td id="detailInstansi">—</td></tr>
                    <tr><th>Jumlah</th><td id="detailJumlah">—</td></tr>
                    <tr><th>Site</th><td id="detailSite">—</td></tr>
                    <tr><th>Catatan</th><td id="detailCatatan">—</td></tr>
                    <tr><th>Status</th><td id="detailStatus">—</td></tr>
                </table>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                {{-- Tombol hapus (hanya jika pending) --}}
                @can('permission', 'lunch.manage')
                <form method="POST" id="deleteForm" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm" id="btnDelete"
                            onclick="return confirm('Yakin ingin menghapus data tamu ini?')">
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </form>
                @endcan
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';

function openAddModal(dateStr, dateLabel) {
    const form = document.getElementById('addForm');
    form.reset();
    document.getElementById('addTanggal').value        = dateStr;
    document.getElementById('addTanggalDisplay').value = dateLabel;
    new bootstrap.Modal(document.getElementById('addModal')).show();
}

function openDetailModal(id, tanggal, instansi, jumlah, site, catatan, statusLabel, badgeClass, status) {
    document.getElementById('detailTanggal').textContent  = tanggal;
    document.getElementById('detailInstansi').textContent = instansi;
    document.getElementById('detailJumlah').textContent   = jumlah + ' orang';
    document.getElementById('detailSite').textContent     = site;
    document.getElementById('detailCatatan').textContent  = catatan || '—';
    document.getElementById('detailStatus').innerHTML =
        `<span class="badge bg-${badgeClass}">${statusLabel}</span>`;

    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/lunch/guests/${id}`;

    const btnDelete = document.getElementById('btnDelete');
    btnDelete.style.display = (status === 'pending') ? 'inline-block' : 'none';

    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

// ── H-2 Confirm ────────────────────────────────────────────────────────────
function h2Confirm(id) {
    if (!confirm('Konfirmasi tamu ini jadi datang? Mereka akan dimasukkan ke order catering.')) return;

    fetch(`/lunch/guests/${id}/h2-confirm`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById(`h2card-${id}`);
            if (card) {
                card.innerHTML = `<div class="card-body py-2 px-3 text-success">
                    <i class="bi bi-check-circle-fill me-1"></i> ${data.message}
                </div>`;
                card.classList.replace('border-warning', 'border-success');
            }
        } else {
            alert(data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(() => alert('Terjadi kesalahan, coba lagi.'));
}

// ── H-2 Cancel ─────────────────────────────────────────────────────────────
function h2Cancel(id) {
    if (!confirm('Batalkan tamu ini? Mereka tidak akan dimasukkan ke order catering.')) return;

    fetch(`/lunch/guests/${id}/h2-cancel`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById(`h2card-${id}`);
            if (card) {
                card.innerHTML = `<div class="card-body py-2 px-3 text-muted">
                    <i class="bi bi-x-circle me-1"></i> ${data.message}
                </div>`;
                card.classList.replace('border-warning', 'border-secondary');
            }
        } else {
            alert(data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(() => alert('Terjadi kesalahan, coba lagi.'));
}
</script>
@endpush
