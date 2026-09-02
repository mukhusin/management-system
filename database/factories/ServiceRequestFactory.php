<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\ServiceRequestSource;
use App\Enums\ServiceRequestState;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceRequest> */
class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => ServiceRequestSource::Website,
            'state' => ServiceRequestState::New,
            'priority' => Priority::Medium,
            'client' => fake()->company(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'summary' => fake()->sentence(4),
            'details' => fake()->paragraph(),
            'estimated_value' => fake()->numberBetween(5000, 200000),
            'currency' => 'USD',
        ];
    }

    public function inState(ServiceRequestState $state): static
    {
        return $this->state(fn () => ['state' => $state]);
    }
}
