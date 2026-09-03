<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\CitationValidator;
use Illuminate\Http\JsonResponse;

class ReferenceController extends Controller
{
    public function __construct(
        private CitationValidator $validator,
    ) {}

    public function validateReferences(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $issues = $this->validator->validate($document);

        return response()->json([
            'document_id' => $document->id,
            'issues' => $issues,
            'summary' => [
                'errors' => count($issues['errors']),
                'warnings' => count($issues['warnings']),
                'info' => count($issues['info']),
            ],
        ]);
    }

    public function referenceIssues(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $result = $this->validator->getIssues($document);

        return response()->json($result);
    }
}
