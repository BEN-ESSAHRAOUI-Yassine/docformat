<?php

namespace App\Jobs;

use App\Models\Batch;
use App\Services\BatchProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    public function __construct(
        public Batch $batch,
        public ?int $userId = null,
    ) {
        $this->onQueue('document-processing');
    }

    public function handle(BatchProcessingService $service): void
    {
        $service->process($this->batch, $this->userId);
    }

    public function failed(\Throwable $exception): void
    {
        $this->batch->update(['status' => 'failed']);

        report($exception);
    }
}
