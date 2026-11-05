{{-- shadcn/ui Field: label + kontrol + keterangan + pesan error validasi. --}}
@props(['label' => null, 'for' => null, 'required' => false, 'hint' => null, 'error' => null])

{{-- grid-cols-1 (minmax(0,1fr)) + min-w-0: isi panjang (mis. label Select) terpotong, tidak melebarkan form. --}}
<div data-slot="field" {{ $attributes->class('grid min-w-0 grid-cols-1 content-start gap-2') }}>
    @if ($label)
        <x-ui.label :for="$for" :required="$required">{{ $label }}</x-ui.label>
    @endif
    {{ $slot }}
    @if ($hint)
        <p data-slot="field-description" class="text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
    @if ($error)
        @error($error)
            <p data-slot="field-error" class="text-xs text-destructive">{{ $message }}</p>
        @enderror
    @endif
</div>
