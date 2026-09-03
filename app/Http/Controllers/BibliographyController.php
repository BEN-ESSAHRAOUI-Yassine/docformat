<?php

namespace App\Http\Controllers;

use App\Models\BibliographyEntry;
use App\Models\Document;
use Illuminate\Http\JsonResponse;

class BibliographyController extends Controller
{
    public function index(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $entries = $document->bibliographyEntries()->with('citations')->get();

        return response()->json([
            'document_id' => $document->id,
            'bibliography' => $entries,
        ]);
    }

    public function citations(Document $document, BibliographyEntry $entry): JsonResponse
    {
        $this->authorize('view', $document);

        if ($entry->document_id !== $document->id) {
            return response()->json(['message' => 'Bibliography entry not found.'], 404);
        }

        $citations = $entry->citations;

        return response()->json([
            'bibliography_entry' => $entry,
            'citations' => $citations,
        ]);
    }
}
