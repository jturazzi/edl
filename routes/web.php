<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EdlController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

// ─── Favicon (évite que le catch-all SPA la capture comme URL "intended") ─
Route::get('/favicon.ico', fn () => redirect('/favicon.svg'));

// ─── Connexion de test (tests navigateur uniquement, jamais en production) ──
// Microsoft SSO ne s'automatise pas : l'environnement `e2e` (voir playwright.config.js) ouvre une session directement.
if (app()->environment('e2e')) {
    Route::get('/__e2e/login/{role}', function (string $role) {
        abort_unless(in_array($role, ['admin', 'technicien', 'technicien2'], true), 404);
        $user = \App\Models\User::firstOrCreate(
            ['microsoft_id' => "e2e-{$role}"],
            ['name' => ucfirst($role) . ' E2E', 'firstname' => ucfirst($role), 'lastname' => 'E2E', 'email' => "{$role}@e2e.test"]
        );
        $user->forceFill(['role' => $role === 'admin' ? 'admin' : 'technicien'])->save();
        auth()->login($user);

        return redirect('/');
    });
}

// ─── Authentification Microsoft 365 ───────────────────────────
Route::get('/login',  [AuthController::class, 'login'])->name('login');
Route::get('/auth/microsoft',          [AuthController::class, 'redirectToMicrosoft'])->name('auth.microsoft')->middleware('throttle:login');
Route::get('/auth/microsoft/callback', [AuthController::class, 'handleMicrosoftCallback'])->name('auth.microsoft.callback')->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── API JSON (auth session) ──────────────────────────────────
Route::middleware(['auth', 'throttle:api'])->prefix('api')->group(function () {
    Route::get('/user',                fn () => response()->json([...auth()->user()->toArray(), 'is_admin' => auth()->user()->isAdmin()]));
    Route::get('/dashboard',           [DashboardController::class, 'index']);
    Route::get('/edls',                [EdlController::class, 'apiIndex']);
    Route::get('/edls/filters',        [EdlController::class, 'apiFilters']);
    Route::get('/edls/export',         [EdlController::class, 'apiExport']);
    Route::post('/edls',               [EdlController::class, 'apiStore']);
    Route::get('/edls/{edl}',          [EdlController::class, 'apiShow']);
    Route::get('/logement/edls',       [EdlController::class, 'logementEdls']);
    Route::get('/edls/{edl}/integrity', [EdlController::class, 'pdfIntegrity']);
    Route::get('/edls/{edl}/comparison', [EdlController::class, 'apiComparison']);
    Route::patch('/photos/{photo}',     [EdlController::class, 'updatePhoto']);
    Route::delete('/photos/{photo}',    [EdlController::class, 'destroyPhoto']);
    Route::post('/edls/{edl}/sortant', [EdlController::class, 'apiCreateSortant']);
    Route::post('/edls/{edl}/duplicate', [EdlController::class, 'apiDuplicate']);
    Route::post('/edls/{edl}/archive',   [EdlController::class, 'apiArchive']);
    Route::delete('/edls/{edl}/archive', [EdlController::class, 'apiUnarchive']);
    Route::put('/edls/{edl}/retenues',   [EdlController::class, 'updateRetenues']);
    Route::patch('/edls/{edl}/steps',  [EdlController::class, 'updateSteps']);
    Route::post('/edls/{edl}/survey',  [EdlController::class, 'saveSurvey']);
    Route::post('/edls/{edl}/photos',  [EdlController::class, 'uploadPhoto'])->middleware('throttle:uploads');
    Route::get('/edls/{edl}/photos',   [EdlController::class, 'listPhotos']);
    Route::post('/edls/{edl}/finalize',[EdlController::class, 'apiFinalize'])->middleware('throttle:finalize');
    Route::post('/edls/{edl}/send-email', [EdlController::class, 'sendEmail']);
    Route::delete('/edls/{edl}',           [EdlController::class, 'apiDestroy']);

    // Administration (réservée aux administrateurs)
    Route::middleware('admin')->group(function () {
        Route::get('/admin/info', [AdminController::class, 'info']);
        Route::get('/admin/users', [AdminController::class, 'users']);
        Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser']);
    });

    // Logs d'activité (administrateurs)
    Route::get('/logs', [ActivityLogController::class, 'index'])->middleware('admin');
});

// ─── Fichiers (auth) ──────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/edl/photos/{photo}',  [EdlController::class, 'showPhoto'])->name('edl.photo');
    Route::get('/edl/{edl}/pdf',       [EdlController::class, 'downloadPdf'])->name('edl.pdf');
    Route::get('/edl/{edl}/pdf/view',  [EdlController::class, 'viewPdf'])->name('edl.pdf.view');
});

// ─── SPA Vue.js catch-all (auth, DOIT être en dernier) ───────
Route::middleware('auth')->get('/{any?}', function () {
    return view('app');
})->where('any', '.*')->name('home');
