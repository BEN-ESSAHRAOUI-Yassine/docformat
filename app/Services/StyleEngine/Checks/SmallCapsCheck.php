<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class SmallCapsCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['small_caps'] ?? null;
        $actual = $element->metadata['small_caps'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['small_caps' => $expected],
            'actual_value' => ['small_caps' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => $expected ? 'Text should be small caps' : 'Text should not be small caps',
            'recommendation' => $expected ? 'Apply small caps formatting' : 'Remove small caps formatting',
        ]);
    }

    public function getCheckType(): string
    {
        return 'small_caps';
    }

    public function getCategory(): string
    {
        return 'capitalization';
    }
}
