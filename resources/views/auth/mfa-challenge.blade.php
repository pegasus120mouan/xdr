<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vérification MFA - KORASHIELD</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Inter, sans-serif;
            min-height: 100vh;
            background: #070b12;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mfa-card {
            width: min(420px, 92vw);
            background: rgba(10, 18, 32, 0.92);
            border: 1px solid rgba(0, 212, 255, 0.18);
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
        }
        h1 { font-size: 1.25rem; margin-bottom: 6px; }
        p { color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px; }
        .email { color: #7dd3fc; font-weight: 600; }
        .error-message {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.85rem;
        }
        .form-input {
            width: 100%;
            padding: 14px 16px;
            background: #0f1419;
            border: 1px solid #2d3748;
            border-radius: 8px;
            color: #fff;
            font-size: 1.35rem;
            letter-spacing: 0.28em;
            text-align: center;
            font-variant-numeric: tabular-nums;
        }
        .form-input:focus { outline: none; border-color: #00d4ff; }
        .hint { font-size: 0.75rem; color: #64748b; margin: 8px 0 18px; }
        .login-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #0066cc 0%, #00d4ff 100%);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .back { display: block; text-align: center; margin-top: 16px; color: #64748b; font-size: 0.8rem; text-decoration: none; }
        .back:hover { color: #7dd3fc; }
    </style>
</head>
<body>
    <div class="mfa-card">
        <h1>Google Authenticator</h1>
        <p>Entrez le code à 6 chiffres pour <span class="email">{{ $email }}</span></p>

        @if ($errors->any())
            <div class="error-message">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.mfa.verify') }}">
            @csrf
            <input
                type="text"
                name="code"
                class="form-input"
                inputmode="numeric"
                autocomplete="one-time-code"
                placeholder="000000"
                maxlength="20"
                required
                autofocus
            >
            <p class="hint">Ou un code de récupération (XXXX-XXXX).</p>
            <button type="submit" class="login-btn">Vérifier</button>
        </form>
        <a class="back" href="{{ route('login') }}">← Retour à la connexion</a>
    </div>
</body>
</html>
