<?php

namespace App\Enums;

enum IssueDecision: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Edited = 'edited';
    case Ignored = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Edited => 'Edited',
            self::Ignored => 'Ignored',
        };
    }

    public function isDecided(): bool
    {
        return $this !== self::Pending;
    }
}
