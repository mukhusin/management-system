<?php

namespace App\Enums\Concerns;

/**
 * Shared helpers for the backed string enums in App\Enums. Each enum still
 * defines its own label() and color(); this adds the list/compare plumbing.
 */
trait EnumHelpers
{
    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function tryFromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->label(), $label) === 0) {
                return $case;
            }
        }

        return null;
    }

    public function is(self $other): bool
    {
        return $this === $other;
    }

    public function isAny(self ...$others): bool
    {
        return in_array($this, $others, true);
    }
}
