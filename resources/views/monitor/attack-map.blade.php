@extends('layouts.monitor-full')

@section('title', 'Live Threat Map — KORASHIELD')

@push('styles')
<style>
    :root {
        --rtm-bg: #020617;
        --rtm-panel: rgba(2, 12, 28, 0.72);
        --rtm-border: rgba(56, 189, 248, 0.18);
        --rtm-cyan: #22d3ee;
        --rtm-blue: #38bdf8;
        --rtm-orange: #fb923c;
        --rtm-red: #f43f5e;
        --rtm-magenta: #e879f9;
        --rtm-green: #34d399;
        --rtm-text: #e2e8f0;
        --rtm-muted: #64748b;
    }

    html, body.monitor-full { height: 100%; overflow: hidden; }

    .rtm {
        position: relative;
        height: 100vh;
        width: 100vw;
        overflow: hidden;
        background:
            radial-gradient(ellipse 70% 55% at 50% 45%, rgba(14, 60, 120, 0.35) 0%, transparent 58%),
            radial-gradient(ellipse 40% 30% at 20% 80%, rgba(225, 29, 72, 0.08) 0%, transparent 50%),
            linear-gradient(180deg, #020617 0%, #06101f 50%, #020617 100%);
        color: var(--rtm-text);
        font-family: 'Inter', system-ui, sans-serif;
    }

    .rtm-stars {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background-image:
            radial-gradient(1px 1px at 10% 20%, rgba(255,255,255,0.35), transparent),
            radial-gradient(1px 1px at 80% 30%, rgba(255,255,255,0.25), transparent),
            radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.2), transparent),
            radial-gradient(1px 1px at 65% 15%, rgba(255,255,255,0.3), transparent);
        opacity: 0.5;
    }

    .rtm-stage {
        position: absolute;
        inset: 0;
        z-index: 1;
    }

    .rtm-svg {
        width: 100%;
        height: 100%;
        display: block;
    }

    .rtm-world {
        opacity: 0.55;
        filter: brightness(0.85) contrast(1.15) saturate(0.7);
    }

    .rtm-arc {
        fill: none;
        stroke-width: 1.4;
        stroke-linecap: round;
        opacity: 0.85;
    }
    .rtm-flow--critical .rtm-arc, .rtm-flow--high .rtm-arc { stroke: var(--rtm-red); }
    .rtm-flow--medium .rtm-arc { stroke: var(--rtm-orange); }
    .rtm-flow--low .rtm-arc { stroke: var(--rtm-cyan); }

    .rtm-pulse {
        fill: #fff;
        filter: drop-shadow(0 0 4px currentColor);
    }
    .rtm-flow--critical .rtm-pulse, .rtm-flow--high .rtm-pulse { fill: #fda4af; }
    .rtm-flow--medium .rtm-pulse { fill: #fdba74; }
    .rtm-flow--low .rtm-pulse { fill: #67e8f9; }

    .rtm-origin-core { fill: var(--rtm-orange); stroke: #fff7ed; stroke-width: 1; }
    .rtm-origin-ring {
        fill: none;
        stroke: var(--rtm-orange);
        stroke-width: 0.8;
        opacity: 0.7;
        animation: rtm-ring 2.4s ease-out infinite;
    }
    .rtm-home {
        fill: var(--rtm-cyan);
        stroke: #e0f2fe;
        stroke-width: 1.6;
        filter: drop-shadow(0 0 12px rgba(34, 211, 238, 0.8));
    }
    .rtm-home-ring {
        fill: none;
        stroke: var(--rtm-cyan);
        stroke-width: 1;
        animation: rtm-ring 2.8s ease-out infinite;
    }
    .rtm-label {
        fill: #cbd5e1;
        font-size: 11px;
        font-weight: 600;
        pointer-events: none;
    }
    .rtm-label--home { fill: #67e8f9; font-size: 12px; }

    @keyframes rtm-ring {
        0% { r: 8; opacity: 0.7; }
        100% { r: 26; opacity: 0; }
    }

    .rtm-flow.is-dimmed { opacity: 0.12; }
    .rtm-flow.is-highlighted .rtm-arc { stroke-width: 2.4; opacity: 1; }
    .rtm-origin.is-dimmed { opacity: 0.15; }

    .rtm-hud {
        position: absolute;
        z-index: 5;
        pointer-events: none;
    }
    .rtm-hud > * { pointer-events: auto; }

    .rtm-top {
        top: 0; left: 0; right: 0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 18px 22px;
        background: linear-gradient(180deg, rgba(2,6,23,0.85) 0%, transparent 100%);
    }

    .rtm-brand {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .rtm-brand__title {
        margin: 0;
        font-family: 'Orbitron', sans-serif;
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #f8fafc;
        text-shadow: 0 0 24px rgba(34, 211, 238, 0.35);
    }
    .rtm-brand__sub {
        font-size: 0.72rem;
        color: var(--rtm-muted);
        letter-spacing: 0.06em;
    }
    .rtm-live {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
        font-size: 0.68rem;
        font-weight: 700;
        color: var(--rtm-green);
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }
    .rtm-live i {
        width: 7px; height: 7px; border-radius: 50%;
        background: var(--rtm-green);
        box-shadow: 0 0 8px var(--rtm-green);
        animation: rtm-blink 1.2s infinite;
    }
    @keyframes rtm-blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.35; }
    }

    .rtm-kpis {
        display: flex;
        gap: 10px;
    }
    .rtm-kpi {
        min-width: 88px;
        padding: 10px 14px;
        background: var(--rtm-panel);
        border: 1px solid var(--rtm-border);
        border-radius: 10px;
        backdrop-filter: blur(10px);
        text-align: center;
    }
    .rtm-kpi__val {
        font-family: 'Orbitron', sans-serif;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--rtm-cyan);
        font-variant-numeric: tabular-nums;
    }
    .rtm-kpi--hot .rtm-kpi__val { color: var(--rtm-red); }
    .rtm-kpi__lbl {
        font-size: 0.62rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--rtm-muted);
        margin-top: 2px;
    }

    .rtm-actions { display: flex; gap: 8px; align-items: center; }
    .rtm-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 36px;
        padding: 0 12px;
        border-radius: 8px;
        border: 1px solid var(--rtm-border);
        background: var(--rtm-panel);
        color: var(--rtm-blue);
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 600;
        backdrop-filter: blur(8px);
    }
    .rtm-btn:hover { border-color: var(--rtm-cyan); color: #fff; }

    .rtm-side {
        top: 110px;
        width: min(260px, 28vw);
        max-height: calc(100vh - 220px);
        overflow: auto;
        padding: 16px;
        background: var(--rtm-panel);
        border: 1px solid var(--rtm-border);
        border-radius: 12px;
        backdrop-filter: blur(14px);
        box-shadow: 0 20px 50px rgba(0,0,0,0.4);
    }
    .rtm-side--left { left: 22px; }
    .rtm-side--right { right: 22px; }

    .rtm-side h3 {
        margin: 0 0 14px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #94a3b8;
    }

    .rtm-rank { list-style: none; margin: 0; padding: 0; }
    .rtm-rank li {
        display: grid;
        grid-template-columns: 22px 1fr auto;
        gap: 8px;
        align-items: center;
        margin-bottom: 12px;
        cursor: pointer;
    }
    .rtm-rank__n {
        font-size: 0.7rem;
        color: var(--rtm-muted);
        font-variant-numeric: tabular-nums;
    }
    .rtm-rank__name {
        font-size: 0.82rem;
        color: #e2e8f0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .rtm-rank__pct {
        font-family: 'Orbitron', sans-serif;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--rtm-cyan);
        font-variant-numeric: tabular-nums;
    }
    .rtm-rank__bar {
        grid-column: 2 / -1;
        height: 3px;
        background: rgba(148,163,184,0.15);
        border-radius: 2px;
        overflow: hidden;
        margin-top: -6px;
    }
    .rtm-rank__bar i {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, var(--rtm-cyan), var(--rtm-magenta));
        border-radius: 2px;
    }
    .rtm-side--right .rtm-rank__bar i {
        background: linear-gradient(90deg, var(--rtm-orange), var(--rtm-red));
    }
    .rtm-side--right .rtm-rank__pct { color: var(--rtm-orange); }

    .rtm-empty {
        font-size: 0.78rem;
        color: var(--rtm-muted);
        line-height: 1.4;
    }

    .rtm-bottom {
        left: 50%;
        bottom: 18px;
        transform: translateX(-50%);
        width: min(920px, calc(100vw - 44px));
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
    }

    .rtm-filters {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px;
    }
    .rtm-chip {
        appearance: none;
        border: 1px solid var(--rtm-border);
        background: var(--rtm-panel);
        color: #94a3b8;
        border-radius: 999px;
        padding: 7px 14px;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        cursor: pointer;
        backdrop-filter: blur(10px);
        transition: all 0.2s;
    }
    .rtm-chip.is-active, .rtm-chip:hover {
        color: #fff;
        border-color: var(--rtm-cyan);
        box-shadow: 0 0 16px rgba(34, 211, 238, 0.25);
    }
    .rtm-chip strong {
        margin-left: 6px;
        color: var(--rtm-cyan);
        font-variant-numeric: tabular-nums;
    }

    .rtm-feed {
        width: 100%;
        max-height: 140px;
        overflow: auto;
        background: var(--rtm-panel);
        border: 1px solid var(--rtm-border);
        border-radius: 12px;
        backdrop-filter: blur(14px);
        padding: 8px 4px;
    }
    .rtm-feed table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.72rem;
    }
    .rtm-feed th {
        text-align: left;
        color: var(--rtm-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 4px 10px;
        border-bottom: 1px solid rgba(148,163,184,0.12);
    }
    .rtm-feed td {
        padding: 6px 10px;
        border-bottom: 1px solid rgba(148,163,184,0.06);
        color: #cbd5e1;
        white-space: nowrap;
    }
    .rtm-feed tr:hover td { background: rgba(56, 189, 248, 0.06); }
    .rtm-sev--critical, .rtm-sev--high { color: var(--rtm-red); font-weight: 700; text-transform: uppercase; }
    .rtm-sev--medium { color: var(--rtm-orange); font-weight: 700; text-transform: uppercase; }
    .rtm-sev--low { color: var(--rtm-cyan); font-weight: 700; text-transform: uppercase; }
    .rtm-atype { color: #fbbf24; }

    .rtm-tip {
        position: absolute;
        z-index: 20;
        min-width: 160px;
        max-width: 260px;
        padding: 10px 12px;
        background: rgba(2, 12, 28, 0.92);
        border: 1px solid var(--rtm-border);
        border-radius: 8px;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.15s;
        backdrop-filter: blur(8px);
    }
    .rtm-tip.is-visible { opacity: 1; }
    .rtm-tip__title { margin: 0 0 4px; font-weight: 700; font-size: 0.85rem; color: #fff; }
    .rtm-tip__meta { margin: 0; font-size: 0.72rem; color: #94a3b8; }

    @media (max-width: 960px) {
        .rtm-side { display: none; }
        .rtm-kpis { display: none; }
        .rtm-brand__title { font-size: 0.9rem; letter-spacing: 0.08em; }
    }
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
    $liveMins = (int) ($liveWindowMinutes ?? 30);
    $displayDays = (int) ($displayDays ?? 7);
    $buckets = $categoryBuckets ?? [];
@endphp

<div class="rtm" id="rtm">
    <div class="rtm-stars" aria-hidden="true"></div>

    <div class="rtm-stage">
        <svg class="rtm-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
             viewBox="0 0 {{ $mapSize['w'] }} {{ $mapSize['h'] }}"
             preserveAspectRatio="xMidYMid slice"
             aria-label="Live Threat Map">
            <defs>
                <filter id="rtm-soft" x="-40%" y="-40%" width="180%" height="180%">
                    <feGaussianBlur stdDeviation="1.2" result="b"/>
                    <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
                <radialGradient id="rtm-glow-home" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#22d3ee" stop-opacity="0.45"/>
                    <stop offset="100%" stop-color="#22d3ee" stop-opacity="0"/>
                </radialGradient>
            </defs>

            <image class="rtm-world"
                   href="{{ asset('images/world-map.svg') }}"
                   xlink:href="{{ asset('images/world-map.svg') }}"
                   x="0" y="0"
                   width="{{ $mapSize['w'] }}" height="{{ $mapSize['h'] }}"
                   preserveAspectRatio="xMidYMid slice"/>

            @foreach($arcs as $i => $arc)
                @php
                    $sev = in_array($arc['severity'] ?? '', ['critical', 'high', 'medium', 'low'], true)
                        ? $arc['severity'] : 'medium';
                    $cat = $arc['category'] ?? 'other';
                @endphp
                <g class="rtm-flow rtm-flow--{{ $sev }}"
                   data-rtm-flow="1"
                   data-code="{{ $arc['code'] }}"
                   data-country="{{ $arc['country'] }}"
                   data-ip="{{ $arc['ip'] }}"
                   data-severity="{{ $sev }}"
                   data-category="{{ $cat }}">
                    <path class="rtm-arc" id="rtm-arc-{{ $i }}" d="{{ $arc['path'] }}" filter="url(#rtm-soft)"/>
                    <circle class="rtm-pulse" r="3">
                        <animateMotion dur="{{ 2.2 + ($i % 5) * 0.4 }}s" repeatCount="indefinite" begin="{{ ($i % 7) * 0.25 }}s">
                            <mpath xlink:href="#rtm-arc-{{ $i }}"/>
                        </animateMotion>
                    </circle>
                </g>
            @endforeach

            @foreach($originMarkers as $om)
                <g class="rtm-origin"
                   transform="translate({{ round($om['sx'], 2) }}, {{ round($om['sy'], 2) }})"
                   data-rtm-origin="1"
                   data-code="{{ $om['code'] }}"
                   data-country="{{ $om['name'] }}"
                   data-ip-count="{{ (int) $om['ip_count'] }}">
                    <circle class="rtm-origin-ring" r="10" cx="0" cy="0"/>
                    <circle class="rtm-origin-core" r="4.5" cx="0" cy="0"/>
                    <text class="rtm-label" x="0" y="-14" text-anchor="middle">{{ $flagEmoji($om['code']) }} {{ \Illuminate\Support\Str::limit($om['name'], 16) }}</text>
                </g>
            @endforeach

            <circle cx="{{ $homeXY['x'] }}" cy="{{ $homeXY['y'] }}" r="32" fill="url(#rtm-glow-home)"/>
            <circle class="rtm-home-ring" cx="{{ $homeXY['x'] }}" cy="{{ $homeXY['y'] }}" r="10"/>
            <circle class="rtm-home" cx="{{ $homeXY['x'] }}" cy="{{ $homeXY['y'] }}" r="6"/>
            <text class="rtm-label rtm-label--home" x="{{ $homeXY['x'] }}" y="{{ $homeXY['y'] + 24 }}" text-anchor="middle">{{ \Illuminate\Support\Str::limit($home['label'] ?? 'SOC', 20) }}</text>
        </svg>

        <div id="rtm-tip" class="rtm-tip" hidden>
            <p class="rtm-tip__title" id="rtm-tip-title"></p>
            <p class="rtm-tip__meta" id="rtm-tip-meta"></p>
        </div>
    </div>

    <div class="rtm-hud rtm-top">
        <div class="rtm-brand">
            <h1 class="rtm-brand__title">Live Threat Map</h1>
            <div class="rtm-brand__sub">KORASHIELD · cible {{ $home['label'] ?? 'SOC' }}</div>
            <div class="rtm-live"><i></i> Carte · {{ $displayDays }} j · maj. auto 60s · <span id="rtm-clock">{{ now()->format('H:i:s') }}</span></div>
        </div>
        <div class="rtm-kpis">
            <div class="rtm-kpi">
                <div class="rtm-kpi__val">{{ number_format($eventsToday) }}</div>
                <div class="rtm-kpi__lbl">Events</div>
            </div>
            <div class="rtm-kpi rtm-kpi--hot">
                <div class="rtm-kpi__val">{{ number_format($attacksToday) }}</div>
                <div class="rtm-kpi__lbl">Attacks</div>
            </div>
            <div class="rtm-kpi">
                <div class="rtm-kpi__val">{{ number_format($threatToday['high'] ?? 0) }}</div>
                <div class="rtm-kpi__lbl">High / Crit</div>
            </div>
        </div>
        <div class="rtm-actions">
            <a class="rtm-btn" href="{{ route('monitor.monitors') }}">Monitors</a>
            <a class="rtm-btn" href="{{ route('dashboard') }}">Dashboard</a>
        </div>
    </div>

    <aside class="rtm-hud rtm-side rtm-side--left">
        <h3>Top Attackers</h3>
        @if(count($sourceCountries) === 0)
            <p class="rtm-empty">Aucune source géolocalisée dans la fenêtre live.</p>
        @else
            <ol class="rtm-rank">
                @foreach($sourceCountries as $src)
                    <li data-rtm-code="{{ $src['code'] }}">
                        <span class="rtm-rank__n">{{ $loop->iteration }}</span>
                        <span class="rtm-rank__name">{{ $flagEmoji($src['code']) }} {{ $src['name'] }}</span>
                        <span class="rtm-rank__pct">{{ $src['share'] ?? $src['pct'] }}%</span>
                        <span class="rtm-rank__bar"><i style="width:{{ $src['pct'] }}%"></i></span>
                    </li>
                @endforeach
            </ol>
        @endif
    </aside>

    <aside class="rtm-hud rtm-side rtm-side--right">
        <h3>Top Attacked</h3>
        @if($topTargets->isEmpty())
            <p class="rtm-empty">Aucune cible dans la fenêtre live.</p>
        @else
            <ol class="rtm-rank">
                @foreach($topTargets as $t)
                    <li>
                        <span class="rtm-rank__n">{{ $loop->iteration }}</span>
                        <span class="rtm-rank__name" title="{{ $t->target_ip }}">{{ \Illuminate\Support\Str::limit($t->affected_asset ?: $t->target_ip, 22) }}</span>
                        <span class="rtm-rank__pct">{{ $t->share ?? 0 }}%</span>
                        <span class="rtm-rank__bar"><i style="width:{{ min(100, max(8, (int)($t->share ?? 0))) }}%"></i></span>
                    </li>
                @endforeach
            </ol>
        @endif
    </aside>

    <div class="rtm-hud rtm-bottom">
        <div class="rtm-filters" role="tablist" aria-label="Filtres d’attaque">
            <button type="button" class="rtm-chip is-active" data-rtm-filter="all">All <strong>{{ count($arcs) }}</strong></button>
            @foreach($buckets as $key => $bucket)
                <button type="button" class="rtm-chip" data-rtm-filter="{{ $key }}">
                    {{ $bucket['label'] }} <strong>{{ (int) $bucket['count'] }}</strong>
                </button>
            @endforeach
        </div>

        <div class="rtm-feed">
            @if($recentRows->isEmpty())
                <p class="rtm-empty" style="padding:12px 16px;margin:0;">Aucun événement sur {{ $displayDays }} jours (avec IP source).</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Origine</th>
                            <th>IP</th>
                            <th>Cible</th>
                            <th>Type</th>
                            <th>Gravité</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentRows->take(12) as $row)
                            <tr>
                                <td title="{{ $row['time']?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '' }}">
                                    {{ $row['time']?->format('H:i:s') ?? '—' }}
                                    @if($row['time'])
                                        <span style="color:#64748b;font-size:0.65rem;display:block;">{{ $row['time']->diffForHumans() }}</span>
                                    @endif
                                </td>
                                <td>{{ $flagEmoji($row['geo_code']) }} {{ $row['geo_label'] }}</td>
                                <td>{{ $row['source_ip'] }}</td>
                                <td>{{ $row['target_ip'] }}</td>
                                <td class="rtm-atype">{{ $row['attack_type'] }}</td>
                                <td class="rtm-sev--{{ $row['severity'] }}">{{ $row['severity'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var clock = document.getElementById('rtm-clock');
    function tick() {
        if (!clock) return;
        var d = new Date();
        clock.textContent = [d.getHours(), d.getMinutes(), d.getSeconds()]
            .map(function (n) { return String(n).padStart(2, '0'); }).join(':');
    }
    tick();
    setInterval(tick, 1000);

    // Rafraîchir la carte toutes les 60s pour suivre les nouvelles alertes
    setTimeout(function () { window.location.reload(); }, 60 * 1000);
})();

(function () {
    var stage = document.querySelector('.rtm-stage');
    var tip = document.getElementById('rtm-tip');
    var tipTitle = document.getElementById('rtm-tip-title');
    var tipMeta = document.getElementById('rtm-tip-meta');
    if (!stage || !tip) return;

    var hideTimer = null;
    var highlightCode = null;
    var activeFilter = 'all';

    function place(e) {
        var r = stage.getBoundingClientRect();
        var x = e.clientX - r.left + 14;
        var y = e.clientY - r.top + 14;
        tip.style.left = x + 'px';
        tip.style.top = y + 'px';
    }
    function show() {
        tip.removeAttribute('hidden');
        requestAnimationFrame(function () { tip.classList.add('is-visible'); });
    }
    function hide() {
        tip.classList.remove('is-visible');
        setTimeout(function () { tip.setAttribute('hidden', ''); }, 150);
    }

    stage.querySelectorAll('[data-rtm-flow="1"]').forEach(function (g) {
        g.addEventListener('mouseenter', function (e) {
            clearTimeout(hideTimer);
            tipTitle.textContent = g.getAttribute('data-ip') || '—';
            tipMeta.textContent = (g.getAttribute('data-country') || '') + ' · ' + (g.getAttribute('data-severity') || '');
            show(); place(e);
        });
        g.addEventListener('mousemove', place);
        g.addEventListener('mouseleave', function () { hideTimer = setTimeout(hide, 80); });
    });

    stage.querySelectorAll('[data-rtm-origin="1"]').forEach(function (g) {
        g.addEventListener('mouseenter', function (e) {
            clearTimeout(hideTimer);
            tipTitle.textContent = g.getAttribute('data-country') || '';
            tipMeta.textContent = (g.getAttribute('data-ip-count') || '0') + ' IP source(s)';
            show(); place(e);
        });
        g.addEventListener('mousemove', place);
        g.addEventListener('mouseleave', function () { hideTimer = setTimeout(hide, 80); });
    });

    function applyHighlight() {
        stage.querySelectorAll('[data-rtm-flow="1"], [data-rtm-origin="1"]').forEach(function (el) {
            if (!highlightCode) {
                el.classList.remove('is-highlighted', 'is-dimmed');
                return;
            }
            var match = el.getAttribute('data-code') === highlightCode;
            el.classList.toggle('is-highlighted', match);
            el.classList.toggle('is-dimmed', !match);
        });
    }

    document.querySelectorAll('[data-rtm-code]').forEach(function (row) {
        row.addEventListener('mouseenter', function () {
            highlightCode = row.getAttribute('data-rtm-code');
            applyHighlight();
        });
        row.addEventListener('mouseleave', function () {
            highlightCode = null;
            applyHighlight();
        });
    });

    function applyFilter() {
        stage.querySelectorAll('[data-rtm-flow="1"]').forEach(function (el) {
            var cat = el.getAttribute('data-category') || '';
            var show = activeFilter === 'all' || cat === activeFilter;
            el.style.display = show ? '' : 'none';
        });
    }

    document.querySelectorAll('[data-rtm-filter]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            document.querySelectorAll('[data-rtm-filter]').forEach(function (c) { c.classList.remove('is-active'); });
            chip.classList.add('is-active');
            activeFilter = chip.getAttribute('data-rtm-filter') || 'all';
            applyFilter();
        });
    });
})();
</script>
@endpush
