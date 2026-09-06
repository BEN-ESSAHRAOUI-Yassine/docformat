<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Document;
use App\Services\Quality\QualityEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private QualityEngine $qualityEngine,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $projectIds = $user->projects()->pluck('id');

        $documents = Document::whereIn('project_id', $projectIds);
        $batches = Batch::whereIn('project_id', $projectIds);

        $recentDocuments = (clone $documents)
            ->with('project', 'currentVersion')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        $recentBatches = (clone $batches)
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        $scores = (clone $documents)->get()
            ->map(fn (Document $document) => $this->qualityEngine->score($document)['overall_score'])
            ->filter();

        return response()->json([
            'counts' => [
                'projects' => $projectIds->count(),
                'documents' => (clone $documents)->count(),
                'batches' => (clone $batches)->count(),
                'average_quality' => $scores->isEmpty() ? null : round($scores->avg(), 1),
            ],
            'recent_documents' => $recentDocuments,
            'recent_batches' => $recentBatches,
        ]);
    }
}
