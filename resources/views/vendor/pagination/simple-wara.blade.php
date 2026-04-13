@if ($paginator->hasPages())
    <nav class="wara-pag wara-pag--simple" role="navigation" aria-label="Pagination">
        <div class="wara-pag-toolbar wara-pag-toolbar--simple">
            @if ($paginator->onFirstPage())
                <span class="wara-pag-btn wara-pag-btn--nav wara-pag-btn--disabled" aria-disabled="true">Précédent</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="wara-pag-btn wara-pag-btn--nav">Précédent</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="wara-pag-btn wara-pag-btn--nav">Suivant</a>
            @else
                <span class="wara-pag-btn wara-pag-btn--nav wara-pag-btn--disabled" aria-disabled="true">Suivant</span>
            @endif
        </div>
    </nav>
@endif
