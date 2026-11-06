{{-- shadcn/ui Card. Padding vertikal & jarak bawaan hanya dipakai bila kelas py-* / gap-* tidak diberikan
     (tanpa tailwind-merge, dua kelas sejenis ditentukan urutan CSS, bukan urutan di atribut). --}}
@php
    $custom = $attributes->get('class', '');
@endphp

<div data-slot="card" {{ $attributes->class([
    'flex flex-col rounded-xl border bg-card text-card-foreground shadow-sm',
    'py-6' => ! preg_match('/(^|\s)py-/', $custom),
    'gap-6' => ! preg_match('/(^|\s)gap-/', $custom),
]) }}>
    {{ $slot }}
</div>
