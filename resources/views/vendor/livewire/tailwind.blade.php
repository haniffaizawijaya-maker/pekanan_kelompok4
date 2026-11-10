@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

{{--
    Salinan view pagination Livewire (livewire::tailwind) yang diganti gayanya
    mengikuti DNA desain: border tinta 1,5px, radius 6px, halaman aktif
    solid mustard. View ini WAJIB ada di sini: Livewire 4 memakai
    Paginator::defaultView('livewire::tailwind') untuk komponen WithPagination,
    sehingga view di resources/views/vendor/pagination/tailwind.blade.php
    tidak pernah dipakai oleh ProductList. Logika wire:click, wire:key,
    dusk, dan aria di bawah dipertahankan sama persis dengan view bawaan
    Livewire; hanya class dan kalimat ringkasan yang diubah.
--}}
<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
            <div class="flex flex-1 justify-between sm:hidden">
                <span>
                    @if ($paginator->onFirstPage())
                        <span class="inline-flex min-h-[44px] cursor-not-allowed items-center rounded-[6px] border-[1.5px] border-ink bg-beige px-4 py-2 text-sm font-bold leading-5 text-muted">
                            {!! __('pagination.previous') !!}
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-2 text-sm font-bold leading-5 text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">
                            {!! __('pagination.previous') !!}
                        </button>
                    @endif
                </span>

                <span>
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="ml-3 inline-flex min-h-[44px] items-center rounded-[6px] border-[1.5px] border-ink bg-card px-4 py-2 text-sm font-bold leading-5 text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink">
                            {!! __('pagination.next') !!}
                        </button>
                    @else
                        <span class="ml-3 inline-flex min-h-[44px] cursor-not-allowed items-center rounded-[6px] border-[1.5px] border-ink bg-beige px-4 py-2 text-sm font-bold leading-5 text-muted">
                            {!! __('pagination.next') !!}
                        </span>
                    @endif
                </span>
            </div>

            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
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
                    <span class="inline-flex rtl:flex-row-reverse">
                        <span>
                            {{-- Previous Page Link --}}
                            @if ($paginator->onFirstPage())
                                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                                    <span class="inline-flex min-h-[44px] cursor-not-allowed items-center rounded-l-[6px] border-[1.5px] border-ink bg-beige px-2 py-2 text-sm font-bold leading-5 text-muted" aria-hidden="true">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    </span>
                                </span>
                            @else
                                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="inline-flex min-h-[44px] items-center rounded-l-[6px] border-[1.5px] border-ink bg-card px-2 py-2 text-sm font-bold leading-5 text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink" aria-label="{{ __('pagination.previous') }}">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                </button>
                            @endif
                        </span>

                        {{-- Pagination Elements --}}
                        @foreach ($elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <span aria-disabled="true">
                                    <span class="inline-flex min-h-[44px] -ml-px cursor-default items-center border-[1.5px] border-ink bg-card px-4 py-2 text-sm font-bold leading-5 text-muted">{{ $element }}</span>
                                </span>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                        @if ($page == $paginator->currentPage())
                                            <span aria-current="page">
                                                <span class="inline-flex min-h-[44px] -ml-px cursor-default items-center border-[1.5px] border-ink bg-mustard px-4 py-2 text-sm font-bold leading-5 text-ink">{{ $page }}</span>
                                            </span>
                                        @else
                                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="inline-flex min-h-[44px] -ml-px items-center border-[1.5px] border-ink bg-card px-4 py-2 text-sm font-bold leading-5 text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                                {{ $page }}
                                            </button>
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        @endforeach

                        <span>
                            {{-- Next Page Link --}}
                            @if ($paginator->hasMorePages())
                                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="inline-flex min-h-[44px] -ml-px items-center rounded-r-[6px] border-[1.5px] border-ink bg-card px-2 py-2 text-sm font-bold leading-5 text-ink hover:bg-parchment focus:outline-none focus:ring-2 focus:ring-ink" aria-label="{{ __('pagination.next') }}">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                                </button>
                            @else
                                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                                    <span class="inline-flex min-h-[44px] -ml-px cursor-not-allowed items-center rounded-r-[6px] border-[1.5px] border-ink bg-beige px-2 py-2 text-sm font-bold leading-5 text-muted" aria-hidden="true">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                                    </span>
                                </span>
                            @endif
                        </span>
                    </span>
                </div>
            </div>
        </nav>
    @endif
</div>
