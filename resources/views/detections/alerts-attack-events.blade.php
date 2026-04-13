@extends('layouts.app')

@section('title', 'Security Alerts — Attack Events - Wara XDR')

@section('content')
<div class="page-content ae-page">
    <div class="page-header ae-header">
        <div>
            <h1 class="page-title">Security Alerts</h1>
            @include('detections.partials.alerts-nav', ['activeTab' => 'attack-events'])
        </div>
    </div>

    <nav class="ae-subtabs" aria-label="Attack Events">
        <span class="ae-subtab active">Résumé</span>
        <a href="{{ route('monitor.attack-map') }}" class="ae-subtab" target="_blank" rel="noopener noreferrer">Carte d’attaques</a>
    </nav>

    <form method="get" action="{{ route('detection.alerts.attack-events') }}" class="ae-toolbar">
        <input type="hidden" name="q" value="{{ request('q') }}">
        <button type="submit" class="btn btn-secondary btn-sm">Actualiser</button>
        <label class="ae-range">
            <span>Période</span>
            <select name="range" class="filter-select" onchange="this.form.submit()">
                <option value="1" @selected((string)$range === '1')>Dernières 24 h</option>
                <option value="7" @selected((string)$range === '7')>7 derniers jours</option>
                <option value="30" @selected((string)$range === '30')>30 derniers jours</option>
                <option value="90" @selected((string)$range === '90')>90 derniers jours</option>
            </select>
        </label>
    </form>

    <div class="ae-charts-row">
        <section class="ae-card">
            <h2 class="ae-card-title">Top 5 types d’attaques</h2>
            <p class="ae-card-sub">Par catégorie de règle de détection (fenêtre {{ $days }} jour{{ $days > 1 ? 's' : '' }}).</p>
            <div class="ae-chart-wrap">
                <canvas id="aeAttackTypesChart" height="220"></canvas>
            </div>
            <p class="ae-legend">Barres = nombre d’alertes enregistrées dans Wara XDR.</p>
        </section>
        <section class="ae-card">
            <h2 class="ae-card-title">Événements les plus fréquents</h2>
            <p class="ae-card-sub">Règles les plus souvent déclenchées (taille ≈ fréquence).</p>
            <div class="ae-tag-cloud" role="list">
                @forelse($hotEvents as $hot)
                    @php
                        $ratio = ($hot->cnt / $maxHot);
                        $cls = $ratio >= 0.7 ? 'ae-tag-threat' : ($ratio >= 0.35 ? 'ae-tag-warn' : 'ae-tag-info');
                    @endphp
                    <span class="ae-tag {{ $cls }}" role="listitem" title="{{ $hot->cnt }} alerte(s)">{{ Str::limit($hot->name, 42) }}</span>
                @empty
                    <span class="ae-empty-cloud">Aucune alerte sur cette période.</span>
                @endforelse
            </div>
            <p class="ae-legend"><span class="ae-tag ae-tag-info">Peu</span> <span class="ae-tag ae-tag-warn">Moyen</span> <span class="ae-tag ae-tag-threat">Élevé</span></p>
        </section>
    </div>

    <section class="ae-card ae-table-card">
        <div class="ae-table-head">
            <h2 class="ae-card-title">Systèmes / événements ({{ $events->total() }})</h2>
            <form method="get" class="ae-filter-form">
                <input type="hidden" name="range" value="{{ $range }}">
                <input type="search" name="q" value="{{ request('q') }}" class="ae-search" placeholder="Filtrer (IP, titre, description…)">
                <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            </form>
        </div>
        <label class="ae-checkbox-hint">
            <input type="checkbox" disabled>
            <span>Afficher uniquement les systèmes critiques (à brancher sur vos assets)</span>
        </label>
        <div class="table-container ae-table-scroll">
            <table class="data-table ae-data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>IP attaquant</th>
                        <th>Localisation</th>
                        <th>Gravité</th>
                        <th>Système / cible</th>
                        <th>Description</th>
                        <th>Période</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $idx => $ev)
                        @php
                            $src = $ev->source_ip ?? '—';
                            $blocked = $src !== '—' && isset($blockedSet[$src]);
                        @endphp
                        <tr>
                            <td>{{ $events->firstItem() + $idx }}</td>
                            <td><code class="ae-mono">{{ $src }}</code></td>
                            <td class="ae-loc"><span class="ae-loc-placeholder" title="Géolocalisation non configurée">—</span></td>
                            <td>
                                <span class="ae-sev ae-sev-{{ $ev->severity }}">{{ ucfirst($ev->severity) }}</span>
                            </td>
                            <td>
                                <code class="ae-mono">{{ $ev->target_ip ?? '—' }}</code>
                                @if($ev->affected_asset)
                                    <div class="ae-asset">{{ Str::limit($ev->affected_asset, 48) }}</div>
                                @endif
                            </td>
                            <td class="ae-desc">{{ Str::limit($ev->description, 120) }} @if($ev->event_count > 1)<span class="ae-ecount">({{ $ev->event_count }} evt.)</span>@endif</td>
                            <td class="ae-times">
                                <div><span class="ae-tlabel">Début</span> {{ $ev->first_seen?->format('Y-m-d H:i') }}</div>
                                <div><span class="ae-tlabel">Fin</span> {{ $ev->last_seen?->format('Y-m-d H:i') }}</div>
                            </td>
                            <td class="ae-ops">
                                @if($blocked)
                                    <span class="ae-blocked">Bloqué</span>
                                @else
                                    <span class="ae-allow">Non bloqué</span>
                                @endif
                                <a href="{{ route('detection.alerts.show', $ev) }}" class="th-link">Détails</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">
                                <div class="empty-icon">📭</div>
                                <div class="empty-text">Aucun événement sur cette période.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-container">
            {{ $events->withQueryString()->links() }}
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    .ae-page .ae-header .page-title { margin-bottom: 0.35rem; }
    .sa-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.75rem;
    }
    .sa-tab {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border-radius: 8px;
        font-size: 0.88rem;
        color: #94a3b8;
        text-decoration: none;
        border: 1px solid transparent;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
    }
    .sa-tab:hover { color: #e2e8f0; background: rgba(0, 212, 255, 0.06); }
    .sa-tab.active {
        color: #f8fafc;
        background: linear-gradient(90deg, rgba(0, 212, 255, 0.15), rgba(59, 130, 246, 0.1));
        border-color: rgba(0, 212, 255, 0.35);
        box-shadow: inset 3px 0 0 #00d4ff;
    }
    .sa-tab-pill {
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.12rem 0.4rem;
        border-radius: 4px;
        background: rgba(34, 197, 94, 0.2);
        color: #86efac;
    }
    .ae-subtabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid #2d3748;
    }
    .ae-subtab {
        padding: 0.35rem 0.85rem;
        font-size: 0.85rem;
        border-radius: 6px;
        color: #94a3b8;
        text-decoration: none;
    }
    .ae-subtab.active {
        color: #00d4ff;
        background: rgba(0, 212, 255, 0.1);
        font-weight: 600;
    }
    .ae-subtab:not(.active):hover { color: #e2e8f0; }
    .ae-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
        margin-bottom: 1.25rem;
    }
    .ae-range {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.82rem;
        color: #94a3b8;
        margin-left: auto;
    }
    .ae-range .filter-select {
        padding: 6px 10px;
        background: #0f1419;
        border: 1px solid #2d3748;
        border-radius: 6px;
        color: #e2e8f0;
        font-size: 0.85rem;
    }
    .ae-charts-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }
    .ae-card {
        background: #1a1f2e;
        border: 1px solid #2d3748;
        border-radius: 10px;
        padding: 1.15rem 1.25rem;
    }
    .ae-card-title { margin: 0 0 0.35rem; font-size: 0.95rem; color: #f1f5f9; }
    .ae-card-sub { margin: 0 0 1rem; font-size: 0.78rem; color: #64748b; line-height: 1.4; }
    .ae-chart-wrap { position: relative; min-height: 200px; }
    .ae-legend { margin: 0.65rem 0 0; font-size: 0.72rem; color: #64748b; }
    .ae-tag-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        align-items: center;
        min-height: 160px;
        padding: 0.5rem 0;
    }
    .ae-tag {
        display: inline-block;
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 500;
        line-height: 1.3;
        max-width: 100%;
    }
    .ae-tag-info { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
    .ae-tag-warn { background: rgba(249, 115, 22, 0.2); color: #fdba74; font-size: 0.82rem; }
    .ae-tag-threat { background: rgba(244, 63, 94, 0.22); color: #fda4af; font-size: 0.88rem; font-weight: 600; }
    .ae-empty-cloud { color: #64748b; font-size: 0.85rem; }
    .ae-table-card { margin-bottom: 2rem; }
    .ae-table-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.65rem;
    }
    .ae-filter-form { display: flex; gap: 0.5rem; align-items: center; }
    .ae-search {
        padding: 0.45rem 0.65rem;
        min-width: 220px;
        background: #0f1419;
        border: 1px solid #2d3748;
        border-radius: 6px;
        color: #e2e8f0;
        font-size: 0.85rem;
    }
    .ae-checkbox-hint {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 0.75rem;
        cursor: not-allowed;
    }
    .ae-table-scroll { overflow-x: auto; }
    .ae-data-table { font-size: 0.8rem; }
    .ae-data-table th, .ae-data-table td { white-space: nowrap; }
    .ae-data-table .ae-desc { white-space: normal; max-width: 280px; }
    .ae-mono { font-family: ui-monospace, monospace; font-size: 0.8rem; color: #00d4ff; }
    .ae-asset { font-size: 0.72rem; color: #94a3b8; margin-top: 0.2rem; }
    .ae-sev {
        padding: 0.15rem 0.45rem;
        border-radius: 4px;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .ae-sev-critical { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
    .ae-sev-high { background: rgba(249, 115, 22, 0.2); color: #fdba74; }
    .ae-sev-medium { background: rgba(234, 179, 8, 0.15); color: #fde047; }
    .ae-sev-low { background: rgba(34, 197, 94, 0.15); color: #86efac; }
    .ae-times { font-size: 0.72rem; color: #94a3b8; line-height: 1.45; }
    .ae-tlabel { color: #64748b; margin-right: 0.25rem; }
    .ae-ops { display: flex; flex-direction: column; gap: 0.35rem; align-items: flex-start; }
    .ae-blocked { color: #f87171; font-size: 0.75rem; font-weight: 600; }
    .ae-allow { color: #94a3b8; font-size: 0.75rem; }
    .ae-ecount { color: #64748b; font-size: 0.72rem; }
    .ae-loc-placeholder { color: #475569; }
    .th-link { color: #38bdf8; font-size: 0.78rem; }
</style>
@endpush

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    var ctx = document.getElementById('aeAttackTypesChart');
    if (!ctx) return;
    var labels = @json($topAttackTypes->pluck('label')->values());
    var data = @json($topAttackTypes->pluck('count')->values());
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Alertes Wara XDR',
                data: data,
                backgroundColor: 'rgba(0, 212, 255, 0.55)',
                borderColor: 'rgba(0, 212, 255, 0.9)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { color: '#94a3b8' },
                    grid: { color: 'rgba(148, 163, 184, 0.12)' }
                },
                y: {
                    ticks: { color: '#cbd5e1', font: { size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
@endsection
