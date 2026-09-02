<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ServiceRequestSource: string
{
    use EnumHelpers;

    case Website = 'website';
    case Referral = 'referral';
    case Call = 'call';
    case Email = 'email';
    case WalkIn = 'walk_in';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Referral => 'Referral',
            self::Call => 'Phone call',
            self::Email => 'Email',
            self::WalkIn => 'Walk-in',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return 'gray';
    }
}
