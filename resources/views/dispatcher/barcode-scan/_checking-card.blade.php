@php
    $okCount  = $detail->itemResults->filter(fn($r) => in_array($r->status, ['ok', 'baik']))->count();
    $ngCount  = $detail->itemResults->filter(fn($r) => !in_array($r->status, ['ok', 'baik']))->count();
    $total    = $detail->itemResults->count();

    $typeLabel = match(true) {
        $detail->type === 'body'                                  => 'Body Check',
        $detail->type === 'routine' && $detail->sub_type === 'daily'   => 'Harian',
        $detail->type === 'routine' && $detail->sub_type === 'weekly'  => 'Mingguan',
        $detail->type === 'routine' && $detail->sub_type === 'monthly' => 'Bulanan',
        default => ucfirst($detail->type),
    };

    $typeCss = match(true) {
        $detail->type === 'body'                                  => 'type-body',
        $detail->type === 'routine' && $detail->sub_type === 'daily'   => 'type-daily',
        $detail->type === 'routine' && $detail->sub_type === 'weekly'  => 'type-weekly',
        $detail->type === 'routine' && $detail->sub_type === 'monthly' => 'type-monthly',
        default => 'type-daily',
    };
@endphp

<div class="check-timeline-item">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <span class="type-badge {{ $typeCss }}">{{ $typeLabel }}</span>
            <span class="ms-2 text-muted" style="font-size:12px;">{{ $detail->checking_detail_code }}</span>
        </div>
        <div class="text-end">
            <div class="fw-semibold" style="font-size:13px; color:#012970;">
                {{ $detail->checked_at ? $detail->checked_at->format('d M Y, H:i') : '—' }}
            </div>
            @if($detail->checked_at)
                <div class="text-muted" style="font-size:11px;">{{ $detail->checked_at->diffForHumans() }}</div>
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-3 mt-2" style="font-size:13px;">
        @if($detail->dispatcher)
            <div>
                <span class="text-muted">Dispatcher:</span>
                <span class="fw-semibold">{{ $detail->dispatcher->name }}</span>
            </div>
        @endif
        @if($detail->currentDriver)
            <div>
                <span class="text-muted">Driver:</span>
                <span class="fw-semibold">{{ $detail->currentDriver->name }}</span>
            </div>
        @endif
    </div>

    @if($total > 0)
        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
            <span class="stat-pill stat-ok">
                <i class="bi bi-check-circle-fill"></i> {{ $okCount }} OK
            </span>
            @if($ngCount > 0)
                <span class="stat-pill stat-ng">
                    <i class="bi bi-exclamation-circle-fill"></i> {{ $ngCount }} NG
                </span>
            @endif
            <span class="text-muted" style="font-size:12px;">dari {{ $total }} item</span>
        </div>
    @endif

    {{-- Link ke detail --}}
    @php
        $showRoute = null;
        if ($detail->type === 'body') {
            $showRoute = route('dispatcher.body-check.show', $detail->checking_detail_code);
        } elseif ($detail->sub_type === 'daily') {
            $showRoute = route('dispatcher.daily-check.show', $detail->checking_detail_code);
        } elseif ($detail->sub_type === 'weekly') {
            $showRoute = route('dispatcher.weekly-check.show', $detail->checking_detail_code);
        } elseif ($detail->sub_type === 'monthly') {
            $showRoute = route('dispatcher.monthly-check.show', $detail->checking_detail_code);
        }
    @endphp
    @if($showRoute && in_array(auth()->user()->role, ['dispatcher', 'superadmin']))
        <div class="mt-2">
            <a href="{{ $showRoute }}" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:12px;">
                <i class="bi bi-eye me-1"></i> Lihat Detail
            </a>
        </div>
    @endif
</div>
