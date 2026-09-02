<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Delivery model for a project. `sdlc` projects run through the stage-gated
 * ProjectPhase pipeline; `engagement` projects (research, M&E, legal,
 * construction, sourcing…) are milestone-driven with no fixed phases.
 */
enum ProjectType: string
{
    use EnumHelpers;

    case Sdlc = 'sdlc';
    case Engagement = 'engagement';

    public function label(): string
    {
        return match ($this) {
            self::Sdlc => 'Software (SDLC)',
            self::Engagement => 'Consulting engagement',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sdlc => 'purple',
            self::Engagement => 'blue',
        };
    }

    public function usesPhases(): bool
    {
        return $this === self::Sdlc;
    }
}
