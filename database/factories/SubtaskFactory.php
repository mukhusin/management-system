<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subtask> */
class SubtaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'title' => fake()->sentence(3),
            'status' => TaskStatus::Todo,
            'progress' => 0,
            'position' => 0,
        ];
    }
}
