<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\TrackerCategory;
use App\Enums\TrackerStatus;
use App\Models\TrackerItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TrackerItem> */
class TrackerItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category' => TrackerCategory::DigitalProduct,
            'title' => fake()->sentence(4),
            'status' => TrackerStatus::NotStarted,
            'priority' => Priority::Medium,
            'entry_date' => now(),
        ];
    }
}
