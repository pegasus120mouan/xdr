<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WARA XDR - Connexion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
            margin: 0;
            overflow-x: hidden;
            background: #070b12;
        }

        /* Dégradé de fond : le noir de l’image est « retiré » visuellement via mix-blend-mode: screen sur le lion */
        .login-bg-layer {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 100% 85% at 50% 45%, rgba(15, 55, 95, 0.55) 0%, rgba(8, 20, 40, 0.92) 42%, #050810 100%),
                radial-gradient(ellipse 50% 40% at 70% 30%, rgba(0, 180, 255, 0.12) 0%, transparent 55%),
                linear-gradient(165deg, #0a1528 0%, #060d18 55%, #03060c 100%);
        }

        .login-bg-layer::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 212, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 212, 255, 0.04) 1px, transparent 1px);
            background-size: 56px 56px;
            opacity: 0.5;
            pointer-events: none;
        }

        .login-lion-wrap {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }

        .login-lion-full {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            /* Les pixels noirs laissent apparaître le dégradé dessous */
            mix-blend-mode: screen;
            opacity: 0.97;
            transform: scale(1.04);
            animation: login-lion-drift 24s ease-in-out infinite;
        }

        @keyframes login-lion-drift {
            0%, 100% { transform: scale(1.04) translate(0, 0); }
            33% { transform: scale(1.06) translate(-0.8%, 0.2%); }
            66% { transform: scale(1.05) translate(0.6%, -0.4%); }
        }

        @media (prefers-reduced-motion: reduce) {
            .login-lion-full {
                animation: none !important;
                transform: scale(1.04);
            }
        }

        .login-brand {
            position: fixed;
            z-index: 10;
            top: 36px;
            left: clamp(24px, 4vw, 56px);
            pointer-events: none;
        }

        .login-brand h1 {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 700;
            color: #fff;
            letter-spacing: 2px;
            text-shadow: 0 2px 32px rgba(0, 0, 0, 0.75), 0 0 40px rgba(0, 100, 180, 0.25);
        }

        .login-brand h1 span {
            color: #00d4ff;
        }

        .login-brand .brand-subtitle {
            color: rgba(186, 198, 214, 0.95);
            font-size: 0.9rem;
            margin-top: 6px;
            text-shadow: 0 1px 16px rgba(0, 0, 0, 0.8);
            max-width: 22rem;
        }

        .right-section {
            position: fixed;
            z-index: 10;
            right: 0;
            top: 0;
            bottom: 0;
            width: min(480px, 100%);
            min-height: 100vh;
            background: rgba(10, 18, 32, 0.82);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            border-left: 1px solid rgba(0, 212, 255, 0.14);
            box-shadow: -24px 0 48px rgba(0, 0, 0, 0.35);
        }

        .login-container {
            width: 100%;
            max-width: 380px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #00d4ff 0%, #0066cc 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .logo-icon::before {
            content: 'W';
            font-size: 24px;
            font-weight: 700;
            color: #fff;
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 16px 50px 16px 16px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(100, 116, 139, 0.3);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-input::placeholder {
            color: #64748b;
        }

        .form-input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        }

        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.2rem;
        }

        .password-toggle {
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #00d4ff;
        }

        .captcha-container {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .captcha-input {
            flex: 1;
            padding: 16px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(100, 116, 139, 0.3);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .captcha-input:focus {
            outline: none;
            border-color: #00d4ff;
        }

        .captcha-display {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 12px 16px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(100, 116, 139, 0.3);
            border-radius: 8px;
        }

        .captcha-char {
            font-size: 1.4rem;
            font-weight: 700;
            width: 24px;
            text-align: center;
        }

        .captcha-char:nth-child(1) { color: #00d4ff; }
        .captcha-char:nth-child(2) { color: #ff6b6b; }
        .captcha-char:nth-child(3) { color: #4ade80; }
        .captcha-char:nth-child(4) { color: #fbbf24; }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 24px;
        }

        .checkbox-wrapper {
            position: relative;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .checkbox-wrapper input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            width: 20px;
            height: 20px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(100, 116, 139, 0.5);
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .checkbox-wrapper input:checked ~ .checkmark {
            background: #00d4ff;
            border-color: #00d4ff;
        }

        .checkmark::after {
            content: '';
            position: absolute;
            display: none;
            left: 7px;
            top: 3px;
            width: 5px;
            height: 10px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .checkbox-wrapper input:checked ~ .checkmark::after {
            display: block;
        }

        .checkbox-label {
            font-size: 0.85rem;
            color: #94a3b8;
            line-height: 1.5;
        }

        .checkbox-label a {
            color: #00d4ff;
            text-decoration: none;
        }

        .checkbox-label a:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #0066cc 0%, #00d4ff 100%);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            color: #fca5a5;
            font-size: 0.9rem;
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 2;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(0, 212, 255, 0.5);
            border-radius: 50%;
            animation: particle-float 15s linear infinite;
        }

        @keyframes particle-float {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }

        @media (max-width: 1024px) {
            .right-section {
                width: 100%;
                max-width: 100%;
                border-left: none;
                position: relative;
                right: auto;
                top: auto;
                bottom: auto;
                flex: 1;
                min-height: auto;
                padding: 48px 24px 40px;
                box-shadow: none;
                background: rgba(10, 18, 32, 0.88);
            }

            body {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }

            .login-brand {
                position: relative;
                top: auto;
                left: auto;
                padding: 28px 24px 8px;
                text-align: center;
            }

            .login-brand .brand-subtitle {
                margin-left: auto;
                margin-right: auto;
            }

            .login-lion-wrap {
                position: fixed;
            }
        }
    </style>
</head>
<body>
    <div class="login-bg-layer" aria-hidden="true"></div>
    <div class="login-lion-wrap" aria-hidden="true">
        <img
            class="login-lion-full"
            src="{{ asset('images/wara-lion.png') }}"
            width="1920"
            height="1080"
            alt=""
            loading="eager"
            decoding="async"
        >
    </div>

    <div class="particles">
        @for ($i = 0; $i < 20; $i++)
            <div class="particle" style="left: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 15) }}s; animation-duration: {{ rand(10, 20) }}s;"></div>
        @endfor
    </div>

    <header class="login-brand">
        <h1>Wara <span>XDR</span></h1>
        <p class="brand-subtitle">Extended Detection & Response Platform</p>
    </header>

    <div class="right-section">
        <div class="login-container">
            <div class="login-header">
                <div class="login-logo">
                    <div class="logo-icon"></div>
                    <span class="logo-text">Wara</span>
                </div>
            </div>

            @if ($errors->any())
                <div class="error-message">
                    @foreach ($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <div class="form-group">
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="Adresse email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                        >
                        <span class="input-icon">👤</span>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="form-input" 
                            placeholder="Mot de passe"
                            required
                        >
                        <span class="input-icon password-toggle" onclick="togglePassword()">👁</span>
                    </div>
                </div>

                <div class="checkbox-group">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                    </div>
                    <label class="checkbox-label" for="remember">
                        Se souvenir de moi
                    </label>
                </div>

                <div class="checkbox-group">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="terms" id="terms" required>
                        <span class="checkmark"></span>
                    </div>
                    <label class="checkbox-label" for="terms">
                        J'accepte les <a href="#">Conditions d'utilisation</a> et la <a href="#">Politique de confidentialité</a>
                    </label>
                </div>

                <button type="submit" class="login-btn">
                    Connexion
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁';
            }
        }
    </script>
</body>
</html>
