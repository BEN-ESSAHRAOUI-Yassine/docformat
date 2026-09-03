<?php

namespace App\Http\Controllers;

use App\Models\Citation;
use App\Models\Document;
use Illuminate\Http\JsonResponse;

class CitationController extends Controller
{
    public function index(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $citations = $document->citations()->with('bibliographyEntry')->get();

        return response()->json([
            'document_id' => $document->id,
            'citations' => $citations,
        ]);
    }

    public function bibliographyEntry(Document $document, Citation $citation): JsonResponse
    {
        $this->authorize('view', $document);

        if ($citation->document_id !== $document->id) {
            return response()->json(['message' => 'Citation not found.'], 404);
        }

        $entry = $citation->bibliographyEntry;

        if (! $entry) {
            return response()->json(['message' => 'No linked bibliography entry.'], 404);
        }

        return response()->json([
            'citation' => $citation,
            'bibliography_entry' => $entry,
        ]);
    }
}
