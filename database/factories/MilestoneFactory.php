<?php

namespace Database\Factories;

use App\Enums\WorkStatus;
use App\Models\Milestone;
use App\Models\Phase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Milestone> */
class MilestoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'status' => WorkStatus::NotStarted,
            'progress' => 0,
            'position' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Milestone $milestone) {
            $milestone->phase_id ??= Phase::factory()->create()->id;
            // The phase owns the project relationship — keep it consistent.
            $milestone->project_id = Phase::find($milestone->phase_id)?->project_id;
        });
    }
}
