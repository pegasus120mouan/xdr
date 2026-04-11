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
        min-height: 420px;
    }

    @media (max-width: 1200px) {
        .amap-map-wrap { grid-row: auto; min-height: 360px; }
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

    .amap-arc {
        fill: none;
        stroke-linecap: round;
        stroke-width: 1.4;
        opacity: 0.85;
        stroke-dasharray: 8 6;
        animation: amap-dash 2.8s linear infinite;
    }

    .amap-arc--critical,
    .amap-arc--high { stroke: #fb923c; filter: drop-shadow(0 0 4px rgba(251, 146, 60, 0.6)); }
    .amap-arc--medium { stroke: #fbbf24; filter: drop-shadow(0 0 3px rgba(251, 191, 36, 0.45)); }
    .amap-arc--low { stroke: #fcd34d; opacity: 0.55; }

    @keyframes amap-dash {
        to { stroke-dashoffset: -28; }
    }

    @media (prefers-reduced-motion: reduce) {
        .amap-arc { animation: none; stroke-dasharray: none; }
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
                        <path class="amap-arc amap-arc--{{ $arc['severity'] ?? 'medium' }}" d="{{ $arc['path'] }}" filter="url(#amap-glow)" />
                    @endforeach
                    <circle class="amap-home-pulse" cx="{{ $homeXY['x'] }}" cy="{{ $homeXY['y'] }}" r="14" fill="none" stroke="#38bdf8" stroke-width="1" />
                    <circle class="amap-home" cx="{{ $homeXY['x'] }}" cy="{{ $homeXY['y'] }}" r="7" fill="#0ea5e9" stroke="#e0f2fe" stroke-width="1.5" />
                    <text class="amap-home-label" x="{{ $homeXY['x'] }}" y="{{ $homeXY['y'] + 22 }}" text-anchor="middle">{{ \Illuminate\Support\Str::limit($home['label'], 18) }}</text>
                </svg>
            </div>
            <p class="amap-map-credit">
                Fond carte :
                <a href="https://commons.wikimedia.org/wiki/File:World_map_-_low_resolution.svg" target="_blank" rel="noopener noreferrer">Wikimedia Commons</a>
            </p>
        </div>

        <div class="amap-panel">
            <h3>Sources d’attaque</h3>
            @if(count($sourceCountries) === 0)
                <p class="amap-empty">Aucune IP source publique sur 7 jours.</p>
            @else
                <div class="amap-src-list">
                    @foreach($sourceCountries as $src)
                        <div>
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
</script>
@endpush
