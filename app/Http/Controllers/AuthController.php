<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            ActivityLog::record('login_failed', null, $request->input('email'));

            throw $e;
        }

        // Indispensable : sans régénération, l'identifiant de session choisi
        // avant l'authentification reste valide (fixation de session).
        $request->session()->regenerate();

        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        ActivityLog::record('login', $user, $user->email);

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Bienvenue, '.$user->name.'.');
    }

    public function logout(Request $request): RedirectResponse
    {
        ActivityLog::record('logout', Auth::user());

        Auth::guard('web')->logout();

        // On invalide la session et on renouvelle le jeton CSRF : le simple
        // session()->forget() de l'ancienne version laissait le cookie utilisable.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Vous êtes déconnecté.');
    }
}
