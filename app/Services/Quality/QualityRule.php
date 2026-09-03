<?php

namespace App\Services\Quality;

interface QualityRule
{
    public function key(): string;

    public function label(): string;

    public function weight(): float;

    public function severity(): string;
}
