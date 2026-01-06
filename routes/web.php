<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

// Routes d'authentification (accessibles à tous)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.authenticate');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Route pour la page d'accueil (redirige vers la liste des audits)
Route::get('/', function () {
    // Si non connecté, on envoie vers la page de login
    if (!session('authenticated')) {
        return redirect()->route('login');
    }
    return redirect()->route('audits.index');
});

// Routes pour les audits (la protection se fera dans le contrôleur)
Route::resource('audits', AuditController::class);
Route::get('audits/{audit}/download-pdf', [AuditController::class, 'downloadPdf'])->name('audits.downloadPdf');
Route::post('audits/{audit}/sign', [AuditController::class, 'sign'])->name('audits.sign');
Route::post('audits/{audit}/unsign', [AuditController::class, 'unsign'])->name('audits.unsign');