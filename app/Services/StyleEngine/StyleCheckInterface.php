<?php

namespace App\Services\StyleEngine;

use App\Models\DetectedElement;
use App\Models\StyleViolation;

interface StyleCheckInterface
{
    public function check(DetectedElement $element, array $rule): ?StyleViolation;

    public function getCheckType(): string;

    public function getCategory(): string;
}
