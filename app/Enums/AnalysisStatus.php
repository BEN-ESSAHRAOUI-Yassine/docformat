<?php

namespace App\Enums;

enum AnalysisStatus: string
{
    case PENDING = 'pending';
    case ANALYZING = 'analyzing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
