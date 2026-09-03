<?php

namespace App\Enums;

enum ReviewMode: string
{
    case All = 'all';
    case Formatting = 'formatting';
    case Citations = 'citations';
    case Bibliography = 'bibliography';
    case Similarity = 'similarity';
    case Ai = 'ai';
    case Grammar = 'grammar';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All',
            self::Formatting => 'Formatting',
            self::Citations => 'Citations',
            self::Bibliography => 'Bibliography',
            self::Similarity => 'Similarity',
            self::Ai => 'AI',
            self::Grammar => 'Grammar',
        };
    }

    /**
     * Coverage of an issue source by this review mode.
     */
    public function matches(IssueSource $source): bool
    {
        if ($this === self::All) {
            return true;
        }

        return $source->reviewMode() === $this
            || ($this === self::Bibliography && in_array($source, [IssueSource::Bibliography], true))
            || ($this === self::Ai && $source === IssueSource::Figure)
            || ($this === self::Grammar && $source === IssueSource::Citation);
    }
}
