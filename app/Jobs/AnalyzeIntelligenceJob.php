<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\IssueCollector;
use App\Services\ReviewStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeIntelligenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public Document $document,
    ) {
        $this->onQueue('nlp');
    }

    public function handle(IssueCollector $collector, ReviewStatusService $reviewStatus): void
    {
        if (! $this->document->ai_enabled) {
            return;
        }

        $collector->collect($this->document);

        $reviewStatus->refresh($this->document);
    }
}
