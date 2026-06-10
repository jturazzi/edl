<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EdlController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

// ─── Authentification Microsoft 365 ───────────────────────────
Route::get('/login',  [AuthController::class, 'login'])->name('login');
Route::get('/auth/microsoft',          [AuthController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [AuthController::class, 'handleMicrosoftCallback'])->name('auth.microsoft.callback');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── API JSON (auth session) ──────────────────────────────────
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/user',                fn () => response()->json(auth()->user()));
    Route::get('/edls',                [EdlController::class, 'apiIndex']);
    Route::post('/edls',               [EdlController::class, 'apiStore']);
    Route::get('/edls/{edl}',          [EdlController::class, 'apiShow']);
    Route::post('/edls/{edl}/survey',  [EdlController::class, 'saveSurvey']);
    Route::post('/edls/{edl}/photos',  [EdlController::class, 'uploadPhoto']);
    Route::get('/edls/{edl}/photos',   [EdlController::class, 'listPhotos']);
    Route::post('/edls/{edl}/finalize',[EdlController::class, 'apiFinalize']);
    Route::post('/edls/{edl}/send-email', [EdlController::class, 'sendEmail']);
    Route::delete('/edls/{edl}',           [EdlController::class, 'apiDestroy']);

    // Admin
    Route::get('/admin/info', [AdminController::class, 'info']);

    // Catégories
    Route::get('/categories',               [CategoryController::class, 'index']);
    Route::post('/categories',              [CategoryController::class, 'store']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Logs d'activité
    Route::get('/logs', [ActivityLogController::class, 'index']);
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
