{{--
  shadcn/ui Pagination (Pagination, PaginationContent, PaginationItem, PaginationLink,
  PaginationPrevious/Next, PaginationEllipsis) untuk paginator Laravel:
  $items->links('vendor.pagination.shadcn'). Tombol sebelumnya/berikutnya hanya ikon, labelnya di aria-label.
--}}
@if ($paginator->hasPages())
    @php
        $item = 'inline-flex size-9 items-center justify-center rounded-lg text-sm font-medium transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';
        $idle = 'text-foreground hover:bg-accent hover:text-accent-foreground';
        $edge = 'border bg-card shadow-xs hover:bg-accent hover:text-accent-foreground';
        $off = 'pointer-events-none border bg-card text-muted-foreground/50';
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman" data-slot="pagination" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted-foreground">
            Menampilkan <span class="font-medium text-foreground tabular-nums">{{ $paginator->firstItem() }} sampai {{ $paginator->lastItem() }}</span>
            dari <span class="font-medium text-foreground tabular-nums">{{ $paginator->total() }}</span> data
        </p>

        <ul data-slot="pagination-content" class="flex flex-wrap items-center gap-1">
            <li data-slot="pagination-item">
                @if ($paginator->onFirstPage())
                    <span class="{{ $item }} {{ $off }}" aria-disabled="true" aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $item }} {{ $edge }}" aria-label="Halaman sebelumnya" title="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li data-slot="pagination-item"><span class="{{ $item }} text-muted-foreground" aria-hidden="true"><i class="bi bi-three-dots"></i></span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li data-slot="pagination-item">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $item }} bg-linear-to-br from-primary to-indigo-600 text-primary-foreground shadow-md shadow-primary/25 tabular-nums">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $item }} {{ $idle }} tabular-nums" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li data-slot="pagination-item">
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $item }} {{ $edge }}" aria-label="Halaman berikutnya" title="Halaman berikutnya"><i class="bi bi-chevron-right"></i></a>
                @else
                    <span class="{{ $item }} {{ $off }}" aria-disabled="true" aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
