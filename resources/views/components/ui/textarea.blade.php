{{-- shadcn/ui Textarea. Tinggi tetap (mengikuti rows) dan tidak bisa ditarik, kecuali diberi kelas resize-*. --}}
@props(['invalid' => null])

@php($invalid ??= $attributes->has('name') && $errors->has($attributes->get('name')))

<textarea data-slot="textarea" @if ($invalid) aria-invalid="true" @endif {{ $attributes->class(preg_match('/(^|\s)resize-/', $attributes->get('class', '')) ? '' : 'resize-none')->class(
    'flex min-h-16 w-full rounded-md border border-input bg-card px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm
     placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50
     focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50
     aria-invalid:border-destructive aria-invalid:ring-destructive/20'
) }}>{{ $slot }}</textarea>
