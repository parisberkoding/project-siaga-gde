{{--
  Kepala dashboard modul.
  Parameter:
    $module      : App\Models\Module
    $heading     : judul halaman (mis. "Dashboard SPD")
    $description : satu paragraf ringkas
    $actions     : array of ['label','route','icon','permission'(opsional),'class'(opsional)]
--}}
<div class="module-dash__head">
  <div class="module-dash__identity">
    <span class="module-dash__icon"><i class="{{ $module->icon ?: 'bi bi-grid' }}"></i></span>
    <div>
      <span class="module-dash__eyebrow">{{ $module->name }}</span>
      <h1>{{ $heading }}</h1>
      <p>{{ $description }}</p>
    </div>
  </div>

  @if (!empty($actions))
    <div class="module-dash__actions">
      @foreach ($actions as $action)
        @if (empty($action['permission']) || Auth::user()->hasPermission($action['permission']))
          <a href="{{ route($action['route']) }}"
             class="gde-btn {{ $action['class'] ?? 'gde-btn--outline-primary' }} gde-btn--sm">
            <i class="{{ $action['icon'] ?? 'bi bi-arrow-right' }}"></i>
            {{ $action['label'] }}
          </a>
        @endif
      @endforeach
    </div>
  @endif
</div>
