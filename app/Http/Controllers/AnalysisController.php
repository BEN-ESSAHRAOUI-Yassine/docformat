<?php

namespace App\Http\Controllers;

use App\Http\Requests\TriggerAnalysisRequest;
use App\Http\Resources\AnalysisResource;
use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function store(TriggerAnalysisRequest $request, Document $document): JsonResponse
    {
        AnalyzeDocumentJob::dispatch($document);

        return response()->json([
            'message' => 'Analysis has been queued.',
            'status' => 'analyzing',
        ], 202);
    }

    public function show(Request $request, Document $document): AnalysisResource
    {
        $this->authorize('view', $document);

        $analysis = $document->analyses()
            ->with('detectedElements')
            ->latest()
            ->firstOrFail();

        return new AnalysisResource($analysis);
    }
}
