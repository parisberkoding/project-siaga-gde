{{-- TabsTrigger berbentuk link, untuk tab yang dipilih lewat URL (server-side). Letakkan di <x-ui.tabs-list>. --}}
@props(['href', 'active' => false])

<a href="{{ $href }}" data-state="{{ $active ? 'active' : 'inactive' }}" @if ($active) aria-current="page" @endif {{ $attributes->class(
    'inline-flex h-8 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-transparent px-3 text-sm font-medium transition-[color,box-shadow]
     hover:text-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50
     data-[state=active]:bg-card data-[state=active]:text-foreground data-[state=active]:shadow-sm'
) }}>
    {{ $slot }}
</a>
