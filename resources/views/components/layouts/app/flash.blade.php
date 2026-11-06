{{-- Pesan sesi (success/error/warning/info) tampil sebagai toast lewat resources/js/ui/toast.js.
     Daftar error validasi tetap inline supaya bisa dibaca sambil memperbaiki isian. --}}
@php
    $titles = ['success' => 'Berhasil', 'error' => 'Gagal', 'warning' => 'Perhatian', 'info' => 'Informasi'];
    $flash = collect($titles)
        ->filter(fn ($title, $key) => session()->has($key) && filled(session($key)))
        ->map(fn ($title, $key) => ['variant' => $key, 'title' => $title, 'description' => (string) session($key), 'duration' => $key === 'error' ? 8000 : 5000])
        ->values();
    if ($errors->any()) {
        $flash->push(['variant' => 'error', 'title' => 'Periksa kembali isian', 'description' => $errors->count() . ' isian perlu diperbaiki.']);
    }
@endphp

<div id="toaster" data-flash="{{ $flash->toJson() }}" aria-live="polite"
     class="pointer-events-none fixed inset-x-4 bottom-4 z-[60] flex flex-col gap-2 sm:left-auto sm:right-6 sm:bottom-6 sm:w-96"></div>

@if ($errors->any())
    <x-ui.alert variant="destructive" title="Terjadi kesalahan" dismissible>
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
