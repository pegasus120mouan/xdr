@extends('layouts.monitor-full')

@section('title', 'Attack Map — Wara XDR')

@push('styles')
<style>
    .amap {
        --amap-bg0: #030712;
        --amap-bg1: #0a1628;
        --amap-panel: rgba(12, 28, 52, 0.82);
        --amap-border: rgba(56, 189, 248, 0.22);
        --amap-orange: #f59e0b;
        --amap-orange2: #fbbf24;
        --amap-cyan: #38bdf8;
        --amap-red: #f87171;
        min-height: 100vh;
        padding: 16px 20px 24px;
        box-sizing: border-box;
        background: linear-gradient(165deg, var(--amap-bg0) 0%, var(--amap-bg1) 45%, #050a14 100%);
        color: #e2e8f0;
        font-family: 'Inter', system-ui, sans-serif;
        font-size: 13px;
    }

    .amap-header {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: start;
        gap: 12px;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--amap-border);
    }

    .amap-header__left { font-size: 0.78rem; color: #94a3b8; line-height: 1.5; }
    .amap-header__left strong { color: #f1f5f9; font-weight: 600; }
    .amap-header__title {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-align: center;
        color: #f8fafc;
    }
    .amap-header__right { text-align: right; }

    .amap-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: 1px solid var(--amap-border);
        border-radius: 6px;
        background: rgba(15, 40, 70, 0.5);
        color: var(--amap-cyan);
        text-decoration: none;
        font-size: 1rem;
        transition: background 0.2s, border-color 0.2s;
    }
    .amap-icon-btn:hover { background: rgba(56, 189, 248, 0.12); border-color: var(--amap-cyan); }

    .amap-grid {
        display: grid;
        grid-template-columns: 240px minmax(420px, 1fr) 260px;
        grid-template-rows: auto 1fr;
        gap: 14px;
        align-items: stretch;
    }

    @media (max-width: 1200px) {
        .amap-grid {
            grid-template-columns: 1fr;
            grid-template-rows: auto;
        }
    }

    .amap-panel {
        background: var(--amap-panel);
        border: 1px solid var(--amap-border);
        border-radius: 10px;
        padding: 14px 16px;
        backdrop-filter: blur(8px);
    }

    .amap-panel h3 {
        margin: 0 0 12px;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
    }

    .amap-kpis {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .amap-kpi {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .amap-kpi__icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        background: rgba(56, 189, 248, 0.12);
        border: 1px solid rgba(56, 189, 248, 0.28);
    }

    .amap-kpi--attacks .amap-kpi__icon {
        background: rgba(248, 113, 113, 0.12);
        border-color: rgba(248, 113, 113, 0.35);
    }

    .amap-kpi__val {
        font-size: 1.65rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
        color: #fff;
    }

    .amap-kpi__lbl { font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; }

    .amap-bars { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
    .amap-bar-row { display: flex; align-items: center; gap: 8px; font-size: 0.72rem; }
    .amap-bar-row span:first-child { width: 52px; color: #94a3b8; flex-shrink: 0; }
    .amap-bar-track {
        flex: 1;
        height: 8px;
        border-radius: 4px;
        background: rgba(30, 58, 95, 0.6);
        overflow: hidden;
    }
    .amap-bar-fill { height: 100%; border-radius: 4px; transition: width 0.6s ease; }
    .amap-bar-fill--high { background: linear-gradient(90deg, #dc2626, #f87171); }
    .amap-bar-fill--med { background: linear-gradient(90deg, #ea580c, #fb923c); }
    .amap-bar-fill--low { background: linear-gradient(90deg, #15803d, #4ade80); }
    .amap-bar-row strong { min-width: 22px; text-align: right; font-variant-numeric: tabular-nums; color: #cbd5e1; }

    .amap-map-wrap {
        grid-row: span 2;
        position: relative;
        min-height: 580px;
    }

    @media (max-width: 1200px) {
        .amap-map-wrap { grid-row: auto; min-height: 450px; }
    }

    .amap-map-inner {
        position: absolute;
        inset: 0;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--amap-border);
        background:
            radial-gradient(ellipse 80% 60% at 50% 45%, rgba(30, 64, 120, 0.35) 0%, transparent 55%),
            linear-gradient(180deg, #050d1c 0%, #0a1628 100%);
        isolation: isolate;
    }

    .amap-map-inner::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0.14;
        background-image:
            linear-gradient(rgba(56, 189, 248, 0.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(56, 189, 248, 0.08) 1px, transparent 1px);
        background-size: 48px 48px;
        pointer-events: none;
    }

    .amap-svg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }

    .amap-svg .amap-world-bg {
        opacity: 0.38;
        filter: brightness(0.55) contrast(1.15) saturate(0.35);
    }

    .amap-map-credit {
        position: absolute;
        right: 14px;
        bottom: 10px;
        margin: 0;
        font-size: 0.62rem;
        color: #475569;
        z-index: 4;
        pointer-events: auto;
    }

    .amap-map-credit a {
        color: #64748b;
        text-decoration: none;
    }

    .amap-map-credit a:hover {
        color: #94a3b8;
        text-decoration: underline;
    }

    .amap-arc.amap-arc--visible {
        fill: none;
        stroke-linecap: round;
        stroke-width: 1.5;
        opacity: 0.88;
        stroke-dasharray: 8 6;
        animation: amap-dash 2.8s linear infinite;
        pointer-events: none;
        transition: stroke-width 0.2s ease, opacity 0.2s ease, filter 0.2s ease;
    }

    .amap-arc-hit {
        fill: none;
        stroke: transparent;
        stroke-width: 16;
        stroke-linecap: round;
        pointer-events: stroke;
        cursor: pointer;
    }

    .amap-flow--critical .amap-arc--visible,
    .amap-flow--high .amap-arc--visible { stroke: #fb923c; filter: drop-shadow(0 0 4px rgba(251, 146, 60, 0.55)); }
    .amap-flow--medium .amap-arc--visible { stroke: #fbbf24; filter: drop-shadow(0 0 3px rgba(251, 191, 36, 0.4)); }
    .amap-flow--low .amap-arc--visible { stroke: #fcd34d; opacity: 0.62; filter: drop-shadow(0 0 2px rgba(252, 211, 77, 0.35)); }

    .amap-flow:hover .amap-arc--visible,
    .amap-flow.is-highlighted .amap-arc--visible {
        stroke-width: 2.6;
        opacity: 1;
        filter: drop-shadow(0 0 10px rgba(251, 191, 36, 0.85)) !important;
    }

    .amap-flow.is-dimmed .amap-arc--visible {
        opacity: 0.18;
        filter: none !important;
    }

    @keyframes amap-dash {
        to { stroke-dashoffset: -28; }
    }

    @media (prefers-reduced-motion: reduce) {
        .amap-arc.amap-arc--visible { animation: none; stroke-dasharray: none; }
    }

    .amap-origin {
        cursor: pointer;
        pointer-events: all;
        transition: opacity 0.2s ease, filter 0.2s ease;
    }

    .amap-origin:hover,
    .amap-origin.is-highlighted {
        filter: drop-shadow(0 0 12px rgba(56, 189, 248, 0.45));
    }

    .amap-origin-lbl {
        font-size: 11px;
        font-weight: 600;
        fill: #f8fafc;
        paint-order: stroke fill;
        stroke: rgba(3, 7, 18, 0.92);
        stroke-width: 3.5px;
        stroke-linejoin: round;
        pointer-events: none;
    }

    .amap-origin-sub {
        font-size: 9px;
        font-weight: 500;
        fill: #94a3b8;
        paint-order: stroke fill;
        stroke: rgba(3, 7, 18, 0.92);
        stroke-width: 3px;
        pointer-events: none;
    }

    .amap-origin-core {
        transition: stroke 0.2s ease, fill 0.2s ease;
    }

    .amap-origin:hover .amap-origin-core,
    .amap-origin.is-highlighted .amap-origin-core {
        fill: #1e3a5f;
        stroke: #38bdf8;
        stroke-width: 2;
    }

    .amap-origin-ring {
        animation: amap-origin-pulse 2.2s ease-in-out infinite;
        transform-origin: center;
        transform-box: fill-box;
    }

    @keyframes amap-origin-pulse {
        0%, 100% { opacity: 0.22; }
        50% { opacity: 0.55; }
    }

    @media (prefers-reduced-motion: reduce) {
        .amap-origin-ring { animation: none; opacity: 0.35; }
    }

    .amap-origin.is-dimmed {
        opacity: 0.22;
    }

    .amap-origin.is-dimmed .amap-origin-lbl,
    .amap-origin.is-dimmed .amap-origin-sub {
        opacity: 0.35;
    }

    .amap-tip {
        position: absolute;
        z-index: 30;
        min-width: 200px;
        max-width: 280px;
        padding: 12px 14px;
        border-radius: 10px;
        background: linear-gradient(165deg, rgba(22, 32, 52, 0.98) 0%, rgba(15, 23, 42, 0.98) 100%);
        border: 1px solid rgba(56, 189, 248, 0.28);
        box-shadow:
            0 0 0 1px rgba(0, 0, 0, 0.35),
            0 16px 40px rgba(0, 0, 0, 0.55);
        pointer-events: none;
        opacity: 0;
        transform: translateY(4px);
        transition: opacity 0.18s ease, transform 0.18s ease;
    }

    .amap-tip.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .amap-tip__title {
        font-size: 0.82rem;
        font-weight: 700;
        color: #f1f5f9;
        margin: 0 0 6px;
        line-height: 1.35;
    }

    .amap-tip__meta {
        font-size: 0.72rem;
        color: #94a3b8;
        line-height: 1.45;
        margin: 0;
    }

    .amap-tip__meta strong {
        color: #cbd5e1;
        font-weight: 600;
    }

    .amap-tip__sev {
        display: inline-block;
        margin-top: 8px;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: capitalize;
    }

    .amap-tip__sev--critical,
    .amap-tip__sev--high { background: rgba(248, 113, 113, 0.2); color: #fca5a5; }
    .amap-tip__sev--medium { background: rgba(251, 146, 60, 0.2); color: #fdba74; }
    .amap-tip__sev--low { background: rgba(74, 222, 128, 0.15); color: #86efac; }

    .amap-src-item--interactive {
        cursor: pointer;
        border-radius: 8px;
        margin: 0 -6px;
        padding: 4px 6px;
        transition: background 0.15s ease;
    }

    .amap-src-item--interactive:hover {
        background: rgba(56, 189, 248, 0.08);
    }

    .amap-home {
        filter: drop-shadow(0 0 12px rgba(56, 189, 248, 0.85));
    }

    .amap-home-pulse {
        animation: amap-pulse 2.4s ease-in-out infinite;
    }

    @keyframes amap-pulse {
        0%, 100% { r: 9; opacity: 0.5; }
        50% { r: 18; opacity: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        .amap-home-pulse { display: none; }
    }

    .amap-home-label {
        font-size: 11px;
        fill: #7dd3fc;
        font-weight: 600;
    }

    .amap-src-list { display: flex; flex-direction: column; gap: 10px; }
    .amap-src-item { display: grid; grid-template-columns: 28px 1fr 36px; gap: 8px; align-items: center; font-size: 0.78rem; }
    .amap-src-flag { font-size: 1.1rem; line-height: 1; text-align: center; }
    .amap-src-name { color: #cbd5e1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .amap-src-bar { height: 6px; border-radius: 3px; background: rgba(30, 58, 95, 0.6); overflow: hidden; grid-column: 2 / -1; }
    .amap-src-bar > span { display: block; height: 100%; border-radius: 3px; background: linear-gradient(90deg, #d97706, #fbbf24); }

    .amap-types { list-style: none; margin: 0; padding: 0; }
    .amap-types li {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        padding: 6px 0;
        border-bottom: 1px solid rgba(51, 65, 85, 0.45);
        font-size: 0.78rem;
    }
    .amap-types li:last-child { border-bottom: 0; }
    .amap-types span:first-child { color: #94a3b8; overflow: hidden; text-overflow: ellipsis; }
    .amap-types strong { color: var(--amap-orange2); font-variant-numeric: tabular-nums; }

    .amap-table-wrap { overflow-x: auto; }
    .amap-table { width: 100%; border-collapse: collapse; font-size: 0.72rem; }
    .amap-table th {
        text-align: left;
        padding: 8px 6px;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--amap-border);
    }
    .amap-table td { padding: 8px 6px; border-bottom: 1px solid rgba(51, 65, 85, 0.35); vertical-align: middle; }
    .amap-table tr:hover td { background: rgba(56, 189, 248, 0.04); }

    .amap-sev { font-weight: 700; text-transform: capitalize; }
    .amap-sev--critical, .amap-sev--high { color: #f87171; }
    .amap-sev--medium { color: #fb923c; }
    .amap-sev--low { color: #4ade80; }

    .amap-atype { color: var(--amap-orange); font-weight: 500; }

    .amap-bottom {
        display: grid;
        grid-template-columns: 240px minmax(420px, 1fr) 260px;
        gap: 14px;
        margin-top: 14px;
    }

    @media (max-width: 1200px) {
        .amap-bottom { grid-template-columns: 1fr; }
    }

    .amap-empty { color: #64748b; font-size: 0.8rem; text-align: center; padding: 12px; }
</style>
@endpush

@section('content')
@php
    $flagEmoji = function (?string $code): string {
        $code = strtoupper(substr((string) $code, 0, 2));
        if (strlen($code) !== 2 || ! ctype_alpha($code)) {
            return '🌐';
        }

        return mb_chr(0x1F1E6 - 65 + ord($code[0]), 'UTF-8').mb_chr(0x1F1E6 - 65 + ord($code[1]), 'UTF-8');
    };
@endphp
<div class="amap">
    <header class="amap-header">
        <div class="amap-header__left">
            <div id="amap-clock" data-locale="fr">{{ now()->format('D Y-m-d H:i:s') }}</div>
            <div><strong>Région / pays :</strong> {{ $home['label'] }}</div>
        </div>
        <h1 class="amap-header__title">Attack Map</h1>
        <div class="amap-header__right">
            <a href="{{ route('dashboard') }}" class="amap-icon-btn" title="Retour à l’application">🖥</a>
        </div>
    </header>

    <div class="amap-grid">
        <div class="amap-panel">
            <h3>Aujourd’hui</h3>
            <div class="amap-kpis">
                <div class="amap-kpi">
                    <div class="amap-kpi__icon" aria-hidden="true">🔔</div>
                    <div>
                        <div class="amap-kpi__val">{{ $eventsToday }}</div>
                        <div class="amap-kpi__lbl">Événements sécurité</div>
                    </div>
                </div>
                <div class="amap-kpi amap-kpi--attacks">
                    <div class="amap-kpi__icon" aria-hidden="true">⚔</div>
                    <div>
                        <div class="amap-kpi__val">{{ $attacksToday }}</div>
                        <div class="amap-kpi__lbl">Attaques (volume)</div>
                    </div>
                </div>
            </div>
            @php
                $thMax = max(1, $threatToday['high'], $threatToday['medium'], $threatToday['low']);
                $pctH = (int) round(100 * $threatToday['high'] / $thMax);
                $pctM = (int) round(100 * $threatToday['medium'] / $thMax);
                $pctL = (int) round(100 * $threatToday['low'] / $thMax);
            @endphp
            <h3 style="margin-top:18px;">Niveau de menace</h3>
            <div class="amap-bars">
                <div class="amap-bar-row">
                    <span>High</span>
                    <div class="amap-bar-track"><div class="amap-bar-fill amap-bar-fill--high" style="width: {{ $pctH }}%;"></div></div>
                    <strong>{{ $threatToday['high'] }}</strong>
                </div>
                <div class="amap-bar-row">
                    <span>Medium</span>
                    <div class="amap-bar-track"><div class="amap-bar-fill amap-bar-fill--med" style="width: {{ $pctM }}%;"></div></div>
                    <strong>{{ $threatToday['medium'] }}</strong>
                </div>
                <div class="amap-bar-row">
                    <span>Low</span>
                    <div class="amap-bar-track"><div class="amap-bar-fill amap-bar-fill--low" style="width: {{ $pctL }}%;"></div></div>
                    <strong>{{ $threatToday['low'] }}</strong>
                </div>
            </div>
        </div>

        <div class="amap-panel amap-map-wrap">
            <div class="amap-map-inner">
                <svg class="amap-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 {{ $mapSize['w'] }} {{ $mapSize['h'] }}" preserveAspectRatio="xMidYMid meet" aria-label="Carte des flux d’attaque">
                    <defs>
                        <filter id="amap-glow" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur stdDeviation="1.2" result="b" />
                            <feMerge><feMergeNode in="b" /><feMergeNode in="SourceGraphic" /></feMerge>
                        </filter>
                    </defs>
                    <image
                        class="amap-world-bg"
                        href="{{ asset('images/world-map.svg') }}"
                        xlink:href="{{ asset('images/world-map.svg') }}"
                        x="0"
                        y="0"
                        width="{{ $mapSize['w'] }}"
                        height="{{ $mapSize['h'] }}"
                        preserveAspectRatio="xMidYMid slice"
                    />
                    @foreach($arcs as $arc)
                        @php
                            $sev = in_array($arc['severity'] ?? '', ['critical', 'high', 'medium', 'low'], true)
                                ? $arc['severity']
                                : 'medium';
                        @endphp
                        <g
                            class="amap-flow amap-flow--{{ $sev }}"
                            data-amap-flow="1"
                            data-code="{{ $arc['code'] }}"
                            data-country="{{ $arc['country'] }}"
                            data-ip="{{ $arc['ip'] }}"
                            data-severity="{{ $sev }}"
                        >
                            <path class="amap-arc amap-arc--visible amap-arc--{{ $sev }}" d="{{ $arc['path'] }}" filter="url(#amap-glow)" />
                            <path class="amap-arc-hit" d="{{ $arc['path'] }}" vector-effect="non-scaling-stroke" />
                        </g>
                    @endforeach
                    @foreach($originMarkers as $om)
                        @php $omFlag = $flagEmoji($om['code']); @endphp
                        <g
                            class="amap-origin"
                            transform="translate({{ round($om['sx'], 2) }}, {{ round($om['sy'], 2) }})"
                            data-amap-origin="1"
                            data-code="{{ $om['code'] }}"
                            data-country="{{ $om['name'] }}"
                            data-ip-count="{{ (int) $om['ip_count'] }}"
                            data-ips="{{ implode(' · ', $om['ips_preview']) }}"
                        >
                            <circle class="amap-origin-ring" r="12" cx="0" cy="0" fill="none" stroke="#fb923c" stroke-width="0.75" />
                            <circle class="amap-origin-core" r="5" cx="0" cy="0" />
                            <text class="amap-origin-lbl" x="0" y="-14" text-anchor="middle">{{ $omFlag }} {{ \Illuminate\Support\Str::limit($om['name'], 22) }}</text>
                            @if($om['ip_count'] > 1)
                                <text class="amap-origin-sub" x="0" y="17" text-anchor="middle">{{ $om['ip_count'] }} IP distinctes</text>
                            @endif
                        </g>
                    @endforeach
                    <circle class="amap-home-pulse" cx="{{ $homeXY['x'] }}" cy="{{ $homeXY['y'] }}" r="14" fill="none" stroke="#38bdf8" stroke-width="1" />
                    <circle class="amap-home" cx="{{ $homeXY['x'] }}" cy="{{ $homeXY['y'] }}" r="7" fill="#0ea5e9" stroke="#e0f2fe" stroke-width="1.5" />
                    <text class="amap-home-label" x="{{ $homeXY['x'] }}" y="{{ $homeXY['y'] + 22 }}" text-anchor="middle">{{ \Illuminate\Support\Str::limit($home['label'], 18) }}</text>
                </svg>
                <div id="amap-tip" class="amap-tip" role="tooltip" hidden>
                    <p class="amap-tip__title" id="amap-tip-title"></p>
                    <p class="amap-tip__meta" id="amap-tip-meta"></p>
                    <span class="amap-tip__sev" id="amap-tip-sev" hidden></span>
                </div>
            </div>
        </div>

        <div class="amap-panel">
            <h3>Sources d’attaque</h3>
            @if(count($sourceCountries) === 0)
                <p class="amap-empty">Aucune IP source publique sur 7 jours.</p>
            @else
                <div class="amap-src-list">
                    @foreach($sourceCountries as $src)
                        <div class="amap-src-item--interactive" data-amap-code="{{ $src['code'] }}">
                            <div class="amap-src-item">
                                <span class="amap-src-flag" title="{{ $src['code'] }}">{{ $flagEmoji($src['code']) }}</span>
                                <span class="amap-src-name">{{ $src['name'] }}</span>
                                <strong style="font-variant-numeric:tabular-nums;color:#f1f5f9;">{{ $src['count'] }}</strong>
                            </div>
                            <div class="amap-src-bar"><span style="width: {{ $src['pct'] }}%;"></span></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="amap-bottom">
        <div class="amap-panel">
            <h3>Principales cibles</h3>
            @if($topTargets->isEmpty())
                <p class="amap-empty">Aucune cible récente.</p>
            @else
                <table class="amap-table">
                    <thead>
                        <tr><th>Nom</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        @foreach($topTargets as $t)
                            <tr>
                                <td>{{ ($t->affected_asset ?: 'Sans nom') }} ({{ $t->target_ip }})</td>
                                <td><strong>{{ $t->c }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="amap-panel">
            <h3>Attaques récentes</h3>
            <div class="amap-table-wrap">
                @if($recentRows->isEmpty())
                    <p class="amap-empty">Aucune alerte en base.</p>
                @else
                    <table class="amap-table">
                        <thead>
                            <tr>
                                <th>Heure</th>
                                <th>Origine</th>
                                <th>IP attaquant</th>
                                <th>IP cible</th>
                                <th>Type</th>
                                <th>Gravité</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentRows as $row)
                                <tr>
                                    <td>{{ $row['time']?->format('m/d H:i:s') ?? '—' }}</td>
                                    <td>{{ $flagEmoji($row['geo_code']) }} {{ $row['geo_label'] }}</td>
                                    <td>{{ $row['source_ip'] }}</td>
                                    <td>{{ $row['target_ip'] }}</td>
                                    <td class="amap-atype">{{ $row['attack_type'] }}</td>
                                    <td class="amap-sev amap-sev--{{ $row['severity'] }}">{{ $row['severity'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="amap-panel">
            <h3>Types d’attaque</h3>
            @if($attackTypes->isEmpty())
                <p class="amap-empty">Aucune donnée sur 7 jours.</p>
            @else
                <ul class="amap-types">
                    @foreach($attackTypes as $label => $cnt)
                        <li><span>{{ $label }}</span><strong>{{ $cnt }}</strong></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var el = document.getElementById('amap-clock');
    if (!el) return;
    var locale = el.getAttribute('data-locale') || 'fr';
    var daysEn = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var daysFr = ['dim.','lun.','mar.','mer.','jeu.','ven.','sam.'];
    function tick() {
        var d = new Date();
        var days = locale === 'fr' ? daysFr : daysEn;
        var y = d.getFullYear();
        var mo = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        var h = String(d.getHours()).padStart(2, '0');
        var mi = String(d.getMinutes()).padStart(2, '0');
        var s = String(d.getSeconds()).padStart(2, '0');
        el.textContent = days[d.getDay()] + ' ' + y + '-' + mo + '-' + day + ' ' + h + ':' + mi + ':' + s;
    }
    tick();
    setInterval(tick, 1000);
})();

(function () {
    var wrap = document.querySelector('.amap-map-inner');
    var tip = document.getElementById('amap-tip');
    var tipTitle = document.getElementById('amap-tip-title');
    var tipMeta = document.getElementById('amap-tip-meta');
    var tipSev = document.getElementById('amap-tip-sev');
    if (!wrap || !tip || !tipTitle || !tipMeta || !tipSev) return;

    var hideTimer = null;
    var highlightCode = null;

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function placeTip(clientX, clientY) {
        var r = wrap.getBoundingClientRect();
        var x = clientX - r.left + 12;
        var y = clientY - r.top + 12;
        tip.style.left = x + 'px';
        tip.style.top = y + 'px';
        requestAnimationFrame(function () {
            var tw = tip.offsetWidth;
            var th = tip.offsetHeight;
            if (x + tw > r.width - 10) x = Math.max(10, r.width - tw - 10);
            if (y + th > r.height - 10) y = Math.max(10, r.height - th - 10);
            tip.style.left = x + 'px';
            tip.style.top = y + 'px';
        });
    }

    function showTip() {
        tip.removeAttribute('hidden');
        requestAnimationFrame(function () {
            tip.classList.add('is-visible');
        });
    }

    function hideTip() {
        tip.classList.remove('is-visible');
        setTimeout(function () {
            tip.setAttribute('hidden', '');
        }, 200);
    }

    function bindFlow(g) {
        g.addEventListener('mouseenter', function (e) {
            clearTimeout(hideTimer);
            var country = g.getAttribute('data-country') || '';
            var sev = g.getAttribute('data-severity') || 'medium';
            tipTitle.textContent = g.getAttribute('data-ip') || '—';
            tipMeta.innerHTML = '<strong>Provenance</strong> · ' + escapeHtml(country);
            tipSev.hidden = false;
            tipSev.textContent = sev;
            tipSev.className = 'amap-tip__sev amap-tip__sev--' + sev;
            showTip();
            placeTip(e.clientX, e.clientY);
        });
        g.addEventListener('mousemove', function (e) {
            if (!tip.hasAttribute('hidden')) placeTip(e.clientX, e.clientY);
        });
        g.addEventListener('mouseleave', function () {
            hideTimer = setTimeout(hideTip, 100);
        });
    }

    function bindOrigin(g) {
        g.addEventListener('mouseenter', function (e) {
            clearTimeout(hideTimer);
            var country = g.getAttribute('data-country') || '';
            var cnt = g.getAttribute('data-ip-count') || '0';
            var ips = g.getAttribute('data-ips') || '';
            tipTitle.textContent = country;
            var html = '<strong>Sources</strong> · ' + escapeHtml(String(cnt)) + ' adresse(s) distincte(s)';
            if (ips) html += '<br><span style="color:#64748b;font-size:0.68rem;">' + escapeHtml(ips) + '</span>';
            tipMeta.innerHTML = html;
            tipSev.hidden = true;
            showTip();
            placeTip(e.clientX, e.clientY);
        });
        g.addEventListener('mousemove', function (e) {
            if (!tip.hasAttribute('hidden')) placeTip(e.clientX, e.clientY);
        });
        g.addEventListener('mouseleave', function () {
            hideTimer = setTimeout(hideTip, 100);
        });
    }

    wrap.querySelectorAll('[data-amap-flow="1"]').forEach(bindFlow);
    wrap.querySelectorAll('[data-amap-origin="1"]').forEach(bindOrigin);

    function applyHighlight() {
        var flows = wrap.querySelectorAll('[data-amap-flow="1"]');
        var origins = wrap.querySelectorAll('[data-amap-origin="1"]');
        if (!highlightCode) {
            flows.forEach(function (el) { el.classList.remove('is-highlighted', 'is-dimmed'); });
            origins.forEach(function (el) { el.classList.remove('is-highlighted', 'is-dimmed'); });
            return;
        }
        flows.forEach(function (el) {
            var match = el.getAttribute('data-code') === highlightCode;
            el.classList.toggle('is-highlighted', match);
            el.classList.toggle('is-dimmed', !match);
        });
        origins.forEach(function (el) {
            var match = el.getAttribute('data-code') === highlightCode;
            el.classList.toggle('is-highlighted', match);
            el.classList.toggle('is-dimmed', !match);
        });
    }

    document.querySelectorAll('[data-amap-code]').forEach(function (row) {
        row.addEventListener('mouseenter', function () {
            highlightCode = row.getAttribute('data-amap-code');
            applyHighlight();
        });
        row.addEventListener('mouseleave', function () {
            highlightCode = null;
            applyHighlight();
        });
    });
})();
</script>
@endpush
