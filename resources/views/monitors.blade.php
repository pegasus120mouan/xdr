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
        min-height: 560px;
    }

    .soc-hub-visual {
        position: relative;
        width: 100%;
        max-width: 440px;
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
        bottom: 68px;
        width: 360px;
        height: 228px;
        margin-left: -180px;
        pointer-events: none;
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
        animation: iron-strike-flight var(--strike-t, 2.6s) ease-in infinite;
        animation-delay: var(--strike-d, 0s);
    }

    .soc-dome-strike::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 5px;
        height: 102px;
        margin-left: -2.5px;
        border-radius: 2px;
        background: linear-gradient(
            to top,
            rgba(255, 240, 180, 1) 0%,
            rgba(255, 90, 40, 0.95) 18%,
            rgba(220, 40, 20, 0.65) 55%,
            transparent 100%
        );
        box-shadow: 0 0 14px rgba(255, 100, 50, 0.9);
        transform-origin: bottom center;
    }

    .soc-dome-strike::after {
        content: '';
        position: absolute;
        bottom: 125px;
        left: 50%;
        width: 32px;
        height: 32px;
        margin-left: -16px;
        border-radius: 50%;
        background: radial-gradient(
            circle,
            rgba(255, 255, 255, 0.95) 0%,
            rgba(255, 200, 80, 0.55) 30%,
            rgba(255, 60, 40, 0.3) 55%,
            transparent 72%
        );
        opacity: 0;
        animation: iron-strike-burst var(--strike-t, 2.6s) ease-in infinite;
        animation-delay: var(--strike-d, 0s);
    }

    @keyframes iron-strike-flight {
        0% {
            opacity: 0;
            transform: rotate(var(--strike-a)) translateY(0) scaleY(0.35);
        }
        10% {
            opacity: 1;
        }
        50% {
            opacity: 1;
            transform: rotate(var(--strike-a)) translateY(-132px) scaleY(1.08);
        }
        56% {
            opacity: 1;
            filter: brightness(2.4) saturate(1.25);
        }
        72% {
            opacity: 0.25;
            transform: rotate(var(--strike-a)) translateY(-145px) scaleY(0.55);
            filter: none;
        }
        100% {
            opacity: 0;
            transform: rotate(var(--strike-a)) translateY(-158px) scaleY(0.25);
        }
    }

    @keyframes iron-strike-burst {
        0%, 44% {
            opacity: 0;
            transform: scale(0.2);
        }
        50% {
            opacity: 1;
            transform: scale(1.2);
        }
        68% {
            opacity: 0;
            transform: scale(2.2);
        }
        100% {
            opacity: 0;
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
                    <div class="soc-hub" aria-label="Hub de surveillance des actifs">
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
                    </div>
                    @if($monitoredAssets->isNotEmpty())
                        @php
                            $step = $monitoredCount > 0 ? 360 / $monitoredCount : 0;
                            $platR = $monitoredCount > 6 ? '108px' : '118px';
                            $platDur = $monitoredCount > 4 ? '58s' : '48s';
                        @endphp
                        <div class="soc-plat-stack">
                            <div class="soc-plat-beams" aria-hidden="true"></div>
                            @if(!empty($showIronDome))
                                <div class="soc-iron-dome-shell" aria-hidden="true">
                                    <div class="soc-iron-dome__hemi"></div>
                                    <div class="soc-iron-dome__mesh"></div>
                                </div>
                            @endif
                            <div class="soc-plat-scene">
                                <div class="soc-plat-stage">
                                    <div class="soc-plat-floor" style="--orbit-dur: {{ $platDur }}; --orbit-r: {{ $platR }};">
                                        <div class="soc-plat-ring" aria-hidden="true"></div>
                                        <div class="soc-plat-orbit" role="presentation">
                                            @foreach($monitoredAssets as $asset)
                                                @php
                                                    $displayLabel = $asset->ip_address ?: $asset->hostname;
                                                    $angle = $loop->index * $step;
                                                @endphp
                                                <div class="soc-plat-slot" style="--a: {{ $angle }}deg;">
                                                    <div class="soc-plat-node">
                                                        <div class="soc-machine-node{{ !empty($underAttackAssetIds[$asset->id]) ? ' soc-machine-node--attack' : '' }}">
                                                            <div class="soc-holo-cube-wrap">
                                                                <div class="soc-iso-cube" title="{{ !empty($underAttackAssetIds[$asset->id]) ? $asset->hostname.' — victime d’attaque (alerte)' : $asset->hostname }}">
                                                                    <div class="soc-iso-face soc-iso-face--top" aria-hidden="true"></div>
                                                                    <div class="soc-iso-face soc-iso-face--right" aria-hidden="true"></div>
                                                                    <div class="soc-iso-face soc-iso-face--front">
                                                                        <span class="soc-iso-redstrip" aria-hidden="true"></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="soc-holo-pedestal" aria-hidden="true"></div>
                                                            <div class="soc-holo-ip">{{ $displayLabel }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if(!empty($showIronDome))
                                <div class="soc-iron-dome-fx" role="presentation" aria-hidden="true">
                                    <div class="soc-iron-dome__strikes">
                                        <span class="soc-dome-strike" style="--strike-a: -76deg; --strike-d: 0s; --strike-t: 2.45s;"></span>
                                        <span class="soc-dome-strike" style="--strike-a: -48deg; --strike-d: 0.28s; --strike-t: 2.7s;"></span>
                                        <span class="soc-dome-strike" style="--strike-a: -22deg; --strike-d: 0.55s; --strike-t: 2.55s;"></span>
                                        <span class="soc-dome-strike" style="--strike-a: 5deg; --strike-d: 0.12s; --strike-t: 2.85s;"></span>
                                        <span class="soc-dome-strike" style="--strike-a: 32deg; --strike-d: 0.65s; --strike-t: 2.5s;"></span>
                                        <span class="soc-dome-strike" style="--strike-a: 58deg; --strike-d: 0.4s; --strike-t: 2.75s;"></span>
                                        <span class="soc-dome-strike" style="--strike-a: 88deg; --strike-d: 0.82s; --strike-t: 2.6s;"></span>
                                        <span class="soc-dome-strike" style="--strike-a: 118deg; --strike-d: 0.2s; --strike-t: 2.65s;"></span>
                                        <span class="soc-dome-strike" style="--strike-a: -105deg; --strike-d: 0.5s; --strike-t: 2.8s;"></span>
                                        <span class="soc-dome-strike" style="--strike-a: 142deg; --strike-d: 0.95s; --strike-t: 2.52s;"></span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="soc-hub-caption">
                    <h2>Asset Monitoring</h2>
                    @if($monitoredCount > 0)
                        <p style="max-width: 320px;"><strong style="color:#7dd3fc;">{{ $monitoredCount }}</strong> machine(s) sur la plateforme de surveillance — flux actif vers le hub.</p>
                        @if(!empty($showIronDome))
                            <p style="max-width: 320px; margin: -6px auto 10px; font-size: 0.72rem; color: #22d3ee;">Dôme de fer actif — interceptions sur le bouclier.</p>
                        @endif
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
                    <div class="soc-contain-orbit">
                        <div class="soc-contain-orbit-inner">🛡</div>
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
