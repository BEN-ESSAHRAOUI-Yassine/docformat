<?php

namespace App\Services\StyleEngine\Checks;

use App\Models\DetectedElement;
use App\Models\StyleViolation;
use App\Services\StyleEngine\StyleCheckInterface;

class IndentationCheck implements StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation
    {
        $expected = $rule['indentation'] ?? null;
        $actual = $element->metadata['indentation'] ?? null;

        if ($expected === null || $actual === null || $expected === $actual) {
            return null;
        }

        return new StyleViolation([
            'check_type' => $this->getCheckType(),
            'expected_value' => ['indentation' => $expected],
            'actual_value' => ['indentation' => $actual],
            'severity' => 'warning',
            'category' => $this->getCategory(),
            'description' => "Indentation is {$actual}\", expected {$expected}\"",
            'recommendation' => "Set indentation to {$expected}\"",
        ]);
    }

    public function getCheckType(): string
    {
        return 'indentation';
    }

    public function getCategory(): string
    {
        return 'spacing';
    }
}
