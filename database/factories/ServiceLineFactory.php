<?php

namespace Database\Factories;

use App\Models\ServiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceLine> */
class ServiceLineFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => str($name)->slug()->value(),
            'active' => true,
            'position' => 0,
        ];
    }
}
