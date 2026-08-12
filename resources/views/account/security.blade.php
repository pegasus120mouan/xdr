@extends('layouts.app')

@section('title', 'Sécurité du compte - Wara XDR')

@section('content')
<div class="page-content mfa-page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Sécurité du compte</h1>
            <p class="mfa-sub">Google Authenticator (TOTP) — 2e facteur à la connexion.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <div class="mfa-grid">
        <div class="mfa-card">
            <h2>Google Authenticator</h2>
            @if($user->hasMfaEnabled())
                <p class="mfa-status mfa-status--on">Activé depuis {{ $user->mfa_confirmed_at->format('d/m/Y H:i') }}</p>
                <p class="mfa-help">À chaque connexion, un code de l’application Authenticator sera demandé.</p>

                <form method="POST" action="{{ route('account.mfa.recovery') }}" class="mfa-form">
                    @csrf
                    <label>Régénérer les codes de récupération</label>
                    <input type="text" name="code" class="form-input" placeholder="Code à 6 chiffres" required>
                    <button type="submit" class="btn btn-secondary">Régénérer</button>
                </form>

                <form method="POST" action="{{ route('account.mfa.disable') }}" class="mfa-form mfa-form--danger" onsubmit="return confirm('Désactiver Google Authenticator ?');">
                    @csrf
                    <label>Désactiver MFA</label>
                    <input type="password" name="password" class="form-input" placeholder="Mot de passe actuel" required>
                    <input type="text" name="code" class="form-input" placeholder="Code Authenticator ou récupération" required>
                    <button type="submit" class="btn btn-danger">Désactiver</button>
                </form>
            @elseif($setupSecret)
                <p class="mfa-help">1. Ouvrez Google Authenticator → <strong>Ajouter</strong> → scanner le QR (ou saisir la clé).</p>
                <div class="mfa-qr">{!! $qrSvg !!}</div>
                <p class="mfa-secret-lbl">Clé manuelle</p>
                <code class="mfa-secret">{{ trim(chunk_split($setupSecret, 4, ' ')) }}</code>
                <form method="POST" action="{{ route('account.mfa.confirm') }}" class="mfa-form">
                    @csrf
                    <label>2. Entrez le code à 6 chiffres pour confirmer</label>
                    <input type="text" name="code" class="form-input" inputmode="numeric" autocomplete="one-time-code" maxlength="8" placeholder="000000" required>
                    <button type="submit" class="btn btn-primary">Activer MFA</button>
                </form>
            @else
                <p class="mfa-status mfa-status--off">Non activé</p>
                <p class="mfa-help">Installez <strong>Google Authenticator</strong> (Android / iOS), puis générez un QR à scanner.</p>
                <form method="POST" action="{{ route('account.mfa.start') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Configurer Google Authenticator</button>
                </form>
            @endif
        </div>

        <div class="mfa-card">
            <h2>Codes de récupération</h2>
            @if(!empty($recoveryCodes))
                <p class="mfa-help mfa-warn">Affichés une seule fois. Conservez-les hors ligne.</p>
                <ul class="mfa-codes">
                    @foreach($recoveryCodes as $code)
                        <li><code>{{ $code }}</code></li>
                    @endforeach
                </ul>
            @elseif($user->hasMfaEnabled())
                <p class="mfa-help">{{ count($user->mfa_recovery_codes ?? []) }} code(s) restant(s). Ils ne sont plus réaffichés après génération.</p>
            @else
                <p class="mfa-help">Ils seront créés automatiquement à l’activation.</p>
            @endif
        </div>
    </div>
</div>

<style>
.mfa-sub { color: #94a3b8; margin-top: 4px; }
.mfa-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 20px; }
.mfa-card {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 10px;
    padding: 24px;
}
.mfa-card h2 { font-size: 1.05rem; margin: 0 0 12px; color: #fff; }
.mfa-status { font-weight: 700; margin-bottom: 8px; }
.mfa-status--on { color: #22c55e; }
.mfa-status--off { color: #f59e0b; }
.mfa-help { color: #94a3b8; font-size: 0.88rem; line-height: 1.45; margin-bottom: 16px; }
.mfa-warn { color: #fde047; }
.mfa-qr {
    width: 220px;
    height: 220px;
    background: #fff;
    border-radius: 8px;
    padding: 8px;
    margin: 0 0 16px;
}
.mfa-qr svg { width: 100%; height: 100%; display: block; }
.mfa-secret-lbl { font-size: 0.75rem; color: #64748b; margin-bottom: 4px; }
.mfa-secret {
    display: block;
    background: #0f1419;
    border: 1px solid #2d3748;
    padding: 10px 12px;
    border-radius: 6px;
    color: #7dd3fc;
    letter-spacing: 0.12em;
    margin-bottom: 16px;
    word-break: break-all;
}
.mfa-form { display: flex; flex-direction: column; gap: 8px; margin-top: 16px; }
.mfa-form label { font-size: 0.8rem; color: #94a3b8; }
.mfa-form .form-input {
    width: 100%;
    padding: 10px 12px;
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 6px;
    color: #e2e8f0;
}
.mfa-form--danger { border-top: 1px solid #2d3748; padding-top: 16px; }
.mfa-codes { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.mfa-codes code {
    display: block;
    background: #0f1419;
    padding: 8px;
    border-radius: 6px;
    color: #e2e8f0;
    letter-spacing: 0.06em;
}
.btn { padding: 10px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
.btn-primary { background: linear-gradient(135deg, #0066cc, #00d4ff); color: #fff; }
.btn-secondary { background: #374151; color: #e2e8f0; }
.btn-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
.alert-success { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
.alert-error { background: rgba(239, 68, 68, 0.15); color: #fca5a5; }
@media (max-width: 900px) { .mfa-grid { grid-template-columns: 1fr; } }
</style>
@endsection
