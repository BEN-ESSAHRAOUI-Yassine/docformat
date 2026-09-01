<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class BordersCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['borders'] ?? null;
        $actual = $element->metadata['borders'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['borders' => $expected],
            'actual_value' => ['borders' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => 'Border formatting does not match expected style',
            'recommendation' => 'Update border formatting to match profile',
        ]);
    }

    public function getCheckType(): string
    {
        return 'borders';
    }

    public function getCategory(): string
    {
        return 'borders';
    }
}
