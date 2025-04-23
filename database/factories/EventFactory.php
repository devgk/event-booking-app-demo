<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
		return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'event_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'seats_available' => $this->faker->numberBetween(10, 200),
            'location_country' => $this->faker->country,
            'user_id' => 1, // or optionally create a user factory
        ];
    }
}
