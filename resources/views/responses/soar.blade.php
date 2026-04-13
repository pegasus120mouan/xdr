@extends('layouts.app')

@section('title', 'SOAR - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">SOAR</h1>
        <p class="page-subtitle">Security Orchestration, Automation and Response</p>
    </div>

    <div class="soar-intro">
        <p>
            Le <strong>SOAR</strong> sert à <strong>enchaîner</strong> détection, décision et action : quand un incident est identifié,
            vous <strong>orchestrez</strong> les réponses (blocage, notification, escalade) plutôt que tout faire à la main.
        </p>
        <p>
            Dans cette console, la partie <em>automation</em> est déjà portée par les <strong>règles de détection</strong> et la page
            <strong>Responses</strong> (actions comme blocage d’IP après brute force). Le SOAR, ici, c’est surtout votre
            <strong>tableau de pilotage</strong> : suivre les alertes, ajuster les playbooks et aller vers le containment.
        </p>
    </div>

    <div class="soar-stats">
        <div class="stat-card">
            <span class="stat-label">Alertes nouvelles</span>
            <strong class="stat-value">{{ $alertStats['new'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">En analyse</span>
            <strong class="stat-value">{{ $alertStats['investigating'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">Résolues (7 jours)</span>
            <strong class="stat-value">{{ $alertStats['resolved_week'] }}</strong>
        </div>
    </div>

    <h2 class="section-heading">Que faire concrètement ?</h2>
    <ul class="soar-checklist">
        <li>
            <strong>Automatiser les réponses répétitives</strong> — Vérifier les règles avec blocage / notification sous
            <a href="{{ route('responses.index') }}">Responses</a> et
            <a href="{{ route('detection.rules') }}">Detection Rules</a>.
        </li>
        <li>
            <strong>Traiter le flux d’incidents</strong> — Ouvrir
            <a href="{{ route('detection.alerts') }}">Security Alerts</a>, qualifier (nouveau → en cours → résolu) et consigner la cause.
        </li>
        <li>
            <strong>Confiner</strong> — Contrôler le mode sous
            <a href="{{ route('responses.auto-containment') }}">Auto Containment</a> et les IP bloquées sous
            <a href="{{ route('detection.blocked-ips') }}">Blocked IPs</a>.
        </li>
        <li>
            <strong>Enrichir plus tard</strong> — Webhooks, tickets ITSM, runbooks multi-étapes : ce sont les extensions typiques d’un SOAR mûr (non branchées ici pour l’instant).
        </li>
    </ul>

    <div class="soar-actions">
        <a href="{{ route('detection.alerts') }}" class="btn btn-primary">Ouvrir les alertes</a>
        <a href="{{ route('responses.index') }}" class="btn btn-secondary">Voir les réponses auto</a>
        <a href="{{ route('detection.rules') }}" class="btn btn-secondary">Règles de détection</a>
    </div>
</div>

@push('styles')
<style>
    .page-subtitle {
        margin: 0.25rem 0 0;
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 400;
    }
    .soar-intro {
        color: #94a3b8;
        line-height: 1.65;
        max-width: 48rem;
        margin-bottom: 1.75rem;
    }
    .soar-intro p { margin-bottom: 1rem; }
    .soar-intro p:last-child { margin-bottom: 0; }
    .soar-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .soar-stats .stat-card {
        background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid #334155;
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    .soar-stats .stat-label {
        display: block;
        font-size: 0.72rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.35rem;
    }
    .soar-stats .stat-value {
        font-size: 1.45rem;
        color: #f1f5f9;
    }
    .section-heading {
        font-size: 1.05rem;
        color: #e2e8f0;
        margin: 0 0 1rem;
    }
    .soar-checklist {
        margin: 0 0 1.75rem;
        padding-left: 1.25rem;
        color: #cbd5e1;
        line-height: 1.7;
        max-width: 48rem;
    }
    .soar-checklist li { margin-bottom: 0.85rem; }
    .soar-checklist a { color: #38bdf8; text-decoration: none; }
    .soar-checklist a:hover { text-decoration: underline; }
    .soar-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .btn-secondary {
        background: #334155;
        color: #e2e8f0;
        border: 1px solid #475569;
    }
    .btn-secondary:hover { background: #475569; }
</style>
@endpush
@endsection
