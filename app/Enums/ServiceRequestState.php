<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Inbound service-enquiry lifecycle:
 * New → Qualified → Quoted → Won → Engaged, with Declined / Lost exits.
 * A "Won" request is promoted to a Project, which moves it to "Engaged".
 */
enum ServiceRequestState: string
{
    use EnumHelpers;

    case New = 'new';
    case Qualified = 'qualified';
    case Quoted = 'quoted';
    case Won = 'won';
    case Engaged = 'engaged';
    case Declined = 'declined';
    case Lost = 'lost';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::Qualified => 'amber',
            self::Quoted => 'blue',
            self::Won => 'green',
            self::Engaged => 'green',
            self::Declined => 'gray',
            self::Lost => 'red',
        };
    }

    public function allowedNext(): array
    {
        return match ($this) {
            self::New => [self::Qualified, self::Declined],
            self::Qualified => [self::Quoted, self::Declined, self::Lost],
            self::Quoted => [self::Won, self::Lost, self::Declined],
            self::Won => [self::Engaged],
            self::Engaged, self::Declined, self::Lost => [],
        };
    }

    public function isTerminal(): bool
    {
        return $this->allowedNext() === [];
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNext(), true);
    }
}
