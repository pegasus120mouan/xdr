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
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0f0f1a 100%);
            display: flex;
            overflow: hidden;
        }

        .left-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            position: relative;
        }

        .brand {
            position: absolute;
            top: 40px;
            left: 60px;
        }

        .brand h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 2px;
        }

        .brand h1 span {
            color: #00d4ff;
        }

        .brand-subtitle {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .cyber-visual {
            position: relative;
            width: 100%;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .grid-background {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0, 212, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 212, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            perspective: 500px;
            transform: rotateX(60deg);
            transform-origin: center bottom;
        }

        .floating-elements {
            position: relative;
            width: 400px;
            height: 400px;
        }

        .cube {
            position: absolute;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 8px;
            box-shadow: 
                0 0 30px rgba(0, 212, 255, 0.2),
                inset 0 0 20px rgba(0, 212, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .cube::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            border-radius: 4px;
            opacity: 0.8;
        }

        .cube:nth-child(1) { top: 50px; left: 50px; animation-delay: 0s; }
        .cube:nth-child(2) { top: 150px; left: 200px; animation-delay: 1s; }
        .cube:nth-child(3) { top: 250px; left: 80px; animation-delay: 2s; }
        .cube:nth-child(4) { top: 180px; left: 300px; animation-delay: 0.5s; }

        .connection-lines {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .connection-lines svg {
            width: 100%;
            height: 100%;
        }

        .connection-lines line {
            stroke: rgba(0, 212, 255, 0.3);
            stroke-width: 1;
            stroke-dasharray: 5, 5;
            animation: dash 20s linear infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        @keyframes dash {
            to { stroke-dashoffset: -100; }
        }

        .right-section {
            width: 480px;
            min-height: 100vh;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            border-left: 1px solid rgba(0, 212, 255, 0.1);
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
            content: 'X';
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
            z-index: 0;
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
            .left-section {
                display: none;
            }
            .right-section {
                width: 100%;
                border-left: none;
            }
        }
    </style>
</head>
<body>
    <div class="particles">
        @for ($i = 0; $i < 20; $i++)
            <div class="particle" style="left: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 15) }}s; animation-duration: {{ rand(10, 20) }}s;"></div>
        @endfor
    </div>

    <div class="left-section">
        <div class="brand">
            <h1>Athena <span>XDR</span></h1>
            <p class="brand-subtitle">Extended Detection & Response Platform</p>
        </div>

        <div class="cyber-visual">
            <div class="grid-background"></div>
            <div class="floating-elements">
                <div class="cube"></div>
                <div class="cube"></div>
                <div class="cube"></div>
                <div class="cube"></div>
                <svg class="connection-lines" viewBox="0 0 400 400">
                    <line x1="90" y1="90" x2="240" y2="190" />
                    <line x1="240" y1="190" x2="120" y2="290" />
                    <line x1="120" y1="290" x2="340" y2="220" />
                    <line x1="340" y1="220" x2="90" y2="90" />
                </svg>
            </div>
        </div>
    </div>

    <div class="right-section">
        <div class="login-container">
            <div class="login-header">
                <div class="login-logo">
                    <div class="logo-icon"></div>
                    <span class="logo-text">APEX</span>
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
