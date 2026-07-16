@php
    $home = $cyberMap['home'];
    $mapSize = $cyberMap['mapSize'];
    $homeXY = $cyberMap['homeXY'];
    $arcs = $cyberMap['arcs'];
    $originMarkers = $cyberMap['originMarkers'];
    $sourceCountries = $cyberMap['sourceCountries'];
    $eventsToday = $cyberMap['eventsToday'];
    $attacksToday = $cyberMap['attacksToday'];
    $threatToday = $cyberMap['threatToday'];
    $liveWindowMinutes = (int) ($cyberMap['liveWindowMinutes'] ?? 30);
    $flagEmoji = function (?string $code): string {
        $code = strtoupper(substr((string) $code, 0, 2));
        if (strlen($code) !== 2 || ! ctype_alpha($code)) {
            return '🌐';
        }

        return mb_chr(0x1F1E6 - 65 + ord($code[0]), 'UTF-8').mb_chr(0x1F1E6 - 65 + ord($code[1]), 'UTF-8');
    };
@endphp

<div class="cmap" id="soc-cybermap">
    <div class="cmap-top">
        <div>
            <h2 class="cmap-title">Carte des cybermenaces</h2>
            <p class="cmap-subtitle">Flux live · fenêtre {{ $liveWindowMinutes }} min · cible {{ $home['label'] ?? 'Home' }}</p>
        </div>
        <div class="cmap-kpis">
            <div class="cmap-kpi">
                <span class="cmap-kpi__val">{{ number_format($eventsToday) }}</span>
                <span class="cmap-kpi__lbl">Événements</span>
            </div>
            <div class="cmap-kpi cmap-kpi--hot">
                <span class="cmap-kpi__val">{{ number_format($attacksToday) }}</span>
                <span class="cmap-kpi__lbl">Volume attaques</span>
            </div>
            <div class="cmap-kpi">
                <span class="cmap-kpi__val">{{ number_format($threatToday['high'] ?? 0) }}</span>
                <span class="cmap-kpi__lbl">Haute / critique</span>
            </div>
            <a href="{{ route('monitor.attack-map') }}" target="_blank" rel="noopener noreferrer" class="soc-header__btn">Plein écran</a>
        </div>
    </div>

    <div class="cmap-body">
        <aside class="cmap-rank">
            <h3># Pays les plus attaquants</h3>
            @if(count($sourceCountries) === 0)
                <p class="cmap-empty">Aucune IP source publique géolocalisée ({{ $liveWindowMinutes }} min).</p>
            @else
                <ol class="cmap-rank-list">
                    @foreach($sourceCountries as $src)
                        <li data-cmap-code="{{ $src['code'] }}">
                            <span class="cmap-rank-n">{{ $loop->iteration }}</span>
                            <span class="cmap-rank-flag">{{ $flagEmoji($src['code']) }}</span>
                            <span class="cmap-rank-name">{{ $src['name'] }}</span>
                            <strong>{{ $src['count'] }}</strong>
                            <span class="cmap-rank-bar"><i style="width:{{ $src['pct'] }}%"></i></span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </aside>

        <div class="cmap-stage">
            <svg class="cmap-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                 viewBox="0 0 {{ $mapSize['w'] }} {{ $mapSize['h'] }}"
                 preserveAspectRatio="xMidYMid meet"
                 aria-label="Carte mondiale des cybermenaces">
                <defs>
                    <radialGradient id="cmap-glow-home" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.55"/>
                        <stop offset="100%" stop-color="#38bdf8" stop-opacity="0"/>
                    </radialGradient>
                    <filter id="cmap-soft" x="-40%" y="-40%" width="180%" height="180%">
                        <feGaussianBlur stdDeviation="1.4" result="b"/>
                        <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                    </filter>
                </defs>

                <image class="cmap-world"
                       href="{{ asset('images/world-map.svg') }}"
                       xlink:href="{{ asset('images/world-map.svg') }}"
                       x="0" y="0"
                       width="{{ $mapSize['w'] }}" height="{{ $mapSize['h'] }}"
                       preserveAspectRatio="xMidYMid slice"/>

                @foreach($arcs as $i => $arc)
                    @php
                        $sev = in_array($arc['severity'] ?? '', ['critical', 'high', 'medium', 'low'], true)
                            ? $arc['severity'] : 'medium';
                    @endphp
                    <g class="cmap-flow cmap-flow--{{ $sev }}" data-code="{{ $arc['code'] }}" data-ip="{{ $arc['ip'] }}" data-country="{{ $arc['country'] }}">
                        <path class="cmap-arc" id="cmap-arc-{{ $i }}" d="{{ $arc['path'] }}" filter="url(#cmap-soft)"/>
                        <circle class="cmap-pulse" r="3.5">
                            <animateMotion dur="{{ 2.4 + ($i % 5) * 0.35 }}s" repeatCount="indefinite" begin="{{ ($i % 7) * 0.2 }}s">
                                <mpath xlink:href="#cmap-arc-{{ $i }}"/>
                            </animateMotion>
                        </circle>
                    </g>
                @endforeach

                @foreach($originMarkers as $om)
                    <g class="cmap-origin" transform="translate({{ round($om['sx'], 2) }}, {{ round($om['sy'], 2) }})" data-code="{{ $om['code'] }}">
                        <circle r="11" class="cmap-origin-ring" fill="none" stroke="#f97316" stroke-width="0.8"/>
                        <circle r="4.5" fill="#fb923c" stroke="#fff7ed" stroke-width="1"/>
                        <text class="cmap-origin-lbl" x="0" y="-13" text-anchor="middle">{{ $flagEmoji($om['code']) }} {{ \Illuminate\Support\Str::limit($om['name'], 16) }}</text>
                    </g>
                @endforeach

                <circle cx="{{ $homeXY['x'] }}" cy="{{ $homeXY['y'] }}" r="28" fill="url(#cmap-glow-home)"/>
                <circle class="cmap-home-pulse" cx="{{ $homeXY['x'] }}" cy="{{ $homeXY['y'] }}" r="10" fill="none" stroke="#38bdf8" stroke-width="1.2"/>
                <circle cx="{{ $homeXY['x'] }}" cy="{{ $homeXY['y'] }}" r="6" fill="#0ea5e9" stroke="#e0f2fe" stroke-width="1.5"/>
                <text class="cmap-home-lbl" x="{{ $homeXY['x'] }}" y="{{ $homeXY['y'] + 22 }}" text-anchor="middle">{{ \Illuminate\Support\Str::limit($home['label'] ?? 'SOC', 20) }}</text>
            </svg>

            @if(count($arcs) === 0)
                <div class="cmap-empty-overlay">
                    <p>En attente de flux géolocalisés</p>
                    <span>Les arcs apparaissent dès qu’une alerte a une IP source publique.</span>
                </div>
            @endif

            <div class="cmap-legend">
                <span><i class="cmap-dot cmap-dot--hi"></i> Critique / High</span>
                <span><i class="cmap-dot cmap-dot--med"></i> Medium</span>
                <span><i class="cmap-dot cmap-dot--lo"></i> Low</span>
                <span>{{ count($arcs) }} flux actifs</span>
            </div>
        </div>
    </div>
</div>

{{-- Recharge la page Monitors pour rafraîchir la fenêtre live --}}
<script>
(function () {
    var minutes = {{ $liveWindowMinutes }};
    if (minutes < 1) return;
    setTimeout(function () {
        window.location.reload();
    }, minutes * 60 * 1000);
})();
</script>
