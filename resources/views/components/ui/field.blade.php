{{-- shadcn/ui Field: label + kontrol + keterangan + pesan error validasi. --}}
@props(['label' => null, 'for' => null, 'required' => false, 'hint' => null, 'error' => null])

<div data-slot="field" {{ $attributes->class('grid content-start gap-2') }}>
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
