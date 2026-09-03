<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentActionResource;
use App\Models\Document;
use App\Services\HistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function __construct(
        private HistoryService $history,
    ) {}

    public function index(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $query = $document->actions()->with('user')->orderByDesc('id');

        if ($request->has('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        if ($request->has('origin')) {
            $query->where('origin', $request->input('origin'));
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        if ($request->has('limit')) {
            $query->limit(min((int) $request->input('limit'), HistoryService::MAX_DEPTH));
        }

        return response()->json([
            'document_id' => $document->id,
            'actions' => DocumentActionResource::collection($query->get()),
        ]);
    }

    public function history(Request $request, Document $document): JsonResponse
    {
        return $this->index($request, $document);
    }

    public function undo(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $action = $this->history->undo($document);

        if (! $action) {
            return response()->json(['message' => 'No reversible action to undo.'], 422);
        }

        return response()->json([
            'message' => 'Action undone.',
            'action' => new DocumentActionResource($action),
        ]);
    }

    public function redo(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $action = $this->history->redo($document);

        if (! $action) {
            return response()->json(['message' => 'No action to redo.'], 422);
        }

        return response()->json([
            'message' => 'Action redone.',
            'action' => new DocumentActionResource($action),
        ]);
    }
}
