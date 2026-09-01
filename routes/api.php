<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__.'/auth.php';

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::apiResource('projects', ProjectController::class);

        Route::apiResource('projects.documents', DocumentController::class)->except(['update']);
    });
});
