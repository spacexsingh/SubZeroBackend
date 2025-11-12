<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SideEvent>
 */
class SideEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, $startDate->format('Y-m-d H:i:s') . ' +4 hours');

        return [
            'conference_id' => \App\Models\Conference::factory(),
            'title' => fake()->sentence(3),
            'slug' => fake()->slug(),
            'description' => fake()->paragraph(),
            'start_datetime' => $startDate,
            'end_datetime' => $endDate,
            'venue_name' => fake()->company() . ' Hall',
            'venue_address' => fake()->address(),
            'room_number' => 'Room ' . fake()->numberBetween(100, 500),
            'event_type' => fake()->randomElement(['workshop', 'panel', 'networking', 'presentation']),
            'sort_order' => fake()->numberBetween(0, 10),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
