<?php

namespace App\Enums;

enum Reversibility: string
{
    case Full = 'full';
    case Partial = 'partial';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Fully reversible',
            self::Partial => 'Partially reversible',
            self::None => 'Not reversible',
        };
    }

    public function isUndoable(): bool
    {
        return $this !== self::None;
    }
}
