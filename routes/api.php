<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StyleAnalysisController;
use App\Http\Controllers\StyleProfileController;
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

        Route::post('/documents/{document}/analyze', [AnalysisController::class, 'store']);
        Route::get('/documents/{document}/analysis', [AnalysisController::class, 'show']);
        Route::post('/documents/{document}/analyze-style', [StyleAnalysisController::class, 'store']);
        Route::get('/documents/{document}/style-violations', [StyleAnalysisController::class, 'index']);

        Route::apiResource('style-profiles', StyleProfileController::class);
        Route::post('/style-profiles/import', [StyleProfileController::class, 'import']);
        Route::get('/style-profiles/{profile}/export', [StyleProfileController::class, 'export']);
    });
});
