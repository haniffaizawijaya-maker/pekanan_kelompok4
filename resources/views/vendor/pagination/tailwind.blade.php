@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman">

        <div class="flex items-center justify-between gap-2 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex min-h-[44px] cursor-not-allowed items-center rounded-[6px] border-[1.5px] border-ink bg-beige px-4 py-2 text-sm font-bold leading-5 text-muted">
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-2 text-sm font-bold leading-5 text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-2 text-sm font-bold leading-5 text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">
                    Berikutnya
                </a>
            @else
                <span class="inline-flex min-h-[44px] cursor-not-allowed items-center rounded-[6px] border-[1.5px] border-ink bg-beige px-4 py-2 text-sm font-bold leading-5 text-muted">
                    Berikutnya
                </span>
            @endif
        </div>

        <div class="hidden items-center justify-between gap-2 sm:flex">
            <div>
                <p class="text-sm leading-5 text-muted">
                    Menampilkan
                    @if ($paginator->firstItem())
                        <span class="font-bold text-ink">{{ $paginator->firstItem() }}</span>
                        sampai
                        <span class="font-bold text-ink">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    dari
                    <span class="font-bold text-ink">{{ $paginator->total() }}</span>
                    hasil
                </p>
            </div>

            <div>
                <span class="inline-flex">
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Sebelumnya">
                            <span class="inline-flex min-h-[44px] cursor-not-allowed items-center rounded-l-[6px] border-[1.5px] border-ink bg-beige px-2 py-2 text-sm font-bold leading-5 text-muted" aria-hidden="true">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-[44px] items-center rounded-l-[6px] border-[1.5px] border-ink bg-card px-2 py-2 text-sm font-bold leading-5 text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink" aria-label="Sebelumnya">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="inline-flex min-h-[44px] -ml-px cursor-default items-center border-[1.5px] border-ink bg-card px-4 py-2 text-sm font-bold leading-5 text-muted">{{ $element }}</span>
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="inline-flex min-h-[44px] -ml-px cursor-default items-center border-[1.5px] border-ink bg-mustard px-4 py-2 text-sm font-bold leading-5 text-ink">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="inline-flex min-h-[44px] -ml-px items-center border-[1.5px] border-ink bg-card px-4 py-2 text-sm font-bold leading-5 text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink" aria-label="Ke halaman {{ $page }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-[44px] -ml-px items-center rounded-r-[6px] border-[1.5px] border-ink bg-card px-2 py-2 text-sm font-bold leading-5 text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink" aria-label="Berikutnya">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Berikutnya">
                            <span class="inline-flex min-h-[44px] -ml-px cursor-not-allowed items-center rounded-r-[6px] border-[1.5px] border-ink bg-beige px-2 py-2 text-sm font-bold leading-5 text-muted" aria-hidden="true">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
