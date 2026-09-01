<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class AlignmentCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['alignment'] ?? null;
        $actual = $element->metadata['alignment'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['alignment' => $expected],
            'actual_value' => ['alignment' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => "Alignment is '{$actual}', expected '{$expected}'",
            'recommendation' => "Change alignment to {$expected}",
        ]);
    }

    public function getCheckType(): string
    {
        return 'alignment';
    }

    public function getCategory(): string
    {
        return 'alignment';
    }
}
