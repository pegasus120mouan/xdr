<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Bloqué - APEX XDR</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0f0f1a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
        }

        .blocked-container {
            text-align: center;
            max-width: 500px;
            padding: 40px;
        }

        .blocked-icon {
            width: 120px;
            height: 120px;
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
            font-size: 3rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .blocked-title {
            font-size: 2rem;
            font-weight: 700;
            color: #ef4444;
            margin-bottom: 16px;
        }

        .blocked-message {
            font-size: 1rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .blocked-details {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 32px;
        }

        .blocked-details h3 {
            font-size: 0.9rem;
            color: #ef4444;
            margin-bottom: 12px;
        }

        .blocked-details p {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .blocked-details .ip {
            font-family: monospace;
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .contact-link {
            color: #00d4ff;
            text-decoration: none;
        }

        .contact-link:hover {
            text-decoration: underline;
        }

        .logo {
            position: absolute;
            top: 40px;
            left: 40px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }

        .logo span {
            color: #00d4ff;
        }
    </style>
</head>
<body>
    <div class="logo">Athena <span>XDR</span></div>

    <div class="blocked-container">
        <div class="blocked-icon">🛡️</div>
        <h1 class="blocked-title">Accès Bloqué</h1>
        <p class="blocked-message">
            Votre adresse IP a été temporairement bloquée en raison d'activités suspectes détectées sur votre connexion.
            Cette mesure de sécurité protège notre système contre les attaques par force brute.
        </p>

        <div class="blocked-details">
            <h3>⚠️ Détails de la détection</h3>
            <p>
                IP détectée : <span class="ip">{{ request()->ip() }}</span><br>
                Raison : Tentatives de connexion multiples échouées<br>
                Durée du blocage : Temporaire (jusqu'à 24 heures)
            </p>
        </div>

        <p class="blocked-message">
            Si vous pensez qu'il s'agit d'une erreur, veuillez contacter 
            <a href="mailto:security@apex-xdr.com" class="contact-link">l'équipe de sécurité</a>.
        </p>
    </div>
</body>
</html>
