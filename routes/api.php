<?php

use App\Http\Controllers\AbbreviationController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BibliographyController;
use App\Http\Controllers\CitationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\IntelligenceController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\PageBreakController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QualityController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\ReportController;
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

        Route::get('/user/data-export', [PrivacyController::class, 'exportData']);
        Route::delete('/user/data', [PrivacyController::class, 'deleteData']);

        Route::apiResource('projects', ProjectController::class);

        Route::apiResource('projects.documents', DocumentController::class)->except(['update']);

        Route::post('/documents/{document}/analyze', [AnalysisController::class, 'store']);
        Route::get('/documents/{document}/analysis', [AnalysisController::class, 'show']);
        Route::post('/documents/{document}/analyze-style', [StyleAnalysisController::class, 'store']);
        Route::get('/documents/{document}/style-violations', [StyleAnalysisController::class, 'index']);

        Route::post('/documents/{document}/validate-references', [ReferenceController::class, 'validateReferences']);
        Route::get('/documents/{document}/reference-issues', [ReferenceController::class, 'referenceIssues']);

        Route::get('/documents/{document}/abbreviations', [AbbreviationController::class, 'index']);
        Route::get('/documents/{document}/abbreviation-issues', [AbbreviationController::class, 'issues']);

        Route::get('/documents/{document}/citations', [CitationController::class, 'index']);
        Route::get('/documents/{document}/citations/{citation}/bibliography-entry', [CitationController::class, 'bibliographyEntry']);

        Route::get('/documents/{document}/bibliography', [BibliographyController::class, 'index']);
        Route::get('/documents/{document}/bibliography/{entry}/citations', [BibliographyController::class, 'citations']);
        Route::post('/documents/{document}/bibliography/{entry}/merge', [BibliographyController::class, 'merge']);

        Route::get('/documents/{document}/actions', [HistoryController::class, 'index']);
        Route::get('/documents/{document}/history', [HistoryController::class, 'history']);
        Route::post('/documents/{document}/undo', [HistoryController::class, 'undo']);
        Route::post('/documents/{document}/redo', [HistoryController::class, 'redo']);

        Route::get('/documents/{document}/issues', [IssueController::class, 'index']);
        Route::post('/documents/{document}/issues/{issue}/accept', [IssueController::class, 'accept']);
        Route::post('/documents/{document}/issues/{issue}/reject', [IssueController::class, 'reject']);
        Route::post('/documents/{document}/issues/{issue}/edit', [IssueController::class, 'edit']);
        Route::post('/documents/{document}/issues/{issue}/ignore', [IssueController::class, 'ignore']);
        Route::post('/documents/{document}/issues/bulk', [IssueController::class, 'bulk']);

        Route::post('/documents/{document}/page-breaks', [PageBreakController::class, 'store']);
        Route::delete('/documents/{document}/page-breaks/{element}', [PageBreakController::class, 'destroy']);

        Route::get('/documents/{document}/quality', [QualityController::class, 'show']);
        Route::get('/documents/{document}/report', [ReportController::class, 'show']);
        Route::post('/documents/{document}/report/generate', [ReportController::class, 'generate']);
        Route::post('/documents/{document}/export', [ExportController::class, 'store']);
        Route::get('/documents/{document}/download', [ExportController::class, 'download']);
        Route::get('/documents/{document}/download/stream', [ExportController::class, 'stream']);

        Route::post('/documents/{document}/analyze-intelligence', [IntelligenceController::class, 'analyze']);
        Route::get('/documents/{document}/similarity', [IntelligenceController::class, 'similarity']);
        Route::get('/documents/{document}/ai-analysis', [IntelligenceController::class, 'aiAnalysis']);
        Route::post('/documents/{document}/corrections/run', [IntelligenceController::class, 'runCorrections']);
        Route::post('/documents/{document}/paraphrase/suggest', [IntelligenceController::class, 'paraphrase']);
        Route::post('/documents/{document}/synonyms/suggest', [IntelligenceController::class, 'synonyms']);
        Route::post('/documents/{document}/ai/toggle', [IntelligenceController::class, 'toggle']);

        Route::get('/batches', [BatchController::class, 'index']);
        Route::post('/batches', [BatchController::class, 'store']);
        Route::get('/batches/{batch}', [BatchController::class, 'show']);
        Route::get('/batches/{batch}/items', [BatchController::class, 'items']);
        Route::post('/batches/{batch}/export', [BatchController::class, 'export']);
        Route::get('/batches/{batch}/export/download', [BatchController::class, 'exportDownload']);

        Route::apiResource('style-profiles', StyleProfileController::class);
        Route::post('/style-profiles/import', [StyleProfileController::class, 'import']);
        Route::get('/style-profiles/{profile}/export', [StyleProfileController::class, 'export']);
    });
});
