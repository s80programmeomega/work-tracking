<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\Yaml\Yaml;

/*
|--------------------------------------------------------------------------
| Routes de documentation API (Scribe + Swagger UI)
|--------------------------------------------------------------------------
|
| Chargées avec les middlewares "web" + "api" via RouteServiceProvider,
| ce qui permet l'authentification via cookie de session (navigateur)
| ou via token Bearer Sanctum (client API).
|
| EnsureFrontendRequestsAreStateful est ajouté ici uniquement — pas sur
| tout le groupe "api" — pour ne pas impacter les autres routes API.
|
| Les noms "scribe", "scribe.postman" et "scribe.openapi" sont obligatoires :
| les vues Blade publiées par Scribe appellent route() avec ces noms exacts.
|
*/

Route::middleware([EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'])->group(function () {
    // UI Scribe (thème Pastel) — nom obligatoire : "scribe"
    Route::view('/docs', 'scribe.index')
        ->name('scribe');

    // Collection Postman — nom obligatoire : "scribe.postman"
    Route::get('/docs.postman', function () {
        return new JsonResponse(Storage::disk('local')->get('scribe/collection.json'), json: true);
    })->name('scribe.postman');

    // Spécification OpenAPI brute (YAML) — nom obligatoire : "scribe.openapi"
    Route::get('/docs.openapi', function () {
        return response()->file(Storage::disk('local')->path('scribe/openapi.yaml'));
    })->name('scribe.openapi');

    // Spécification OpenAPI en JSON — consommée par Swagger UI
    Route::get('/docs.json', function () {
        $yaml = Storage::disk('local')->get('scribe/openapi.yaml');
        $data = Yaml::parse($yaml);

        return response()->json($data);
    })->name('scribe.docs.json');

    // Swagger UI
    Route::get('/docs/swagger', function () {
        return view('scribe.swagger');
    })->name('scribe.docs.swagger');
});
