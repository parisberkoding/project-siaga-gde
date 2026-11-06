{{-- shadcn/ui NativeSelect: <select> asli dengan gaya shadcn. --}}
{{-- Pakai inline untuk select selebar isinya (mis. filter di toolbar). --}}
@props(['invalid' => null, 'size' => 'default', 'inline' => false])

@php($invalid ??= $attributes->has('name') && $errors->has($attributes->get('name')))

<div data-slot="native-select-wrapper" @class(['relative', 'w-fit' => $inline, 'w-full' => ! $inline])>
    <select data-slot="native-select" @if ($invalid) aria-invalid="true" @endif {{ $attributes->class([
        'w-full min-w-0 appearance-none rounded-md border border-input bg-card pl-3 pr-9 text-sm shadow-xs transition-[color,box-shadow] outline-none
         disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50
         focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50
         aria-invalid:border-destructive aria-invalid:ring-destructive/20',
        $size === 'sm' ? 'h-8' : 'h-9',
    ]) }}>{{ $slot }}</select>
    <i class="bi bi-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground"></i>
</div>
