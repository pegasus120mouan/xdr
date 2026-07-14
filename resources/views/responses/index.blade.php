@extends('layouts.app')

@section('title', 'Automated Responses - Wara XDR')

@section('content')
@php
    $rulesByCategory = $rules->groupBy('category');
@endphp
<div class="page-content resp-page">
    <div class="resp-hero">
        <div>
            <p class="resp-kicker">SOAR léger · réponses automatisées</p>
            <h1 class="page-title">Automated Responses</h1>
            <p class="resp-intro">
                Quand une règle de détection se déclenche, ces actions s’exécutent automatiquement
                (log, notification, blocage IP). Le brute-force login déclenche un blocage si la règle inclut <strong>Block IP</strong>.
            </p>
        </div>
        <nav class="resp-nav" aria-label="Raccourcis réponses">
            <a href="{{ route('responses.auto-containment') }}" class="resp-nav__link">Auto Containment</a>
            <a href="{{ route('detection.blocked-ips') }}" class="resp-nav__link resp-nav__link--primary">Blocked IPs</a>
            <a href="{{ route('detection.rules') }}" class="resp-nav__link">Detection Rules</a>
            <a href="{{ route('responses.soar') }}" class="resp-nav__link">Orchestration</a>
        </nav>
    </div>

    <div class="resp-stats">
        <article class="resp-stat resp-stat--cyan">
            <div class="resp-stat__icon" aria-hidden="true">⚙</div>
            <div>
                <span class="resp-stat__label">Règles auto-block actives</span>
                <strong class="resp-stat__value">{{ number_format($stats['rules_with_auto_block']) }}</strong>
            </div>
        </article>
        <article class="resp-stat resp-stat--amber">
            <div class="resp-stat__icon" aria-hidden="true">🤖</div>
            <div>
                <span class="resp-stat__label">IP bloquées par automation</span>
                <strong class="resp-stat__value">{{ number_format($stats['system_blocked_ips']) }}</strong>
            </div>
        </article>
        <article class="resp-stat resp-stat--red">
            <div class="resp-stat__icon" aria-hidden="true">🛡</div>
            <div>
                <span class="resp-stat__label">Tous les blocs actifs</span>
                <strong class="resp-stat__value">{{ number_format($stats['active_blocked_ips']) }}</strong>
            </div>
        </article>
        <article class="resp-stat resp-stat--slate">
            <div class="resp-stat__icon" aria-hidden="true">📘</div>
            <div>
                <span class="resp-stat__label">Playbooks configurés</span>
                <strong class="resp-stat__value">{{ number_format($rules->count()) }}</strong>
            </div>
        </article>
    </div>

    <div class="resp-toolbar">
        <h2 class="resp-section-title">Response playbooks</h2>
        <div class="resp-filters" id="respFilters">
            <button type="button" class="resp-filter is-active" data-cat="all">Tous</button>
            @foreach($rulesByCategory as $catKey => $catRules)
                <button type="button" class="resp-filter" data-cat="{{ $catKey }}">
                    {{ $categories[$catKey] ?? $catKey }}
                    <span>{{ $catRules->count() }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @forelse($rulesByCategory as $catKey => $catRules)
        <section class="resp-group" data-cat-group="{{ $catKey }}">
            <header class="resp-group__head">
                <h3>{{ $categories[$catKey] ?? $catKey }}</h3>
                <span>{{ $catRules->count() }} règle{{ $catRules->count() > 1 ? 's' : '' }}</span>
            </header>
            <div class="resp-cards">
                @foreach($catRules as $rule)
                    <article class="resp-card{{ $rule->is_active ? '' : ' resp-card--off' }}">
                        <div class="resp-card__main">
                            <div class="resp-card__title-row">
                                <h4 class="resp-card__name">{{ $rule->name }}</h4>
                                @if($rule->is_active)
                                    <span class="resp-status resp-status--on">Active</span>
                                @else
                                    <span class="resp-status resp-status--off">Disabled</span>
                                @endif
                            </div>
                            <p class="resp-card__desc">{{ \Illuminate\Support\Str::limit($rule->description, 140) }}</p>
                        </div>
                        <div class="resp-card__actions">
                            <span class="resp-cat">{{ $categories[$rule->category] ?? $rule->category }}</span>
                            <div class="resp-actions">
                                @foreach($rule->actions ?? [] as $action)
                                    @if(in_array($action['type'] ?? '', ['block_ip', 'notify', 'log'], true))
                                        @switch($action['type'])
                                            @case('block_ip')
                                                <span class="resp-badge resp-badge--block">
                                                    Block IP
                                                    @if(!empty($action['duration']))
                                                        <em>{{ $action['duration'] }}h</em>
                                                    @else
                                                        <em>permanent</em>
                                                    @endif
                                                </span>
                                                @break
                                            @case('notify')
                                                <span class="resp-badge resp-badge--notify">
                                                    Notify
                                                    @if(!empty($action['channel']))
                                                        <em>{{ $action['channel'] }}</em>
                                                    @endif
                                                </span>
                                                @break
                                            @case('log')
                                                <span class="resp-badge resp-badge--log">Log</span>
                                                @break
                                        @endswitch
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="resp-empty">
            <div class="resp-empty__icon">🛡</div>
            <p>Aucune action de réponse configurée.</p>
            <a href="{{ route('detection.rules') }}" class="resp-nav__link resp-nav__link--primary">Configurer les règles</a>
        </div>
    @endforelse
</div>
@endsection

@push('styles')
<style>
    .resp-page { max-width: 1200px; }

    .resp-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .resp-kicker {
        margin: 0 0 6px;
        font-size: 0.7rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .resp-intro {
        margin: 8px 0 0;
        max-width: 40rem;
        color: #94a3b8;
        font-size: 0.9rem;
        line-height: 1.55;
    }

    .resp-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .resp-nav__link {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #2d3748;
        background: #151a24;
        color: #cbd5e1;
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 500;
        transition: border-color 0.15s, background 0.15s, color 0.15s;
    }

    .resp-nav__link:hover {
        border-color: rgba(0, 212, 255, 0.4);
        color: #e2e8f0;
        background: #1a1f2e;
    }

    .resp-nav__link--primary {
        background: rgba(0, 212, 255, 0.12);
        border-color: rgba(0, 212, 255, 0.35);
        color: #67e8f9;
    }

    .resp-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 28px;
    }

    @media (max-width: 960px) {
        .resp-stats { grid-template-columns: repeat(2, 1fr); }
    }

    .resp-stat {
        display: flex;
        gap: 12px;
        align-items: center;
        padding: 16px;
        border-radius: 12px;
        background: linear-gradient(165deg, #1a2332 0%, #0f1419 100%);
        border: 1px solid #2d3748;
        position: relative;
        overflow: hidden;
    }

    .resp-stat::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
    }

    .resp-stat--cyan::before { background: #22d3ee; }
    .resp-stat--amber::before { background: #fbbf24; }
    .resp-stat--red::before { background: #f87171; }
    .resp-stat--slate::before { background: #94a3b8; }

    .resp-stat__icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid #334155;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .resp-stat__label {
        display: block;
        font-size: 0.68rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .resp-stat__value {
        display: block;
        font-size: 1.55rem;
        font-weight: 700;
        color: #f8fafc;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .resp-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .resp-section-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 600;
        color: #e2e8f0;
    }

    .resp-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .resp-filter {
        border: 1px solid #2d3748;
        background: #12171f;
        color: #94a3b8;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 0.72rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .resp-filter span {
        background: rgba(148, 163, 184, 0.15);
        border-radius: 999px;
        padding: 1px 6px;
        font-variant-numeric: tabular-nums;
    }

    .resp-filter.is-active,
    .resp-filter:hover {
        border-color: rgba(34, 211, 238, 0.45);
        color: #e2e8f0;
        background: rgba(34, 211, 238, 0.08);
    }

    .resp-group {
        margin-bottom: 22px;
    }

    .resp-group__head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 10px;
        padding: 0 2px;
    }

    .resp-group__head h3 {
        margin: 0;
        font-size: 0.78rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
    }

    .resp-group__head span {
        font-size: 0.72rem;
        color: #475569;
    }

    .resp-cards {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .resp-card {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 16px;
        align-items: center;
        padding: 14px 16px;
        border-radius: 12px;
        background: #151a24;
        border: 1px solid #2d3748;
        transition: border-color 0.15s, background 0.15s, transform 0.15s;
    }

    .resp-card:hover {
        border-color: rgba(34, 211, 238, 0.35);
        background: #181e2a;
    }

    .resp-card--off {
        opacity: 0.55;
    }

    @media (max-width: 800px) {
        .resp-card {
            grid-template-columns: 1fr;
            gap: 10px;
        }
    }

    .resp-card__title-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 4px;
    }

    .resp-card__name {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #f1f5f9;
    }

    .resp-card__desc {
        margin: 0;
        font-size: 0.8rem;
        color: #64748b;
        line-height: 1.4;
        max-width: 48rem;
    }

    .resp-card__actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
        min-width: 200px;
    }

    @media (max-width: 800px) {
        .resp-card__actions { align-items: flex-start; min-width: 0; }
    }

    .resp-cat {
        font-size: 0.68rem;
        color: #7dd3fc;
        background: rgba(14, 116, 144, 0.2);
        border: 1px solid rgba(34, 211, 238, 0.25);
        padding: 3px 8px;
        border-radius: 999px;
    }

    .resp-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: flex-end;
    }

    .resp-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 4px 8px;
        border-radius: 6px;
    }

    .resp-badge em {
        font-style: normal;
        font-weight: 500;
        opacity: 0.85;
        text-transform: none;
        letter-spacing: 0;
    }

    .resp-badge--block {
        background: rgba(239, 68, 68, 0.18);
        color: #fca5a5;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .resp-badge--notify {
        background: rgba(234, 179, 8, 0.16);
        color: #fde68a;
        border: 1px solid rgba(234, 179, 8, 0.3);
    }

    .resp-badge--log {
        background: rgba(100, 116, 139, 0.22);
        color: #cbd5e1;
        border: 1px solid rgba(148, 163, 184, 0.25);
    }

    .resp-status {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 3px 8px;
        border-radius: 999px;
    }

    .resp-status--on {
        background: rgba(34, 197, 94, 0.15);
        color: #86efac;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .resp-status--off {
        background: rgba(100, 116, 139, 0.2);
        color: #94a3b8;
        border: 1px solid rgba(100, 116, 139, 0.3);
    }

    .resp-empty {
        text-align: center;
        padding: 48px 20px;
        border: 1px dashed #2d3748;
        border-radius: 12px;
        color: #94a3b8;
    }

    .resp-empty__icon { font-size: 2rem; margin-bottom: 8px; }
</style>
@endpush

@section('scripts')
<script>
(function () {
    var root = document.getElementById('respFilters');
    if (!root) return;
    var buttons = root.querySelectorAll('.resp-filter');
    var groups = document.querySelectorAll('[data-cat-group]');

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cat = btn.getAttribute('data-cat');
            buttons.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
            groups.forEach(function (g) {
                var show = cat === 'all' || g.getAttribute('data-cat-group') === cat;
                g.style.display = show ? '' : 'none';
            });
        });
    });
})();
</script>
@endsection
