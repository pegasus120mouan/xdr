@php
    $active = $activeTab ?? 'list';
    $aeQuery = array_filter([
        'range' => request('range'),
        'q' => request('q'),
    ]);
@endphp
<nav class="sa-tabs" aria-label="Security Alerts navigation">
    <a href="{{ route('detection.alerts') }}" class="sa-tab {{ $active === 'list' ? 'active' : '' }}">
        Liste des alertes
    </a>
    <a href="{{ route('detection.alerts.attack-events', $aeQuery) }}" class="sa-tab {{ $active === 'attack-events' ? 'active' : '' }}">
        Détails
        <span class="sa-tab-pill">Attack Events</span>
    </a>
</nav>
