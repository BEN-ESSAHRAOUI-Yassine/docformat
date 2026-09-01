<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class AllCapsCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['all_caps'] ?? null;
        $actual = $element->metadata['all_caps'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['all_caps' => $expected],
            'actual_value' => ['all_caps' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => $expected ? 'Text should be ALL CAPS' : 'Text should not be ALL CAPS',
            'recommendation' => $expected ? 'Apply all caps formatting' : 'Remove all caps formatting',
        ]);
    }

    public function getCheckType(): string
    {
        return 'all_caps';
    }

    public function getCategory(): string
    {
        return 'capitalization';
    }
}
