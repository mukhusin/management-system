<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\TenderState;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tender> */
class TenderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => 'manual',
            'external_id' => fake()->unique()->uuid(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'client' => fake()->company(),
            'country' => 'Tanzania',
            'state' => TenderState::Draft,
            'priority' => Priority::Medium,
            'value' => fake()->numberBetween(10000, 500000),
            'currency' => 'USD',
            'deadline_date' => now()->addDays(fake()->numberBetween(5, 60)),
            'adopted_at' => now(),
        ];
    }

    public function inState(TenderState $state): static
    {
        return $this->state(fn () => ['state' => $state]);
    }

    public function won(): static
    {
        return $this->inState(TenderState::Won);
    }

    /** An unclaimed opportunity fresh from an external source. */
    public function opportunity(): static
    {
        return $this->state(fn () => [
            'source' => 'world_bank',
            'state' => TenderState::Draft,
            'adopted_at' => null,
            'adopted_by' => null,
        ]);
    }
}
