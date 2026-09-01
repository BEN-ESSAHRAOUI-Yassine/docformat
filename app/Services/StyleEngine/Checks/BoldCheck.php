<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class BoldCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['bold'] ?? null;
        $actual = $element->metadata['bold'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['bold' => $expected],
            'actual_value' => ['bold' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => $expected ? 'Text should be bold' : 'Text should not be bold',
            'recommendation' => $expected ? 'Apply bold formatting' : 'Remove bold formatting',
        ]);
    }

    public function getCheckType(): string
    {
        return 'bold';
    }

    public function getCategory(): string
    {
        return 'formatting';
    }
}
