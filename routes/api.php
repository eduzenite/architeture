<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Nfse\NfseController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('nfse')->group(function () {
    Route::post('/create', [NfseController::class, 'create']);
    Route::post('/create-batch', [NfseController::class, 'createBatch']);

//    Route::post('/send/{nfseId}', function (Request $request, $nfseId) { dd(public_path('storage/certificates/nfse_certificate.pfx')); });
    Route::post('/send/{nfseId}', [NfseController::class, 'send']);
    Route::post('/send-batch/{batchId}', [NfseController::class, 'sendBatch']);
    Route::get('/check/{nfseId}', [NfseController::class, 'check']);
    Route::get('/check-batch/{batchId}', [NfseController::class, 'checkBatch']);
    Route::put('/cancel/{nfseId}', [NfseController::class, 'cancel']);
})->middleware('auth:sanctum');
