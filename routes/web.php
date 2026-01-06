<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route pour la page d'accueil du site
// Redirige vers la liste des audits pour une meilleure expérience utilisateur
Route::get('/', function () {
    return redirect()->route('audits.index');
});

// Routes "resourceful" pour la gestion des audits
// Cette seule ligne génère automatiquement 7 routes pour les opérations CRUD (Créer, Lire, Mettre à jour, Supprimer)
Route::resource('audits', AuditController::class);