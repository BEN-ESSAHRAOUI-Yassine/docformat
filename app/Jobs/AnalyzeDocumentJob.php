<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentAnalysisService;
use App\Services\IssueCollector;
use App\Services\ReviewStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public Document $document,
    ) {
        $this->onQueue('document-processing');
    }

    public function handle(DocumentAnalysisService $service, IssueCollector $collector, ReviewStatusService $reviewStatus): void
    {
        if (! $this->document->language) {
            $this->document->update(['status' => 'failed']);

            return;
        }

        $version = $this->document->currentVersion;

        if (! $version) {
            $this->document->update(['status' => 'failed']);

            return;
        }

        $this->document->update(['status' => 'analyzing']);

        $service->analyze($this->document, $version);

        $collector->collect($this->document);

        $reviewStatus->refresh($this->document);
    }

    public function failed(\Throwable $exception): void
    {
        $this->document->update(['status' => 'failed']);

        report($exception);
    }
}
