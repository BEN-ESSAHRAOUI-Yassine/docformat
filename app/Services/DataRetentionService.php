<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

class DataRetentionService
{
    public function __construct(
        private ActionLogger $actionLogger,
    ) {}

    /**
     * Remove documents older than the configured retention window (and their stored files).
     */
    public function purge(): int
    {
        $days = (int) config('services.retention.document_days', 365);
        $cutoff = now()->subDays($days);

        $documents = Document::where('created_at', '<', $cutoff)->get();

        foreach ($documents as $document) {
            $this->deleteDocumentFiles($document);
            $document->forceDelete();
        }

        // Projects with no remaining documents and past the cutoff.
        Project::where('created_at', '<', $cutoff)
            ->whereDoesntHave('documents')
            ->forceDelete();

        return $documents->count();
    }

    private function deleteDocumentFiles(Document $document): void
    {
        foreach ($document->versions as $version) {
            Storage::disk('docformat')->delete($version->file_path);
        }
    }
}
