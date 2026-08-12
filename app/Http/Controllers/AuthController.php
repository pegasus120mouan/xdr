<?php

namespace App\Http\Controllers;

use App\Services\BruteForceDetector;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected BruteForceDetector $bruteForceDetector;

    public function __construct(BruteForceDetector $bruteForceDetector)
    {
        $this->bruteForceDetector = $bruteForceDetector;
    }

    public function showLogin(Request $request)
    {
        // Vérifier si l'IP est bloquée
        if ($this->bruteForceDetector->isIpBlocked($request->ip())) {
            return view('auth.blocked');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Vérifier si l'IP est bloquée
        if ($this->bruteForceDetector->isIpBlocked($request->ip())) {
            return view('auth.blocked');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::validate($credentials)) {
            $user = User::where('email', $credentials['email'])->firstOrFail();

            if ($user->hasMfaEnabled()) {
                $request->session()->put('mfa.pending_user_id', $user->id);
                $request->session()->put('mfa.remember', $request->boolean('remember'));
                $request->session()->put('mfa.pending_expires', now()->addMinutes(10)->getTimestamp());
                $request->session()->regenerate();

                return redirect()->route('login.mfa');
            }

            Auth::login($user, $request->boolean('remember'));

            $this->bruteForceDetector->recordAttempt(
                $request,
                $credentials['email'],
                true,
                null,
                $user->id
            );

            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        // Enregistrer la tentative échouée
        $this->bruteForceDetector->recordAttempt(
            $request,
            $credentials['email'],
            false,
            'Invalid credentials'
        );

        // Analyser pour détecter une attaque brute force
        $alert = $this->bruteForceDetector->analyze($request, $credentials['email']);

        if ($alert && $this->bruteForceDetector->isIpBlocked($request->ip())) {
            return view('auth.blocked');
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
