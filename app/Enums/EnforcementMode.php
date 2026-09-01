<?php

namespace App\Enums;

enum EnforcementMode: string
{
    case Strict = 'strict';
    case Recommended = 'recommended';
    case AuditOnly = 'audit_only';

    public function label(): string
    {
        return match ($this) {
            self::Strict => 'Strict',
            self::Recommended => 'Recommended',
            self::AuditOnly => 'Audit Only',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Strict => 'Automatically enforce configured rules on processing',
            self::Recommended => 'Generate suggestions and allow user review',
            self::AuditOnly => 'Detect issues but never modify',
        };
    }
}
