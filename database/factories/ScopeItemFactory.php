<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ScopeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ScopeItem> */
class ScopeItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'code' => 'S'.fake()->unique()->numberBetween(1, 999),
            'description' => fake()->sentence(8),
            'source' => 'manual',
            'position' => 0,
        ];
    }
}
