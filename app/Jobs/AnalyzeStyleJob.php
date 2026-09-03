<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\StyleProfile;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeStyleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public Document $document,
        public StyleProfile $profile,
    ) {}

    public function handle(StyleEngine $engine): void
    {
        $analysis = $this->document->latestAnalysis;

        if (! $analysis) {
            return;
        }

        $elements = $analysis->detectedElements()->get();

        $violations = $engine->analyze($elements, $this->profile, [], $this->document->enforcement_mode);

        foreach ($violations as $violation) {
            StyleViolation::create([
                'document_analysis_id' => $analysis->id,
                'detected_element_id' => $violation->detected_element_id,
                'check_type' => $violation->check_type,
                'expected_value' => $violation->expected_value,
                'actual_value' => $violation->actual_value,
                'severity' => $violation->severity,
                'category' => $violation->category,
                'description' => $violation->description,
                'recommendation' => $violation->recommendation,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        report($exception);
    }
}
