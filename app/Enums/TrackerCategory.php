<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Categories from the EMREC Master Business Tracker. "New Advertised Works"
 * from the spreadsheet maps to the Tenders module, not here.
 */
enum TrackerCategory: string
{
    use EnumHelpers;

    case DigitalProduct = 'digital_product';
    case ECommerce = 'ecommerce';
    case Partnership = 'partnership';
    case EmrecAffairs = 'emrec_affairs';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DigitalProduct => 'Digital Products',
            self::ECommerce => 'E-Commerce',
            self::Partnership => 'Agreements & Partnerships',
            self::EmrecAffairs => 'Emrec Affairs',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DigitalProduct => 'blue',
            self::ECommerce => 'purple',
            self::Partnership => 'green',
            self::EmrecAffairs => 'amber',
            self::Other => 'gray',
        };
    }
}
