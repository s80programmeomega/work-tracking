<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

// Portail de documentation API — servi côté serveur, auth gérée côté client via localStorage
Route::get('/docs', function () {
    return view('docs.index');
})->name('docs.portal');

// Téléchargement des fichiers d'export de recherche générés par SearchExportJob
Route::get('/exports/search/{file}', function (string $file) {
    // Sécuriser le nom de fichier — jamais de traversée de répertoire
    $safe = basename($file);
    $path = 'exports/'.$safe;

    if (! Storage::disk('local')->exists($path)) {
        abort(404);
    }

    return Storage::disk('local')->download($path);
})->middleware(['auth:sanctum'])->name('search.export.download');

// Route de login minimal (pour éviter l'erreur)
Route::get('/login', function () {
    return response()->json([
        'message' => 'Cette application utilise une API. Veuillez utiliser /api/login',
    ], 401);
})->name('login');

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
