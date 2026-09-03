<?php

namespace App\Enums;

enum IssueSource: string
{
    case Style = 'style';
    case Citation = 'citation';
    case Bibliography = 'bibliography';
    case Abbreviation = 'abbreviation';
    case Duplicate = 'duplicate';
    case Figure = 'figure';
    case Table = 'table';
    case PageIntegrity = 'page_integrity';
    case Numbering = 'numbering';

    public function label(): string
    {
        return match ($this) {
            self::Style => 'Style',
            self::Citation => 'Citations',
            self::Bibliography => 'Bibliography',
            self::Abbreviation => 'Abbreviations',
            self::Duplicate => 'Duplicates',
            self::Figure => 'Figures',
            self::Table => 'Tables',
            self::PageIntegrity => 'Page integrity',
            self::Numbering => 'Numbering',
        };
    }

    public function reviewMode(): ReviewMode
    {
        return match ($this) {
            self::Style => ReviewMode::Formatting,
            self::Citation, self::Bibliography, self::Abbreviation, self::Duplicate => ReviewMode::Citations,
            self::Figure, self::Table, self::PageIntegrity, self::Numbering => ReviewMode::Formatting,
        };
    }
}
