<?php

namespace Database\Factories;

use App\Enums\WorkStatus;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Milestone> */
class MilestoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(3, true),
            'status' => WorkStatus::NotStarted,
            'progress' => 0,
            'position' => 0,
        ];
    }
}
