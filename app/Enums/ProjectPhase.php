<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Stage-gated SDLC phases (SRS SDLC-1). Sequential — a project advances one
 * phase at a time and cannot skip.
 */
enum ProjectPhase: string
{
    use EnumHelpers;

    case Requirements = 'requirements';
    case SystemDesign = 'system_design';
    case Implementation = 'implementation';
    case Qa = 'qa';
    case Deployment = 'deployment';

    public function label(): string
    {
        return match ($this) {
            self::Requirements => 'Requirements',
            self::SystemDesign => 'System Design',
            self::Implementation => 'Implementation / Coding',
            self::Qa => 'Quality Assurance',
            self::Deployment => 'Deployment',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Requirements => 'gray',
            self::SystemDesign => 'blue',
            self::Implementation => 'amber',
            self::Qa => 'purple',
            self::Deployment => 'green',
        };
    }

    public function next(): ?self
    {
        $cases = self::cases();
        $i = array_search($this, $cases, true);

        return $cases[$i + 1] ?? null;
    }

    public function order(): int
    {
        return array_search($this, self::cases(), true);
    }
}
