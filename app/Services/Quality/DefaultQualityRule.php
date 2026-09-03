<?php

namespace App\Services\Quality;

use App\Enums\IssueSource;

class DefaultQualityRule implements QualityRule
{
    public function __construct(
        private string $key,
        private string $label,
        private float $weight,
        private string $severity = 'warning',
        private array $sources = [],
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function weight(): float
    {
        return $this->weight;
    }

    public function severity(): string
    {
        return $this->severity;
    }

    /**
     * @return IssueSource[]
     */
    public function sources(): array
    {
        return $this->sources;
    }
}
