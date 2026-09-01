<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class ItalicCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['italic'] ?? null;
        $actual = $element->metadata['italic'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['italic' => $expected],
            'actual_value' => ['italic' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => $expected ? 'Text should be italic' : 'Text should not be italic',
            'recommendation' => $expected ? 'Apply italic formatting' : 'Remove italic formatting',
        ]);
    }

    public function getCheckType(): string
    {
        return 'italic';
    }

    public function getCategory(): string
    {
        return 'formatting';
    }
}
