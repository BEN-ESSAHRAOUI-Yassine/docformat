<?php

namespace App\Http\Controllers;

use App\Models\DetectedElement;
use App\Models\Document;
use App\Services\PageBreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageBreakController extends Controller
{
    public function __construct(
        private PageBreakService $pageBreak,
    ) {}

    public function store(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $data = $request->validate([
            'context' => ['required', 'in:chapter,section,figure,table,appendix'],
            'element_id' => ['required_without:element_index'],
            'element_index' => ['required_without:element_id'],
            'type' => ['nullable', 'string'],
        ]);

        if (! empty($data['element_id'])) {
            $target = DetectedElement::find($data['element_id']);

            if (! $target || $target->document_id !== $document->id) {
                return response()->json(['message' => 'Target element not found.'], 404);
            }
        } else {
            $target = $document->elements()
                ->where('element_index', $data['element_index'])
                ->first();

            if (! $target) {
                return response()->json(['message' => 'Target element not found.'], 404);
            }
        }

        if (isset($data['type']) && ! in_array($target->type, [$data['type'], 'caption', 'heading'])) {
            $target->type = $data['type'];
        }

        $element = $this->pageBreak->insertBefore($document, $target, $data['context']);

        return response()->json($element, 201);
    }

    public function destroy(Document $document, DetectedElement $element): JsonResponse
    {
        $this->authorize('view', $document);

        if ($element->document_id !== $document->id) {
            return response()->json(['message' => 'Element not found.'], 404);
        }

        $removed = $this->pageBreak->remove($document, $element);

        if (! $removed) {
            return response()->json(['message' => 'Only user-inserted page breaks can be removed.'], 422);
        }

        return response()->json(['message' => 'Page break removed.']);
    }
}
