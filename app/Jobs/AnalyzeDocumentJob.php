<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentAnalysisService;
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

    public function handle(DocumentAnalysisService $service): void
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
    }

    public function failed(\Throwable $exception): void
    {
        $this->document->update(['status' => 'failed']);

        report($exception);
    }
}
