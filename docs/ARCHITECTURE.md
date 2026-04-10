# Architecture Wara XDR

## Diagramme de flux

```
                                    ┌─────────────────────────────────────┐
                                    │         Wara XDR SERVER           │
                                    │        (korashield.online)          │
                                    │                                     │
                                    │  ┌───────────────────────────────┐  │
                                    │  │        Laravel Backend        │  │
                                    │  │                               │  │
                                    │  │  ┌─────────┐  ┌────────────┐  │  │
                                    │  │  │ Routes  │  │Controllers │  │  │
                                    │  │  │  API    │──│   Agent    │  │  │
                                    │  │  └─────────┘  └─────┬──────┘  │  │
                                    │  │                     │         │  │
                                    │  │              ┌──────▼──────┐  │  │
                                    │  │              │ LogAnalyzer │  │  │
                                    │  │              │  (Service)  │  │  │
                                    │  │              └──────┬──────┘  │  │
                                    │  │                     │         │  │
                                    │  │  ┌─────────────┐    │         │  │
                                    │  │  │ Detection   │◄───┘         │  │
                                    │  │  │   Rules     │              │  │
                                    │  │  └─────────────┘              │  │
                                    │  │         │                     │  │
                                    │  │         ▼                     │  │
                                    │  │  ┌─────────────┐              │  │
                                    │  │  │  Security   │              │  │
                                    │  │  │   Alerts    │              │  │
                                    │  │  └─────────────┘              │  │
                                    │  └───────────────────────────────┘  │
                                    │                                     │
                                    │  ┌───────────────────────────────┐  │
                                    │  │          MySQL DB             │  │
                                    │  │  ┌─────────┐ ┌─────────────┐  │  │
                                    │  │  │ agents  │ │ agent_logs  │  │  │
                                    │  │  ├─────────┤ ├─────────────┤  │  │
                                    │  │  │ assets  │ │security_    │  │  │
                                    │  │  │         │ │alerts       │  │  │
                                    │  │  ├─────────┤ ├─────────────┤  │  │
                                    │  │  │ tenant_ │ │detection_   │  │  │
                                    │  │  │ groups  │ │rules        │  │  │
                                    │  │  └─────────┘ └─────────────┘  │  │
                                    │  └───────────────────────────────┘  │
                                    └──────────────────▲──────────────────┘
                                                       │
                                                       │ HTTPS
                                                       │ POST /api/agent/logs
                                                       │
                    ┌──────────────────────────────────┼──────────────────────────────────┐
                    │                                  │                                  │
           ┌────────┴────────┐                ┌────────┴────────┐                ┌────────┴────────┐
           │  AGENT LINUX    │                │  AGENT LINUX    │                │  AGENT LINUX    │
           │   (client1)     │                │   (server2)     │                │   (serverN)     │
           │                 │                │                 │                │                 │
           │ ┌─────────────┐ │                │ ┌─────────────┐ │                │ ┌─────────────┐ │
           │ │ xdr-agent.sh│ │                │ │ xdr-agent.sh│ │                │ │ xdr-agent.sh│ │
           │ │  (systemd)  │ │                │ │  (systemd)  │ │                │ │  (systemd)  │ │
           │ └──────┬──────┘ │                │ └──────┬──────┘ │                │ └──────┬──────┘ │
           │        │        │                │        │        │                │        │        │
           │        ▼        │                │        ▼        │                │        ▼        │
           │ ┌─────────────┐ │                │ ┌─────────────┐ │                │ ┌─────────────┐ │
           │ │/var/log/    │ │                │ │/var/log/    │ │                │ │/var/log/    │ │
           │ │ auth.log    │ │                │ │ auth.log    │ │                │ │ auth.log    │ │
           │ │ syslog      │ │                │ │ syslog      │ │                │ │ syslog      │ │
           │ └─────────────┘ │                │ └─────────────┘ │                │ └─────────────┘ │
           └─────────────────┘                └─────────────────┘                └─────────────────┘
```

## Flux de données

### 1. Collecte des logs

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ /var/log/   │     │ xdr-agent   │     │   Queue     │
│ auth.log    │────▶│   .sh       │────▶│   JSON      │
│ syslog      │     │             │     │   files     │
└─────────────┘     └─────────────┘     └─────────────┘
```

### 2. Envoi au serveur

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Queue     │     │   HTTPS     │     │  API        │
│   JSON      │────▶│   POST      │────▶│  /agent/    │
│   files     │     │             │     │  logs       │
└─────────────┘     └─────────────┘     └─────────────┘
```

### 3. Analyse et détection

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Received   │     │    Log      │     │  Security   │
│    Logs     │────▶│  Analyzer   │────▶│   Alert     │
│             │     │             │     │             │
└─────────────┘     └─────────────┘     └─────────────┘
                           │
                           ▼
                    ┌─────────────┐
                    │ Detection   │
                    │   Rules     │
                    │  (23 rules) │
                    └─────────────┘
```

## Structure de la base de données

### Tables principales

```
┌─────────────────┐       ┌─────────────────┐
│  tenant_groups  │       │     agents      │
├─────────────────┤       ├─────────────────┤
│ id              │◄──────│ tenant_group_id │
│ name            │       │ id              │
│ slug            │       │ agent_id        │
│ parent_id       │       │ name            │
│ description     │       │ hostname        │
└─────────────────┘       │ ip_address      │
                          │ api_key         │
                          │ status          │
                          └────────┬────────┘
                                   │
                                   ▼
┌─────────────────┐       ┌─────────────────┐
│ detection_rules │       │   agent_logs    │
├─────────────────┤       ├─────────────────┤
│ id              │       │ id              │
│ name            │       │ agent_id (FK)   │
│ slug            │       │ log_type        │
│ category        │       │ message         │
│ severity        │       │ severity        │
│ conditions      │       │ hostname        │
│ is_active       │       │ log_timestamp   │
└────────┬────────┘       └─────────────────┘
         │
         ▼
┌─────────────────┐
│ security_alerts │
├─────────────────┤
│ id              │
│ detection_rule_ │
│   id (FK)       │
│ title           │
│ description     │
│ severity        │
│ status          │
│ source_ip       │
│ event_count     │
│ first_seen      │
│ last_seen       │
└─────────────────┘
```

## Composants

### Backend (Laravel)

| Composant | Chemin | Description |
|-----------|--------|-------------|
| AgentApiController | `app/Http/Controllers/Api/` | API pour les agents |
| LogAnalyzer | `app/Services/` | Moteur de détection |
| Agent Model | `app/Models/` | Modèle agent |
| AgentLog Model | `app/Models/` | Modèle logs |
| SecurityAlert Model | `app/Models/` | Modèle alertes |
| DetectionRule Model | `app/Models/` | Modèle règles |

### Agent Linux

| Fichier | Chemin | Description |
|---------|--------|-------------|
| config.conf | `/opt/athena-xdr/` | Configuration |
| xdr-agent.sh | `/opt/athena-xdr/` | Script principal |
| agent.log | `/opt/athena-xdr/logs/` | Logs de l'agent |
| queue/ | `/opt/athena-xdr/` | Files d'attente |
| state/ | `/opt/athena-xdr/` | État des fichiers lus |

### Service systemd

```ini
[Unit]
Description=Wara XDR Log Collection Agent
After=network.target

[Service]
Type=simple
ExecStart=/bin/bash /opt/athena-xdr/xdr-agent.sh
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

## Sécurité

### Authentification des agents

- Chaque agent possède un `agent_id` unique (format: `AGT-XXXXXXXX`)
- Chaque agent possède une `api_key` secrète
- Les deux sont transmis dans les headers HTTP

### Communication

- HTTPS obligatoire en production
- Certificat SSL valide requis
- Fallback HTTP pour les tests locaux

### Stockage des secrets

- Les clés API sont hashées en base de données
- La configuration locale est en mode 600 (lecture root uniquement)
