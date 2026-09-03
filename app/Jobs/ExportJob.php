<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Notifications\DocumentExportCompleted;
use App\Notifications\DocumentExportFailed;
use App\Services\DocxExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public Document $document,
        public ?int $userId = null,
    ) {
        $this->onQueue('exports');
    }

    public function handle(DocxExportService $service): void
    {
        $this->document->update(['status' => DocumentStatus::EXPORTING]);

        try {
            $export = $service->export($this->document, $this->userId);

            $this->document->update([
                'status' => $export['integrity']['valid']
                    ? DocumentStatus::COMPLETED
                    : DocumentStatus::FAILED,
            ]);

            $owner = $this->document->project->owner;

            if ($owner) {
                $owner->notify(new DocumentExportCompleted($this->document, $export));
            }
        } catch (\Throwable $e) {
            $this->document->update(['status' => DocumentStatus::FAILED]);

            $owner = $this->document->project->owner;

            if ($owner) {
                $owner->notify(new DocumentExportFailed($this->document, $e->getMessage()));
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->document->update(['status' => DocumentStatus::FAILED]);

        report($exception);
    }
}
