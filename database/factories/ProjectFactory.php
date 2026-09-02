<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->catchPhrase(),
            'type' => ProjectType::Engagement,
            'status' => ProjectStatus::NotStarted,
            'priority' => Priority::Medium,
            'client' => fake()->company(),
            'progress' => 0,
        ];
    }

    public function sdlc(): static
    {
        return $this->state(fn () => ['type' => ProjectType::Sdlc])
            ->afterCreating(fn ($project) => \App\Models\Phase::seedSdlc($project));
    }
}
