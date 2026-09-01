<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class LineSpacingCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['line_spacing'] ?? null;
        $actual = $element->metadata['line_spacing'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['line_spacing' => $expected],
            'actual_value' => ['line_spacing' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => "Line spacing is {$actual}, expected {$expected}",
            'recommendation' => "Set line spacing to {$expected}",
        ]);
    }

    public function getCheckType(): string
    {
        return 'line_spacing';
    }

    public function getCategory(): string
    {
        return 'spacing';
    }
}
