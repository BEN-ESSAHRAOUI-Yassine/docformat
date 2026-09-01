<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class SpacingCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $violations = [];

        $expectedBefore = $rule['spacing_before'] ?? null;
        $actualBefore = $element->metadata['spacing_before'] ?? null;

        if ($expectedBefore !== null && $actualBefore !== null && $expectedBefore !== $actualBefore) {
            $violations[] = new StyleViolation([
                'check_type' => $this->getCheckType(),
                'expected_value' => ['spacing_before' => $expectedBefore],
                'actual_value' => ['spacing_before' => $actualBefore],
                'severity' => 'warning',
                'category' => $this->getCategory(),
                'description' => "Spacing before is {$actualBefore}, expected {$expectedBefore}",
                'recommendation' => "Set spacing before to {$expectedBefore}",
            ]);
        }

        $expectedAfter = $rule['spacing_after'] ?? null;
        $actualAfter = $element->metadata['spacing_after'] ?? null;

        if ($expectedAfter !== null && $actualAfter !== null && $expectedAfter !== $actualAfter) {
            $violations[] = new StyleViolation([
                'check_type' => $this->getCheckType(),
                'expected_value' => ['spacing_after' => $expectedAfter],
                'actual_value' => ['spacing_after' => $actualAfter],
                'severity' => 'warning',
                'category' => $this->getCategory(),
                'description' => "Spacing after is {$actualAfter}, expected {$expectedAfter}",
                'recommendation' => "Set spacing after to {$expectedAfter}",
            ]);
        }

        return $violations !== [] ? $violations[0] : null;
    }

    public function getCheckType(): string
    {
        return 'spacing';
    }

    public function getCategory(): string
    {
        return 'spacing';
    }
}
