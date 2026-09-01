<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class NumberingCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['numbering'] ?? null;
        $actual = $element->metadata['numbering'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['numbering' => $expected],
            'actual_value' => ['numbering' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => $expected ? 'Text should have numbering' : 'Text should not have numbering',
            'recommendation' => $expected ? 'Apply numbering' : 'Remove numbering',
        ]);
    }

    public function getCheckType(): string
    {
        return 'numbering';
    }

    public function getCategory(): string
    {
        return 'formatting';
    }
}
