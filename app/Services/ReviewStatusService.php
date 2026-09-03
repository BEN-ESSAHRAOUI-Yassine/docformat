<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\Document;

class ReviewStatusService
{
    /**
     * Recompute the document's review status based on pending issues.
     */
    public function refresh(Document $document): Document
    {
        $hasPending = $document->pendingIssues()->exists();

        $document->update([
            'status' => $hasPending
                ? DocumentStatus::REVIEW_REQUIRED
                : DocumentStatus::READY_FOR_EXPORT,
        ]);

        return $document->fresh();
    }
}
