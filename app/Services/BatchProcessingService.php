<?php

namespace App\Services;

use App\Enums\BatchItemStatus;
use App\Enums\BatchStatus;
use App\Jobs\BatchJob;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Services\Quality\QualityEngine;

class BatchProcessingService
{
    public function __construct(
        private DocumentAnalysisService $analysisService,
        private IssueCollector $issueCollector,
        private ReviewStatusService $reviewStatus,
        private QualityEngine $qualityEngine,
    ) {}

    public function create(int $projectId, string $name, array $documentIds, ?int $styleProfileId = null, ?int $userId = null): Batch
    {
        $batch = Batch::create([
            'project_id' => $projectId,
            'style_profile_id' => $styleProfileId,
            'name' => $name,
            'status' => BatchStatus::Queued,
        ]);

        foreach ($documentIds as $documentId) {
            $batch->items()->create([
                'document_id' => $documentId,
                'status' => BatchItemStatus::Queued,
            ]);
        }

        $this->recomputeSummary($batch);

        BatchJob::dispatch($batch, $userId);

        return $batch->fresh();
    }

    public function process(Batch $batch, ?int $userId = null): Batch
    {
        $batch->update(['status' => BatchStatus::Processing]);

        foreach ($batch->items as $item) {
            $this->processItem($item, $userId);
        }

        $this->recomputeSummary($batch);

        return $batch->fresh();
    }

    private function processItem(BatchItem $item, ?int $userId = null): void
    {
        $document = $item->document;

        $item->update(['status' => BatchItemStatus::Processing]);

        try {
            if ($document->currentVersion && $document->language) {
                $document->update(['status' => 'analyzing']);
                $this->analysisService->analyze($document, $document->currentVersion);
                $this->issueCollector->collect($document);
                $this->reviewStatus->refresh($document);
            }

            $score = $this->qualityEngine->score($document);

            $item->update([
                'status' => BatchItemStatus::Completed,
                'quality_score' => $score['overall_score'],
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            $item->update([
                'status' => BatchItemStatus::Failed,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function recomputeSummary(Batch $batch): void
    {
        $items = $batch->items()->get();

        $total = $items->count();
        $completed = $items->where('status', BatchItemStatus::Completed->value)->count();
        $failed = $items->where('status', BatchItemStatus::Failed->value)->count();

        $scores = $items->where('status', BatchItemStatus::Completed->value)
            ->pluck('quality_score')
            ->filter(fn ($s) => $s !== null);

        $average = $scores->isEmpty() ? null : round($scores->avg(), 1);

        $pending = $items->where('status', BatchItemStatus::Queued->value)->count() + $items->where('status', BatchItemStatus::Processing->value)->count();
        $processing = $items->where('status', BatchItemStatus::Processing->value)->count();

        $status = match (true) {
            $total === 0 => BatchStatus::Queued,
            $processing > 0 => BatchStatus::Processing,
            $failed === $total && $total > 0 => BatchStatus::Failed,
            $failed > 0 => BatchStatus::Partial,
            // All items resolved (none failed) but queue still has queued items => not started yet.
            $pending > 0 && $completed === 0 => BatchStatus::Queued,
            default => BatchStatus::Completed,
        };

        $batch->update([
            'status' => $status,
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'failed' => $failed,
                'pending' => $items->where('status', BatchItemStatus::Queued->value)->count() + $items->where('status', BatchItemStatus::Processing->value)->count(),
                'average_score' => $average,
            ],
        ]);
    }
}
