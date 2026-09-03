<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\AbbreviationDetector;
use Illuminate\Http\JsonResponse;

class AbbreviationController extends Controller
{
    public function __construct(
        private AbbreviationDetector $detector,
    ) {}

    public function index(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $abbreviations = $document->abbreviations;

        return response()->json([
            'document_id' => $document->id,
            'abbreviations' => $abbreviations,
        ]);
    }

    public function issues(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $abbreviations = $document->abbreviations->toArray();
        $issues = $this->detector->getIssues($abbreviations);

        return response()->json([
            'document_id' => $document->id,
            'issues' => $issues,
            'summary' => [
                'inconsistent' => count($issues['inconsistent']),
                'unused' => count($issues['unused']),
            ],
        ]);
    }
}
