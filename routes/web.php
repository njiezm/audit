<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditTemplateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.authenticate');

    Route::get('/mot-de-passe/oubli', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/mot-de-passe/oubli', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/mot-de-passe/reinitialiser/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/mot-de-passe/reinitialiser', [PasswordResetController::class, 'reset'])->name('password.update');
});

// Vérification publique d'un rapport signé, sans authentification.
Route::get('/verifier', [VerificationController::class, 'form'])->name('verify.form');
Route::post('/verifier', [VerificationController::class, 'lookup'])->name('verify.lookup');
Route::get('/verifier/{code}', [VerificationController::class, 'show'])->name('verify.show');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Routes authentifiées
|--------------------------------------------------------------------------
| La protection est portée par le middleware, plus par un checkAuth()
| recopié dans chaque méthode de contrôleur.
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard.alias');

    // --- Audits ---
    Route::get('audits/corbeille', [AuditController::class, 'trash'])->name('audits.trash');
    Route::get('audits/export', [AuditController::class, 'export'])->name('audits.export');
    Route::post('audits/actions-groupees', [AuditController::class, 'bulk'])->name('audits.bulk');
    Route::post('audits/{id}/restaurer', [AuditController::class, 'restore'])->whereNumber('id')->name('audits.restore');
    Route::delete('audits/{id}/supprimer-definitivement', [AuditController::class, 'forceDestroy'])->whereNumber('id')->name('audits.forceDestroy');

    Route::resource('audits', AuditController::class)->whereNumber('audit');

    Route::prefix('audits/{audit}')->whereNumber('audit')->name('audits.')->group(function () {
        Route::get('pdf', [AuditController::class, 'previewPdf'])->name('previewPdf');
        Route::get('pdf/telecharger', [AuditController::class, 'downloadPdf'])->name('downloadPdf');
        Route::post('signer', [AuditController::class, 'sign'])->name('sign');
        Route::post('retirer-signature', [AuditController::class, 'unsign'])->name('unsign');
        Route::post('finaliser', [AuditController::class, 'finalize'])->name('finalize');
        Route::post('archiver', [AuditController::class, 'archive'])->name('archive');
        Route::post('desarchiver', [AuditController::class, 'unarchive'])->name('unarchive');
        Route::post('dupliquer', [AuditController::class, 'duplicate'])->name('duplicate');
        Route::get('envoyer', [AuditController::class, 'sendForm'])->name('sendForm');
        Route::post('envoyer', [AuditController::class, 'send'])->middleware('throttle:10,10')->name('send');

        Route::post('pieces-jointes', [AttachmentController::class, 'store'])->name('attachments.store');
        Route::get('pieces-jointes/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('pieces-jointes/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
    });

    // --- Clients ---
    Route::resource('clients', ClientController::class)->parameters(['clients' => 'client']);

    // --- Modèles d'audit ---
    Route::get('modeles/{template}/categories', [AuditTemplateController::class, 'categories'])->name('templates.categories');
    Route::resource('modeles', AuditTemplateController::class)
        ->parameters(['modeles' => 'template'])
        ->names('templates')
        ->except(['show']);

    // --- Profil ---
    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profil/mot-de-passe', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // --- Administration ---
    Route::middleware('admin')->group(function () {
        Route::get('journal', [UserController::class, 'activity'])->name('activity.index');
        Route::resource('utilisateurs', UserController::class)
            ->parameters(['utilisateurs' => 'user'])
            ->names('users')
            ->except(['show']);
    });
});
