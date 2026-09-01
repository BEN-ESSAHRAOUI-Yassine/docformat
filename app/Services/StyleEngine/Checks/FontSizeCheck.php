<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class FontSizeCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['font_size'] ?? null;
        $actual = $element->metadata['font_size'] ?? null;

        if ($expected === null || $actual === null || $expected == $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['font_size' => $expected],
            'actual_value' => ['font_size' => $actual],
            'severity' => 'error',
            'category' => $this->getCategory(),
            'description' => "Font size is {$actual}pt, expected {$expected}pt",
            'recommendation' => "Change font size to {$expected}pt",
        ]);
    }

    public function getCheckType(): string
    {
        return 'font_size';
    }

    public function getCategory(): string
    {
        return 'font';
    }
}
