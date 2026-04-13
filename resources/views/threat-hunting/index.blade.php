@extends('layouts.app')

@section('title', 'Threat Hunting - Wara XDR')

@section('content')
<div class="page-content th-page">
    <div class="page-header th-header">
        <div>
            <h1 class="page-title">Threat Hunting</h1>
            <p class="th-tagline">Investigation unifiée — IP, compte, chaîne dans les journaux agents et les alertes</p>
        </div>
        <div class="th-header-links">
            <a href="{{ route('detection.alerts') }}" class="btn btn-secondary">Alertes</a>
            <a href="{{ route('detection.login-attempts') }}" class="btn btn-secondary">Logins</a>
            <a href="{{ route('monitor.attack-map') }}" class="btn btn-secondary" target="_blank" rel="noopener">Attack Map</a>
        </div>
    </div>

    <section class="th-hero">
        <form method="get" action="{{ route('threat-hunting.index') }}" class="th-search-form">
            <label for="th-q" class="th-label">Recherche investigator (IOC / IP / e-mail / mot-clé)</label>
            <div class="th-search-row">
                <input type="search" name="q" id="th-q" value="{{ $q }}" class="th-input"
                    placeholder="Ex. 203.0.113.50, admin@domaine.tld, Failed password, CVE-…" autocomplete="off">
                <select name="since" class="th-select" title="Fenêtre temporelle pour logins, alertes et logs agents">
                    @foreach([24 => '24 h', 72 => '3 j', 168 => '7 j', 336 => '14 j', 720 => '30 j'] as $h => $label)
                        <option value="{{ $h }}" @selected((int)$since === $h)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary th-submit">Analyser</button>
            </div>
            <div class="th-tm-row">
                <label class="th-tm-check">
                    <input type="checkbox" name="tm" value="1" @checked(request()->boolean('tm'))>
                    <span>Enrichir avec <strong>ThreatMiner</strong> (OSINT)</span>
                </label>
                <select name="tm_rt" class="th-select th-tm-rt" title="Type de requête ThreatMiner (rt)">
                    @foreach($tmRtOptions as $rtVal => $rtLabel)
                        <option value="{{ $rtVal }}" @selected((int) request('tm_rt', 6) === (int) $rtVal)>
                            rt={{ $rtVal }} — {{ $rtLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="th-tm-row th-oc-row">
                <label class="th-tm-check">
                    <input type="checkbox" name="opencti" value="1" @checked(request()->boolean('opencti'))>
                    <span>Enrichir avec <strong>OpenCTI</strong> (CTI)</span>
                </label>
                <label class="th-oc-first-wrap">
                    <span class="th-oc-first-label">Max. résultats</span>
                    <input type="number" name="opencti_first" class="th-oc-first-input" min="5" max="100" step="1"
                        value="{{ (int) request('opencti_first', config('opencti.default_first', 25)) }}">
                </label>
            </div>
            <div class="th-tm-row th-otx-row">
                <label class="th-tm-check">
                    <input type="checkbox" name="otx" value="1" @checked(request()->boolean('otx'))>
                    <span>Enrichir avec <strong>AlienVault OTX</strong></span>
                </label>
                <label class="th-tm-check th-otx-ext">
                    <input type="checkbox" name="otx_extended" value="1" @checked(request()->boolean('otx_extended'))>
                    <span>2ᵉ section API (réputation, passive DNS, analyse fichier…)</span>
                </label>
            </div>
            <p class="th-hint">
                Les IP complètes utilisent une correspondance exacte sur logins et alertes. Les autres termes cherchent dans les messages, titres et métadonnées.
                ThreatMiner : IP, domaine ou hash MD5/SHA256 — voir la
                <a href="https://www.threatminer.org/api.php" target="_blank" rel="noopener noreferrer">documentation API</a>
                (limite d’environ <strong>10 requêtes/minute</strong> ; cette appli met en cache et limite les appels sortants).
                <strong>OpenCTI</strong> : recherche plein texte sur les objets STIX via l’API GraphQL
                (<a href="https://docs.opencti.io/latest/reference/api/" target="_blank" rel="noopener noreferrer">documentation</a>) —
                activer <code>OPENCTI_ENABLED=true</code>, renseigner <code>OPENCTI_URL</code> et <code>OPENCTI_TOKEN</code> (Bearer).
                <strong>OTX</strong> : clé dans <a href="https://otx.alienvault.com/settings" target="_blank" rel="noopener noreferrer">OTX → Settings</a>
                → <em>OTX Key</em>, puis <code>OTX_ENABLED=true</code> et <code>OTX_API_KEY</code> dans <code>.env</code>
                (ne commitez jamais la clé ; régénérez-la si elle a pu être exposée).
            </p>
        </form>
    </section>

    <div class="th-stats">
        <div class="th-stat">
            <span class="th-stat-label">Alertes en cours</span>
            <strong>{{ $stats['pending_alerts'] }}</strong>
        </div>
        <div class="th-stat th-stat-warn">
            <span class="th-stat-label">Critiques / High ouvertes</span>
            <strong>{{ $stats['critical_open'] }}</strong>
        </div>
        <div class="th-stat">
            <span class="th-stat-label">Échecs login (24 h)</span>
            <strong>{{ number_format($stats['failed_logins_24h']) }}</strong>
        </div>
        <div class="th-stat">
            <span class="th-stat-label">Blocs IP actifs</span>
            <strong>{{ $stats['active_blocks'] }}</strong>
        </div>
    </div>

    <div class="th-grid-2">
        <section class="th-panel">
            <h2 class="th-panel-title">Top IP — échecs login (24 h)</h2>
            @if($topFailedIps->isEmpty())
                <p class="th-empty">Aucune donnée récente.</p>
            @else
                <table class="th-mini-table">
                    <thead><tr><th>IP</th><th>Échecs</th><th></th></tr></thead>
                    <tbody>
                        @foreach($topFailedIps as $row)
                            <tr>
                                <td><code>{{ $row->ip_address }}</code></td>
                                <td>{{ $row->fail_count }}</td>
                                <td><a href="{{ route('threat-hunting.index', array_merge(request()->only(['since', 'tm', 'tm_rt', 'opencti', 'opencti_first', 'otx', 'otx_extended']), ['q' => $row->ip_address])) }}" class="th-link">Pivot →</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="th-panel">
            <h2 class="th-panel-title">Alertes critiques / high récentes</h2>
            @if($recentCritical->isEmpty())
                <p class="th-empty">Aucune alerte ouverte dans cette catégorie.</p>
            @else
                <ul class="th-alert-list">
                    @foreach($recentCritical as $alert)
                        <li>
                            <a href="{{ route('detection.alerts.show', $alert) }}" class="th-alert-link">
                                <span class="severity-pill sev-{{ $alert->severity }}">{{ $alert->severity }}</span>
                                {{ Str::limit($alert->title, 72) }}
                            </a>
                            <span class="th-muted">{{ $alert->last_seen?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    @if($search !== null)
        <section class="th-results">
            <div class="th-results-head">
                <h2>Résultats pour « {{ $q }} »</h2>
                <span class="th-badge">{{ $search['total_hits'] }} correspondances (fenêtre {{ $sinceHours }} h pour les séries temporelles)</span>
            </div>

            @if($search['total_hits'] === 0)
                <p class="th-empty-block">Aucun résultat. Élargissez la fenêtre temporelle ou essayez un autre terme.</p>
            @endif

            @if($search['blocked']->isNotEmpty())
                <h3 class="th-sub">IPs bloquées</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr><th>IP</th><th>Raison</th><th>Jusqu’au</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            @foreach($search['blocked'] as $b)
                                <tr>
                                    <td><code>{{ $b->ip_address }}</code></td>
                                    <td>{{ Str::limit($b->reason, 60) }}</td>
                                    <td>{{ $b->blocked_until ? $b->blocked_until->format('Y-m-d H:i') : '—' }}</td>
                                    <td>{{ $b->is_active ? 'Actif' : 'Inactif' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($search['logins']->isNotEmpty())
                <h3 class="th-sub">Tentatives de connexion</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr><th>Date</th><th>IP</th><th>Compte</th><th>Résultat</th></tr>
                        </thead>
                        <tbody>
                            @foreach($search['logins'] as $la)
                                <tr>
                                    <td>{{ $la->attempted_at->format('Y-m-d H:i:s') }}</td>
                                    <td><code>{{ $la->ip_address }}</code></td>
                                    <td>{{ $la->email }}</td>
                                    <td>{{ $la->successful ? 'OK' : 'Échec' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($search['alerts']->isNotEmpty())
                <h3 class="th-sub">Alertes sécurité</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr><th>Sévérité</th><th>Titre</th><th>IP source</th><th>Dernière vue</th></tr>
                        </thead>
                        <tbody>
                            @foreach($search['alerts'] as $a)
                                <tr>
                                    <td><span class="severity-pill sev-{{ $a->severity }}">{{ $a->severity }}</span></td>
                                    <td><a href="{{ route('detection.alerts.show', $a) }}">{{ Str::limit($a->title, 56) }}</a></td>
                                    <td><code>{{ $a->source_ip ?? '—' }}</code></td>
                                    <td>{{ $a->last_seen?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($search['agent_logs']->isNotEmpty())
                <h3 class="th-sub">Logs agents (contenu du message)</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr><th>Horodatage</th><th>Agent</th><th>Type</th><th>Sévérité</th><th>Message</th></tr>
                        </thead>
                        <tbody>
                            @foreach($search['agent_logs'] as $log)
                                <tr>
                                    <td>{{ $log->log_timestamp?->format('Y-m-d H:i:s') ?? $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        @if($log->agent)
                                            <a href="{{ route('agents.logs', $log->agent) }}">{{ $log->agent->name ?? $log->agent->hostname }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $log->log_type }}</td>
                                    <td>{{ $log->severity }}</td>
                                    <td class="th-msg">{{ Str::limit($log->message, 140) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    @if($threatMiner !== null)
        <section class="th-threatminer">
            <div class="th-tm-head">
                <h2 class="th-tm-title">ThreatMiner</h2>
                <a href="https://www.threatminer.org/api.php" target="_blank" rel="noopener noreferrer" class="th-link">API ThreatMiner</a>
            </div>

            @if(!($threatMiner['ok'] ?? false))
                <div class="th-tm-alert th-tm-alert-warn">
                    @switch($threatMiner['reason'] ?? '')
                        @case('disabled')
                            Enrichissement ThreatMiner désactivé (configuration <code>THREATMINER_ENABLED</code>).
                            @break
                        @case('rate_limited')
                            Trop de requêtes vers l’API. Réessayez dans {{ (int) ($threatMiner['retry_after'] ?? 60) }} s.
                            @break
                        @case('indicator_not_supported')
                            {{ $threatMiner['message'] ?? 'Indicateur non pris en charge.' }}
                            @break
                        @case('http_error')
                            Erreur HTTP {{ $threatMiner['http_status'] ?? '?' }} vers ThreatMiner.
                            @break
                        @case('http_exception')
                            Impossible de joindre ThreatMiner : {{ Str::limit($threatMiner['message'] ?? '', 120) }}
                            @break
                        @case('invalid_json')
                            {{ $threatMiner['message'] ?? 'Réponse invalide.' }}
                            @break
                        @case('unsupported_type')
                            Type d’indicateur non géré.
                            @break
                        @default
                            {{ $threatMiner['message'] ?? 'Requête ThreatMiner indisponible.' }}
                    @endswitch
                </div>
            @else
                <div class="th-tm-meta">
                    <span><code>{{ $threatMiner['endpoint'] ?? '' }}</code></span>
                    <span>indicateur <strong>{{ $threatMiner['classified']['type'] ?? '' }}</strong> : <code>{{ $threatMiner['value'] ?? '' }}</code></span>
                    <span>rt={{ $threatMiner['rt'] ?? '' }}
                        @if(!empty($threatMiner['rt_labels'][$threatMiner['rt'] ?? 0]))
                            ({{ $threatMiner['rt_labels'][$threatMiner['rt']] }})
                        @endif
                    </span>
                    @if(!empty($threatMiner['cached']))
                        <span class="th-tm-cached">cache appli</span>
                    @endif
                </div>
                <p class="th-tm-api-status">
                    API <code>status_code</code> : <strong>{{ $threatMiner['status_code'] ?? '—' }}</strong>
                    @if(!empty($threatMiner['status_message']))
                        — {{ $threatMiner['status_message'] }}
                    @endif
                    (200 = données trouvées, 404 = pas de résultat côté ThreatMiner.)
                </p>
                <pre class="th-tm-json" id="th-tm-json">{{ json_encode($threatMiner['results'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            @endif
        </section>
    @endif

    @if($openCti !== null)
        <section class="th-opencti">
            <div class="th-oc-head">
                <h2 class="th-oc-title">OpenCTI</h2>
                <a href="https://docs.opencti.io/latest/reference/api/" target="_blank" rel="noopener noreferrer" class="th-link">Documentation API</a>
            </div>

            @if(!($openCti['ok'] ?? false))
                <div class="th-oc-alert">
                    @switch($openCti['reason'] ?? '')
                        @case('disabled')
                            OpenCTI est désactivé. Passez <code>OPENCTI_ENABLED=true</code> dans <code>.env</code>.
                            @break
                        @case('missing_token')
                        @case('missing_url')
                            {{ $openCti['message'] ?? 'Configurez OPENCTI_URL et OPENCTI_TOKEN.' }}
                            @break
                        @case('rate_limited')
                            Trop de requêtes OpenCTI. Réessayez dans {{ (int) ($openCti['retry_after'] ?? 60) }} s.
                            @break
                        @case('http_error')
                            Erreur HTTP {{ $openCti['http_status'] ?? '?' }}.
                            @if(!empty($openCti['body']))
                                <pre class="th-oc-pre-inline">{{ $openCti['body'] }}</pre>
                            @endif
                            @break
                        @case('http_exception')
                            Impossible de joindre OpenCTI : {{ Str::limit($openCti['message'] ?? '', 160) }}
                            @break
                        @case('graphql_errors')
                            Erreur GraphQL (schéma ou droits). Vérifiez la version d’OpenCTI et le token.
                            <pre class="th-oc-pre-inline">{{ json_encode($openCti['errors'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @if(!empty($openCti['hint']))
                                <p class="th-oc-hint">{{ $openCti['hint'] }}</p>
                            @endif
                            @break
                        @case('empty_search')
                            Recherche vide.
                            @break
                        @case('invalid_json')
                            Réponse OpenCTI non interprétable.
                            @break
                        @default
                            {{ $openCti['message'] ?? 'Requête OpenCTI indisponible.' }}
                    @endswitch
                </div>
            @else
                <div class="th-oc-meta">
                    <span>Recherche <code>{{ $openCti['search'] ?? '' }}</code></span>
                    <span>jusqu’à <strong>{{ $openCti['first'] ?? '' }}</strong> objets</span>
                    @if(!empty($openCti['page_info']['globalCount']))
                        <span>total index (indicatif) : {{ $openCti['page_info']['globalCount'] }}</span>
                    @endif
                    @if(!empty($openCti['cached']))
                        <span class="th-oc-cached">cache appli</span>
                    @endif
                </div>
                @if(!empty($openCti['rows']))
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Libellé / pattern</th>
                                    <th>MITRE</th>
                                    <th>Créé</th>
                                    <th>OpenCTI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($openCti['rows'] as $row)
                                    <tr>
                                        <td><code>{{ $row['entity_type'] ?? '—' }}</code></td>
                                        <td>{{ Str::limit($row['label'] ?? $row['pattern'] ?? '—', 80) }}</td>
                                        <td>{{ $row['x_mitre_id'] ?? '—' }}</td>
                                        <td>{{ $row['created_at'] ?? '—' }}</td>
                                        <td>
                                            @if(!empty($row['open_url']))
                                                <a href="{{ $row['open_url'] }}" target="_blank" rel="noopener noreferrer" class="th-link">Ouvrir →</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="th-empty-block">Aucun objet STIX ne correspond à cette recherche dans OpenCTI.</p>
                @endif
                <details class="th-oc-raw">
                    <summary>Réponse GraphQL brute (debug)</summary>
                    <pre class="th-tm-json">{{ json_encode($openCti['raw'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
            @endif
        </section>
    @endif

    @if($otx !== null)
        <section class="th-otx">
            <div class="th-otx-head">
                <h2 class="th-otx-title">AlienVault OTX</h2>
                <a href="https://otx.alienvault.com/api" target="_blank" rel="noopener noreferrer" class="th-link">API OTX</a>
            </div>

            @if(!($otx['ok'] ?? false))
                <div class="th-otx-alert">
                    @switch($otx['reason'] ?? '')
                        @case('disabled')
                            OTX est désactivé. Utilisez <code>OTX_ENABLED=true</code> et <code>OTX_API_KEY</code> dans <code>.env</code>.
                            @break
                        @case('missing_key')
                            {{ $otx['message'] ?? 'Clé OTX manquante.' }}
                            @break
                        @case('indicator_not_supported')
                            {{ $otx['message'] ?? 'Indicateur non pris en charge.' }}
                            @break
                        @case('rate_limited')
                            Trop de requêtes OTX. Réessayez dans {{ (int) ($otx['retry_after'] ?? 60) }} s.
                            @break
                        @case('http_error')
                            Erreur HTTP {{ $otx['http_status'] ?? '?' }}.
                            @if(!empty($otx['body']))
                                <pre class="th-oc-pre-inline">{{ $otx['body'] }}</pre>
                            @endif
                            @break
                        @case('http_exception')
                            Impossible de joindre OTX : {{ Str::limit($otx['message'] ?? '', 160) }}
                            @break
                        @default
                            {{ $otx['message'] ?? 'Requête OTX indisponible.' }}
                    @endswitch
                </div>
            @else
                <div class="th-otx-meta">
                    @if(!empty($otx['classified']))
                        <span>Type <strong>{{ $otx['classified']['type'] ?? '' }}</strong></span>
                        <span>IOC <code>{{ $otx['classified']['value'] ?? '' }}</code></span>
                    @endif
                    @if(!empty($otx['browse_url']))
                        <a href="{{ $otx['browse_url'] }}" target="_blank" rel="noopener noreferrer" class="th-link">Voir sur OTX →</a>
                    @endif
                    @if(!empty($otx['cached']))
                        <span class="th-otx-cached">cache appli</span>
                    @endif
                </div>
                @php
                    $otxGeneral = $otx['sections']['general'] ?? null;
                    $otxPulses = is_array($otxGeneral) ? data_get($otxGeneral, 'pulse_info.pulses', []) : [];
                    if (! is_array($otxPulses)) {
                        $otxPulses = [];
                    }
                    $otxPulseCount = is_array($otxGeneral) ? (int) data_get($otxGeneral, 'pulse_info.count', count($otxPulses)) : 0;
                    $otxRep = is_array($otxGeneral) ? data_get($otxGeneral, 'reputation') : null;
                    $otxRepInt = is_numeric($otxRep) ? (int) $otxRep : 0;
                    $otxIocLabel = match ($otx['classified']['type'] ?? '') {
                        'IPv4', 'IPv6' => 'adresse IP',
                        'domain' => 'nom de domaine',
                        'URL' => 'lien (URL)',
                        'FileHash-MD5', 'FileHash-SHA1', 'FileHash-SHA256' => 'empreinte de fichier',
                        'CVE' => 'référence de vulnérabilité',
                        default => 'indicateur',
                    };
                    if ($otxPulseCount >= 30 || $otxRepInt >= 4) {
                        $otxRisk = ['level' => 'critical', 'label' => 'Risque élevé', 'short' => 'Très largement signalé par la communauté.'];
                    } elseif ($otxPulseCount >= 15 || $otxRepInt >= 2) {
                        $otxRisk = ['level' => 'high', 'label' => 'Risque significatif', 'short' => 'Signalé dans de nombreux rapports de menace.'];
                    } elseif ($otxPulseCount >= 5 || $otxRepInt >= 1) {
                        $otxRisk = ['level' => 'medium', 'label' => 'Risque modéré', 'short' => 'Présent dans plusieurs sources ; mérite analyse.'];
                    } elseif ($otxPulseCount > 0) {
                        $otxRisk = ['level' => 'low', 'label' => 'Risque limité', 'short' => 'Peu de rapports publics ; rester prudent.'];
                    } else {
                        $otxRisk = ['level' => 'minimal', 'label' => 'Peu ou pas de consensus public', 'short' => 'Peu d’informations partagées sur cet élément dans OTX.'];
                    }
                    $otxBlob = '';
                    foreach (array_slice($otxPulses, 0, 25) as $op) {
                        if (is_array($op)) {
                            $otxBlob .= ' '.strtolower(strip_tags(($op['name'] ?? '').' '.($op['description'] ?? '')));
                        }
                    }
                    $otxThemes = [];
                    if (str_contains($otxBlob, 'ssh') || str_contains($otxBlob, 'brute')) {
                        $otxThemes[] = 'tentatives d’intrusion automatiques (ex. cassage de mots de passe sur serveurs)';
                    }
                    if (str_contains($otxBlob, 'phish') || str_contains($otxBlob, 'courrier') || str_contains($otxBlob, 'email')) {
                        $otxThemes[] = 'arnaques ou e-mails piégés';
                    }
                    if (str_contains($otxBlob, 'malware') || str_contains($otxBlob, 'ransom') || str_contains($otxBlob, 'trojan')) {
                        $otxThemes[] = 'logiciels malveillants';
                    }
                    if (str_contains($otxBlob, 'scan') || str_contains($otxBlob, 'honeypot') || str_contains($otxBlob, 'port')) {
                        $otxThemes[] = 'reconnaissance ou scans automatiques (systèmes leurre)';
                    }
                    if (str_contains($otxBlob, 'spam') || str_contains($otxBlob, 'botnet')) {
                        $otxThemes[] = 'spam ou réseaux d’ordinateurs compromis';
                    }
                @endphp

                @if(is_array($otxGeneral))
                    <div class="th-otx-exec" role="region" aria-label="Synthèse pour la direction">
                        <div class="th-otx-exec-head">
                            <h3 class="th-otx-exec-title">Synthèse — lecture direction</h3>
                            <span class="th-otx-risk-badge th-otx-risk-{{ $otxRisk['level'] }}">{{ $otxRisk['label'] }}</span>
                        </div>
                        <p class="th-otx-exec-lead">
                            Cet élément est une <strong>{{ $otxIocLabel }}</strong>
                            (<code class="th-otx-exec-code">{{ data_get($otxGeneral, 'indicator', $otx['classified']['value'] ?? '—') }}</code>).
                            @if($otxPulseCount > 0)
                                Il apparaît dans <strong>{{ $otxPulseCount }}</strong> rapport{{ $otxPulseCount > 1 ? 's' : '' }} de menace partagé{{ $otxPulseCount > 1 ? 's' : '' }} par la communauté AlienVault OTX
                                (des « pulses », soit des dossiers qui décrivent des attaques ou des comportements suspects).
                                {{ $otxRisk['short'] }}
                            @else
                                Peu de rapports publics ne le citent pour l’instant ; cela ne veut pas dire « sans danger », seulement « moins documenté ».
                            @endif
                        </p>
                        @if(count($otxThemes) > 0)
                            <p class="th-otx-exec-themes">
                                <strong>Thèmes souvent évoqués dans ces rapports :</strong>
                                {{ implode(' · ', $otxThemes) }}.
                            </p>
                        @endif
                        <div class="th-otx-exec-columns">
                            <div class="th-otx-exec-col">
                                <h4 class="th-otx-exec-sub">Ce que ça veut dire pour l’entreprise</h4>
                                <ul class="th-otx-exec-ul">
                                    @if($otxPulseCount >= 15)
                                        <li><strong>Consensus fort :</strong> beaucoup d’acteurs indépendants ont identifié ce même élément dans des incidents — ce n’est pas une alerte isolée.</li>
                                    @elseif($otxPulseCount > 0)
                                        <li><strong>Signal crédible :</strong> l’élément est associé à des scénarios d’attaque documentés.</li>
                                    @else
                                        <li><strong>Visibilité limitée</strong> dans les bases communautaires ; l’équipe technique doit compléter avec vos propres journaux.</li>
                                    @endif
                                    <li>Les rapports OTX sont <strong>indicatifs</strong> : ils aident à prioriser, pas à remplacer une analyse interne.</li>
                                </ul>
                            </div>
                            <div class="th-otx-exec-col">
                                <h4 class="th-otx-exec-sub">Décisions / suites possibles</h4>
                                <ul class="th-otx-exec-ul">
                                    @if(in_array($otxRisk['level'], ['critical', 'high'], true))
                                        <li><strong>Bloquer</strong> cet élément aux pare-feu / accès réseau si ce n’est pas déjà fait.</li>
                                        <li><strong>Vérifier</strong> s’il apparaît dans vos connexions ou journaux récents.</li>
                                    @else
                                        <li><strong>Évaluer</strong> avec l’équipe sécurité avant tout blocage large (faux positifs possibles).</li>
                                    @endif
                                    <li><strong>Informer</strong> la direction / RSSI si l’élément touche un système sensible ou des données personnelles.</li>
                                    <li><strong>Conserver</strong> une trace (capture d’écran ou export) pour comité de crise ou audit.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="th-otx-readable">
                        <h3 class="th-otx-readable-title">Détail — équipe technique</h3>
                        <div class="th-otx-kv-grid">
                            <div class="th-otx-kv">
                                <span class="th-otx-k">Indicateur (IOC)</span>
                                <span class="th-otx-v"><code>{{ data_get($otxGeneral, 'indicator', $otx['classified']['value'] ?? '—') }}</code></span>
                            </div>
                            @if($otxRep !== null && $otxRep !== '')
                                <div class="th-otx-kv">
                                    <span class="th-otx-k">Réputation OTX</span>
                                    <span class="th-otx-v">
                                        <span class="th-otx-rep th-otx-rep-{{ (int) $otxRep >= 3 ? 'high' : ((int) $otxRep >= 1 ? 'mid' : 'low') }}">{{ $otxRep }}</span>
                                        <span class="th-otx-k-hint">(0 = peu signalé, plus haut = plus d’alertes communauté)</span>
                                    </span>
                                </div>
                            @endif
                            @if($urlWhois = data_get($otxGeneral, 'whois'))
                                <div class="th-otx-kv th-otx-kv-full">
                                    <span class="th-otx-k">WHOIS</span>
                                    <span class="th-otx-v">
                                        <a href="{{ $urlWhois }}" target="_blank" rel="noopener noreferrer" class="th-link">{{ Str::limit($urlWhois, 72) }}</a>
                                    </span>
                                </div>
                            @endif
                            @if($baseIndicator = data_get($otxGeneral, 'base_indicator'))
                                <div class="th-otx-kv th-otx-kv-full">
                                    <span class="th-otx-k">Métadonnées</span>
                                    <span class="th-otx-v th-otx-v-muted">{{ Str::limit(is_array($baseIndicator) ? json_encode($baseIndicator, JSON_UNESCAPED_UNICODE) : (string) $baseIndicator, 240) }}</span>
                                </div>
                            @endif
                        </div>

                        @if($otxPulseCount > 0 || count($otxPulses) > 0)
                            <h4 class="th-otx-pulses-title">Rapports communautaires ({{ $otxPulseCount ?: count($otxPulses) }})</h4>
                            <p class="th-otx-pulses-intro">Chaque ligne est un <strong>rapport de menace</strong> publié par la communauté ; la colonne « Contexte » résume pourquoi cet indicateur y figure.</p>
                            <div class="table-container th-otx-table-wrap">
                                <table class="data-table th-otx-pulse-table">
                                    <thead>
                                        <tr>
                                            <th>Titre du rapport</th>
                                            <th>Contexte (résumé)</th>
                                            <th>Publié</th>
                                            <th>Mis à jour</th>
                                            <th>Lien</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($otxPulses, 0, 40) as $pulse)
                                            @if(is_array($pulse))
                                                <tr>
                                                    <td class="th-otx-pulse-name">{{ Str::limit($pulse['name'] ?? '—', 80) }}</td>
                                                    <td class="th-otx-pulse-desc">{{ Str::limit(strip_tags($pulse['description'] ?? ''), 160) }}</td>
                                                    <td class="th-otx-pulse-date">{{ $pulse['created'] ?? '—' }}</td>
                                                    <td class="th-otx-pulse-date">{{ $pulse['modified'] ?? '—' }}</td>
                                                    <td>
                                                        @if(!empty($pulse['id']))
                                                            <a href="https://otx.alienvault.com/pulse/{{ $pulse['id'] }}" target="_blank" rel="noopener noreferrer" class="th-link">Voir le rapport →</a>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($otxPulses) > 40)
                                <p class="th-otx-pulses-more">Les 40 premiers pulses sont affichés ; le détail complet reste dans la réponse brute ci-dessous.</p>
                            @endif
                        @else
                            <p class="th-otx-empty-pulses">Aucun pulse listé dans <code>pulse_info</code> pour cet indicateur (réponse vide ou IOC peu documenté).</p>
                        @endif
                    </div>
                @endif

                @php
                    $otxRepSec = $otx['sections']['reputation'] ?? null;
                @endphp
                @if(is_array($otxRepSec) && count($otxRepSec) > 0)
                    <div class="th-otx-readable th-otx-readable-secondary">
                        <h4 class="th-otx-readable-title">Réputation (section dédiée)</h4>
                        <ul class="th-otx-rep-list">
                            @foreach($otxRepSec as $rk => $rv)
                                <li><strong>{{ $rk }}</strong> : @if(is_array($rv))<code>{{ json_encode($rv, JSON_UNESCAPED_UNICODE) }}</code>@else{{ $rv }}@endif</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <h4 class="th-otx-raw-title">Données techniques (JSON)</h4>
                <p class="th-otx-raw-hint">Réservé aux équipes IT / sécurité — réponse brute de l’API.</p>
                @foreach($otx['sections'] ?? [] as $secName => $secPayload)
                    <details class="th-otx-sec">
                        <summary>Section <code>{{ $secName }}</code>
                            @if(isset($otx['section_http'][$secName]))
                                (HTTP {{ $otx['section_http'][$secName] }})
                            @endif
                        </summary>
                        <pre class="th-tm-json">{{ json_encode($secPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endforeach
            @endif
        </section>
    @endif

    <section class="th-shortcuts">
        <h2 class="th-panel-title">Raccourcis investigation</h2>
        <div class="th-cards">
            <a class="th-card" href="{{ route('detection.blocked-ips') }}">
                <span class="th-card-icon">🚫</span>
                <span class="th-card-title">Blocked IPs</span>
                <span class="th-card-desc">Blocs manuels et automatiques</span>
            </a>
            <a class="th-card" href="{{ route('responses.index') }}">
                <span class="th-card-icon">⚡</span>
                <span class="th-card-title">Responses</span>
                <span class="th-card-desc">Playbooks et actions auto</span>
            </a>
            <a class="th-card" href="{{ route('agents.index') }}">
                <span class="th-card-icon">📡</span>
                <span class="th-card-title">Agents</span>
                <span class="th-card-desc">Journaux par hôte</span>
            </a>
            <a class="th-card" href="{{ route('detection.rules') }}">
                <span class="th-card-icon">📋</span>
                <span class="th-card-title">Detection rules</span>
                <span class="th-card-desc">MITRE, seuils, catégories</span>
            </a>
        </div>
    </section>
</div>

@push('styles')
<style>
    .th-page .th-tagline { color: #64748b; font-size: 0.88rem; margin-top: 0.35rem; }
    .th-header { align-items: flex-start; flex-wrap: wrap; gap: 1rem; }
    .th-header-links { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .th-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid #334155;
        border-radius: 12px;
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.5rem;
    }
    .th-label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 0.5rem; }
    .th-search-row { display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: stretch; }
    .th-input {
        flex: 1 1 220px;
        min-width: 0;
        padding: 0.65rem 0.85rem;
        border-radius: 8px;
        border: 1px solid #475569;
        background: #0f172a;
        color: #f1f5f9;
        font-size: 0.95rem;
    }
    .th-input:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2); }
    .th-select {
        padding: 0.65rem 0.75rem;
        border-radius: 8px;
        border: 1px solid #475569;
        background: #1e293b;
        color: #e2e8f0;
        font-size: 0.85rem;
    }
    .th-submit { padding-left: 1.25rem; padding-right: 1.25rem; }
    .th-hint { margin: 0.75rem 0 0; font-size: 0.78rem; color: #64748b; line-height: 1.45; }
    .th-hint a { color: #38bdf8; }
    .th-tm-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem 1.25rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #334155;
    }
    .th-tm-check {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: #cbd5e1;
        cursor: pointer;
    }
    .th-tm-check input { width: 1rem; height: 1rem; accent-color: #38bdf8; }
    .th-tm-rt { min-width: 220px; }
    .th-oc-row { border-top-color: #3d4f6a; }
    .th-oc-first-wrap {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        color: #94a3b8;
    }
    .th-oc-first-label { white-space: nowrap; }
    .th-oc-first-input {
        width: 4.5rem;
        padding: 0.45rem 0.5rem;
        border-radius: 6px;
        border: 1px solid #475569;
        background: #0f172a;
        color: #e2e8f0;
        font-size: 0.85rem;
    }
    .th-opencti {
        margin-bottom: 2rem;
        padding: 1.25rem 1.35rem;
        background: linear-gradient(145deg, #1f1815 0%, #0f172a 100%);
        border: 1px solid #7c2d12;
        border-radius: 12px;
    }
    .th-oc-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.85rem; }
    .th-oc-title { margin: 0; font-size: 1.05rem; color: #fed7aa; }
    .th-oc-alert {
        padding: 0.85rem 1rem;
        border-radius: 8px;
        font-size: 0.88rem;
        line-height: 1.5;
        color: #e2e8f0;
        background: rgba(249, 115, 22, 0.1);
        border: 1px solid rgba(249, 115, 22, 0.35);
    }
    .th-oc-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        font-size: 0.8rem;
        color: #94a3b8;
        margin-bottom: 0.85rem;
    }
    .th-oc-cached { color: #86efac; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; }
    .th-oc-pre-inline {
        margin: 0.5rem 0 0;
        padding: 0.65rem;
        font-size: 0.72rem;
        background: #0b1220;
        border-radius: 6px;
        overflow: auto;
        max-height: 200px;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .th-oc-hint { margin: 0.5rem 0 0; font-size: 0.78rem; color: #94a3b8; }
    .th-oc-raw { margin-top: 1rem; font-size: 0.8rem; color: #94a3b8; }
    .th-oc-raw summary { cursor: pointer; margin-bottom: 0.5rem; }
    .th-otx-exec {
        margin-bottom: 1.25rem;
        padding: 1.15rem 1.35rem;
        background: linear-gradient(160deg, #1a2e24 0%, #0f172a 100%);
        border: 1px solid #2d5a45;
        border-radius: 12px;
        border-left: 4px solid #34d399;
    }
    .th-otx-exec-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }
    .th-otx-exec-title { margin: 0; font-size: 1.05rem; color: #ecfdf5; font-weight: 700; }
    .th-otx-risk-badge {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
    }
    .th-otx-risk-critical { background: rgba(239, 68, 68, 0.25); color: #fecaca; border: 1px solid rgba(239, 68, 68, 0.45); }
    .th-otx-risk-high { background: rgba(249, 115, 22, 0.2); color: #fdba74; border: 1px solid rgba(249, 115, 22, 0.4); }
    .th-otx-risk-medium { background: rgba(234, 179, 8, 0.15); color: #fde047; border: 1px solid rgba(234, 179, 8, 0.35); }
    .th-otx-risk-low { background: rgba(59, 130, 246, 0.15); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.35); }
    .th-otx-risk-minimal { background: rgba(100, 116, 139, 0.25); color: #cbd5e1; border: 1px solid rgba(100, 116, 139, 0.4); }
    .th-otx-exec-lead {
        margin: 0 0 0.85rem;
        font-size: 0.95rem;
        line-height: 1.65;
        color: #e2e8f0;
    }
    .th-otx-exec-code { font-size: 0.88em; color: #a7f3d0; }
    .th-otx-exec-themes {
        margin: 0 0 1rem;
        padding: 0.65rem 0.85rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        font-size: 0.88rem;
        line-height: 1.5;
        color: #d1fae5;
    }
    .th-otx-exec-columns {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.25rem;
    }
    .th-otx-exec-sub {
        margin: 0 0 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #6ee7b7;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .th-otx-exec-ul {
        margin: 0;
        padding-left: 1.15rem;
        color: #cbd5e1;
        font-size: 0.86rem;
        line-height: 1.55;
    }
    .th-otx-exec-ul li { margin-bottom: 0.4rem; }
    .th-otx-row { border-top-color: #14532d; }
    .th-otx-ext { font-size: 0.8rem; color: #94a3b8; }
    .th-otx {
        margin-bottom: 2rem;
        padding: 1.25rem 1.35rem;
        background: linear-gradient(145deg, #0f1f14 0%, #0f172a 100%);
        border: 1px solid #166534;
        border-radius: 12px;
    }
    .th-otx-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.85rem; }
    .th-otx-title { margin: 0; font-size: 1.05rem; color: #86efac; }
    .th-otx-alert {
        padding: 0.85rem 1rem;
        border-radius: 8px;
        font-size: 0.88rem;
        line-height: 1.5;
        color: #e2e8f0;
        background: rgba(34, 197, 94, 0.08);
        border: 1px solid rgba(34, 197, 94, 0.35);
    }
    .th-otx-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 1rem;
        font-size: 0.8rem;
        color: #94a3b8;
        margin-bottom: 0.65rem;
    }
    .th-otx-cached { color: #86efac; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; }
    .th-otx-summary { font-size: 0.82rem; color: #cbd5e1; margin: 0 0 0.75rem; }
    .th-otx-readable {
        margin-bottom: 1.25rem;
        padding: 1rem 1.15rem;
        background: rgba(15, 23, 42, 0.65);
        border: 1px solid #1e3a2f;
        border-radius: 10px;
    }
    .th-otx-readable-secondary { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #1e3a2f; }
    .th-otx-readable-title { margin: 0 0 0.85rem; font-size: 0.95rem; color: #ecfdf5; font-weight: 600; }
    .th-otx-kv-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.65rem 1.25rem;
        margin-bottom: 1rem;
    }
    .th-otx-kv { display: flex; flex-direction: column; gap: 0.2rem; }
    .th-otx-kv-full { grid-column: 1 / -1; }
    .th-otx-k { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; }
    .th-otx-v { font-size: 0.88rem; color: #e2e8f0; word-break: break-word; }
    .th-otx-v-muted { font-size: 0.8rem; color: #94a3b8; }
    .th-otx-k-hint { display: block; font-size: 0.72rem; color: #64748b; font-weight: 400; margin-top: 0.25rem; }
    .th-otx-rep {
        display: inline-block;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: 6px;
        font-size: 0.9rem;
    }
    .th-otx-rep-low { background: rgba(34, 197, 94, 0.2); color: #86efac; }
    .th-otx-rep-mid { background: rgba(234, 179, 8, 0.2); color: #fde047; }
    .th-otx-rep-high { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
    .th-otx-pulses-title { margin: 1rem 0 0.35rem; font-size: 0.88rem; color: #a7f3d0; }
    .th-otx-pulses-intro { margin: 0 0 0.65rem; font-size: 0.78rem; color: #64748b; }
    .th-otx-table-wrap { margin-top: 0.25rem; }
    .th-otx-pulse-table { font-size: 0.8rem; }
    .th-otx-pulse-name { font-weight: 600; color: #f1f5f9; max-width: 14rem; }
    .th-otx-pulse-desc { color: #94a3b8; line-height: 1.4; max-width: 28rem; }
    .th-otx-pulse-date { color: #64748b; white-space: nowrap; font-size: 0.75rem; }
    .th-otx-pulses-more, .th-otx-empty-pulses { font-size: 0.78rem; color: #64748b; margin: 0.5rem 0 0; }
    .th-otx-rep-list { margin: 0; padding-left: 1.1rem; color: #cbd5e1; font-size: 0.82rem; line-height: 1.55; }
    .th-otx-raw-title { margin: 1.25rem 0 0.25rem; font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
    .th-otx-raw-hint { margin: 0 0 0.65rem; font-size: 0.75rem; color: #475569; }
    .th-otx-sec { margin-bottom: 0.65rem; font-size: 0.82rem; color: #94a3b8; }
    .th-otx-sec summary { cursor: pointer; margin-bottom: 0.35rem; }
    .th-threatminer {
        margin-bottom: 2rem;
        padding: 1.25rem 1.35rem;
        background: linear-gradient(145deg, #1a2744 0%, #0f172a 100%);
        border: 1px solid #3b5998;
        border-radius: 12px;
    }
    .th-tm-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.85rem; }
    .th-tm-title { margin: 0; font-size: 1.05rem; color: #e8f0fe; }
    .th-tm-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        font-size: 0.8rem;
        color: #94a3b8;
        margin-bottom: 0.65rem;
    }
    .th-tm-cached { color: #86efac; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; }
    .th-tm-api-status { font-size: 0.78rem; color: #94a3b8; margin: 0 0 0.75rem; line-height: 1.45; }
    .th-tm-json {
        margin: 0;
        padding: 1rem;
        max-height: 420px;
        overflow: auto;
        font-size: 0.72rem;
        line-height: 1.4;
        background: #0b1220;
        border: 1px solid #334155;
        border-radius: 8px;
        color: #a5d6ff;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .th-tm-alert {
        padding: 0.85rem 1rem;
        border-radius: 8px;
        font-size: 0.88rem;
        line-height: 1.5;
        color: #e2e8f0;
    }
    .th-tm-alert-warn {
        background: rgba(234, 179, 8, 0.12);
        border: 1px solid rgba(234, 179, 8, 0.35);
    }
    .th-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    .th-stat {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 10px;
        padding: 0.85rem 1rem;
    }
    .th-stat-warn { border-color: rgba(249, 115, 22, 0.35); }
    .th-stat-label { display: block; font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; margin-bottom: 0.25rem; }
    .th-stat strong { font-size: 1.35rem; color: #f8fafc; }
    .th-grid-2 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .th-panel {
        background: #162032;
        border: 1px solid #2d3b50;
        border-radius: 10px;
        padding: 1rem 1.15rem;
    }
    .th-panel-title { font-size: 0.95rem; color: #e2e8f0; margin: 0 0 0.85rem; font-weight: 600; }
    .th-empty, .th-empty-block { color: #64748b; font-size: 0.88rem; margin: 0; }
    .th-empty-block { padding: 1.5rem; text-align: center; background: #1e293b; border-radius: 8px; }
    .th-mini-table { width: 100%; font-size: 0.85rem; border-collapse: collapse; }
    .th-mini-table th { text-align: left; color: #94a3b8; font-weight: 500; padding: 0.35rem 0; border-bottom: 1px solid #334155; }
    .th-mini-table td { padding: 0.45rem 0; border-bottom: 1px solid #1e293b; color: #cbd5e1; }
    .th-link { color: #38bdf8; font-size: 0.8rem; text-decoration: none; }
    .th-link:hover { text-decoration: underline; }
    .th-alert-list { list-style: none; margin: 0; padding: 0; }
    .th-alert-list li { display: flex; flex-direction: column; gap: 0.2rem; padding: 0.5rem 0; border-bottom: 1px solid #1e293b; }
    .th-alert-link { color: #e2e8f0; text-decoration: none; font-size: 0.86rem; line-height: 1.35; }
    .th-alert-link:hover { color: #38bdf8; }
    .th-muted { font-size: 0.72rem; color: #64748b; }
    .severity-pill {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.15rem 0.4rem;
        border-radius: 4px;
        margin-right: 0.35rem;
    }
    .sev-critical { background: rgba(239, 68, 68, 0.25); color: #fca5a5; }
    .sev-high { background: rgba(249, 115, 22, 0.2); color: #fdba74; }
    .sev-medium { background: rgba(234, 179, 8, 0.15); color: #fde047; }
    .sev-low { background: rgba(34, 197, 94, 0.15); color: #86efac; }
    .th-results { margin-top: 0.5rem; margin-bottom: 2rem; }
    .th-results-head { display: flex; flex-wrap: wrap; align-items: baseline; gap: 0.75rem; margin-bottom: 1rem; }
    .th-results-head h2 { margin: 0; font-size: 1.1rem; color: #f1f5f9; }
    .th-badge { font-size: 0.75rem; color: #94a3b8; background: #1e293b; padding: 0.25rem 0.55rem; border-radius: 6px; }
    .th-sub { font-size: 0.88rem; color: #94a3b8; margin: 1.25rem 0 0.5rem; font-weight: 600; }
    .th-msg { font-size: 0.8rem; color: #94a3b8; max-width: 32rem; }
    .th-shortcuts { margin-bottom: 2rem; }
    .th-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; }
    .th-card {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 1rem 1.1rem;
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 10px;
        text-decoration: none;
        color: inherit;
        transition: border-color 0.15s, background 0.15s;
    }
    .th-card:hover { border-color: #38bdf8; background: #243447; }
    .th-card-icon { font-size: 1.25rem; }
    .th-card-title { font-weight: 600; color: #f1f5f9; font-size: 0.9rem; }
    .th-card-desc { font-size: 0.78rem; color: #64748b; line-height: 1.35; }
    .btn-secondary {
        background: #334155;
        color: #e2e8f0;
        border: 1px solid #475569;
    }
    .btn-secondary:hover { background: #475569; }
</style>
@endpush
@endsection
