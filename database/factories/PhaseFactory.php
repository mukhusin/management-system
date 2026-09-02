<?php

namespace Database\Factories;

use App\Enums\WorkStatus;
use App\Models\Phase;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Phase> */
class PhaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->randomElement(['Requirements', 'Design', 'Build', 'QA', 'Deployment']),
            'status' => WorkStatus::NotStarted,
            'progress' => 0,
            'position' => 0,
        ];
    }
}
