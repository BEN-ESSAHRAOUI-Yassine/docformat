<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class ShadingCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['shading'] ?? null;
        $actual = $element->metadata['shading'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['shading' => $expected],
            'actual_value' => ['shading' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => 'Shading formatting does not match expected style',
            'recommendation' => 'Update shading formatting to match profile',
        ]);
    }

    public function getCheckType(): string
    {
        return 'shading';
    }

    public function getCategory(): string
    {
        return 'borders';
    }
}
