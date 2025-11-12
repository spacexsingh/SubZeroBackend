<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conference>
 */
class ConferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+2 months');

        return [
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'start_datetime' => $startDate,
            'end_datetime' => $endDate,
            'registration_start_datetime' => fake()->dateTimeBetween('now', $startDate),
            'registration_end_datetime' => $startDate,
            'timezone' => 'UTC',
            'venue_name' => fake()->company() . ' Convention Center',
            'venue_address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
