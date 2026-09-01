<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case UPLOADED = 'uploaded';
    case QUEUED = 'queued';
    case ANALYZING = 'analyzing';
    case ANALYSIS_COMPLETED = 'analysis_completed';
    case PROCESSING = 'processing';
    case REVIEW_REQUIRED = 'review_required';
    case READY_FOR_EXPORT = 'ready_for_export';
    case EXPORTING = 'exporting';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case ARCHIVED = 'archived';
}
