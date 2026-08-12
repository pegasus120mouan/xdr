<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BruteForceDetector;
use App\Services\TotpService;
use App\Support\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MfaController extends Controller
{
    public function __construct(
        protected TotpService $totp,
        protected BruteForceDetector $bruteForceDetector,
    ) {}

    public function showChallenge(Request $request)
    {
        if ($this->bruteForceDetector->isIpBlocked($request->ip())) {
            return view('auth.blocked');
        }

        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Session MFA expirée. Reconnectez-vous.',
            ]);
        }

        return view('auth.mfa-challenge', [
            'email' => $user->email,
        ]);
    }

    public function verifyChallenge(Request $request)
    {
        if ($this->bruteForceDetector->isIpBlocked($request->ip())) {
            return view('auth.blocked');
        }

        $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Session MFA expirée. Reconnectez-vous.',
            ]);
        }

        $code = (string) $request->input('code');
        $ok = $this->verifyTotp($user, $code) || $this->consumeRecoveryCode($user, $code);

        if (! $ok) {
            $this->bruteForceDetector->recordAttempt(
                $request,
                $user->email,
                false,
                'Invalid MFA code',
                $user->id
            );
            $this->bruteForceDetector->analyze($request, $user->email);

            if ($this->bruteForceDetector->isIpBlocked($request->ip())) {
                $request->session()->forget(['mfa.pending_user_id', 'mfa.remember', 'mfa.pending_expires']);

                return view('auth.blocked');
            }

            throw ValidationException::withMessages([
                'code' => 'Code invalide. Utilisez Google Authenticator ou un code de récupération.',
            ]);
        }

        $remember = (bool) $request->session()->pull('mfa.remember', false);
        $request->session()->forget(['mfa.pending_user_id', 'mfa.pending_expires']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $this->bruteForceDetector->recordAttempt(
            $request,
            $user->email,
            true,
            null,
            $user->id
        );

        SecurityAudit::log('auth.mfa_verified', [
            'email' => $user->email,
        ], User::class, $user->id);

        return redirect()->intended('/dashboard');
    }

    public function showSecurity(Request $request)
    {
        $user = $request->user();
        $setupSecret = $request->session()->get('mfa.setup_secret');
        $qrSvg = null;
        $otpauth = null;

        if ($setupSecret && ! $user->hasMfaEnabled()) {
            $otpauth = $this->totp->otpauthUrl($user->email, $setupSecret);
            $qrSvg = $this->totp->qrSvg($otpauth);
        }

        return view('account.security', [
            'user' => $user,
            'setupSecret' => $setupSecret,
            'qrSvg' => $qrSvg,
            'recoveryCodes' => $request->session()->get('mfa.recovery_codes_plain', []),
        ]);
    }

    public function startSetup(Request $request)
    {
        $user = $request->user();
        if ($user->hasMfaEnabled()) {
            return back()->with('error', 'MFA est déjà activé.');
        }

        $secret = $this->totp->generateSecret();
        $request->session()->put('mfa.setup_secret', $secret);

        return redirect()->route('account.security');
    }

    public function confirmSetup(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $secret = $request->session()->get('mfa.setup_secret');
        if (! $secret) {
            return back()->with('error', 'Générez d’abord un QR code.');
        }

        $verified = $this->totp->verify($secret, (string) $request->input('code'));
        if ($verified === false) {
            throw ValidationException::withMessages([
                'code' => 'Code Google Authenticator invalide.',
            ]);
        }

        [$plain, $hashed] = $this->makeRecoveryCodes();

        $user->forceFill([
            'mfa_secret' => $secret,
            'mfa_recovery_codes' => $hashed,
            'mfa_confirmed_at' => now(),
            'mfa_last_used_ts' => is_int($verified) ? $verified : null,
        ])->save();

        $request->session()->forget('mfa.setup_secret');
        $request->session()->flash('mfa.recovery_codes_plain', $plain);

        SecurityAudit::log('auth.mfa_enabled', [
            'email' => $user->email,
        ], User::class, $user->id);

        return redirect()->route('account.security')
            ->with('success', 'Google Authenticator activé. Enregistrez vos codes de récupération.');
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        $user = $request->user();
        if (! $user->hasMfaEnabled()) {
            return back()->with('error', 'MFA n’est pas activé.');
        }

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Mot de passe incorrect.',
            ]);
        }

        $code = (string) $request->input('code');
        if (! $this->verifyTotp($user, $code) && ! $this->consumeRecoveryCode($user, $code)) {
            throw ValidationException::withMessages([
                'code' => 'Code MFA ou de récupération invalide.',
            ]);
        }

        $user->forceFill([
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
            'mfa_confirmed_at' => null,
            'mfa_last_used_ts' => null,
        ])->save();

        $request->session()->forget(['mfa.setup_secret', 'mfa.recovery_codes_plain']);

        SecurityAudit::log('auth.mfa_disabled', [
            'email' => $user->email,
        ], User::class, $user->id);

        return back()->with('success', 'Google Authenticator désactivé.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $user = $request->user();
        if (! $user->hasMfaEnabled()) {
            return back()->with('error', 'Activez d’abord MFA.');
        }

        if (! $this->verifyTotp($user, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'Code Google Authenticator invalide.',
            ]);
        }

        [$plain, $hashed] = $this->makeRecoveryCodes();
        $user->forceFill(['mfa_recovery_codes' => $hashed])->save();
        $request->session()->flash('mfa.recovery_codes_plain', $plain);

        SecurityAudit::log('auth.mfa_recovery_regenerated', [
            'email' => $user->email,
        ], User::class, $user->id);

        return redirect()->route('account.security')
            ->with('success', 'Nouveaux codes de récupération générés. Les anciens ne fonctionnent plus.');
    }

    protected function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('mfa.pending_user_id');
        $expires = (int) $request->session()->get('mfa.pending_expires', 0);
        if (! $userId || $expires < now()->getTimestamp()) {
            $request->session()->forget(['mfa.pending_user_id', 'mfa.remember', 'mfa.pending_expires']);

            return null;
        }

        $user = User::find($userId);

        return $user && $user->hasMfaEnabled() ? $user : null;
    }

    protected function verifyTotp(User $user, string $code): bool
    {
        $secret = (string) $user->mfa_secret;
        if ($secret === '') {
            return false;
        }

        $ts = $this->totp->verify($secret, $code, $user->mfa_last_used_ts ? (int) $user->mfa_last_used_ts : null);
        if ($ts === false) {
            return false;
        }

        if (is_int($ts)) {
            $user->forceFill(['mfa_last_used_ts' => $ts])->save();
        }

        return true;
    }

    protected function consumeRecoveryCode(User $user, string $code): bool
    {
        $normalized = strtoupper(preg_replace('/[\s\-]+/', '', $code) ?? '');
        $hashes = $user->mfa_recovery_codes ?? [];
        if (! is_array($hashes) || $normalized === '') {
            return false;
        }

        foreach ($hashes as $i => $hash) {
            $plainVariants = [
                $normalized,
                substr($normalized, 0, 4).'-'.substr($normalized, 4),
            ];
            foreach ($plainVariants as $variant) {
                if (is_string($hash) && Hash::check($variant, $hash)) {
                    unset($hashes[$i]);
                    $user->forceFill(['mfa_recovery_codes' => array_values($hashes)])->save();

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    protected function makeRecoveryCodes(): array
    {
        $plain = [];
        $hashed = [];
        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(Str::random(4).'-'.Str::random(4));
            $plain[] = $code;
            $hashed[] = Hash::make($code);
        }

        return [$plain, $hashed];
    }
}
