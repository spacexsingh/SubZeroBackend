<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Administrator
        User::factory()->administrator()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create Administrator Assistant
        User::factory()->administratorAssistant()->create([
            'name' => 'Assistant User',
            'email' => 'assistant@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create Site Manager
        User::factory()->siteManager()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create Volunteer
        User::factory()->volunteer()->create([
            'name' => 'Volunteer User',
            'email' => 'volunteer@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create VIP Attendee
        User::factory()->vipAttendee()->create([
            'name' => 'VIP User',
            'email' => 'vip@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create General Attendee
        User::factory()->create([
            'name' => 'General User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}
