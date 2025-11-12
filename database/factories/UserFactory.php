<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'user_type' => 'general_attendee',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Create an administrator user.
     */
    public function administrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'administrator',
        ]);
    }

    /**
     * Create an administrator assistant user.
     */
    public function administratorAssistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'administrator_assistant',
        ]);
    }

    /**
     * Create a site manager user.
     */
    public function siteManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'site_manager',
        ]);
    }

    /**
     * Create a volunteer user.
     */
    public function volunteer(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'volunteer',
        ]);
    }

    /**
     * Create a VIP attendee user.
     */
    public function vipAttendee(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'vip_attendee',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
