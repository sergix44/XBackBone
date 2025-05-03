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

<div>
    @if ($paginator->hasPages())
        <div class="join">
            <button class="join-item btn" {{ $paginator->onFirstPage() ? 'disabled' : '' }}
            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                    wire:loading.attr="disabled">«
            </button>

            @foreach($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true">
                        <span class="join-item btn disabled">{{ $element }}</span>
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <button type="button"
                                wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                dusk="gotoPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                                class="join-item btn {{ $page == $paginator->currentPage() ? 'btn-active' : '' }}"
                                aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                            {{ $page }}
                        </button>
                    @endforeach
                @endif

            @endforeach

            <button class="join-item btn" {{ $paginator->hasMorePages() ? '' : 'disabled' }}
            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                    wire:loading.attr="disabled"
                    dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                    aria-label="{{ __('pagination.next') }}">»
            </button>
        </div>
    @endif
</div>
