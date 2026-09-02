<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\FeatureSet;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'feature_set_id' => FeatureSet::factory(),
            'title' => fake()->sentence(4),
            'status' => TaskStatus::Todo,
            'priority' => Priority::Medium,
            'progress' => 0,
            'position' => 0,
        ];
    }
}
