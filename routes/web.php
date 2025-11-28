<?php

use Illuminate\Support\Facades\Route;

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

// Route de login minimal (pour éviter l'erreur)
Route::get('/login', function () {
    return response()->json([
        'message' => 'Cette application utilise une API. Veuillez utiliser /api/login'
    ], 401);
})->name('login');

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
