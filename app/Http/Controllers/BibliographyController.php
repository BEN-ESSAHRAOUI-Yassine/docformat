<?php

namespace App\Http\Controllers;

use App\Models\BibliographyEntry;
use App\Models\Document;
use App\Services\DuplicateDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function merge(Request $request, Document $document, BibliographyEntry $entry): JsonResponse
    {
        $this->authorize('view', $document);

        if ($entry->document_id !== $document->id) {
            return response()->json(['message' => 'Bibliography entry not found.'], 404);
        }

        $data = $request->validate([
            'merge_with' => ['required', 'exists:bibliography_entries,id'],
            'field_choices' => ['nullable', 'array'],
        ]);

        $mergeEntry = BibliographyEntry::findOrFail($data['merge_with']);

        if ($mergeEntry->document_id !== $document->id || $mergeEntry->id === $entry->id) {
            return response()->json(['message' => 'Cannot merge with that entry.'], 422);
        }

        app(DuplicateDetector::class)->merge($entry, $mergeEntry, $data['field_choices'] ?? []);

        return response()->json([
            'message' => 'Bibliography entries merged.',
            'entry' => $entry->fresh(),
        ]);
    }
}
