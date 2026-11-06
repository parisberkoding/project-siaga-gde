{{-- shadcn/ui Input. Atribut aria-invalid otomatis bila ada error validasi untuk `name`. --}}
{{-- Lebar bawaan w-full; pakai inline (w-auto) atau tulis kelas w-* sendiri untuk lebar lain. --}}
@props(['type' => 'text', 'invalid' => null, 'inline' => false])

@php
    $invalid ??= $attributes->has('name') && $errors->has(str_replace(['[', ']'], ['.', ''], $attributes->get('name')));
    $width = preg_match('/(^|\s)w-/', $attributes->get('class', '')) ? null : ($inline ? 'w-auto' : 'w-full');
@endphp

<input type="{{ $type }}" data-slot="input" @if ($invalid) aria-invalid="true" @endif {{ $attributes->class($width)->class(
    'flex h-9 min-w-0 rounded-md border border-input bg-card px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm
     placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground
     file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground
     disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50
     focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50
     aria-invalid:border-destructive aria-invalid:ring-destructive/20'
) }}>
