@extends('templates.main')

@section('title', 'Form Pengecekan Rutin')

@section('content')

<style>
    /* ═══════════════════════════════════════════ */
    /* MOBILE-FIRST RESPONSIVE FORM               */
    /* ═══════════════════════════════════════════ */

    .check-item-row {
        border-bottom: 1px solid #eee;
        padding: 10px 0;
        transition: background 0.15s;
    }
    .check-item-row:hover {
        background: #F8FAFC;
    }
    .check-item-row:last-child {
        border-bottom: none;
    }

    /* Status toggle buttons - big tap targets for mobile */
    .status-toggle {
        display: none;
    }
    .status-toggle + label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 56px;
        height: 42px;
        border: 2px solid #E4E9F0;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.15s;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    .status-toggle-ok:checked + label {
        background: #71A62F;
        border-color: #4A7A1C;
        color: #fff;
    }
    .status-toggle-ng:checked + label {
        background: #ED1B2F;
        border-color: #C4142A;
        color: #fff;
    }

    /* Category header */
    .category-header {
        background: linear-gradient(135deg, #093D7D 0%, #0B50A1 100%);
        color: #fff;
        padding: 10px 16px;
        border-radius: 8px;
        margin-bottom: 8px;
        font-weight: 600;
    }

    /* Schedule info badges */
    .schedule-badge {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
    }

    /* Needs replacement checkbox */
    .replacement-check {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    /* Notes input - expandable */
    .item-notes {
        border: 1px solid #E4E9F0;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 13px;
        width: 100%;
        resize: none;
        transition: border-color 0.15s;
    }
    .item-notes:focus {
        border-color: #0B50A1;
        box-shadow: 0 0 0 0.15rem rgba(11, 80, 161, 0.1);
        outline: none;
    }

    /* Frequency tabs - mobile friendly */
    .freq-tab {
        padding: 8px 16px;
        border: 2px solid #E4E9F0;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.15s;
        text-decoration: none;
        color: #333;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .freq-tab:hover { border-color: #0B50A1; color: #0B50A1; }
    .freq-tab.active { background: #0B50A1; border-color: #0B50A1; color: #fff; }
    .freq-tab.tab-overdue { border-color: #C4142A; }
    .freq-tab.tab-overdue.active { background: #ED1B2F; border-color: #C4142A; }
    .freq-tab.tab-due { border-color: #D8CE3A; }
    .freq-tab.tab-due.active { background: #D8CE3A; border-color: #D8CE3A; color: #000; }

    /* Standard description tooltip */
    .standard-desc {
        font-size: 12px;
        color: #64748B;
        font-style: italic;
    }

    /* Mobile adjustments */
    @media (max-width: 576px) {
        .status-toggle + label {
            min-width: 48px;
            height: 38px;
            font-size: 14px;
        }
        .check-item-row {
            padding: 8px 0;
        }
    }
</style>

<div class="pagetitle">
    <h1>Pengecekan Rutin</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.dispatcher-dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('dispatcher.routine-check.index') }}">Pengecekan Rutin</a></li>
            <li class="breadcrumb-item active">Form Pengecekan</li>
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

<form action="{{ route('dispatcher.routine-check.store') }}" method="POST" id="routineCheckForm">
    @csrf

    {{-- ═══════════════════════════════════════════ --}}
    {{-- HEADER: Kendaraan, Driver, Jenis            --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body pt-3">
                        <div class="row g-3">

                            {{-- Dropdown Kendaraan --}}
                            <div class="col-md-4">
                                <label for="vehicle_id" class="form-label fw-semibold">
                                    <i class="bi bi-truck me-1"></i> Kendaraan <span class="text-danger">*</span>
                                </label>
                                <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                                    <option value="">— Pilih Kendaraan —</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}"
                                                data-nopol="{{ $vehicle->consumerial_code }}"
                                                {{ old('vehicle_id', request('vehicle_id')) == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->consumerial_code }} — {{ $vehicle->consumerial_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Dropdown Driver --}}
                            <div class="col-md-4">
                                <label for="current_driver_id" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i> Driver <span class="text-danger">*</span>
                                </label>
                                <select name="current_driver_id" id="current_driver_id" class="form-select" required>
                                    <option value="">— Pilih Driver —</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" {{ old('current_driver_id') == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->nama_lengkap }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Hidden sub_type (diisi via JS saat pilih tab) --}}
                            <input type="hidden" name="sub_type" id="sub_type" value="{{ old('sub_type', 'daily') }}">

                            {{-- Catatan --}}
                            <div class="col-md-4">
                                <label for="notes" class="form-label fw-semibold">
                                    <i class="bi bi-chat-left-text me-1"></i> Catatan Umum
                                </label>
                                <input type="text" name="notes" id="notes" class="form-control"
                                       placeholder="Opsional..." value="{{ old('notes') }}">
                            </div>

                        </div>

                        {{-- Schedule Info (muncul setelah pilih kendaraan) --}}
                        <div id="scheduleInfo" class="mt-3" style="display: none;">
                            <div class="d-flex flex-wrap gap-2" id="scheduleInfoBadges"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- FREQUENCY TABS                              --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="section mb-3" style="margin-top: -10px;">
        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="#" class="freq-tab active" data-freq="daily" onclick="switchFrequency('daily', this)">
                        <i class="bi bi-calendar-day"></i> Harian
                        <span class="badge bg-light text-dark" id="badgeDaily">{{ $dailyParts->count() }}</span>
                    </a>
                    <a href="#" class="freq-tab" data-freq="weekly" onclick="switchFrequency('weekly', this)">
                        <i class="bi bi-calendar-week"></i> Mingguan
                        <span class="badge bg-light text-dark" id="badgeWeekly">{{ $weeklyParts->count() }}</span>
                    </a>
                    <a href="#" class="freq-tab" data-freq="monthly" onclick="switchFrequency('monthly', this)">
                        <i class="bi bi-calendar-month"></i> Bulanan
                        <span class="badge bg-light text-dark" id="badgeMonthly">{{ $monthlyParts->count() }}</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- CHECKLIST ITEMS                             --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="section" style="margin-top: -20px;">

        {{-- DAILY ITEMS --}}
        <div id="panel-daily" class="freq-panel">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body pt-3">

                            <div class="category-header">
                                <i class="bi bi-shield-check me-1"></i> KESIAPAN — Pengecekan Harian
                            </div>

                            @foreach($dailyParts as $part)
                            <div class="check-item-row">
                                <div class="row align-items-center g-2">
                                    {{-- No + Nama Item --}}
                                    <div class="col-12 col-md-4">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="badge bg-light text-dark mt-1">{{ $loop->iteration }}</span>
                                            <div>
                                                <strong>{{ $part->name }}</strong>
                                                @if($part->standard_description)
                                                    <br><span class="standard-desc">{{ $part->standard_description }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Status V/X --}}
                                    <div class="col-auto">
                                        <div class="d-flex gap-2">
                                            <input type="radio" name="items[{{ $part->id }}][status]" value="ok"
                                                   id="ok_{{ $part->id }}" class="status-toggle status-toggle-ok"
                                                   {{ old("items.{$part->id}.status") == 'ok' ? 'checked' : '' }} required>
                                            <label for="ok_{{ $part->id }}"><i class="bi bi-check2-circle"></i></label>

                                            <input type="radio" name="items[{{ $part->id }}][status]" value="ng"
                                                   id="ng_{{ $part->id }}" class="status-toggle status-toggle-ng"
                                                   {{ old("items.{$part->id}.status") == 'ng' ? 'checked' : '' }}>
                                            <label for="ng_{{ $part->id }}"><i class="bi bi-x-circle"></i></label>
                                        </div>
                                    </div>

                                    {{-- Needs Replacement --}}
                                    <div class="col-auto">
                                        <div class="form-check">
                                            <input type="hidden" name="items[{{ $part->id }}][needs_replacement]" value="0">
                                            <input type="checkbox" name="items[{{ $part->id }}][needs_replacement]" value="1"
                                                   class="form-check-input replacement-check"
                                                   id="replace_{{ $part->id }}"
                                                   {{ old("items.{$part->id}.needs_replacement") == '1' ? 'checked' : '' }}>
                                            <label for="replace_{{ $part->id }}" class="form-check-label small">Ganti</label>
                                        </div>
                                    </div>

                                    {{-- Notes --}}
                                    <div class="col-12 col-md">
                                        <textarea name="items[{{ $part->id }}][notes]" class="item-notes"
                                                  rows="1" placeholder="Keterangan...">{{ old("items.{$part->id}.notes") }}</textarea>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- WEEKLY ITEMS --}}
        <div id="panel-weekly" class="freq-panel" style="display: none;">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body pt-3">

                            {{-- Keselamatan --}}
                            <div class="category-header">
                                <i class="bi bi-heart-pulse me-1"></i> KESELAMATAN — Pengecekan Mingguan
                            </div>

                            @foreach($weeklyParts->where('category', 'keselamatan') as $part)
                            <div class="check-item-row">
                                <div class="row align-items-center g-2">
                                    <div class="col-12 col-md-4">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="badge bg-light text-dark mt-1">{{ $part->sort_order }}</span>
                                            <div>
                                                <strong>{{ $part->name }}</strong>
                                                @if($part->standard_description)
                                                    <br><span class="standard-desc">{{ $part->standard_description }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="d-flex gap-2">
                                            <input type="radio" name="items[{{ $part->id }}][status]" value="ok"
                                                   id="ok_{{ $part->id }}" class="status-toggle status-toggle-ok"
                                                   {{ old("items.{$part->id}.status") == 'ok' ? 'checked' : '' }} required>
                                            <label for="ok_{{ $part->id }}"><i class="bi bi-check2-circle"></i></label>
                                            <input type="radio" name="items[{{ $part->id }}][status]" value="ng"
                                                   id="ng_{{ $part->id }}" class="status-toggle status-toggle-ng"
                                                   {{ old("items.{$part->id}.status") == 'ng' ? 'checked' : '' }}>
                                            <label for="ng_{{ $part->id }}"><i class="bi bi-x-circle"></i></label>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-check">
                                            <input type="hidden" name="items[{{ $part->id }}][needs_replacement]" value="0">
                                            <input type="checkbox" name="items[{{ $part->id }}][needs_replacement]" value="1"
                                                   class="form-check-input replacement-check" id="replace_{{ $part->id }}"
                                                   {{ old("items.{$part->id}.needs_replacement") == '1' ? 'checked' : '' }}>
                                            <label for="replace_{{ $part->id }}" class="form-check-label small">Ganti</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md">
                                        <textarea name="items[{{ $part->id }}][notes]" class="item-notes"
                                                  rows="1" placeholder="Keterangan...">{{ old("items.{$part->id}.notes") }}</textarea>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                            {{-- Kelengkapan --}}
                            <div class="category-header mt-3">
                                <i class="bi bi-gear me-1"></i> KELENGKAPAN — Pengecekan Mingguan
                            </div>

                            @foreach($weeklyParts->where('category', 'kelengkapan') as $part)
                            <div class="check-item-row">
                                <div class="row align-items-center g-2">
                                    <div class="col-12 col-md-4">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="badge bg-light text-dark mt-1">{{ $part->sort_order }}</span>
                                            <div>
                                                <strong>{{ $part->name }}</strong>
                                                @if($part->standard_description)
                                                    <br><span class="standard-desc">{{ $part->standard_description }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="d-flex gap-2">
                                            <input type="radio" name="items[{{ $part->id }}][status]" value="ok"
                                                   id="ok_{{ $part->id }}" class="status-toggle status-toggle-ok"
                                                   {{ old("items.{$part->id}.status") == 'ok' ? 'checked' : '' }} required>
                                            <label for="ok_{{ $part->id }}"><i class="bi bi-check2-circle"></i></label>
                                            <input type="radio" name="items[{{ $part->id }}][status]" value="ng"
                                                   id="ng_{{ $part->id }}" class="status-toggle status-toggle-ng"
                                                   {{ old("items.{$part->id}.status") == 'ng' ? 'checked' : '' }}>
                                            <label for="ng_{{ $part->id }}"><i class="bi bi-x-circle"></i></label>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-check">
                                            <input type="hidden" name="items[{{ $part->id }}][needs_replacement]" value="0">
                                            <input type="checkbox" name="items[{{ $part->id }}][needs_replacement]" value="1"
                                                   class="form-check-input replacement-check" id="replace_{{ $part->id }}"
                                                   {{ old("items.{$part->id}.needs_replacement") == '1' ? 'checked' : '' }}>
                                            <label for="replace_{{ $part->id }}" class="form-check-label small">Ganti</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md">
                                        <textarea name="items[{{ $part->id }}][notes]" class="item-notes"
                                                  rows="1" placeholder="Keterangan...">{{ old("items.{$part->id}.notes") }}</textarea>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MONTHLY ITEMS --}}
        <div id="panel-monthly" class="freq-panel" style="display: none;">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body pt-3">

                            <div class="category-header">
                                <i class="bi bi-calendar-check me-1"></i> KELENGKAPAN — Pengecekan Bulanan
                            </div>

                            @foreach($monthlyParts as $part)
                            <div class="check-item-row">
                                <div class="row align-items-center g-2">
                                    <div class="col-12 col-md-4">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="badge bg-light text-dark mt-1">{{ $part->sort_order }}</span>
                                            <div>
                                                <strong>{{ $part->name }}</strong>
                                                @if($part->standard_description)
                                                    <br><span class="standard-desc">{{ $part->standard_description }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="d-flex gap-2">
                                            <input type="radio" name="items[{{ $part->id }}][status]" value="ok"
                                                   id="ok_{{ $part->id }}" class="status-toggle status-toggle-ok"
                                                   {{ old("items.{$part->id}.status") == 'ok' ? 'checked' : '' }} required>
                                            <label for="ok_{{ $part->id }}"><i class="bi bi-check2-circle"></i></label>
                                            <input type="radio" name="items[{{ $part->id }}][status]" value="ng"
                                                   id="ng_{{ $part->id }}" class="status-toggle status-toggle-ng"
                                                   {{ old("items.{$part->id}.status") == 'ng' ? 'checked' : '' }}>
                                            <label for="ng_{{ $part->id }}"><i class="bi bi-x-circle"></i></label>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-check">
                                            <input type="hidden" name="items[{{ $part->id }}][needs_replacement]" value="0">
                                            <input type="checkbox" name="items[{{ $part->id }}][needs_replacement]" value="1"
                                                   class="form-check-input replacement-check" id="replace_{{ $part->id }}"
                                                   {{ old("items.{$part->id}.needs_replacement") == '1' ? 'checked' : '' }}>
                                            <label for="replace_{{ $part->id }}" class="form-check-label small">Ganti</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md">
                                        <textarea name="items[{{ $part->id }}][notes]" class="item-notes"
                                                  rows="1" placeholder="Keterangan...">{{ old("items.{$part->id}.notes") }}</textarea>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- SUBMIT BUTTON (Sticky di mobile)           --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="position-sticky bottom-0 bg-white py-3 border-top shadow-sm" style="z-index: 100;">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="text-muted small" id="checkedCount">0 item dicek</span>
                    <span class="text-danger small ms-2" id="ngCount" style="display: none;">
                        <i class="bi bi-exclamation-circle"></i> <span id="ngCountNum">0</span> item bermasalah
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dispatcher.routine-check.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="bi bi-check-lg me-1"></i> Submit Pengecekan
                    </button>
                </div>
            </div>
        </div>
    </div>

</form>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ─── Tab Switching ───
    window.switchFrequency = function(freq, el) {
        event.preventDefault();

        // Hide all panels
        document.querySelectorAll('.freq-panel').forEach(p => p.style.display = 'none');
        document.querySelectorAll('.freq-tab').forEach(t => t.classList.remove('active'));

        // Show selected
        document.getElementById('panel-' + freq).style.display = 'block';
        el.classList.add('active');
        document.getElementById('sub_type').value = freq;

        // Disable required pada panel yang hidden, enable pada panel yang aktif
        document.querySelectorAll('.freq-panel').forEach(panel => {
            const isVisible = panel.style.display !== 'none';
            panel.querySelectorAll('.status-toggle').forEach(input => {
                if (isVisible) {
                    // Hanya set required pada salah satu radio per group
                    const name = input.getAttribute('name');
                    const group = panel.querySelectorAll('[name="' + name + '"]');
                    if (group.length > 0) group[0].required = true;
                } else {
                    input.required = false;
                }
            });
        });

        updateCounts();
    };

    // ─── Schedule Info AJAX ───
    const vehicleSelect = document.getElementById('vehicle_id');
    vehicleSelect.addEventListener('change', function() {
        const vehicleId = this.value;
        const infoDiv = document.getElementById('scheduleInfo');
        const badgesDiv = document.getElementById('scheduleInfoBadges');

        if (!vehicleId) {
            infoDiv.style.display = 'none';
            return;
        }

        fetch('{{ route("dispatcher.routine-check.schedule-info") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ vehicle_id: vehicleId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                badgesDiv.innerHTML = '';
                const schedules = data.schedules;

                ['daily', 'weekly', 'monthly'].forEach(freq => {
                    const s = schedules[freq];
                    if (!s) return;

                    const statusColors = {
                        overdue: 'bg-danger',
                        due: 'bg-warning text-dark',
                        completed: 'bg-success',
                        pending: 'bg-secondary'
                    };

                    let html = '<span class="schedule-badge badge ' + (statusColors[s.status] || 'bg-secondary') + '">';
                    html += s.frequency_label + ': ' + (s.status === 'overdue' ? 'TERLAMBAT' : s.status === 'due' ? 'HARI INI' : s.status);
                    if (s.last_checked_at) html += ' (terakhir: ' + s.last_checked_at + ')';
                    html += '</span>';
                    badgesDiv.innerHTML += html;

                    // Auto-highlight tab yang overdue/due
                    const tab = document.querySelector('[data-freq="' + freq + '"]');
                    if (tab) {
                        tab.classList.remove('tab-overdue', 'tab-due');
                        if (s.status === 'overdue') tab.classList.add('tab-overdue');
                        if (s.status === 'due') tab.classList.add('tab-due');
                    }
                });

                infoDiv.style.display = 'block';
            }
        })
        .catch(err => console.error('Schedule info error:', err));
    });

    // Auto-trigger jika sudah ada vehicle_id di URL
    if (vehicleSelect.value) {
        vehicleSelect.dispatchEvent(new Event('change'));
    }

    // ─── Live Count ───
    function updateCounts() {
        const activePanel = document.querySelector('.freq-panel[style*="display: block"], .freq-panel:not([style*="display: none"])');
        if (!activePanel) return;

        const radios = activePanel.querySelectorAll('.status-toggle:checked');
        const ngRadios = activePanel.querySelectorAll('.status-toggle-ng:checked');

        document.getElementById('checkedCount').textContent = radios.length + ' item dicek';

        const ngCountEl = document.getElementById('ngCount');
        const ngCountNum = document.getElementById('ngCountNum');
        if (ngRadios.length > 0) {
            ngCountEl.style.display = 'inline';
            ngCountNum.textContent = ngRadios.length;
        } else {
            ngCountEl.style.display = 'none';
        }
    }

    // Listen to all radio changes
    document.querySelectorAll('.status-toggle').forEach(radio => {
        radio.addEventListener('change', updateCounts);
    });

    updateCounts();

    // ─── Double Submit Prevention ───
    // ─── Fix: Ensure all statuses set before submit ───
    document.getElementById('routineCheckForm').addEventListener('submit', function() {
        document.querySelectorAll('.replacement-check:checked').forEach(cb => {
            const partId = cb.name.match(/items\\[(\\d+)\\]/)[1];
            const ngRadio = document.getElementById('ng_' + partId);
            if (ngRadio && !ngRadio.checked) {
                ngRadio.checked = true;
            }
        });
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    });

    // ─── Fix 1: Ganti checkbox → auto X + disable V/X ───
document.querySelectorAll('.replacement-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        // Ambil part id dari name: items[123][needs_replacement] → 123
        const partId = this.name.match(/items\[(\d+)\]/)[1];
        const okRadio = document.getElementById('ok_' + partId);
        const ngRadio = document.getElementById('ng_' + partId);
        const okLabel = okRadio.nextElementSibling;
        const ngLabel = ngRadio.nextElementSibling;

        if (this.checked) {
            // Auto-set ke X (NG)
            ngRadio.checked = true;
            okRadio.checked = false;

            // Disable kedua tombol
            okRadio.disabled = true;
            ngRadio.disabled = true;
            okLabel.style.opacity = '0.4';
            ngLabel.style.opacity = '0.4';
            okLabel.style.pointerEvents = 'none';
            ngLabel.style.pointerEvents = 'none';
        } else {
            // Enable kembali
            okRadio.disabled = false;
            ngRadio.disabled = false;
            okLabel.style.opacity = '1';
            ngLabel.style.opacity = '1';
            okLabel.style.pointerEvents = 'auto';
            ngLabel.style.pointerEvents = 'auto';
        }

        updateCounts();
    });

    // Trigger on page load (kalau old() ada)
    if (checkbox.checked) {
        checkbox.dispatchEvent(new Event('change'));
    }
});

});
</script>

@endsection
