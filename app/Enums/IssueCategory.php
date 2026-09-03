<?php

namespace App\Enums;

enum IssueCategory: string
{
    case Font = 'font';
    case Spacing = 'spacing';
    case Alignment = 'alignment';
    case Numbering = 'numbering';
    case Caption = 'caption';
    case Source = 'source';
    case Hierarchy = 'hierarchy';
    case Consistency = 'consistency';
    case Duplicate = 'duplicate';
    case Reference = 'reference';
    case PageIntegrity = 'page_integrity';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Font => 'Font',
            self::Spacing => 'Spacing',
            self::Alignment => 'Alignment',
            self::Numbering => 'Numbering',
            self::Caption => 'Caption',
            self::Source => 'Source',
            self::Hierarchy => 'Hierarchy',
            self::Consistency => 'Consistency',
            self::Duplicate => 'Duplicate',
            self::Reference => 'Reference',
            self::PageIntegrity => 'Page integrity',
            self::Other => 'Other',
        };
    }
}
