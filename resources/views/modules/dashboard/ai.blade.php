@extends('templates.main')

@section('content')
<div class="module-dash">

  @include('modules.dashboard.partials.head', [
    'module' => $module,
    'heading' => 'Dashboard AI Assistant',
    'description' => 'Pemakaian asisten AI, jumlah percakapan, dan sesi terakhir yang bisa dibuka kembali.',
    'actions' => [
      ['label' => 'Buka AI Assistant', 'route' => 'ai-assistant.index', 'icon' => 'bi bi-robot', 'permission' => 'ai.view', 'class' => 'gde-btn--primary'],
    ],
  ])

  <div class="module-dash__grid module-dash__grid--kpi">
    <div class="gde-stat">
      <span class="gde-stat__icon"><i class="bi bi-chat-dots"></i></span>
      <div>
        <div class="gde-stat__label">Total Sesi</div>
        <div class="gde-stat__value">{{ number_format($totalSesi) }}</div>
        <div class="gde-stat__hint">Percakapan tersimpan</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--green">
      <span class="gde-stat__icon"><i class="bi bi-calendar-check"></i></span>
      <div>
        <div class="gde-stat__label">Sesi Bulan Ini</div>
        <div class="gde-stat__value">{{ number_format($sesiBulanIni) }}</div>
        <div class="gde-stat__hint">Sejak {{ now()->startOfMonth()->translatedFormat('d M Y') }}</div>
      </div>
    </div>

    <div class="gde-stat gde-stat--yellow">
      <span class="gde-stat__icon"><i class="bi bi-chat-square-text"></i></span>
      <div>
        <div class="gde-stat__label">Total Pesan</div>
        <div class="gde-stat__value">{{ number_format($totalPesan) }}</div>
        <div class="gde-stat__hint">Pesan pengguna & AI</div>
      </div>
    </div>

    <div class="gde-stat">
      <span class="gde-stat__icon"><i class="bi bi-people"></i></span>
      <div>
        <div class="gde-stat__label">Pengguna Aktif</div>
        <div class="gde-stat__value">{{ number_format($penggunaAktif) }}</div>
        <div class="gde-stat__hint">Pernah membuka sesi</div>
      </div>
    </div>
  </div>

  <div class="gde-card module-dash__card">
    <div class="gde-card__head">
      <div>
        <div class="gde-card__title"><i class="bi bi-clock-history"></i> Sesi Terakhir</div>
        <p class="gde-card__sub">8 percakapan terakhir</p>
      </div>
      <a href="{{ route('ai-assistant.index') }}" class="gde-btn gde-btn--ghost gde-btn--xs">Buka Assistant</a>
    </div>
    <div class="gde-card__body">
      @if ($sesiTerbaru->isEmpty())
        <div class="gde-empty">
          <span class="gde-empty__icon"><i class="bi bi-chat-square-dots"></i></span>
          <h4>Belum ada percakapan</h4>
          <p>Mulai bertanya ke AI Assistant untuk melihat ringkasan data SIAGA.</p>
          <a href="{{ route('ai-assistant.index') }}" class="gde-btn gde-btn--primary gde-btn--sm">
            <i class="bi bi-robot"></i> Mulai Percakapan
          </a>
        </div>
      @else
        <div class="gde-table-wrap">
          <table class="gde-table gde-table--compact">
            <thead>
              <tr>
                <th>Judul Sesi</th>
                <th>Pengguna</th>
                <th>Pesan</th>
                <th>Terakhir Digunakan</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($sesiTerbaru as $sesi)
                <tr>
                  <td><strong>{{ \Illuminate\Support\Str::limit($sesi->title ?: 'Tanpa judul', 48) }}</strong></td>
                  <td>{{ $sesi->user?->nama_lengkap ?? '-' }}</td>
                  <td>{{ number_format($sesi->messages_count) }}</td>
                  <td>{{ $sesi->updated_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                  <td class="text-end">
                    <a href="{{ route('ai-assistant.index', ['session' => $sesi->session_key]) }}"
                       class="gde-btn gde-btn--ghost gde-btn--xs">
                      <i class="bi bi-box-arrow-up-right"></i> Buka
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection
