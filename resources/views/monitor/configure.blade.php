@extends('layouts.app')

@section('title', 'Surveillance des actifs - Wara XDR')

@section('content')
<div class="page-content monitor-configure-page">
    <div class="page-header" style="flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 class="page-title">Surveillance des actifs</h1>
            <p style="margin-top: 6px; font-size: 0.85rem; color: #94a3b8;">
                Choisissez les <strong>groupes</strong> (via « Tout le groupe ») ou les <strong>machines</strong> individuelles à inclure dans le monitoring XDR.
            </p>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <a href="{{ route('monitor.monitors') }}" target="_blank" rel="noopener noreferrer" class="mc-link">Ouvrir Security O&amp;M Monitor</a>
            <a href="{{ route('tenants.index') }}" class="mc-link">All Tenants</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mc-alert mc-alert--ok">{{ session('success') }}</div>
    @endif

    @if($assets->isEmpty())
        <div class="mc-empty">
            <p>Aucun actif enregistré. Ajoutez des machines depuis <a href="{{ route('tenants.index') }}">All Tenants</a> ou les agents.</p>
        </div>
    @else
        <p class="mc-summary">{{ $monitoredCount }} machine(s) sélectionnée(s) sur {{ $assets->count() }}.</p>

        <form action="{{ route('monitor.configure.save') }}" method="POST" id="monitor-config-form">
            @csrf

            @foreach($sections as $section)
                <div class="mc-group card-like" data-mc-group>
                    <div class="mc-group__head">
                        <div>
                            <h2 class="mc-group__title">{{ $section['title'] }}</h2>
                            @if($section['path'])
                                <span class="mc-group__path">{{ $section['path'] }}</span>
                            @endif
                        </div>
                        <button type="button" class="mc-btn mc-btn--ghost js-toggle-group" aria-label="Basculer toute la sélection du groupe">
                            Tout le groupe
                        </button>
                    </div>

                    <div class="table-container" style="overflow-x: auto;">
                        <table class="mc-table">
                            <thead>
                                <tr>
                                    <th style="width: 48px;"></th>
                                    <th>Machine</th>
                                    <th>IP</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($section['assets'] as $asset)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="monitored[]" value="{{ $asset->id }}" id="m-{{ $asset->id }}"
                                                {{ $asset->is_monitored ? 'checked' : '' }} class="mc-check">
                                        </td>
                                        <td>
                                            <label for="m-{{ $asset->id }}" class="mc-host">{{ $asset->hostname }}</label>
                                        </td>
                                        <td class="mc-muted">{{ $asset->ip_address ?? '—' }}</td>
                                        <td>
                                            <span class="mc-status mc-status--{{ $asset->status }}">{{ $asset->status }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="mc-actions">
                <button type="submit" class="mc-btn mc-btn--primary">Enregistrer la sélection</button>
                <a href="{{ route('dashboard') }}" class="mc-btn mc-btn--ghost">Annuler</a>
            </div>
        </form>
    @endif
</div>

<style>
.monitor-configure-page .card-like {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 8px;
    margin-bottom: 20px;
    overflow: hidden;
}
.mc-group__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid #2d3748;
    background: rgba(0, 212, 255, 0.04);
}
.mc-group__title {
    font-size: 1rem;
    font-weight: 600;
    color: #e2e8f0;
    margin: 0;
}
.mc-group__path {
    font-size: 0.75rem;
    color: #64748b;
    display: block;
    margin-top: 4px;
}
.mc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}
.mc-table th {
    text-align: left;
    padding: 10px 16px;
    color: #94a3b8;
    font-weight: 500;
    border-bottom: 1px solid #2d3748;
}
.mc-table td {
    padding: 10px 16px;
    border-bottom: 1px solid rgba(45, 55, 72, 0.6);
    color: #cbd5e1;
}
.mc-table tr:last-child td { border-bottom: none; }
.mc-host {
    cursor: pointer;
    color: #00d4ff;
    font-weight: 500;
}
.mc-muted { color: #64748b; font-size: 0.8rem; }
.mc-check {
    width: 18px;
    height: 18px;
    accent-color: #00d4ff;
    cursor: pointer;
}
.mc-status {
    font-size: 0.72rem;
    text-transform: capitalize;
    padding: 2px 8px;
    border-radius: 4px;
}
.mc-status--online { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
.mc-status--offline { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
.mc-status--alerting { background: rgba(249, 115, 22, 0.15); color: #f97316; }
.mc-status--unknown { background: rgba(148, 163, 184, 0.12); color: #94a3b8; }
.mc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    border: none;
    font-family: inherit;
}
.mc-btn--primary {
    background: linear-gradient(180deg, #0891b2, #0e7490);
    color: #fff;
    border: 1px solid #22d3ee;
}
.mc-btn--primary:hover { filter: brightness(1.08); }
.mc-btn--ghost {
    background: transparent;
    color: #00d4ff;
    border: 1px solid rgba(0, 212, 255, 0.35);
}
.mc-btn--ghost:hover { background: rgba(0, 212, 255, 0.08); }
.mc-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 8px;
    margin-bottom: 32px;
}
.mc-link {
    color: #00d4ff;
    font-size: 0.85rem;
    text-decoration: none;
}
.mc-link:hover { text-decoration: underline; }
.mc-alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 0.85rem;
}
.mc-alert--ok {
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.35);
    color: #86efac;
}
.mc-summary {
    font-size: 0.85rem;
    color: #94a3b8;
    margin-bottom: 16px;
}
.mc-empty {
    padding: 32px;
    text-align: center;
    background: #1a1f2e;
    border: 1px dashed #2d3748;
    border-radius: 8px;
    color: #94a3b8;
}
.mc-empty a { color: #00d4ff; }
</style>
@endsection

@section('scripts')
@if(!$assets->isEmpty())
<script>
document.querySelectorAll('[data-mc-group]').forEach(function (block) {
    var btn = block.querySelector('.js-toggle-group');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var boxes = block.querySelectorAll('input.mc-check[type="checkbox"]');
        var allOn = Array.prototype.every.call(boxes, function (b) { return b.checked; });
        Array.prototype.forEach.call(boxes, function (b) { b.checked = !allOn; });
    });
});
</script>
@endif
@endsection
