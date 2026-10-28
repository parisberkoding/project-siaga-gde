{{-- shadcn/ui Pagination untuk paginator Laravel: $items->links('vendor.pagination.shadcn') --}}
@if ($paginator->hasPages())
    @php($link = 'inline-flex h-8 min-w-8 items-center justify-center gap-1 rounded-md px-2 text-sm transition-colors hover:bg-accent hover:text-accent-foreground')

    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-muted-foreground">
            Menampilkan {{ $paginator->firstItem() }} sampai {{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
        </p>

        <ul class="flex flex-wrap items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="{{ $link }} pointer-events-none opacity-50"><i class="bi bi-chevron-left"></i> Sebelumnya</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }}"><i class="bi bi-chevron-left"></i> Sebelumnya</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="{{ $link }} pointer-events-none">…</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $link }} border bg-card font-medium shadow-xs">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $link }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }}">Berikutnya <i class="bi bi-chevron-right"></i></a>
                @else
                    <span class="{{ $link }} pointer-events-none opacity-50">Berikutnya <i class="bi bi-chevron-right"></i></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
