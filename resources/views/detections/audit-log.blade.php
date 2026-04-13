@extends('layouts.app')

@section('title', 'Journal d\'audit — Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Security Alerts</h1>
            @include('detections.partials.alerts-nav', ['activeTab' => 'audit'])
        </div>
    </div>

    <div class="audit-toolbar">
        <form method="get" action="{{ route('detection.audit-log') }}" class="audit-filter">
            <label for="audit-action">Filtrer par action</label>
            <input type="search" id="audit-action" name="action" value="{{ request('action') }}" placeholder="ex. blocked_ip, detection_rule…">
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
        </form>
        <p class="audit-hint">
            Traçabilité des opérations sensibles : blocages, statuts d’alertes, règles, agents, groupes, surveillance des actifs.
        </p>
    </div>

    <div class="table-container" style="background:#1a1f2e;border:1px solid #2d3748;border-radius:8px;overflow:hidden;">
        <table class="data-table audit-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>Utilisateur</th>
                    <th>Cible</th>
                    <th>Détails</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="audit-mono">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td><code class="audit-code">{{ $log->action }}</code></td>
                        <td>{{ $log->user?->name ?? '—' }}</td>
                        <td class="audit-mono">
                            @if($log->subject_type)
                                {{ class_basename($log->subject_type) }}#{{ $log->subject_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="audit-props">
                            @if($log->properties)
                                <pre>{{ json_encode($log->properties, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                            @else
                                —
                            @endif
                        </td>
                        <td class="audit-mono">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state" style="text-align:center;padding:2rem;color:#64748b;">
                            Aucune entrée pour le moment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        {{ $logs->withQueryString()->links() }}
    </div>
</div>

<style>
.audit-toolbar { margin-bottom: 1.25rem; }
.audit-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.65rem;
    margin-bottom: 0.75rem;
}
.audit-filter label { font-size: 0.75rem; color: #94a3b8; width: 100%; }
.audit-filter input {
    padding: 0.45rem 0.65rem;
    min-width: 220px;
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 6px;
    color: #e2e8f0;
    font-size: 0.85rem;
}
.audit-hint { font-size: 0.78rem; color: #64748b; margin: 0; line-height: 1.45; max-width: 52rem; }
.audit-table { font-size: 0.75rem; }
.audit-mono { font-family: ui-monospace, monospace; font-size: 0.72rem; color: #94a3b8; }
.audit-code {
    font-size: 0.7rem;
    color: #7dd3fc;
    background: rgba(0, 212, 255, 0.08);
    padding: 2px 6px;
    border-radius: 4px;
}
.audit-props pre {
    margin: 0;
    max-width: 320px;
    max-height: 120px;
    overflow: auto;
    font-size: 0.65rem;
    color: #cbd5e1;
    white-space: pre-wrap;
    word-break: break-word;
}
</style>
@endsection
