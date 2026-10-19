@if ($paginator->hasPages())
<nav aria-label="Paginación" style="display:flex;gap:8px;align-items:center;justify-content:flex-end;padding:12px 16px;">
  @if ($paginator->onFirstPage())
    <span class="chip" style="opacity:.5;">← Anterior</span>
  @else
    <a class="chip" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Anterior</a>
  @endif
  <span class="muted" style="font-size:12px;">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
  @if ($paginator->hasMorePages())
    <a class="chip" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente →</a>
  @else
    <span class="chip" style="opacity:.5;">Siguiente →</span>
  @endif
</nav>
@endif
