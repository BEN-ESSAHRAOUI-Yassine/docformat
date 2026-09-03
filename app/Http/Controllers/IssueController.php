<?php

namespace App\Http\Controllers;

use App\Enums\ActionOrigin;
use App\Enums\ActionType;
use App\Enums\IssueDecision;
use App\Enums\Reversibility;
use App\Http\Resources\DocumentIssueResource;
use App\Models\Document;
use App\Models\DocumentIssue;
use App\Services\ActionLogger;
use App\Services\ReviewStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IssueController extends Controller
{
    public function __construct(
        private ActionLogger $actionLogger,
        private ReviewStatusService $reviewStatus,
    ) {}

    public function index(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $query = $document->issues();

        if ($request->has('severity')) {
            $query->forSeverity($request->input('severity'));
        }

        if ($request->has('category')) {
            $query->forCategory($request->input('category'));
        }

        if ($request->has('decision')) {
            $query->byDecision($request->input('decision'));
        }

        if ($request->has('review_mode')) {
            $query->inReviewMode($request->input('review_mode'));
        }

        return response()->json([
            'document_id' => $document->id,
            'issues' => DocumentIssueResource::collection($query->orderByDesc('id')->paginate($request->input('per_page', 50))),
        ]);
    }

    public function accept(Request $request, Document $document, DocumentIssue $issue): JsonResponse
    {
        $this->authorize('view', $document);

        if ($issue->document_id !== $document->id) {
            return response()->json(['message' => 'Issue not found.'], 404);
        }

        $issue->update($this->decide($issue, IssueDecision::Accepted, $request));

        $this->actionLogger->record($document, [
            'action_type' => ActionType::IssueAccepted,
            'element_type' => 'DocumentIssue',
            'element_id' => $issue->id,
            'origin' => ActionOrigin::Manual,
            'reversibility' => Reversibility::Partial,
        ]);

        $this->reviewStatus->refresh($document);

        return response()->json(new DocumentIssueResource($issue->fresh()));
    }

    public function reject(Request $request, Document $document, DocumentIssue $issue): JsonResponse
    {
        $this->authorize('view', $document);

        if ($issue->document_id !== $document->id) {
            return response()->json(['message' => 'Issue not found.'], 404);
        }

        $issue->update($this->decide($issue, IssueDecision::Rejected, $request));

        $this->actionLogger->record($document, [
            'action_type' => ActionType::IssueRejected,
            'element_type' => 'DocumentIssue',
            'element_id' => $issue->id,
            'origin' => ActionOrigin::Manual,
            'reversibility' => Reversibility::Partial,
        ]);

        $this->reviewStatus->refresh($document);

        return response()->json(new DocumentIssueResource($issue->fresh()));
    }

    public function edit(Request $request, Document $document, DocumentIssue $issue): JsonResponse
    {
        $this->authorize('view', $document);

        if ($issue->document_id !== $document->id) {
            return response()->json(['message' => 'Issue not found.'], 404);
        }

        $data = $request->validate(['recommendation' => ['nullable', 'string']]);

        $issue->update([
            'recommendation' => $data['recommendation'] ?? $issue->recommendation,
            'decision' => IssueDecision::Edited->value,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        $this->actionLogger->record($document, [
            'action_type' => ActionType::IssueEdited,
            'element_type' => 'DocumentIssue',
            'element_id' => $issue->id,
            'origin' => ActionOrigin::Manual,
            'reversibility' => Reversibility::Partial,
        ]);

        $this->reviewStatus->refresh($document);

        return response()->json(new DocumentIssueResource($issue->fresh()));
    }

    public function ignore(Request $request, Document $document, DocumentIssue $issue): JsonResponse
    {
        $this->authorize('view', $document);

        if ($issue->document_id !== $document->id) {
            return response()->json(['message' => 'Issue not found.'], 404);
        }

        $data = $request->validate(['reason' => ['nullable', 'string']]);

        $issue->update([
            'decision' => IssueDecision::Ignored->value,
            'ignored_reason' => $data['reason'] ?? null,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        $this->actionLogger->record($document, [
            'action_type' => ActionType::IssueIgnored,
            'element_type' => 'DocumentIssue',
            'element_id' => $issue->id,
            'origin' => ActionOrigin::Manual,
            'reversibility' => Reversibility::Partial,
        ]);

        $this->reviewStatus->refresh($document);

        return response()->json(new DocumentIssueResource($issue->fresh()));
    }

    public function bulk(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $data = $request->validate([
            'decision' => ['required', 'in:accept,reject'],
            'mode' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
        ]);

        $query = $document->issues()->pending();

        if (! empty($data['mode']) && $data['mode'] !== 'all') {
            $query->inReviewMode($data['mode']);
        }

        if (! empty($data['category'])) {
            $query->forCategory($data['category']);
        }

        $issues = $query->get();

        $bulkId = Str::uuid()->toString();
        $decision = $data['decision'];

        foreach ($issues as $issue) {
            $issue->update([
                'decision' => $decision === 'accept' ? IssueDecision::Accepted->value : IssueDecision::Rejected->value,
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);

            $this->actionLogger->record($document, [
                'action_type' => $decision === 'accept' ? ActionType::IssueAccepted : ActionType::IssueRejected,
                'element_type' => 'DocumentIssue',
                'element_id' => $issue->id,
                'origin' => ActionOrigin::Manual,
                'reversibility' => Reversibility::Partial,
                'bulk_id' => $bulkId,
            ]);
        }

        $this->reviewStatus->refresh($document);

        return response()->json([
            'message' => "{$issues->count()} issues {$decision}ed.",
            'bulk_id' => $bulkId,
            'count' => $issues->count(),
        ]);
    }

    private function decide(DocumentIssue $issue, IssueDecision $decision, Request $request): array
    {
        return [
            'decision' => $decision->value,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ];
    }
}
