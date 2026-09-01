<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class FontColorCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['color'] ?? null;
        $actual = $element->metadata['color'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['color' => $expected],
            'actual_value' => ['color' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => "Font color is '{$actual}', expected '{$expected}'",
            'recommendation' => "Change font color to {$expected}",
        ]);
    }

    public function getCheckType(): string
    {
        return 'font_color';
    }

    public function getCategory(): string
    {
        return 'font';
    }
}
