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
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Permohonan</h5>
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-lightning-charge"></i> Dimuat 100 data per batch &middot; 10 per halaman
                    </span>
                </div>
                <div class="card-body">
                    {{-- Filter rentang waktu (maks. 30 hari) --}}
                    <form method="GET" action="{{ route('requester-history') }}" class="row g-2 align-items-end mb-3" id="historyFilter">
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label small mb-1" for="historyFrom">Dari Tanggal</label>
                            <input type="date" class="form-control form-control-sm" id="historyFrom" name="from"
                                   value="{{ $from }}">
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label small mb-1" for="historyTo">Sampai Tanggal</label>
                            <input type="date" class="form-control form-control-sm" id="historyTo" name="to"
                                   value="{{ $to }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-funnel"></i> Terapkan Filter
                            </button>
                            <a href="{{ route('requester-history') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">
                                Rentang maksimal 30 hari. Bila tanggal terbalik, sistem otomatis membacanya
                                dari tanggal awal ke tanggal akhir.
                            </small>
                        </div>
                    </form>

                    @if(!empty($rangeNotice))
                        <div class="alert alert-warning py-2 small">
                            <i class="bi bi-info-circle"></i> {{ $rangeNotice }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="historyTable">
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
                            <tbody id="historyBody">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Memuat riwayat…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination 10 baris/halaman + tombol memuat batch 100 berikutnya --}}
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                        <div class="small text-muted" id="historyCount">&nbsp;</div>
                        <nav aria-label="Pagination riwayat" id="historyPager" hidden>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item" id="historyPrevItem">
                                    <button type="button" class="page-link" id="historyPrev" aria-label="Sebelumnya">
                                        <i class="bi bi-chevron-left"></i> Sebelumnya
                                    </button>
                                </li>
                                <li class="page-item disabled">
                                    <span class="page-link" id="historyPageInfo">Halaman 1 / 1</span>
                                </li>
                                <li class="page-item" id="historyNextItem">
                                    <button type="button" class="page-link" id="historyNext" aria-label="Berikutnya">
                                        Berikutnya <i class="bi bi-chevron-right"></i>
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const FILTER_URL = @json(route('requester-history'));
    const PER_PAGE = 10;
    const initialRows = @json($historyRows);

    const state = {
        rows: Array.isArray(initialRows) ? initialRows : [],
        page: 0,
        hasMore: @json((bool) $hasMore),
        loading: false,
    };

    const body = document.getElementById('historyBody');
    const countEl = document.getElementById('historyCount');
    const pager = document.getElementById('historyPager');
    const prevBtn = document.getElementById('historyPrev');
    const nextBtn = document.getElementById('historyNext');
    const prevItem = document.getElementById('historyPrevItem');
    const nextItem = document.getElementById('historyNextItem');
    const pageInfo = document.getElementById('historyPageInfo');

    const fromInput = document.getElementById('historyFrom');
    const toInput = document.getElementById('historyTo');
    const filterForm = document.getElementById('historyFilter');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function currentPageRows() {
        const start = state.page * PER_PAGE;
        return state.rows.slice(start, start + PER_PAGE);
    }

    function loadedPageCount() {
        return Math.max(1, Math.ceil(state.rows.length / PER_PAGE));
    }

    function render() {
        const rows = currentPageRows();

        if (rows.length === 0) {
            body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">'
                + (state.hasMore ? 'Tidak ada data pada halaman ini.' : 'Belum ada riwayat permohonan.')
                + '</td></tr>';
        } else {
            body.innerHTML = rows.map(function (item) {
                const vehicle = escapeHtml(item.vehicle_code || 'N/A')
                    + '<br><small class="text-muted">' + escapeHtml(item.vehicle_name || '')
                    + (item.vehicle_type ? ' (' + escapeHtml(item.vehicle_type) + ')' : '')
                    + '</small>';

                return '<tr>'
                    + '<td><code>' + escapeHtml(item.request_number) + '</code></td>'
                    + '<td>' + escapeHtml(item.date) + '</td>'
                    + '<td>' + vehicle + '</td>'
                    + '<td><span class="badge bg-secondary">' + escapeHtml(item.gasoline_type) + '</span></td>'
                    + '<td><strong>' + escapeHtml(item.amount_formatted) + '</strong></td>'
                    + '<td><span class="badge ' + escapeHtml(item.status_class) + '">' + escapeHtml(item.status_label) + '</span></td>'
                    + '<td><a href="' + escapeHtml(item.detail_url) + '" class="btn btn-sm btn-primary">'
                    + '<i class="bi bi-eye"></i> Detail</a></td>'
                    + '</tr>';
            }).join('');
        }

        const startNo = state.page * PER_PAGE + 1;
        const endNo = state.page * PER_PAGE + rows.length;
        countEl.textContent = rows.length
            ? 'Menampilkan ' + startNo + '–' + endNo + ' dari ' + state.rows.length + ' data dimuat'
              + (state.hasMore ? ' (masih ada data berikutnya)' : '')
            : 'Tidak ada data';

        const pages = loadedPageCount();
        pager.hidden = state.rows.length <= PER_PAGE;
        pageInfo.textContent = 'Halaman ' + (state.page + 1) + ' / ' + pages + (state.hasMore ? '+' : '');

        prevItem.classList.toggle('disabled', state.page === 0 || state.loading);
        prevBtn.disabled = state.page === 0 || state.loading;

        // Tombol "Berikutnya" tetap aktif bila batch 100 sudah habis tetapi
        // server masih punya data (akan memuat 100 baris berikutnya).
        const hasNextPage = (state.page + 1) * PER_PAGE < state.rows.length;
        nextItem.classList.toggle('disabled', (!hasNextPage && !state.hasMore) || state.loading);
        nextBtn.disabled = (!hasNextPage && !state.hasMore) || state.loading;
        nextBtn.innerHTML = state.loading
            ? '<span class="spinner-border spinner-border-sm"></span> Memuat…'
            : 'Berikutnya <i class="bi bi-chevron-right"></i>';
    }

    async function fetchNextBatch() {
        if (state.loading || !state.hasMore) {
            return false;
        }

        state.loading = true;
        render();

        try {
            const url = new URL(FILTER_URL, window.location.origin);
            url.searchParams.set('json', '1');
            url.searchParams.set('offset', state.rows.length);
            if (fromInput && fromInput.value) url.searchParams.set('from', fromInput.value);
            if (toInput && toInput.value) url.searchParams.set('to', toInput.value);

            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const payload = await response.json();
            state.rows = state.rows.concat(payload.rows || []);
            state.hasMore = !!payload.has_more;

            return (payload.rows || []).length > 0;
        } catch (error) {
            console.error('Gagal memuat batch riwayat:', error);
            alert('Gagal memuat data riwayat berikutnya. Silakan coba lagi.');
            return false;
        } finally {
            state.loading = false;
        }
    }

    prevBtn.addEventListener('click', function () {
        if (state.page > 0 && !state.loading) {
            state.page--;
            render();
        }
    });

    nextBtn.addEventListener('click', async function () {
        if (state.loading) return;

        const nextPageStart = (state.page + 1) * PER_PAGE;

        if (nextPageStart >= state.rows.length && state.hasMore) {
            const loaded = await fetchNextBatch();
            if (!loaded) {
                render();
                return;
            }
        }

        if ((state.page + 1) * PER_PAGE < state.rows.length) {
            state.page++;
        }

        render();
    });

    // Jaga filter tetap masuk akal sebelum dikirim: tukar bila terbalik,
    // dan potong rentang bila lebih dari 30 hari.
    if (filterForm) {
        filterForm.addEventListener('submit', function () {
            if (!fromInput || !toInput) return;

            let from = fromInput.value;
            let to = toInput.value;

            if (from && to && from > to) {
                fromInput.value = to;
                toInput.value = from;
                from = fromInput.value;
                to = toInput.value;
            }

            if (from && to) {
                const max = new Date(from + 'T00:00:00');
                max.setDate(max.getDate() + 29);
                const maxStr = max.toISOString().slice(0, 10);
                if (to > maxStr) {
                    toInput.value = maxStr;
                }
            }
        });
    }

    render();
});
</script>
@endpush
