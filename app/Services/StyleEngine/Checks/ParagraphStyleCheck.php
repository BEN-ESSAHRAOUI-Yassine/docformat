<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class ParagraphStyleCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['paragraph_style'] ?? null;
        $actual = $element->metadata['paragraph_style'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['paragraph_style' => $expected],
            'actual_value' => ['paragraph_style' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => "Paragraph style is '{$actual}', expected '{$expected}'",
            'recommendation' => "Apply paragraph style '{$expected}'",
        ]);
    }

    public function getCheckType(): string
    {
        return 'paragraph_style';
    }

    public function getCategory(): string
    {
        return 'paragraph';
    }
}
