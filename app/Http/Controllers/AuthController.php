<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Affiche le formulaire de connexion
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Traite la tentative de connexion
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Identifiants en dur
        $hardcodedCredentials = [
            'email' => 'njiezamon10@gmail.com',
            'password' => 'adminaudit',
        ];

        if ($credentials['email'] === $hardcodedCredentials['email'] && 
            $credentials['password'] === $hardcodedCredentials['password']) {
            
            // Créer une session manuellement
            session([
                'authenticated' => true,
                'user' => [
                    'name' => 'Expert N\'jie ZAMON',
                    'email' => $hardcodedCredentials['email'],
                ]
            ]);
            
            return redirect()->intended(route('audits.index'));
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent pas.',
        ]);
    }

    /**
     * Déconnecte l'utilisateur
     */
    public function logout()
    {
        session()->forget(['authenticated', 'user']);
        return redirect()->route('login');
    }
}