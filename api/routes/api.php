<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SignatureWebhookController;
use App\Http\Controllers\SigningController;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

/*
| Panel (usuario autenticado con sesión Sanctum)
*/
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{id}', [DocumentController::class, 'show'])->whereUuid('id');
    Route::get('/documents/{id}/file', [DocumentController::class, 'file'])->whereUuid('id');
});

/*
| Firmante (público; el token del enlace es la credencial)
| El patrón rechaza de inmediato cualquier cosa que no sea un token de 64 hex.
*/
Route::middleware('throttle:signing')
    ->prefix('/sign/{token}')
    ->where(['token' => '[a-f0-9]{64}'])
    ->group(function () {
        Route::get('/', [SigningController::class, 'show']);
        Route::get('/file', [SigningController::class, 'file']);
        Route::post('/', [SigningController::class, 'sign']);
    });

/*
| Webhook del proveedor de firma (autenticado por HMAC)
*/
Route::post('/webhooks/signatures', SignatureWebhookController::class)
    ->middleware(['throttle:webhook', VerifyWebhookSignature::class]);
