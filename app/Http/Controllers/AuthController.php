<?php

namespace App\Http\Controllers;

use App\Services\BruteForceDetector;
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

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Enregistrer la tentative réussie
            $this->bruteForceDetector->recordAttempt(
                $request,
                $credentials['email'],
                true,
                null,
                Auth::id()
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
