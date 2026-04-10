# Wara XDR

**Extended Detection and Response Platform**

## Fonctionnalites

- **Collecte de logs** - Agents Linux pour collecter les logs systeme
- **Detection en temps reel** - 23+ regles de detection basees sur MITRE ATT&CK
- **Alertes de securite** - Notifications instantanees des menaces
- **Multi-tenant** - Gestion de plusieurs groupes/clients
- **Dashboard** - Visualisation de l'etat de securite

## Documentation

- [Guide de demarrage rapide](docs/QUICKSTART.md)
- [Documentation complete](docs/README.md)
- [Architecture](docs/ARCHITECTURE.md)

## Demarrage rapide

### Deployer un agent

```bash
curl -ksS https://korashield.online/api/agent/install.sh -o athena-xdr-agent.sh \
&& sudo XDR_MANAGER='korashield.online' \
XDR_AGENT_GROUP='Production' \
bash ./athena-xdr-agent.sh
```

## Stack technique

- **Backend**: Laravel 11 (PHP 8.2+)
- **Database**: MySQL 8.0
- **Frontend**: Blade + TailwindCSS
- **Agents**: Bash + systemd
- **CI/CD**: GitHub Actions

## Licence

MIT License
