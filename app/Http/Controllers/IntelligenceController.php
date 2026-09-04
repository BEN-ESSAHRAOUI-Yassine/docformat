<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeIntelligenceJob;
use App\Models\Document;
use App\Services\AiContentService;
use App\Services\IssueCollector;
use App\Services\ParaphraseEngine;
use App\Services\SimilarityEngine;
use App\Services\SynonymEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntelligenceController extends Controller
{
    public function analyze(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        AnalyzeIntelligenceJob::dispatch($document);

        return response()->json(['message' => 'Intelligence analysis started.'], 202);
    }

    public function similarity(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json(app(SimilarityEngine::class)->compare($document));
    }

    public function aiAnalysis(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json([
            'findings' => app(AiContentService::class)->analyze($document),
            'estimate' => true,
        ]);
    }

    public function runCorrections(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        app(IssueCollector::class)->collect($document);

        return response()->json(['message' => 'Corrections collected.']);
    }

    public function paraphrase(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $data = $request->validate(['text' => ['required', 'string']]);

        return response()->json(app(ParaphraseEngine::class)->suggest($data['text']));
    }

    public function synonyms(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $data = $request->validate(['word' => ['required', 'string']]);

        return response()->json(app(SynonymEngine::class)->suggest($data['word']));
    }

    public function toggle(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $data = $request->validate(['enabled' => ['required', 'boolean']]);

        $document->update(['ai_enabled' => $data['enabled']]);

        return response()->json(['ai_enabled' => $document->fresh()->ai_enabled]);
    }
}
