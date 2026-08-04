<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Page publique de vérification. C'est elle qui donne une valeur à la
 * signature : le destinataire du PDF saisit le code imprimé et la
 * plateforme confirme — ou non — que le document n'a pas été altéré.
 */
class VerificationController extends Controller
{
    public function form(): View
    {
        return view('verify.form');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:24']]);

        $key = 'verify:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            return back()->withErrors([
                'code' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($key).' secondes.',
            ]);
        }

        RateLimiter::hit($key, 300);

        return redirect()->route('verify.show', ['code' => strtoupper(trim($request->input('code')))]);
    }

    public function show(string $code): View
    {
        $audit = Audit::with('categories')
            ->where('verification_code', strtoupper($code))
            ->where('is_signed', true)
            ->first();

        return view('verify.show', [
            'code' => strtoupper($code),
            'audit' => $audit,
            // On ne divulgue jamais le contenu : seulement l'existence, la
            // date de signature, le signataire et l'état d'intégrité.
            'intact' => $audit?->isIntact() ?? false,
        ]);
    }
}
