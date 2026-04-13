@if ($paginator->hasPages())
    <nav class="wara-pag" role="navigation" aria-label="Pagination">
        <div class="wara-pag-toolbar">
            <p class="wara-pag-info">
                @if ($paginator->firstItem())
                    Affichage de <span class="wara-pag-info-strong">{{ $paginator->firstItem() }}</span>
                    à <span class="wara-pag-info-strong">{{ $paginator->lastItem() }}</span>
                    sur <span class="wara-pag-info-strong">{{ $paginator->total() }}</span> résultats
                @else
                    <span class="wara-pag-info-strong">{{ $paginator->total() }}</span> résultat(s)
                @endif
            </p>

            <div class="wara-pag-controls">
                @if ($paginator->onFirstPage())
                    <span class="wara-pag-btn wara-pag-btn--nav wara-pag-btn--disabled" aria-disabled="true">
                        <span class="wara-pag-icon" aria-hidden="true">‹</span>
                        Précédent
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="wara-pag-btn wara-pag-btn--nav">
                        <span class="wara-pag-icon" aria-hidden="true">‹</span>
                        Précédent
                    </a>
                @endif

                <div class="wara-pag-pages" role="list">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="wara-pag-btn wara-pag-btn--ellipsis" aria-hidden="true">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="wara-pag-btn wara-pag-btn--page wara-pag-btn--active" aria-current="page" role="listitem">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="wara-pag-btn wara-pag-btn--page" role="listitem">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="wara-pag-btn wara-pag-btn--nav">
                        Suivant
                        <span class="wara-pag-icon wara-pag-icon--after" aria-hidden="true">›</span>
                    </a>
                @else
                    <span class="wara-pag-btn wara-pag-btn--nav wara-pag-btn--disabled" aria-disabled="true">
                        Suivant
                        <span class="wara-pag-icon wara-pag-icon--after" aria-hidden="true">›</span>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
