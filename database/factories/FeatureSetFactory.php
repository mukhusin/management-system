<?php

namespace Database\Factories;

use App\Enums\WorkStatus;
use App\Models\FeatureSet;
use App\Models\Milestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeatureSet> */
class FeatureSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'milestone_id' => Milestone::factory(),
            'name' => fake()->words(2, true),
            'status' => WorkStatus::NotStarted,
            'progress' => 0,
            'position' => 0,
        ];
    }
}
