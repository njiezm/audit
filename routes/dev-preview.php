<?php

/*
|--------------------------------------------------------------------------
| Connexion de prévisualisation — environnement local uniquement
|--------------------------------------------------------------------------
|
| Ouvre une session authentifiée à partir d'une URL signée, afin de pouvoir
| capturer les pages protégées avec un navigateur en mode sans interface.
|
| Ce fichier n'est chargé que si APP_ENV=local ET PREVIEW_LOGIN=true.
| Il n'a rien à faire en production : ne pas définir PREVIEW_LOGIN là-bas.
*/

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/__preview-login/{user}', function (User $user) {
    Auth::login($user);
    request()->session()->regenerate();

    return redirect(request()->query('to', '/'));
})->middleware('signed')->name('preview.login');
