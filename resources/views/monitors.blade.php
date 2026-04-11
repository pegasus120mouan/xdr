@extends('layouts.monitor-full')

@section('title', 'Security O&M Monitor - Wara XDR')

@push('styles')
<style>
    .soc-page {
        --soc-bg: #000b1e;
        --soc-cyan: #00d4ff;
        --soc-cyan-dim: rgba(0, 212, 255, 0.35);
        --soc-green: #22c55e;
        --soc-panel: rgba(15, 30, 55, 0.65);
        padding: 0 24px 32px;
        background: linear-gradient(180deg, #050d1a 0%, #0a1528 40%, #060f1c 100%);
        min-height: 100vh;
        box-sizing: border-box;
    }

    .soc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        padding: 20px 0 16px;
        border-bottom: 1px solid rgba(0, 212, 255, 0.15);
        margin-bottom: 20px;
    }

    .soc-header__badge {
        font-size: 0.75rem;
        color: #94a3b8;
        padding: 6px 12px;
        border: 1px solid var(--soc-cyan-dim);
        border-radius: 4px;
        background: var(--soc-panel);
    }

    .soc-header__badge strong {
        color: var(--soc-cyan);
    }

    .soc-header__title {
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        background: linear-gradient(90deg, #fff 0%, var(--soc-cyan) 50%, #7dd3fc 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        text-shadow: 0 0 40px rgba(0, 212, 255, 0.3);
    }

    .soc-header__meta {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 0.8rem;
        color: #94a3b8;
    }

    .soc-header__btn {
        padding: 8px 16px;
        background: linear-gradient(180deg, rgba(0, 212, 255, 0.25), rgba(0, 100, 180, 0.4));
        border: 1px solid var(--soc-cyan);
        color: #e0f7ff;
        border-radius: 4px;
        font-size: 0.8rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .soc-grid {
        display: grid;
        grid-template-columns: minmax(240px, 1fr) minmax(320px, 1.4fr) minmax(260px, 1fr);
        gap: 20px;
        align-items: start;
    }

    @media (max-width: 1200px) {
        .soc-grid {
            grid-template-columns: 1fr;
        }
    }

    .soc-panel {
        background: var(--soc-panel);
        border: 1px solid rgba(0, 212, 255, 0.2);
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 0 24px rgba(0, 40, 80, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }

    .soc-panel h3 {
        font-size: 0.85rem;
        color: var(--soc-cyan);
        margin-bottom: 12px;
        font-weight: 600;
    }

    .soc-secure-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .soc-shield {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: radial-gradient(circle at 30% 30%, rgba(34, 197, 94, 0.5), rgba(16, 80, 40, 0.9));
        border: 2px solid var(--soc-green);
        box-shadow: 0 0 20px rgba(34, 197, 94, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        animation: shield-pulse 3s ease-in-out infinite;
    }

    @keyframes shield-pulse {
        0%, 100% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.45); }
        50% { box-shadow: 0 0 32px rgba(34, 197, 94, 0.75); }
    }

    .soc-stat-list {
        font-size: 0.78rem;
        color: #cbd5e1;
        line-height: 1.7;
    }

    .soc-stat-list strong { color: #fff; }

    .soc-threat-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin-top: 12px;
    }

    .soc-threat-cell {
        padding: 8px 10px;
        background: rgba(0, 0, 0, 0.25);
        border-radius: 4px;
        font-size: 0.72rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .soc-threat-cell span:last-child {
        color: var(--soc-green);
        font-weight: 600;
    }

    .soc-funnel {
        margin-top: 16px;
        text-align: center;
        font-size: 0.7rem;
        color: #64748b;
    }

    .soc-funnel-steps {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin: 10px 0;
    }

    .soc-funnel-steps span {
        padding: 6px;
        background: rgba(0, 212, 255, 0.08);
        border: 1px solid rgba(0, 212, 255, 0.15);
        border-radius: 4px;
        color: #94a3b8;
    }

    /* Center hub — animated */
    .soc-hub-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: 420px;
        position: relative;
    }

    .soc-hub-wrap--active {
        min-height: 480px;
    }

    .soc-hub-visual {
        position: relative;
        width: 100%;
        max-width: 440px;
        margin: 0 auto;
    }

    /* Machines en orbite circulaire autour du hub */
    .soc-orbit {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 0;
        height: 0;
        z-index: 4;
        pointer-events: none;
        animation: soc-orbit-spin var(--orbit-dur, 48s) linear infinite;
    }

    @keyframes soc-orbit-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .soc-orbit-slot {
        position: absolute;
        left: 0;
        top: 0;
        width: 0;
        height: 0;
        transform: rotate(var(--a, 0deg)) translateY(calc(-1 * var(--orbit-r, 128px)));
        transform-origin: center center;
    }

    .soc-orbit-counter {
        animation: soc-orbit-counter var(--orbit-dur, 48s) linear infinite;
        transform: translate(-50%, -50%);
        pointer-events: auto;
    }

    @keyframes soc-orbit-counter {
        from { transform: translate(-50%, -50%) rotate(0deg); }
        to { transform: translate(-50%, -50%) rotate(-360deg); }
    }

    .soc-machine-node {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        filter: drop-shadow(0 0 10px rgba(0, 212, 255, 0.2));
    }

    .soc-machine-node__rack {
        width: 38px;
        height: 50px;
        background: linear-gradient(180deg, #1a5080 0%, #0a1f38 55%, #071426 100%);
        border: 1px solid rgba(0, 212, 255, 0.55);
        border-radius: 5px;
        box-shadow:
            0 0 0 3px rgba(220, 38, 38, 0.5),
            0 0 18px rgba(220, 38, 38, 0.25),
            0 10px 28px rgba(0, 40, 90, 0.45);
        position: relative;
        overflow: hidden;
        animation: rack-glow 3s ease-in-out infinite;
    }

    @keyframes rack-glow {
        0%, 100% { box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.45), 0 0 14px rgba(220, 38, 38, 0.2), 0 10px 28px rgba(0, 40, 90, 0.45); }
        50% { box-shadow: 0 0 0 4px rgba(248, 113, 113, 0.65), 0 0 22px rgba(220, 38, 38, 0.35), 0 10px 28px rgba(0, 40, 90, 0.45); }
    }

    .soc-machine-node__rack::before {
        content: '';
        position: absolute;
        left: 5px;
        right: 5px;
        top: 10px;
        bottom: 12px;
        background: repeating-linear-gradient(
            180deg,
            rgba(15, 40, 70, 0.9),
            rgba(15, 40, 70, 0.9) 5px,
            rgba(239, 68, 68, 0.75) 5px,
            rgba(239, 68, 68, 0.75) 7px
        );
        border-radius: 2px;
    }

    .soc-machine-node__led {
        position: absolute;
        bottom: 5px;
        left: 50%;
        transform: translateX(-50%);
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 8px #22c55e;
        z-index: 1;
    }

    .soc-machine-node__label {
        font-size: 0.68rem;
        font-weight: 600;
        color: #7dd3fc;
        font-variant-numeric: tabular-nums;
        max-width: 96px;
        text-align: center;
        word-break: break-all;
        line-height: 1.2;
    }

    .soc-machine-node__stat {
        font-size: 0.58rem;
        text-transform: capitalize;
    }

    .soc-machine-node__stat--online { color: #22c55e; }
    .soc-machine-node__stat--offline { color: #64748b; }
    .soc-machine-node__stat--alerting { color: #f97316; }
    .soc-machine-node__stat--unknown { color: #94a3b8; }

    .soc-hub {
        position: relative;
        width: min(100%, 380px);
        height: 340px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .soc-rings {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .soc-ring {
        position: absolute;
        border-radius: 50%;
        border: 1px solid var(--soc-cyan-dim);
        box-shadow: 0 0 12px rgba(0, 212, 255, 0.15), inset 0 0 20px rgba(0, 212, 255, 0.05);
    }

    .soc-ring--1 {
        width: 280px;
        height: 280px;
        animation: ring-spin 24s linear infinite, ring-glow 4s ease-in-out infinite;
    }

    .soc-ring--2 {
        width: 220px;
        height: 220px;
        animation: ring-spin-rev 18s linear infinite, ring-glow 5s ease-in-out infinite 0.5s;
        border-color: rgba(0, 180, 255, 0.45);
    }

    .soc-ring--3 {
        width: 160px;
        height: 160px;
        animation: ring-spin 12s linear infinite, ring-glow 3.5s ease-in-out infinite 1s;
    }

    @keyframes ring-spin {
        from { transform: rotate(0deg) scaleX(1); }
        to { transform: rotate(360deg) scaleX(1); }
    }

    @keyframes ring-spin-rev {
        from { transform: rotate(360deg); }
        to { transform: rotate(0deg); }
    }

    @keyframes ring-glow {
        0%, 100% { opacity: 0.65; filter: drop-shadow(0 0 4px rgba(0, 212, 255, 0.3)); }
        50% { opacity: 1; filter: drop-shadow(0 0 14px rgba(0, 212, 255, 0.55)); }
    }

    .soc-core {
        position: relative;
        z-index: 2;
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: radial-gradient(circle at 35% 30%, #1e3a5f, #0a1628);
        border: 2px solid var(--soc-cyan);
        box-shadow:
            0 0 30px rgba(0, 212, 255, 0.5),
            0 0 60px rgba(0, 120, 200, 0.25),
            inset 0 0 20px rgba(0, 212, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 800;
        color: var(--soc-cyan);
        animation: core-breathe 3.5s ease-in-out infinite;
    }

    @keyframes core-breathe {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.06); }
    }

    .soc-float-label {
        position: absolute;
        z-index: 6;
        font-size: 0.68rem;
        color: #7dd3fc;
        padding: 4px 10px;
        background: rgba(0, 20, 45, 0.85);
        border: 1px solid rgba(0, 212, 255, 0.35);
        border-radius: 4px;
        white-space: nowrap;
        box-shadow: 0 0 12px rgba(0, 212, 255, 0.2);
    }

    .soc-float-label--1 { top: 8%; left: 50%; transform: translateX(-50%); animation: float-a 5s ease-in-out infinite; }
    .soc-float-label--2 { top: 28%; right: 0; animation: float-b 4.5s ease-in-out infinite 0.3s; }
    .soc-float-label--3 { bottom: 28%; left: 0; animation: float-c 5.5s ease-in-out infinite 0.6s; }
    .soc-float-label--4 { bottom: 8%; left: 50%; transform: translateX(-50%); animation: float-d 4.8s ease-in-out infinite 0.2s; }

    @keyframes float-a {
        0%, 100% { transform: translateX(-50%) translateY(0); }
        50% { transform: translateX(-50%) translateY(-8px); }
    }

    @keyframes float-b {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-4px, -10px); }
    }

    @keyframes float-c {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(6px, 8px); }
    }

    @keyframes float-d {
        0%, 100% { transform: translateX(-50%) translateY(0); }
        50% { transform: translateX(-50%) translateY(10px); }
    }

    .soc-hub-caption {
        text-align: center;
        margin-top: 8px;
    }

    .soc-hub-caption h2 {
        font-size: 1rem;
        color: var(--soc-cyan);
        margin-bottom: 6px;
    }

    .soc-hub-caption p {
        font-size: 0.75rem;
        color: #64748b;
        max-width: 280px;
        margin: 0 auto 14px;
        line-height: 1.5;
    }

    .soc-hub-caption .soc-header__btn {
        margin: 0 auto;
    }

    .soc-flow {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 12px;
    }

    .soc-flow-step {
        padding: 10px 12px;
        background: rgba(0, 30, 60, 0.4);
        border-left: 3px solid var(--soc-cyan);
        border-radius: 0 4px 4px 0;
        font-size: 0.72rem;
        color: #cbd5e1;
    }

    .soc-flow-step strong {
        display: block;
        color: #e2e8f0;
        margin-bottom: 2px;
        font-size: 0.78rem;
    }

    .soc-contain-visual {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        margin: 12px 0;
        flex-wrap: wrap;
    }

    .soc-orbit {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 2px dashed rgba(0, 212, 255, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: orbit-spin 20s linear infinite;
    }

    @keyframes orbit-spin {
        to { transform: rotate(360deg); }
    }

    .soc-orbit-inner {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: rgba(0, 212, 255, 0.12);
        border: 1px solid var(--soc-cyan-dim);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        animation: orbit-spin 20s linear infinite reverse;
    }

    .soc-mini-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        font-size: 0.72rem;
        margin-top: 10px;
    }

    .soc-mini-stats div {
        padding: 8px;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 4px;
        text-align: center;
        color: #94a3b8;
    }

    .soc-mini-stats strong {
        display: block;
        color: var(--soc-cyan);
        font-size: 1rem;
    }

    .soc-table {
        width: 100%;
        font-size: 0.68rem;
        border-collapse: collapse;
        margin-top: 10px;
        color: #64748b;
    }

    .soc-table th {
        text-align: left;
        padding: 6px 4px;
        border-bottom: 1px solid rgba(0, 212, 255, 0.15);
        color: #94a3b8;
    }

    .soc-table td {
        padding: 8px 4px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .soc-sev-bar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
        font-size: 0.65rem;
    }

    .soc-sev-bar span { color: #64748b; }
    .soc-sev-bar strong { color: #cbd5e1; }

    .soc-header__actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .soc-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        padding: 0;
        border: 1px solid rgba(0, 212, 255, 0.35);
        background: rgba(0, 30, 55, 0.5);
        color: var(--soc-cyan);
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        font-size: 1rem;
        line-height: 1;
        transition: background 0.2s, border-color 0.2s;
    }

    .soc-icon-btn:hover {
        background: rgba(0, 212, 255, 0.12);
        border-color: var(--soc-cyan);
    }
</style>
@endpush

@section('content')
<div class="soc-page">
    <header class="soc-header">
        <div class="soc-header__badge"><strong>624</strong> jours protégés par XDR</div>
        <h1 class="soc-header__title">Security O&amp;M Monitor</h1>
        <div class="soc-header__meta">
            <span id="soc-clock" data-locale="fr">{{ now()->format('Y.m.d') }} ({{ now()->locale('fr')->translatedFormat('l') }}) {{ now()->format('H:i:s') }}</span>
            <div class="soc-header__actions">
                <a href="{{ route('dashboard') }}" class="soc-icon-btn" title="Retour à l’application">🖥</a>
                <button type="button" class="soc-icon-btn" id="soc-fullscreen" title="Plein écran">⛶</button>
                <a href="#" class="soc-header__btn">Threat Simulation</a>
            </div>
        </div>
    </header>

    <div class="soc-grid">
        <div class="soc-col">
            <div class="soc-panel">
                <h3>Indicateurs clés</h3>
                <div class="soc-secure-row">
                    <div class="soc-shield" aria-hidden="true">🛡</div>
                    <div>
                        <div style="font-weight:700;color:var(--soc-green);font-size:0.9rem;">Secure</div>
                        <div class="soc-stat-list">
                            <div><strong>0/0</strong> actifs critiques / à risque</div>
                            <div><strong>10</strong> attaquants externes bloqués</div>
                            <div><strong>0</strong> incidents en attente</div>
                            <div><strong>0</strong> incidents résolus</div>
                        </div>
                    </div>
                </div>
                <h3>Menaces majeures</h3>
                <div class="soc-threat-grid">
                    <div class="soc-threat-cell"><span>Ransomware</span><span>None</span></div>
                    <div class="soc-threat-cell"><span>Crypto-mining</span><span>None</span></div>
                    <div class="soc-threat-cell"><span>WebShell</span><span>None</span></div>
                    <div class="soc-threat-cell"><span>Exploit</span><span>None</span></div>
                    <div class="soc-threat-cell"><span>Hacktool</span><span>None</span></div>
                    <div class="soc-threat-cell"><span>Virus</span><span>None</span></div>
                </div>
            </div>
            <div class="soc-panel" style="margin-top:16px;">
                <h3>Vue organisationnelle</h3>
                <div class="soc-funnel">
                    <div class="soc-funnel-steps">
                        <span>Alertes de sécurité massives</span>
                        <span>Moteur d’analyse multivariée</span>
                        <span>Réduction alertes homme-machine</span>
                        <span>Incidents résolus / total (0/0)</span>
                    </div>
                    <table class="soc-table">
                        <thead>
                            <tr>
                                <th>Incident</th>
                                <th>Tag</th>
                                <th>Actif</th>
                                <th>Heure</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" style="text-align:center;padding:16px;">Aucune donnée</td></tr>
                        </tbody>
                    </table>
                    <div class="soc-sev-bar">
                        <span>Critical <strong>0</strong></span>
                        <span>High <strong>0</strong></span>
                        <span>Medium <strong>0</strong></span>
                        <span>Low <strong>0</strong></span>
                        <span>Info <strong>0</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="soc-col">
            <div class="soc-panel soc-hub-wrap {{ $monitoredCount > 0 ? 'soc-hub-wrap--active' : '' }}">
                <div class="soc-hub-visual">
                    <div class="soc-hub" aria-label="Hub de surveillance des actifs" @if($monitoredAssets->isNotEmpty()) style="--orbit-dur: 48s; --orbit-r: {{ $monitoredCount > 6 ? '118px' : '128px' }};" @endif>
                        <div class="soc-rings">
                            <div class="soc-ring soc-ring--1"></div>
                            <div class="soc-ring soc-ring--2"></div>
                            <div class="soc-ring soc-ring--3"></div>
                        </div>
                        <span class="soc-float-label soc-float-label--1">Attack Steps</span>
                        <span class="soc-float-label soc-float-label--2">Contextual Detection</span>
                        <span class="soc-float-label soc-float-label--3">IOA Monitoring</span>
                        <span class="soc-float-label soc-float-label--4">Cloud Threat Intelligence</span>
                        <div class="soc-core" title="Asset monitoring">X</div>
                        @if($monitoredAssets->isNotEmpty())
                            @php
                                $step = $monitoredCount > 0 ? 360 / $monitoredCount : 0;
                            @endphp
                            <div class="soc-orbit" role="presentation">
                                @foreach($monitoredAssets as $asset)
                                    @php
                                        $displayLabel = $asset->ip_address ?: $asset->hostname;
                                        $angle = $loop->index * $step;
                                    @endphp
                                    <div class="soc-orbit-slot" style="--a: {{ $angle }}deg;">
                                        <div class="soc-orbit-counter">
                                            <div class="soc-machine-node">
                                                <div class="soc-machine-node__rack" title="{{ $asset->hostname }}">
                                                    <span class="soc-machine-node__led"></span>
                                                </div>
                                                <span class="soc-machine-node__label">{{ $displayLabel }}</span>
                                                <span class="soc-machine-node__stat soc-machine-node__stat--{{ $asset->status }}">{{ $asset->status }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="soc-hub-caption">
                    <h2>Asset Monitoring</h2>
                    @if($monitoredCount > 0)
                        <p style="max-width: 320px;"><strong style="color:#7dd3fc;">{{ $monitoredCount }}</strong> machine(s) en orbite autour du hub — surveillance active.</p>
                        <a href="{{ route('monitor.configure') }}" class="soc-header__btn">Modifier la sélection</a>
                    @else
                        <p>La protection des actifs critiques n’est pas activée. Activez-la pour renforcer la surveillance.</p>
                        <a href="{{ route('monitor.configure') }}" class="soc-header__btn">Configurer</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="soc-col">
            <div class="soc-panel">
                <h3>Protection des actifs critiques</h3>
                <div class="soc-flow">
                    <div class="soc-flow-step">
                        <strong>Techniques (ATT&amp;CK)</strong>
                        Détection des menaces
                    </div>
                    <div class="soc-flow-step">
                        <strong>Mots de passe faibles</strong>
                        Détection des vulnérabilités
                    </div>
                    <div class="soc-flow-step">
                        <strong>Risques potentiels</strong>
                        Détection des risques
                    </div>
                    <div class="soc-flow-step">
                        <strong>Anomalies (démarrage)</strong>
                        Détection d’anomalies
                    </div>
                </div>
                <p style="font-size:0.72rem;color:#64748b;line-height:1.5;margin-bottom:12px;">
                    Une gestion rigoureuse des risques sur les actifs critiques réduit la surface d’attaque.
                </p>
                <a href="{{ route('monitor.configure') }}" class="soc-header__btn">Configurer</a>
            </div>
            <div class="soc-panel" style="margin-top:16px;">
                <h3>Auto Containment</h3>
                <p class="soc-stat-list">Attaquants externes bloqués : <strong>10</strong><br>Temps moyen de blocage : <strong>0 min</strong></p>
                <div class="soc-contain-visual">
                    <div class="soc-orbit">
                        <div class="soc-orbit-inner">🛡</div>
                    </div>
                </div>
                <div class="soc-mini-stats">
                    <div>Surveillés<strong>{{ $monitoredCount }}</strong></div>
                    <div>Bloqués<strong>10</strong></div>
                    <div>Noms de domaine<strong>8</strong></div>
                    <div>IP<strong>7</strong></div>
                </div>
                <table class="soc-table">
                    <thead>
                        <tr>
                            <th>Entité</th>
                            <th>Type</th>
                            <th>Intel</th>
                            <th>Résolu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="4" style="text-align:center;padding:12px;">—</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var el = document.getElementById('soc-clock');
    if (el) {
        var locale = el.getAttribute('data-locale') || 'fr';
        function tick() {
            var d = new Date();
            var days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            if (locale !== 'fr') {
                days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            }
            var y = d.getFullYear();
            var mo = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            var h = String(d.getHours()).padStart(2, '0');
            var mi = String(d.getMinutes()).padStart(2, '0');
            var s = String(d.getSeconds()).padStart(2, '0');
            el.textContent = y + '.' + mo + '.' + day + ' (' + days[d.getDay()] + ') ' + h + ':' + mi + ':' + s;
        }
        setInterval(tick, 1000);
    }

    var fsBtn = document.getElementById('soc-fullscreen');
    var docEl = document.documentElement;
    if (fsBtn) {
        var reqFs = docEl.requestFullscreen || docEl.webkitRequestFullscreen || docEl.msRequestFullscreen;
        var exitFs = document.exitFullscreen || document.webkitExitFullscreen || document.msExitFullscreen;
        if (reqFs && exitFs) {
            fsBtn.addEventListener('click', function () {
                var fsEl = document.fullscreenElement || document.webkitFullscreenElement;
                if (!fsEl) {
                    var p = reqFs.call(docEl);
                    if (p && typeof p.catch === 'function') p.catch(function () {});
                } else {
                    var q = exitFs.call(document);
                    if (q && typeof q.catch === 'function') q.catch(function () {});
                }
            });
        } else {
            fsBtn.style.display = 'none';
        }
    }
})();
</script>
@endpush
