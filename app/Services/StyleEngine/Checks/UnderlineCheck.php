<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class UnderlineCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['underline'] ?? null;
        $actual = $element->metadata['underline'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['underline' => $expected],
            'actual_value' => ['underline' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => $expected ? 'Text should be underlined' : 'Text should not be underlined',
            'recommendation' => $expected ? 'Apply underline formatting' : 'Remove underline formatting',
        ]);
    }

    public function getCheckType(): string
    {
        return 'underline';
    }

    public function getCategory(): string
    {
        return 'formatting';
    }
}
