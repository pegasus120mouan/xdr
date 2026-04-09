# Athena XDR - Documentation Technique

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Installation](#installation)
4. [Déploiement d'agents](#déploiement-dagents)
5. [Règles de détection](#règles-de-détection)
6. [API Reference](#api-reference)
7. [Gestion des tenants](#gestion-des-tenants)
8. [Alertes de sécurité](#alertes-de-sécurité)

---

## Vue d'ensemble

**Athena XDR** est une plateforme de détection et réponse étendue (Extended Detection and Response) qui permet de :

- 🔍 **Collecter** les logs de serveurs Linux/Windows via des agents
- 🛡️ **Détecter** les menaces en temps réel avec des règles personnalisables
- 🚨 **Alerter** sur les incidents de sécurité
- 📊 **Visualiser** l'état de sécurité de votre infrastructure

### Fonctionnalités principales

| Fonctionnalité | Description |
|----------------|-------------|
| **Multi-tenant** | Gestion de plusieurs groupes/clients |
| **Agents Linux** | Collecte automatique des logs système |
| **Détection en temps réel** | Analyse des logs avec règles MITRE ATT&CK |
| **Brute Force Detection** | Protection contre les attaques par force brute |
| **Dashboard** | Tableau de bord avec métriques de sécurité |

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        ATHENA XDR SERVER                        │
│                      (korashield.online)                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │   Laravel   │  │   MySQL     │  │     LogAnalyzer         │ │
│  │   Backend   │──│   Database  │──│   (Detection Engine)    │ │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘ │
│         │                                      │                │
│         ▼                                      ▼                │
│  ┌─────────────┐                    ┌─────────────────────────┐ │
│  │  REST API   │                    │   Security Alerts       │ │
│  │  /api/agent │                    │   Dashboard             │ │
│  └─────────────┘                    └─────────────────────────┘ │
│         ▲                                                       │
└─────────│───────────────────────────────────────────────────────┘
          │
          │ HTTPS (Port 443)
          │
┌─────────┴─────────┐     ┌───────────────────┐     ┌─────────────┐
│   Agent Linux     │     │   Agent Linux     │     │   Agent     │
│   (client1)       │     │   (server2)       │     │   (serverN) │
│   10.10.0.108     │     │   192.168.1.x     │     │   x.x.x.x   │
└───────────────────┘     └───────────────────┘     └─────────────┘
```

### Stack technique

- **Backend** : Laravel 11 (PHP 8.2+)
- **Base de données** : MySQL 8.0
- **Frontend** : Blade + TailwindCSS
- **Agents** : Bash scripts + systemd
- **Déploiement** : GitHub Actions + VPS

---

## Installation

### Prérequis serveur

```bash
# PHP 8.2+ avec extensions
sudo apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# MySQL
sudo apt install mysql-server
```

### Installation de l'application

```bash
# Cloner le repository
git clone https://github.com/pegasus120mouan/xdr.git
cd xdr

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Configurer la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xdr
DB_USERNAME=root
DB_PASSWORD=your_password

# Exécuter les migrations
php artisan migrate

# Créer les règles de détection
php artisan db:seed --class=DetectionRulesSeeder

# Créer un utilisateur admin
php artisan db:seed --class=DatabaseSeeder
```

### Identifiants par défaut

| Champ | Valeur |
|-------|--------|
| Email | `admin@athena-xdr.local` |
| Password | `password` |

---

## Déploiement d'agents

### Méthode 1 : Via l'interface web (recommandé)

1. Connectez-vous à Athena XDR
2. Allez dans **All Tenants**
3. Sélectionnez un groupe (ex: "Fisher")
4. Cliquez sur **"⊕ Deploy new agent"**
5. Entrez l'adresse du serveur XDR
6. Copiez le script généré
7. Exécutez-le sur votre serveur Linux

### Méthode 2 : Ligne de commande

```bash
# Sur le serveur Linux à monitorer
curl -ksS https://korashield.online/api/agent/install.sh -o athena-xdr-agent.sh \
&& sudo XDR_MANAGER='korashield.online' \
XDR_AGENT_GROUP='NomDuGroupe' \
XDR_AGENT_NAME='nom-serveur' \
bash ./athena-xdr-agent.sh
```

### Variables d'environnement

| Variable | Description | Obligatoire |
|----------|-------------|-------------|
| `XDR_MANAGER` | Adresse du serveur XDR | ✅ Oui |
| `XDR_AGENT_GROUP` | Nom du groupe/tenant | ✅ Oui |
| `XDR_AGENT_NAME` | Nom personnalisé de l'agent | ❌ Non |

### Vérifier l'installation

```bash
# Statut du service
sudo systemctl status athena-xdr-agent

# Logs de l'agent
cat /opt/athena-xdr/logs/agent.log

# Configuration
cat /opt/athena-xdr/config.conf
```

### Désinstallation

```bash
sudo systemctl stop athena-xdr-agent
sudo systemctl disable athena-xdr-agent
sudo rm /etc/systemd/system/athena-xdr-agent.service
sudo rm -rf /opt/athena-xdr
sudo systemctl daemon-reload
```

---

## Règles de détection

### Catégories de règles

| Catégorie | Description |
|-----------|-------------|
| `authentication` | Tentatives de connexion |
| `brute_force` | Attaques par force brute |
| `privilege_escalation` | Élévation de privilèges |
| `persistence` | Mécanismes de persistance |
| `malware` | Activités malveillantes |
| `system` | Changements système |
| `web` | Attaques web (SQLi, XSS, etc.) |

### Règles incluses

#### Authentification SSH
| Règle | Sévérité | Description |
|-------|----------|-------------|
| SSH Failed Login | Medium | Tentative de connexion SSH échouée |
| SSH Brute Force | High | Multiples échecs de connexion SSH |
| Invalid User SSH | High | Connexion avec utilisateur inexistant |
| Root Login Success | High | Connexion root réussie |

#### Élévation de privilèges
| Règle | Sévérité | Description |
|-------|----------|-------------|
| Sudo Command | Low | Exécution de commande sudo |
| Sudo Auth Failure | Medium | Échec d'authentification sudo |
| Sudoers Modified | Critical | Modification du fichier sudoers |

#### Activités malveillantes
| Règle | Sévérité | Description |
|-------|----------|-------------|
| Suspicious Command | Critical | Commandes potentiellement malveillantes |
| Reverse Shell | Critical | Détection de reverse shell |
| Cron Modified | Medium | Modification des tâches cron |

#### Attaques Web
| Règle | Sévérité | Description |
|-------|----------|-------------|
| SQL Injection | High | Tentative d'injection SQL |
| Path Traversal | High | Tentative de traversée de répertoire |
| Web Error Spike | Medium | Pic d'erreurs serveur web |

### Créer une règle personnalisée

```php
// Dans database/seeders/DetectionRulesSeeder.php
[
    'name' => 'Ma Règle Personnalisée',
    'slug' => 'ma-regle-custom',
    'description' => 'Description de la règle',
    'category' => 'authentication',
    'severity' => 'high', // critical, high, medium, low
    'conditions' => [
        'log_types' => ['auth', 'syslog'],
        'patterns' => ['pattern1', 'pattern2'],
        'match_type' => 'any', // 'any' ou 'all'
    ],
    'actions' => [
        ['type' => 'log'],
        ['type' => 'alert'],
        ['type' => 'notify'],
    ],
    'threshold' => 1,
    'time_window' => 60,
    'cooldown' => 300,
    'is_active' => true,
    'mitre_tactics' => ['TA0006'],
    'mitre_techniques' => ['T1110'],
],
```

---

## API Reference

### Authentification

Les agents s'authentifient via headers HTTP :

```
X-Agent-ID: AGT-XXXXXXXX
X-API-Key: votre_api_key
```

### Endpoints

#### POST /api/agent/register

Enregistre un nouvel agent.

**Request:**
```json
{
    "hostname": "server1",
    "ip_address": "192.168.1.100",
    "os_type": "linux",
    "os_version": "Ubuntu 24.04",
    "group_name": "Production",
    "agent_name": "web-server-01"
}
```

**Response:**
```json
{
    "status": "ok",
    "agent_id": "AGT-ABC123XYZ",
    "api_key": "sk_live_xxxxxxxxxxxxx",
    "message": "Agent registered successfully"
}
```

#### POST /api/agent/heartbeat

Envoie un heartbeat pour indiquer que l'agent est actif.

**Headers:**
```
X-Agent-ID: AGT-ABC123XYZ
X-API-Key: sk_live_xxxxxxxxxxxxx
```

**Request:**
```json
{
    "agent_id": "AGT-ABC123XYZ",
    "hostname": "server1",
    "ip_address": "192.168.1.100",
    "os_type": "linux",
    "os_version": "6.8.0-107-generic"
}
```

#### POST /api/agent/logs

Envoie des logs au serveur XDR.

**Request:**
```json
{
    "agent_id": "AGT-ABC123XYZ",
    "hostname": "server1",
    "ip_address": "192.168.1.100",
    "logs": [
        {
            "log_type": "auth",
            "message": "Failed password for invalid user admin from 192.168.1.50",
            "severity": "error",
            "hostname": "server1",
            "log_timestamp": "2026-04-09 20:15:00"
        }
    ]
}
```

**Response:**
```json
{
    "status": "ok",
    "message": "Received 1 logs, 1 alerts created",
    "logs_processed": 1,
    "alerts_created": 1
}
```

#### GET /api/agent/install.sh

Télécharge le script d'installation de l'agent.

---

## Gestion des tenants

### Structure hiérarchique

```
All Tenants
├── Production
│   ├── Web Servers
│   └── Database Servers
├── Development
│   └── Dev Machines
└── Clients
    ├── Client A
    └── Client B
```

### Créer un groupe

1. Allez dans **All Tenants**
2. Cliquez sur **"+ Add Group"**
3. Entrez le nom du groupe
4. Sélectionnez le groupe parent (optionnel)

### Déplacer un asset

1. Cliquez sur l'icône de déplacement (📁) sur l'asset
2. Sélectionnez le nouveau groupe
3. Confirmez

---

## Alertes de sécurité

### Statuts des alertes

| Statut | Description |
|--------|-------------|
| `new` | Nouvelle alerte, non traitée |
| `investigating` | En cours d'investigation |
| `resolved` | Résolue |
| `false_positive` | Faux positif |
| `escalated` | Escaladée |

### Workflow de traitement

```
┌─────────┐     ┌──────────────┐     ┌──────────┐
│   New   │────▶│ Investigating│────▶│ Resolved │
└─────────┘     └──────────────┘     └──────────┘
     │                 │
     │                 ▼
     │          ┌──────────────┐
     └─────────▶│False Positive│
                └──────────────┘
```

### Mapping MITRE ATT&CK

Chaque règle est mappée aux tactiques et techniques MITRE ATT&CK :

- **TA0001** - Initial Access
- **TA0002** - Execution
- **TA0003** - Persistence
- **TA0004** - Privilege Escalation
- **TA0005** - Defense Evasion
- **TA0006** - Credential Access
- **TA0011** - Command and Control

---

## Dépannage

### L'agent ne démarre pas

```bash
# Vérifier les logs systemd
journalctl -u athena-xdr-agent -n 50

# Vérifier les permissions
ls -la /opt/athena-xdr/

# Tester manuellement
sudo bash /opt/athena-xdr/xdr-agent.sh
```

### Les alertes ne sont pas créées

1. Vérifiez que les règles de détection existent :
```bash
php artisan tinker --execute="echo App\Models\DetectionRule::count().' rules'"
```

2. Vérifiez les logs de l'agent :
```bash
cat /opt/athena-xdr/logs/agent.log
```

3. Testez manuellement le LogAnalyzer :
```bash
php artisan tinker
```
```php
$agent = App\Models\Agent::first();
$analyzer = new App\Services\LogAnalyzer();
$testLog = [['message' => 'Failed password for root', 'log_type' => 'auth']];
$alerts = $analyzer->analyzeLogs($agent, $testLog);
echo count($alerts) . ' alerts created';
```

### L'agent n'envoie pas les logs

1. Vérifiez la connectivité :
```bash
curl -ksS https://korashield.online/api/agent/install.sh | head -5
```

2. Vérifiez la configuration :
```bash
cat /opt/athena-xdr/config.conf
```

---

## Licence

MIT License - Voir [LICENSE](../LICENSE)

## Support

Pour toute question ou problème, ouvrez une issue sur GitHub.
