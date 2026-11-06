{{--
  shadcn/ui Dialog di atas <dialog> native. Buka dengan <x-ui.button data-dialog-open="id">.
  Slot `footer` berisi tombol aksi. Atribut `static` = tidak tertutup saat klik di luar.
--}}
@props(['id', 'title' => null, 'description' => null, 'size' => 'md', 'static' => false])

<dialog id="{{ $id }}" data-slot="dialog" @if ($static) data-static @endif aria-labelledby="{{ $id }}-title" {{ $attributes->class([
    'w-[calc(100%-2rem)] rounded-lg border bg-card p-0 text-card-foreground shadow-lg',
    match ($size) {
        'sm' => 'max-w-sm',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        default => 'max-w-lg',
    },
]) }}>
    <div class="grid max-h-[85vh] gap-4 overflow-y-auto p-6">
        @if ($title)
            <div data-slot="dialog-header" class="flex flex-col gap-2 pr-6 text-left">
                <h2 id="{{ $id }}-title" data-slot="dialog-title" class="text-lg font-semibold leading-none">{{ $title }}</h2>
                @if ($description)
                    <p data-slot="dialog-description" class="text-sm text-muted-foreground">{{ $description }}</p>
                @endif
            </div>
        @endif

        {{ $slot }}

        @isset($footer)
            <div data-slot="dialog-footer" class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">{{ $footer }}</div>
        @endisset
    </div>
    <button type="button" data-dialog-close class="absolute right-4 top-4 rounded-sm opacity-70 transition-opacity hover:opacity-100" aria-label="Tutup">
        <i class="bi bi-x-lg"></i>
    </button>
</dialog>
