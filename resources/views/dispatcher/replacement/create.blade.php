@extends('templates.main')

@section('title', 'Ajukan Permohonan Penggantian')

@section('content')

<style>
    .item-card {
        border: 2px solid #E4E9F0;
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 10px;
        transition: all 0.2s;
        cursor: pointer;
    }
    .item-card:hover { border-color: #0B50A1; background: #f8f9ff; }
    .item-card.selected { border-color: #0B50A1; background: #f0f2ff; }
    .item-card .item-checkbox {
        width: 22px;
        height: 22px;
        cursor: pointer;
        accent-color: #0B50A1;
    }

    .status-badge-sm {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-badge-sm.ok   { background: #d1e7dd; color: #4A7A1C; }
    .status-badge-sm.ng   { background: #f8d7da; color: #C4142A; }
    .status-badge-sm.baik   { background: #d1e7dd; color: #4A7A1C; }
    .status-badge-sm.cukup  { background: #cff4fc; color: #0B50A1; }
    .status-badge-sm.kurang { background: #fff3cd; color: #997404; }
    .status-badge-sm.tidak  { background: #f8d7da; color: #C4142A; }

    .source-info {
        background: #F8FAFC;
        border-left: 4px solid #0B50A1;
        padding: 12px 16px;
        border-radius: 0 8px 8px 0;
    }
</style>

<div class="pagetitle">
    <h1>Ajukan Permohonan Penggantian</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.replacement.index') }}">Permohonan</a></li>
            <li class="breadcrumb-item active">Ajukan Baru</li>
        </ol>
    </nav>
</div>

{{-- Validation Errors --}}
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="bi bi-exclamation-triangle me-1"></i> Terdapat kesalahan:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- ═══════════════════════════════════════════════ --}}
{{-- SOURCE INFO — dari pengecekan mana              --}}
{{-- ═══════════════════════════════════════════════ --}}
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="source-info mb-3">
                <div class="row g-2">
                    <div class="col-md-3 col-6">
                        <small class="text-muted d-block">Kode Pengecekan</small>
                        <strong>{{ $detail->checking_detail_code }}</strong>
                    </div>
                    <div class="col-md-3 col-6">
                        <small class="text-muted d-block">Kendaraan</small>
                        <strong>{{ $detail->vehicleChecking->vehicle->consumerial_code ?? '-' }}</strong>
                        <span class="text-muted">— {{ $detail->vehicleChecking->vehicle->consumerial_name ?? '' }}</span>
                    </div>
                    <div class="col-md-3 col-6">
                        <small class="text-muted d-block">Jenis</small>
                        <strong>{{ $detail->type === 'routine' ? 'Rutin' : 'Body' }}</strong>
                        @if($detail->sub_type)
                            @php $subLabels = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan']; @endphp
                            <span class="text-muted">— {{ $subLabels[$detail->sub_type] ?? $detail->sub_type }}</span>
                        @endif
                    </div>
                    <div class="col-md-3 col-6">
                        <small class="text-muted d-block">Tanggal Cek</small>
                        <strong>{{ $detail->checked_at->translatedFormat('d F Y, H:i') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<form action="{{ route('dispatcher.replacement.store', $detail->checking_detail_code) }}" method="POST" id="replacementForm">
    @csrf

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- PILIH ITEM YANG PERLU DIGANTI                   --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <section class="section" style="margin-top: -10px;">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0 pb-0">Pilih Item yang Perlu Diganti</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                <i class="bi bi-check-all me-1"></i> Pilih Semua
                            </button>
                        </div>

                        <p class="text-muted small">
                            Berikut item dari hasil pengecekan yang ditandai perlu penggantian. Pilih item yang ingin diajukan.
                        </p>

                        @forelse($detail->itemResults as $result)
                        <div class="item-card" onclick="toggleItem(this, {{ $result->id }})">
                            <div class="row align-items-center g-2">

                                {{-- Checkbox --}}
                                <div class="col-auto">
                                    <input type="checkbox" name="items[]" value="{{ $result->id }}"
                                           class="item-checkbox" id="item_{{ $result->id }}"
                                           {{ in_array($result->id, old('items', [])) ? 'checked' : '' }}>
                                </div>

                                {{-- Item Info --}}
                                <div class="col">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <strong>{{ $result->vehiclePart->name }}</strong>
                                        <span class="status-badge-sm {{ $result->status }}">
                                            {{ ucfirst($result->status) }}
                                        </span>
                                    </div>
                                    @if($result->notes)
                                        <small class="text-muted fst-italic">
                                            <i class="bi bi-chat-left-text me-1"></i>{{ $result->notes }}
                                        </small>
                                    @endif
                                </div>

                                {{-- Notes per item --}}
                                <div class="col-12 mt-2">
                                    <input type="text" name="notes[{{ $result->id }}]"
                                           class="form-control form-control-sm"
                                           placeholder="Catatan tambahan untuk item ini (opsional)..."
                                           value="{{ old("notes.{$result->id}") }}"
                                           onclick="event.stopPropagation()">
                                </div>

                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle text-success fs-1"></i>
                            <p class="text-muted mt-2 mb-0">Tidak ada item yang perlu penggantian</p>
                        </div>
                        @endforelse

                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- SIDEBAR: Driver & Submit                --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body pt-3">
                        <h5 class="card-title">Info Tambahan</h5>

                        {{-- Driver yang bawa ke service --}}
                        <div class="mb-3">
                            <label for="desired_driver_id" class="form-label fw-semibold">
                                <i class="bi bi-person-gear me-1"></i> Driver untuk Service
                            </label>
                            <select name="desired_driver_id" id="desired_driver_id" class="form-select">
                                <option value="">— Belum ditentukan —</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}"
                                            {{ old('desired_driver_id') == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->nama_lengkap }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Driver yang akan membawa kendaraan ke tempat service. Bisa diisi nanti.</small>
                        </div>

                        {{-- Last driver info --}}
                        <div class="mb-3 p-2 bg-light rounded">
                            <small class="text-muted d-block">Driver Terakhir (saat pengecekan)</small>
                            <strong>{{ $detail->currentDriver->name ?? '-' }}</strong>
                        </div>

                        {{-- Summary --}}
                        <div class="mb-3 p-2 bg-light rounded">
                            <small class="text-muted d-block">Item Dipilih</small>
                            <strong id="selectedCount">0</strong> <span class="text-muted">dari {{ $detail->itemResults->count() }} item</span>
                        </div>

                        {{-- Actions --}}
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>
                                <i class="bi bi-send me-1"></i> Ajukan Permohonan
                            </button>
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

</form>

<script>
document.addEventListener('DOMContentLoaded', function() {

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.item-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = checked;
        document.getElementById('btnSubmit').disabled = checked === 0;
    }

    // Toggle item card
    window.toggleItem = function(card, id) {
        const checkbox = document.getElementById('item_' + id);
        checkbox.checked = !checkbox.checked;
        card.classList.toggle('selected', checkbox.checked);
        updateSelectedCount();
    };

    // Direct checkbox click
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.addEventListener('click', function(e) {
            e.stopPropagation();
            this.closest('.item-card').classList.toggle('selected', this.checked);
            updateSelectedCount();
        });

        // Init state on load
        if (cb.checked) {
            cb.closest('.item-card').classList.add('selected');
        }
    });

    // Select all
    window.selectAll = function() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const allChecked = Array.from(checkboxes).every(c => c.checked);

        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
            cb.closest('.item-card').classList.toggle('selected', cb.checked);
        });
        updateSelectedCount();
    };

    updateSelectedCount();

    // Double submit prevention
    document.getElementById('replacementForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengajukan...';
    });
});
</script>

@endsection
