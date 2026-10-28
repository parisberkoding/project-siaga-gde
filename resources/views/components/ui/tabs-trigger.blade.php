{{-- shadcn/ui TabsTrigger --}}
@props(['value'])

<button type="button" role="tab" data-tabs-trigger="{{ $value }}" data-slot="tabs-trigger" {{ $attributes->class(
    'inline-flex h-8 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-transparent px-3 text-sm font-medium transition-[color,box-shadow] cursor-pointer
     hover:text-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50
     data-[state=active]:bg-card data-[state=active]:text-foreground data-[state=active]:shadow-sm'
) }}>
    {{ $slot }}
</button>
