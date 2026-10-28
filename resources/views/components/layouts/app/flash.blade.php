{{-- Pesan sesi (success/error/warning/info) dan daftar error validasi. --}}
@foreach (['success' => 'success', 'error' => 'destructive', 'warning' => 'warning', 'info' => 'info'] as $key => $variant)
    @if (session($key))
        <x-ui.alert :variant="$variant" dismissible>{{ session($key) }}</x-ui.alert>
    @endif
@endforeach

@if ($errors->any())
    <x-ui.alert variant="destructive" title="Terjadi kesalahan" dismissible>
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
