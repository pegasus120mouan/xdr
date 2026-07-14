@extends('layouts.monitor-full')

@section('title', 'Security O&M Monitor - Wara XDR')

@push('styles')
<style>
    .soc-page {
        --soc-bg: #000b1e;
        --soc-cyan: #00d4ff;
        --soc-cyan-dim: rgba(0, 212, 255, 0.35);
        --soc-green: #22c55e;
        --soc-panel: rgba(10, 22, 42, 0.58);
        --soc-font-display: "Segoe UI", system-ui, sans-serif;
        --soc-font-mono: ui-monospace, "Cascadia Mono", Consolas, monospace;
        padding: 0 24px 36px;
        background:
            radial-gradient(ellipse 100% 80% at 50% -20%, rgba(0, 140, 200, 0.12) 0%, transparent 50%),
            linear-gradient(185deg, #020617 0%, #0c1929 38%, #050a12 72%, #020617 100%);
        min-height: 100vh;
        box-sizing: border-box;
        position: relative;
        isolation: isolate;
    }

    .soc-page::before {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        opacity: 0.055;
        background-image: repeating-radial-gradient(
            circle at 20% 30%,
            rgba(255, 255, 255, 0.09) 0,
            rgba(255, 255, 255, 0.09) 0.5px,
            transparent 0.5px,
            transparent 4px
        );
        mix-blend-mode: overlay;
    }

    .soc-page::after {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        box-shadow:
            inset 0 0 100px rgba(0, 0, 0, 0.65),
            inset 0 0 2px rgba(0, 212, 255, 0.06);
    }

    .soc-page > *:not(.soc-page__fx) {
        position: relative;
        z-index: 1;
    }

    .soc-page__fx {
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
    }

    .soc-page__fx-glow {
        position: absolute;
        top: -28%;
        left: 50%;
        transform: translateX(-50%);
        width: 150%;
        height: 90%;
        background: radial-gradient(ellipse 52% 48% at 50% 0%, rgba(0, 212, 255, 0.11) 0%, transparent 58%);
    }

    .soc-page__fx-grid {
        position: absolute;
        left: -60%;
        right: -60%;
        top: -20%;
        bottom: -40%;
        opacity: 0.22;
        background-image:
            linear-gradient(rgba(0, 212, 255, 0.07) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 212, 255, 0.07) 1px, transparent 1px);
        background-size: 48px 48px;
        transform-origin: 50% 40%;
        transform: perspective(420px) rotateX(58deg);
        animation: none;
        opacity: 0.12;
        mask-image: linear-gradient(180deg, transparent 0%, #000 18%, #000 88%, transparent 100%);
        -webkit-mask-image: linear-gradient(180deg, transparent 0%, #000 18%, #000 88%, transparent 100%);
    }

    .soc-page__fx-scan {
        position: absolute;
        left: 0;
        right: 0;
        height: 100px;
        background: linear-gradient(
            180deg,
            transparent 0%,
            rgba(0, 212, 255, 0.045) 45%,
            rgba(0, 255, 255, 0.07) 50%,
            rgba(0, 212, 255, 0.045) 55%,
            transparent 100%
        );
        animation: none;
        display: none;
    }

    @keyframes soc-cyber-grid {
        from { transform: perspective(420px) rotateX(58deg) translateY(0); }
        to { transform: perspective(420px) rotateX(58deg) translateY(48px); }
    }

    @keyframes soc-cyber-scan {
        from { top: -12%; }
        to { top: 112%; }
    }

    .soc-page .soc-num,
    .soc-page .soc-stat-value {
        font-family: var(--soc-font-mono);
    }

    @keyframes soc-col-in {
        from {
            opacity: 0;
            transform: translateY(14px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .soc-col {
        animation: none;
    }

    @media (prefers-reduced-motion: reduce) {
        .soc-page::before,
        .soc-page::after {
            display: none;
        }

        .soc-page__fx-grid,
        .soc-page__fx-scan {
            animation: none !important;
        }

        .soc-col {
            animation: none !important;
        }

        .soc-page .soc-ring,
        .soc-page .soc-core,
        .soc-page .soc-float-label,
        .soc-page .soc-plat-orbit,
        .soc-page .soc-dome-strike,
        .soc-page .soc-iron-dome__hemi,
        .soc-page .soc-iron-dome__mesh,
        .soc-page .soc-plat-ring,
        .soc-page .soc-contain-orbit,
        .soc-page .soc-contain-orbit-inner,
        .soc-page .soc-shield,
        .soc-page .soc-plat-scene::before,
        .soc-page .soc-plat-ring::after {
            animation: none !important;
        }

        .soc-shield--warn {
            animation: none !important;
        }
    }

    .soc-num {
        font-variant-numeric: tabular-nums;
        transition: color 0.35s ease;
    }

    .soc-stat-value {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: #f1f5f9;
    }

    .soc-secure-label--warn {
        color: #fb923c !important;
    }

    .soc-shield--warn {
        border-color: #fb923c !important;
        background: radial-gradient(circle at 30% 30%, rgba(251, 146, 60, 0.45), rgba(120, 50, 20, 0.85)) !important;
        box-shadow: 0 0 24px rgba(251, 146, 60, 0.4) !important;
        animation: shield-warn-pulse 2.2s ease-in-out infinite;
    }

    @keyframes shield-warn-pulse {
        0%, 100% { filter: brightness(1); }
        50% { filter: brightness(1.12); }
    }

    .soc-hub-legend {
        text-align: center;
        font-size: 0.7rem;
        color: #64748b;
        margin-top: 10px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .soc-hub-legend strong {
        color: #7dd3fc;
        font-weight: 600;
    }

    .soc-machine-wrap {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .soc-machine-link {
        text-decoration: none;
        color: inherit;
        display: block;
        border-radius: 6px;
        transition: transform 0.2s ease, filter 0.2s ease;
    }

    .soc-machine-link:hover {
        transform: scale(1.06);
        filter: brightness(1.08);
    }

    .soc-machine-link:focus-visible {
        outline: 2px solid #00ccff;
        outline-offset: 3px;
    }

    .soc-machine-tip {
        position: absolute;
        left: 50%;
        bottom: calc(100% + 6px);
        transform: translateX(-50%);
        min-width: 140px;
        max-width: 220px;
        padding: 6px 8px;
        font-size: 0.62rem;
        line-height: 1.35;
        color: #e2e8f0;
        background: rgba(0, 15, 40, 0.92);
        border: 1px solid rgba(0, 204, 255, 0.35);
        border-radius: 4px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.22s ease;
        z-index: 30;
        text-align: center;
    }

    .soc-machine-wrap:hover .soc-machine-tip {
        opacity: 1;
    }

    .soc-machine-tip__more {
        display: block;
        margin-top: 4px;
        font-size: 0.58rem;
        color: #22d3ee;
    }

    .soc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        padding: 22px 0 18px;
        margin-bottom: 22px;
        border-bottom: 1px solid transparent;
        border-image: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.35), rgba(0, 212, 255, 0.08), transparent) 1;
        box-shadow: 0 18px 40px -28px rgba(0, 212, 255, 0.25);
    }

    .soc-header__badge {
        font-size: 0.72rem;
        color: #94a3b8;
        padding: 8px 14px;
        border: 1px solid rgba(0, 212, 255, 0.28);
        border-radius: 2px;
        background: linear-gradient(135deg, rgba(0, 40, 70, 0.55) 0%, rgba(12, 28, 52, 0.75) 100%);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.06),
            0 0 24px rgba(0, 100, 160, 0.12);
        letter-spacing: 0.04em;
    }

    .soc-header__badge strong {
        color: var(--soc-cyan);
    }

    .soc-header__title {
        font-family: var(--soc-font-display);
        font-size: clamp(1.05rem, 2.5vw, 1.45rem);
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        background: linear-gradient(92deg, #f8fafc 0%, var(--soc-cyan) 42%, #38bdf8 72%, #7dd3fc 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        filter: drop-shadow(0 0 28px rgba(0, 212, 255, 0.35));
    }

    .soc-header__meta {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 0.78rem;
        color: #94a3b8;
    }

    .soc-header__meta #soc-clock {
        font-family: var(--soc-font-mono);
        font-size: 0.74rem;
        color: #7dd3fc;
        letter-spacing: 0.06em;
        padding: 6px 10px;
        background: rgba(0, 20, 45, 0.45);
        border: 1px solid rgba(0, 212, 255, 0.15);
        border-radius: 2px;
    }

    .soc-header__btn {
        position: relative;
        overflow: hidden;
        padding: 10px 18px;
        font-family: var(--soc-font-display);
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #ecfeff;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        border: 1px solid rgba(0, 212, 255, 0.55);
        background: linear-gradient(165deg, rgba(0, 80, 120, 0.5) 0%, rgba(0, 35, 65, 0.85) 100%);
        clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.1),
            0 0 20px rgba(0, 212, 255, 0.15);
        transition: transform 0.2s ease, box-shadow 0.25s ease, border-color 0.2s ease;
    }

    .soc-header__btn::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(105deg, transparent 35%, rgba(255, 255, 255, 0.12) 50%, transparent 65%);
        transform: translateX(-120%);
        transition: transform 0.55s ease;
    }

    .soc-header__btn:hover {
        transform: translateY(-2px);
        border-color: var(--soc-cyan);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.14),
            0 0 32px rgba(0, 212, 255, 0.35),
            0 8px 24px rgba(0, 0, 0, 0.35);
    }

    .soc-header__btn:hover::after {
        transform: translateX(120%);
    }

    .soc-header__btn:focus-visible {
        outline: 2px solid var(--soc-cyan);
        outline-offset: 3px;
    }

    .soc-grid {
        display: grid;
        grid-template-columns: minmax(220px, 0.9fr) minmax(480px, 1.8fr) minmax(240px, 0.95fr);
        gap: 20px;
        align-items: start;
    }

    @media (max-width: 1200px) {
        .soc-grid {
            grid-template-columns: 1fr;
        }
    }

    .soc-panel {
        position: relative;
        background: linear-gradient(155deg, rgba(12, 26, 48, 0.72) 0%, rgba(8, 18, 38, 0.55) 100%);
        border: 1px solid rgba(0, 212, 255, 0.18);
        border-radius: 4px;
        padding: 16px 17px 18px;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow:
            0 0 0 1px rgba(0, 0, 0, 0.35),
            0 12px 40px rgba(0, 0, 0, 0.35),
            0 0 48px rgba(0, 80, 140, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
        transition: border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .soc-panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 12px;
        right: 12px;
        height: 2px;
        background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.45), transparent);
        opacity: 0.65;
        pointer-events: none;
    }

    .soc-panel:hover {
        border-color: rgba(0, 212, 255, 0.28);
        box-shadow:
            0 0 0 1px rgba(0, 0, 0, 0.35),
            0 14px 44px rgba(0, 0, 0, 0.38),
            0 0 56px rgba(0, 120, 200, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.06);
    }

    .soc-panel h3 {
        font-family: var(--soc-font-display);
        font-size: 0.68rem;
        color: #7dd3fc;
        margin: 0 0 14px;
        font-weight: 600;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(0, 212, 255, 0.12);
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
        padding: 9px 11px;
        background: rgba(0, 8, 22, 0.45);
        border-radius: 2px;
        font-size: 0.7rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid rgba(0, 212, 255, 0.08);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .soc-threat-cell:hover {
        border-color: rgba(0, 212, 255, 0.28);
        background: rgba(0, 40, 70, 0.35);
        box-shadow: 0 0 16px rgba(0, 212, 255, 0.08);
    }

    .soc-threat-cell span:last-child {
        color: #4ade80;
        font-weight: 600;
        font-family: var(--soc-font-mono);
        font-size: 0.72rem;
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
        min-height: 620px;
    }

    .soc-hub-wrap.soc-panel {
        border-color: rgba(0, 212, 255, 0.26);
        box-shadow:
            0 0 0 1px rgba(0, 0, 0, 0.4),
            0 16px 48px rgba(0, 0, 0, 0.4),
            0 0 100px rgba(0, 100, 180, 0.14),
            inset 0 1px 0 rgba(255, 255, 255, 0.07);
    }

    .soc-hub-wrap.soc-panel::before {
        opacity: 0.85;
        height: 3px;
        background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.5), rgba(56, 189, 248, 0.35), transparent);
    }

    .soc-hub-visual {
        position: relative;
        width: 100%;
        max-width: 520px;
        margin: 0 auto;
    }

    /* Flux + pluie de données (style référence) */
    .soc-plat-beams {
        height: 48px;
        margin: -4px auto 0;
        max-width: 220px;
        background:
            repeating-linear-gradient(
                0deg,
                transparent,
                transparent 6px,
                rgba(0, 204, 255, 0.07) 6px,
                rgba(0, 204, 255, 0.07) 7px
            ),
            repeating-linear-gradient(
                90deg,
                transparent,
                transparent 4px,
                rgba(0, 204, 255, 0.06) 4px,
                rgba(0, 204, 255, 0.06) 5px
            );
        -webkit-mask-image: linear-gradient(180deg, transparent 0%, #000 20%, #000 80%, transparent 100%);
        mask-image: linear-gradient(180deg, transparent 0%, #000 20%, #000 80%, transparent 100%);
        animation: soc-beam-pulse 2.4s ease-in-out infinite;
        pointer-events: none;
    }

    @keyframes soc-beam-pulse {
        0%, 100% { opacity: 0.4; }
        50% { opacity: 0.95; }
    }

    /* Plateforme holo cyan + perspective (palette #000033 / #00CCFF / #FF3300) */
    .soc-plat-scene {
        --holo-navy: #000033;
        --holo-cyan: #00ccff;
        --holo-red: #ff3300;
        position: relative;
        z-index: 4;
        perspective: 1100px;
        perspective-origin: 50% 0%;
        margin-top: 4px;
        padding-bottom: 8px;
    }

    .soc-plat-scene::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 52px;
        width: 320px;
        height: 220px;
        margin-left: -160px;
        background: repeating-linear-gradient(
            0deg,
            transparent,
            transparent 10px,
            rgba(0, 204, 255, 0.04) 10px,
            rgba(0, 204, 255, 0.04) 11px
        );
        -webkit-mask-image: radial-gradient(ellipse 55% 50% at 50% 45%, #000 0%, transparent 75%);
        mask-image: radial-gradient(ellipse 55% 50% at 50% 45%, #000 0%, transparent 75%);
        animation: soc-data-rain 5s linear infinite;
        pointer-events: none;
        z-index: 0;
        opacity: 0.85;
    }

    @keyframes soc-data-rain {
        from { transform: translateY(-12px); }
        to { transform: translateY(12px); }
    }

    .soc-plat-stage {
        transform-style: preserve-3d;
        position: relative;
        z-index: 1;
    }

    .soc-plat-floor {
        position: relative;
        height: 210px;
        transform: rotateX(52deg);
        transform-origin: center 22%;
        transform-style: preserve-3d;
    }

    /* Grand disque vitré cyan (référence) */
    .soc-plat-ring {
        position: absolute;
        left: 50%;
        top: 42%;
        width: 300px;
        height: 300px;
        margin-left: -150px;
        margin-top: -150px;
        z-index: 1;
        border-radius: 50%;
        border: 1px solid rgba(0, 204, 255, 0.55);
        background:
            repeating-conic-gradient(
                from 0deg at 50% 50%,
                rgba(0, 204, 255, 0.04) 0deg 3deg,
                transparent 3deg 8deg
            ),
            radial-gradient(
                ellipse 72% 55% at 50% 42%,
                rgba(0, 204, 255, 0.18) 0%,
                rgba(0, 30, 80, 0.12) 45%,
                rgba(0, 0, 51, 0.35) 100%
            );
        box-shadow:
            0 0 2px rgba(0, 204, 255, 0.8),
            0 0 48px rgba(0, 204, 255, 0.22),
            0 0 80px rgba(0, 120, 200, 0.12),
            inset 0 0 70px rgba(0, 204, 255, 0.1),
            inset 0 -20px 50px rgba(0, 0, 51, 0.4);
        animation: soc-plat-glass-pulse 3.5s ease-in-out infinite;
        pointer-events: none;
        overflow: hidden;
    }

    .soc-plat-ring::after {
        content: '';
        position: absolute;
        inset: 10%;
        border-radius: 50%;
        background: linear-gradient(
            105deg,
            transparent 38%,
            rgba(255, 255, 255, 0.09) 47%,
            transparent 55%
        );
        animation: soc-plat-sheen 9s linear infinite;
        pointer-events: none;
    }

    @keyframes soc-plat-sheen {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes soc-plat-glass-pulse {
        0%, 100% {
            box-shadow:
                0 0 2px rgba(0, 204, 255, 0.65),
                0 0 40px rgba(0, 204, 255, 0.18),
                0 0 72px rgba(0, 120, 200, 0.1),
                inset 0 0 60px rgba(0, 204, 255, 0.08),
                inset 0 -20px 50px rgba(0, 0, 51, 0.45);
        }
        50% {
            box-shadow:
                0 0 3px rgba(0, 204, 255, 0.95),
                0 0 56px rgba(0, 204, 255, 0.28),
                0 0 96px rgba(0, 180, 255, 0.14),
                inset 0 0 80px rgba(0, 204, 255, 0.12),
                inset 0 -20px 50px rgba(0, 0, 51, 0.35);
        }
    }

    .soc-plat-orbit {
        position: absolute;
        left: 50%;
        top: 42%;
        width: 0;
        height: 0;
        z-index: 4;
        pointer-events: none;
        animation: soc-plat-spin var(--orbit-dur, 52s) linear infinite;
        transform-style: preserve-3d;
    }

    @keyframes soc-plat-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .soc-plat-slot {
        position: absolute;
        left: 0;
        top: 0;
        width: 0;
        height: 0;
        transform: rotate(var(--a, 0deg)) translateY(calc(-1 * var(--orbit-r, 118px)));
        transform-origin: center center;
    }

    .soc-plat-node {
        transform: translate(-50%, -50%);
        pointer-events: auto;
    }

    /* Nœud machine : cube isométrique + socle rouge + plaque IP (référence) */
    .soc-machine-node {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
        filter: drop-shadow(0 8px 16px rgba(0, 0, 51, 0.65));
    }

    .soc-holo-cube-wrap {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        perspective: 100px;
        perspective-origin: 50% 40%;
    }

    .soc-iso-cube {
        width: 26px;
        height: 26px;
        position: relative;
        transform-style: preserve-3d;
        transform: rotateX(-18deg) rotateY(-40deg);
    }

    .soc-iso-face {
        position: absolute;
        width: 26px;
        height: 26px;
        box-sizing: border-box;
        border: 1px solid rgba(0, 204, 255, 0.45);
        backface-visibility: hidden;
    }

    .soc-iso-face--front {
        background: linear-gradient(160deg, #00a8e0 0%, #006699 42%, #003d55 100%);
        transform: translateZ(13px);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: inset 0 0 12px rgba(0, 204, 255, 0.15);
    }

    .soc-iso-face--right {
        background: linear-gradient(180deg, #005577 0%, #002233 100%);
        transform: rotateY(90deg) translateZ(13px);
        box-shadow: inset -4px 0 8px rgba(0, 0, 0, 0.35);
    }

    .soc-iso-face--top {
        background: linear-gradient(145deg, #00ccff 0%, #0088bb 55%, #004466 100%);
        transform: rotateX(90deg) translateZ(13px);
        box-shadow: inset 0 4px 10px rgba(255, 255, 255, 0.12);
    }

    .soc-iso-redstrip {
        width: 88%;
        height: 5px;
        border-radius: 1px;
        background: linear-gradient(
            90deg,
            transparent 0%,
            var(--holo-red, #ff3300) 20%,
            #ff6633 50%,
            var(--holo-red, #ff3300) 80%,
            transparent 100%
        );
        box-shadow:
            0 0 8px var(--holo-red, #ff3300),
            0 0 16px rgba(255, 51, 0, 0.55);
        animation: soc-redstrip-pulse 2s ease-in-out infinite;
    }

    @keyframes soc-redstrip-pulse {
        0%, 100% { opacity: 0.95; filter: brightness(1); }
        50% { opacity: 1; filter: brightness(1.15); }
    }

    .soc-holo-pedestal {
        width: 38px;
        height: 11px;
        margin-top: -2px;
        border-radius: 50%;
        background: radial-gradient(
            ellipse at center,
            rgba(255, 51, 0, 0.75) 0%,
            rgba(255, 51, 0, 0.35) 45%,
            transparent 72%
        );
        box-shadow:
            0 0 12px var(--holo-red, #ff3300),
            0 0 28px rgba(255, 51, 0, 0.45);
        flex-shrink: 0;
        animation: soc-pedestal-pulse 2.5s ease-in-out infinite;
    }

    @keyframes soc-pedestal-pulse {
        0%, 100% { box-shadow: 0 0 10px #ff3300, 0 0 24px rgba(255, 51, 0, 0.4); }
        50% { box-shadow: 0 0 18px #ff3300, 0 0 36px rgba(255, 51, 0, 0.55); }
    }

    .soc-holo-ip {
        margin-top: 7px;
        padding: 4px 9px;
        font-size: 0.62rem;
        font-weight: 600;
        color: #f1f5f9;
        letter-spacing: 0.03em;
        font-variant-numeric: tabular-nums;
        text-align: center;
        max-width: 104px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        background: rgba(0, 0, 51, 0.82);
        border: 1px solid rgba(0, 204, 255, 0.28);
        border-radius: 2px;
        box-shadow:
            0 2px 12px rgba(0, 0, 0, 0.45),
            0 0 14px rgba(0, 204, 255, 0.08);
    }

    /* Machine victime d’attaque (alerte ouverte ou statut alerting) */
    .soc-machine-node--attack {
        filter: drop-shadow(0 0 14px rgba(255, 51, 0, 0.55)) drop-shadow(0 8px 16px rgba(80, 0, 0, 0.5));
    }

    .soc-machine-node--attack .soc-iso-face {
        border-color: rgba(255, 100, 80, 0.65);
    }

    .soc-machine-node--attack .soc-iso-face--front {
        background: linear-gradient(160deg, #cc2200 0%, #881100 42%, #440808 100%);
        box-shadow: inset 0 0 16px rgba(255, 80, 40, 0.35);
    }

    .soc-machine-node--attack .soc-iso-face--right {
        background: linear-gradient(180deg, #661008 0%, #2a0504 100%);
        box-shadow: inset -4px 0 8px rgba(0, 0, 0, 0.45);
    }

    .soc-machine-node--attack .soc-iso-face--top {
        background: linear-gradient(145deg, #ff3300 0%, #aa1100 55%, #550808 100%);
        box-shadow: inset 0 4px 10px rgba(255, 200, 120, 0.15);
    }

    .soc-machine-node--attack .soc-iso-redstrip {
        background: linear-gradient(
            90deg,
            transparent 0%,
            #ff6633 15%,
            #ffcc88 50%,
            #ff6633 85%,
            transparent 100%
        );
        box-shadow:
            0 0 12px #ff3300,
            0 0 24px rgba(255, 80, 40, 0.8);
        animation: soc-redstrip-pulse-attack 1.2s ease-in-out infinite;
    }

    @keyframes soc-redstrip-pulse-attack {
        0%, 100% { opacity: 1; filter: brightness(1.1); }
        50% { opacity: 1; filter: brightness(1.35); }
    }

    .soc-machine-node--attack .soc-holo-pedestal {
        background: radial-gradient(
            ellipse at center,
            rgba(255, 80, 30, 0.95) 0%,
            rgba(180, 20, 0, 0.55) 45%,
            transparent 72%
        );
        box-shadow:
            0 0 16px #ff2200,
            0 0 32px rgba(255, 51, 0, 0.65);
        animation: soc-pedestal-pulse-attack 1.4s ease-in-out infinite;
    }

    @keyframes soc-pedestal-pulse-attack {
        0%, 100% { box-shadow: 0 0 14px #ff2200, 0 0 28px rgba(255, 51, 0, 0.55); }
        50% { box-shadow: 0 0 22px #ff4400, 0 0 40px rgba(255, 100, 50, 0.75); }
    }

    .soc-machine-node--attack .soc-holo-ip {
        color: #fecaca;
        background: rgba(60, 10, 10, 0.88);
        border-color: rgba(255, 80, 60, 0.55);
        box-shadow:
            0 2px 12px rgba(0, 0, 0, 0.5),
            0 0 18px rgba(255, 51, 0, 0.35);
    }

    /* Dôme de fer — coque derrière les assets, impacts devant */
    .soc-plat-stack {
        position: relative;
    }

    .soc-iron-dome-shell,
    .soc-iron-dome-fx {
        position: absolute;
        left: 50%;
        bottom: 62px;
        width: 360px;
        height: 228px;
        margin-left: -180px;
        pointer-events: none;
        transform: scale(1.48);
        transform-origin: 50% 100%;
    }

    .soc-iron-dome-shell {
        z-index: 2;
    }

    .soc-iron-dome-fx {
        z-index: 6;
    }

    .soc-iron-dome__hemi {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 302px;
        height: 170px;
        margin-left: -151px;
        border-radius: 151px 151px 0 0;
        border: 2px solid rgba(0, 238, 255, 0.5);
        border-bottom: none;
        background: radial-gradient(
            ellipse 88% 100% at 50% 100%,
            rgba(0, 40, 90, 0.06) 0%,
            rgba(0, 200, 255, 0.06) 45%,
            rgba(0, 230, 255, 0.12) 78%,
            rgba(0, 255, 255, 0.06) 100%
        );
        box-shadow:
            0 -10px 55px rgba(0, 210, 255, 0.14),
            inset 0 -28px 75px rgba(0, 180, 255, 0.07);
        animation: iron-dome-breathe 3.2s ease-in-out infinite;
    }

    .soc-iron-dome__mesh {
        position: absolute;
        bottom: 2px;
        left: 50%;
        width: 292px;
        height: 162px;
        margin-left: -146px;
        border-radius: 146px 146px 0 0;
        background:
            repeating-linear-gradient(
                90deg,
                transparent,
                transparent 10px,
                rgba(0, 238, 255, 0.06) 10px,
                rgba(0, 238, 255, 0.06) 11px
            ),
            repeating-linear-gradient(
                0deg,
                transparent,
                transparent 12px,
                rgba(0, 238, 255, 0.05) 12px,
                rgba(0, 238, 255, 0.05) 13px
            );
        -webkit-mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35) 0%, #000 55%);
        mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35) 0%, #000 55%);
        opacity: 0.75;
        animation: iron-dome-scan 5s linear infinite;
    }

    @keyframes iron-dome-breathe {
        0%, 100% { opacity: 0.9; filter: brightness(1); }
        50% { opacity: 1; filter: brightness(1.1); }
    }

    @keyframes iron-dome-scan {
        from { opacity: 0.55; }
        to { opacity: 0.85; }
    }

    .soc-iron-dome__strikes {
        position: absolute;
        bottom: -2px;
        left: 50%;
        width: 380px;
        height: 215px;
        margin-left: -190px;
    }

    .soc-dome-strike {
        position: absolute;
        bottom: 10px;
        left: 50%;
        width: 6px;
        height: 0;
        margin-left: -3px;
        transform-origin: bottom center;
        opacity: 0;
        animation: iron-strike-flight var(--strike-t, 2.6s) cubic-bezier(0.33, 0.65, 0.28, 0.99) infinite;
        animation-delay: var(--strike-d, 0s);
    }

    /* Tête de charge vers le haut (entre dans le dôme), traînée vers l’extérieur */
    .soc-dome-strike::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 5px;
        height: 110px;
        margin-left: -2.5px;
        border-radius: 2px;
        background: linear-gradient(
            to top,
            rgba(80, 20, 10, 0.5) 0%,
            rgba(220, 50, 25, 0.75) 35%,
            rgba(255, 120, 50, 0.95) 72%,
            rgba(255, 255, 220, 1) 92%,
            rgba(255, 255, 255, 1) 100%
        );
        box-shadow: 0 0 14px rgba(255, 100, 50, 0.85);
        transform-origin: bottom center;
    }

    .soc-dome-strike::after {
        content: '';
        position: absolute;
        bottom: 100px;
        left: 50%;
        width: 36px;
        height: 36px;
        margin-left: -18px;
        border-radius: 50%;
        background: radial-gradient(
            circle,
            rgba(255, 255, 255, 0.95) 0%,
            rgba(0, 230, 255, 0.35) 38%,
            rgba(255, 80, 40, 0.25) 58%,
            transparent 72%
        );
        opacity: 0;
        animation: iron-strike-burst var(--strike-t, 2.6s) cubic-bezier(0.4, 0, 0.2, 1) infinite;
        animation-delay: var(--strike-d, 0s);
    }

    /* Entrée depuis l’extérieur (translateY positif) → impact sur la calotte → absorption (pas de sortie vers le haut) */
    @keyframes iron-strike-flight {
        0% {
            opacity: 0;
            transform: rotate(var(--strike-a)) translateY(58px) scaleY(0.5);
        }
        6% {
            opacity: 1;
        }
        38% {
            opacity: 1;
            transform: rotate(var(--strike-a)) translateY(-35px) scaleY(1);
        }
        48% {
            opacity: 1;
            transform: rotate(var(--strike-a)) translateY(-100px) scaleY(1.05);
            filter: brightness(2.2) saturate(1.2);
        }
        54% {
            opacity: 0.9;
            transform: rotate(var(--strike-a)) translateY(-100px) scaleY(0.35);
            filter: brightness(1.6);
        }
        70% {
            opacity: 0.35;
            transform: rotate(var(--strike-a)) translateY(-96px) scaleY(0.2);
            filter: none;
        }
        100% {
            opacity: 0;
            transform: rotate(var(--strike-a)) translateY(-92px) scaleY(0.12);
        }
    }

    @keyframes iron-strike-burst {
        0%, 40% {
            opacity: 0;
            transform: scale(0.15);
        }
        46% {
            opacity: 1;
            transform: scale(1);
        }
        58% {
            opacity: 0.4;
            transform: scale(1.35);
        }
        100% {
            opacity: 0;
            transform: scale(1.6);
        }
    }

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
        font-family: var(--soc-font-display);
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #a5f3fc;
        margin-bottom: 8px;
        text-shadow: 0 0 24px rgba(0, 212, 255, 0.35);
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


    /* Cybermap Kaspersky-style (centre Monitors) */
    .soc-panel--map {
        padding: 0;
        overflow: hidden;
        min-height: 620px;
    }

    .cmap {
        display: flex;
        flex-direction: column;
        min-height: 620px;
        background:
            radial-gradient(ellipse 70% 55% at 50% 40%, rgba(14, 70, 140, 0.35) 0%, transparent 60%),
            linear-gradient(180deg, #061222 0%, #030a14 100%);
    }

    .cmap-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px 10px;
        border-bottom: 1px solid rgba(56, 189, 248, 0.15);
        flex-wrap: wrap;
    }

    .cmap-title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #f1f5f9;
        letter-spacing: 0.02em;
    }

    .cmap-subtitle {
        margin: 4px 0 0;
        font-size: 0.72rem;
        color: #64748b;
    }

    .cmap-kpis {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .cmap-kpi {
        min-width: 72px;
        padding: 6px 10px;
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.65);
        border: 1px solid rgba(51, 65, 85, 0.7);
        text-align: center;
    }

    .cmap-kpi--hot {
        border-color: rgba(249, 115, 22, 0.45);
        background: rgba(67, 20, 7, 0.35);
    }

    .cmap-kpi__val {
        display: block;
        font-size: 1.15rem;
        font-weight: 800;
        color: #fff;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
    }

    .cmap-kpi__lbl {
        font-size: 0.62rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .cmap-body {
        display: grid;
        grid-template-columns: 200px 1fr;
        flex: 1;
        min-height: 0;
    }

    @media (max-width: 1100px) {
        .cmap-body { grid-template-columns: 1fr; }
    }

    .cmap-rank {
        padding: 12px 12px 16px;
        border-right: 1px solid rgba(56, 189, 248, 0.12);
        background: rgba(2, 8, 18, 0.55);
        overflow: auto;
        max-height: 560px;
    }

    .cmap-rank h3 {
        margin: 0 0 12px;
        font-size: 0.68rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .cmap-rank-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .cmap-rank-list li {
        display: grid;
        grid-template-columns: 16px 18px 1fr auto;
        grid-template-rows: auto auto;
        column-gap: 6px;
        row-gap: 3px;
        align-items: center;
        font-size: 0.72rem;
        color: #cbd5e1;
        cursor: default;
    }

    .cmap-rank-n { color: #64748b; font-variant-numeric: tabular-nums; }
    .cmap-rank-flag { font-size: 0.95rem; line-height: 1; }
    .cmap-rank-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cmap-rank-list strong { color: #fbbf24; font-variant-numeric: tabular-nums; }
    .cmap-rank-bar {
        grid-column: 2 / -1;
        height: 4px;
        border-radius: 2px;
        background: rgba(30, 58, 95, 0.7);
        overflow: hidden;
    }
    .cmap-rank-bar i {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #ea580c, #fbbf24);
    }

    .cmap-stage {
        position: relative;
        min-height: 480px;
    }

    .cmap-svg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }

    .cmap-world {
        opacity: 0.42;
        filter: brightness(0.55) contrast(1.2) saturate(0.4);
    }

    .cmap-arc {
        fill: none;
        stroke-linecap: round;
        stroke-width: 1.4;
        stroke-dasharray: 7 5;
        animation: cmap-dash 2.6s linear infinite;
        opacity: 0.88;
    }

    .cmap-flow--critical .cmap-arc,
    .cmap-flow--high .cmap-arc { stroke: #fb923c; }
    .cmap-flow--medium .cmap-arc { stroke: #fbbf24; }
    .cmap-flow--low .cmap-arc { stroke: #fde047; opacity: 0.65; }

    .cmap-pulse {
        fill: #fff7ed;
        stroke: #fb923c;
        stroke-width: 1;
        filter: drop-shadow(0 0 4px rgba(251, 146, 60, 0.9));
    }

    @keyframes cmap-dash {
        to { stroke-dashoffset: -24; }
    }

    .cmap-origin-ring {
        animation: cmap-ring 2.2s ease-in-out infinite;
        transform-origin: center;
        transform-box: fill-box;
    }

    @keyframes cmap-ring {
        0%, 100% { opacity: 0.25; }
        50% { opacity: 0.7; }
    }

    .cmap-origin-lbl {
        font-size: 10px;
        font-weight: 600;
        fill: #f8fafc;
        paint-order: stroke fill;
        stroke: rgba(3, 7, 18, 0.9);
        stroke-width: 3px;
    }

    .cmap-home-pulse {
        animation: cmap-home 2.4s ease-in-out infinite;
    }

    @keyframes cmap-home {
        0%, 100% { r: 10; opacity: 0.55; }
        50% { r: 20; opacity: 0; }
    }

    .cmap-home-lbl {
        font-size: 11px;
        font-weight: 600;
        fill: #7dd3fc;
    }

    .cmap-legend {
        position: absolute;
        left: 12px;
        bottom: 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 0.65rem;
        color: #94a3b8;
        z-index: 2;
        background: rgba(2, 8, 18, 0.55);
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid rgba(51, 65, 85, 0.5);
    }

    .cmap-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 4px;
    }
    .cmap-dot--hi { background: #fb923c; }
    .cmap-dot--med { background: #fbbf24; }
    .cmap-dot--lo { background: #fde047; }

    .cmap-empty, .cmap-empty-overlay {
        color: #64748b;
        font-size: 0.78rem;
    }

    .cmap-empty-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: rgba(3, 10, 20, 0.35);
        text-align: center;
        padding: 20px;
        z-index: 2;
    }

    .cmap-empty-overlay p {
        margin: 0;
        color: #cbd5e1;
        font-weight: 600;
    }

    @media (prefers-reduced-motion: reduce) {
        .cmap-arc, .cmap-origin-ring, .cmap-home-pulse { animation: none !important; }
        .cmap-pulse { display: none; }
    }

    /* Liste d’actifs — panneau central sobre */
    .soc-assets-panel {
        min-height: 420px;
        display: flex;
        flex-direction: column;
    }

    .soc-assets-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
        padding-bottom: 14px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.15);
    }

    .soc-assets-title {
        margin: 0 0 4px;
        font-family: var(--soc-font-display);
        font-size: 1rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: none;
        color: #e2e8f0;
        text-shadow: none;
    }

    .soc-assets-sub {
        margin: 0;
        font-size: 0.78rem;
        color: #94a3b8;
        line-height: 1.4;
    }

    .soc-asset-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
        overflow: auto;
        max-height: min(62vh, 560px);
    }

    .soc-asset-row {
        border: 1px solid rgba(45, 55, 72, 0.9);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.55);
        transition: border-color 0.15s, background 0.15s;
    }

    .soc-asset-row:hover {
        border-color: rgba(0, 212, 255, 0.35);
        background: rgba(15, 23, 42, 0.85);
    }

    .soc-asset-row--alert {
        border-color: rgba(239, 68, 68, 0.45);
        background: rgba(69, 10, 10, 0.25);
    }

    .soc-asset-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        text-decoration: none;
        color: inherit;
    }

    .soc-asset-status {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
        background: #64748b;
    }

    .soc-asset-status--online { background: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15); }
    .soc-asset-status--alerting { background: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15); }
    .soc-asset-status--offline { background: #64748b; }
    .soc-asset-status--unknown { background: #475569; }

    .soc-asset-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .soc-asset-host {
        font-size: 0.88rem;
        font-weight: 600;
        color: #f1f5f9;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .soc-asset-meta {
        font-size: 0.72rem;
        color: #94a3b8;
        font-family: var(--soc-font-mono);
    }

    .soc-asset-badges {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }

    .soc-badge {
        font-size: 0.65rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 999px;
        border: 1px solid transparent;
    }

    .soc-badge--alert {
        color: #fecaca;
        background: rgba(239, 68, 68, 0.18);
        border-color: rgba(239, 68, 68, 0.35);
    }

    .soc-badge--muted {
        color: #cbd5e1;
        background: rgba(148, 163, 184, 0.12);
        border-color: rgba(148, 163, 184, 0.2);
    }

    .soc-asset-hint {
        margin: 0;
        padding: 0 14px 10px 35px;
        font-size: 0.7rem;
        color: #fca5a5;
        line-height: 1.35;
    }

    .soc-assets-empty {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        text-align: center;
        color: #94a3b8;
        font-size: 0.85rem;
        padding: 40px 16px;
    }

    .soc-flow {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 12px;
    }

    .soc-flow-step {
        padding: 10px 12px;
        background: rgba(0, 20, 45, 0.45);
        border-left: 3px solid var(--soc-cyan);
        border-radius: 0 2px 2px 0;
        font-size: 0.72rem;
        color: #cbd5e1;
        box-shadow: inset 0 0 24px rgba(0, 212, 255, 0.03);
        transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .soc-flow-step:hover {
        background: rgba(0, 45, 85, 0.4);
        border-left-color: #38bdf8;
        box-shadow: inset 0 0 32px rgba(0, 212, 255, 0.06);
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

    .soc-contain-orbit {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 2px dashed rgba(0, 212, 255, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: soc-contain-spin 20s linear infinite;
    }

    @keyframes soc-contain-spin {
        to { transform: rotate(360deg); }
    }

    .soc-contain-orbit-inner {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: rgba(0, 212, 255, 0.12);
        border: 1px solid var(--soc-cyan-dim);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        animation: soc-contain-spin 20s linear infinite reverse;
    }

    .soc-mini-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        font-size: 0.72rem;
        margin-top: 10px;
    }

    .soc-mini-stats div {
        padding: 10px 8px;
        background: rgba(0, 12, 28, 0.5);
        border-radius: 2px;
        text-align: center;
        color: #94a3b8;
        border: 1px solid rgba(0, 212, 255, 0.1);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .soc-mini-stats div:hover {
        border-color: rgba(0, 212, 255, 0.25);
        box-shadow: inset 0 0 20px rgba(0, 212, 255, 0.04);
    }

    .soc-mini-stats strong {
        display: block;
        color: var(--soc-cyan);
        font-size: 1.05rem;
        font-family: var(--soc-font-mono);
        margin-top: 4px;
        text-shadow: 0 0 18px rgba(0, 212, 255, 0.35);
    }

    .soc-table {
        width: 100%;
        font-size: 0.66rem;
        border-collapse: collapse;
        margin-top: 10px;
        color: #94a3b8;
    }

    .soc-table th {
        font-family: var(--soc-font-display);
        text-align: left;
        padding: 8px 6px;
        border-bottom: 1px solid rgba(0, 212, 255, 0.22);
        color: #7dd3fc;
        font-size: 0.58rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .soc-table td {
        padding: 9px 6px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        transition: background 0.15s ease;
    }

    .soc-table tbody tr:hover td {
        background: rgba(0, 212, 255, 0.05);
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
        width: 38px;
        height: 38px;
        padding: 0;
        border: 1px solid rgba(0, 212, 255, 0.38);
        background: linear-gradient(160deg, rgba(0, 45, 80, 0.55) 0%, rgba(0, 20, 40, 0.75) 100%);
        color: var(--soc-cyan);
        border-radius: 2px;
        cursor: pointer;
        text-decoration: none;
        font-size: 1rem;
        line-height: 1;
        clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
        transition: background 0.2s, border-color 0.2s, box-shadow 0.2s, transform 0.15s ease;
    }

    .soc-icon-btn:hover {
        background: rgba(0, 212, 255, 0.14);
        border-color: var(--soc-cyan);
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
        transform: translateY(-1px);
    }
</style>
@endpush

@section('content')
<div class="soc-page">
    <div class="soc-page__fx" aria-hidden="true">
        <div class="soc-page__fx-glow"></div>
        <div class="soc-page__fx-grid"></div>
        <div class="soc-page__fx-scan"></div>
    </div>
    <header class="soc-header">
        <div class="soc-header__badge"><strong class="soc-num" data-soc-count="{{ $monitorStats['days_protected'] }}">{{ $monitorStats['days_protected'] }}</strong> jours protégés par XDR</div>
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
                    <div class="soc-shield{{ $monitorStats['secure_state'] === 'warn' ? ' soc-shield--warn' : '' }}" aria-hidden="true">🛡</div>
                    <div>
                        <div class="soc-secure-label{{ $monitorStats['secure_state'] === 'warn' ? ' soc-secure-label--warn' : '' }}" style="font-weight:700;font-size:0.9rem;color:{{ $monitorStats['secure_state'] === 'warn' ? '#fb923c' : 'var(--soc-green)' }};">
                            {{ $monitorStats['secure_state'] === 'warn' ? 'Attention' : 'Secure' }}
                        </div>
                        <div class="soc-stat-list">
                            <div>
                                <strong><span class="soc-num" data-soc-count="{{ $monitorStats['critical'] }}">{{ $monitorStats['critical'] }}</span></strong>/<strong><span class="soc-num" data-soc-count="{{ $monitorStats['risky'] }}">{{ $monitorStats['risky'] }}</span></strong>
                                actifs critiques / à risque
                            </div>
                            <div>
                                <strong><span class="soc-num" data-soc-count="{{ $monitorStats['blocked'] }}">{{ $monitorStats['blocked'] }}</span></strong>
                                blocages IP actifs
                                @if(($blockedDistinctIps ?? 0) > 0)
                                    <span style="color:#64748b;font-weight:500;">({{ $blockedDistinctIps }} IP distinctes)</span>
                                @endif
                            </div>
                            <div><strong><span class="soc-num" data-soc-count="{{ $monitorStats['pending'] }}">{{ $monitorStats['pending'] }}</span></strong> incidents en attente</div>
                            <div><strong><span class="soc-num" data-soc-count="{{ $monitorStats['resolved'] }}">{{ $monitorStats['resolved'] }}</span></strong> incidents résolus</div>
                        </div>
                    </div>
                </div>
                <h3>Menaces majeures</h3>
                <div class="soc-threat-grid">
                    @foreach($threatCategories as $tc)
                        @php $tcnt = (int) ($alertsByCat[$tc['key']] ?? 0); @endphp
                        <div class="soc-threat-cell">
                            <span>{{ $tc['short'] }}</span>
                            <span>{{ $tcnt > 0 ? $tcnt : 'None' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="soc-panel" style="margin-top:16px;">
                <h3>Vue organisationnelle</h3>
                <div class="soc-funnel">
                    <div class="soc-funnel-steps">
                        <span>Alertes de sécurité massives</span>
                        <span>Moteur d’analyse multivariée</span>
                        <span>Réduction alertes homme-machine</span>
                        <span>Incidents résolus / total ({{ $monitorStats['resolved'] }}/{{ $monitorStats['total_alerts'] }})</span>
                    </div>
                    <table class="soc-table">
                        <thead>
                            <tr>
                                <th>Incident</th>
                                <th>Gravité</th>
                                <th>Actif</th>
                                <th>Heure</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAlerts as $ra)
                                <tr>
                                    <td>{{ \Illuminate\Support\Str::limit($ra->title, 42) }}</td>
                                    <td>{{ $ra->severity }}</td>
                                    <td>{{ $ra->affected_asset ?: ($ra->target_ip ?: '—') }}</td>
                                    <td>{{ optional($ra->last_seen)->format('d/m H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;padding:16px;">Aucune alerte récente</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="soc-sev-bar">
                        @foreach(['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $sevKey => $sevLabel)
                            <span>{{ $sevLabel }} <strong class="soc-num" data-soc-count="{{ (int) ($severityOpen[$sevKey] ?? 0) }}">{{ (int) ($severityOpen[$sevKey] ?? 0) }}</strong></span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="soc-col soc-col--map">
            <div class="soc-panel soc-panel--map">
                @include('monitor.partials.cybermap-panel')
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
                <p class="soc-stat-list">
                    Blocages actifs : <strong><span class="soc-num" data-soc-count="{{ $monitorStats['blocked'] }}">{{ $monitorStats['blocked'] }}</span></strong>
                    @if(($blockedDistinctIps ?? 0) > 0)
                        <br>IP sources distinctes : <strong><span class="soc-num" data-soc-count="{{ $blockedDistinctIps }}">{{ $blockedDistinctIps }}</span></strong>
                    @endif
                </p>
                <div class="soc-mini-stats">
                    <div>Surveillés<strong class="soc-num" data-soc-count="{{ $monitoredCount }}">{{ $monitoredCount }}</strong></div>
                    <div>Blocages<strong class="soc-num" data-soc-count="{{ $monitorStats['blocked'] }}">{{ $monitorStats['blocked'] }}</strong></div>
                    <div>Règles act.<strong class="soc-num" data-soc-count="{{ $monitorStats['active_rules'] }}">{{ $monitorStats['active_rules'] }}</strong></div>
                    <div>En ligne<strong class="soc-num" data-soc-count="{{ $monitorStats['online_assets'] }}">{{ $monitorStats['online_assets'] }}</strong></div>
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

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduceMotion) {
        function easeOutQuad(t) {
            return 1 - (1 - t) * (1 - t);
        }

        function runCountUp(el, target, duration) {
            var start = performance.now();
            var from = 0;
            function frame(now) {
                var p = Math.min(1, (now - start) / duration);
                var v = Math.round(from + (target - from) * easeOutQuad(p));
                el.textContent = String(v);
                if (p < 1) {
                    requestAnimationFrame(frame);
                } else {
                    el.textContent = String(target);
                }
            }
            requestAnimationFrame(frame);
        }

        document.querySelectorAll('[data-soc-count]').forEach(function (node) {
            var raw = node.getAttribute('data-soc-count');
            var n = parseInt(raw, 10);
            if (isNaN(n) || n <= 0) {
                return;
            }
            node.textContent = '0';
            runCountUp(node, n, Math.min(1400, 320 + n * 12));
        });
    }
})();
</script>
@endpush
