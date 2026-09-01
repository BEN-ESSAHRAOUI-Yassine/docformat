<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class FontFamilyCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['font_family'] ?? null;
        $actual = $element->metadata['font_family'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['font_family' => $expected],
            'actual_value' => ['font_family' => $actual],
            'severity' => 'error',
            'category' => $this->getCategory(),
            'description' => "Font family is '{$actual}', expected '{$expected}'",
            'recommendation' => "Change font to {$expected}",
        ]);
    }

    public function getCheckType(): string
    {
        return 'font_family';
    }

    public function getCategory(): string
    {
        return 'font';
    }
}
