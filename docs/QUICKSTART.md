# 🚀 Guide de Démarrage Rapide - Wara XDR

## Déployer un agent en 2 minutes

### Étape 1 : Créer un groupe

1. Connectez-vous à `https://korashield.online`
2. Allez dans **All Tenants** (menu de gauche)
3. Cliquez sur **"+ Add Group"**
4. Entrez un nom (ex: "Production")

### Étape 2 : Déployer l'agent

1. Cliquez sur votre groupe
2. Cliquez sur **"⊕ Deploy new agent"**
3. Entrez l'adresse du serveur : `korashield.online`
4. Copiez le script affiché

### Étape 3 : Installer sur le serveur Linux

```bash
# Coller et exécuter le script copié
curl -ksS https://korashield.online/api/agent/install.sh -o athena-xdr-agent.sh \
&& sudo XDR_MANAGER='korashield.online' \
XDR_AGENT_GROUP='Production' \
bash ./athena-xdr-agent.sh
```

### Étape 4 : Vérifier

```bash
# L'agent doit être "active (running)"
sudo systemctl status athena-xdr-agent
```

✅ **C'est fait !** L'agent collecte maintenant les logs et les envoie au serveur XDR.

---

## Tester la détection

### Simuler une attaque brute force

Depuis une autre machine (ex: Kali Linux) :

```bash
# Installer hydra
sudo apt install hydra

# Lancer une attaque de test
hydra -l admin -P /usr/share/wordlists/rockyou.txt <IP_SERVEUR> ssh -t 4
```

### Voir les alertes

1. Allez sur `https://korashield.online/detection/alerts`
2. Les alertes apparaissent en temps réel !

---

## Commandes utiles

| Action | Commande |
|--------|----------|
| Statut agent | `sudo systemctl status athena-xdr-agent` |
| Logs agent | `cat /opt/athena-xdr/logs/agent.log` |
| Redémarrer | `sudo systemctl restart athena-xdr-agent` |
| Arrêter | `sudo systemctl stop athena-xdr-agent` |
| Config | `cat /opt/athena-xdr/config.conf` |

---

## Besoin d'aide ?

📖 Documentation complète : [docs/README.md](README.md)
