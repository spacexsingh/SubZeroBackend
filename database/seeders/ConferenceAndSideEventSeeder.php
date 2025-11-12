<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\LumaApiKey;
use App\Models\LumaEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Conference;
use App\Models\SideEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConferenceAndSideEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $conferenceStart = $now->copy()->subDays(2)->setTime(9, 0, 0);
        $conferenceEnd = $now->copy()->addDays(5)->setTime(18, 0, 0);

        // Create Conference
        $conference = Conference::updateOrCreate(
            ['id' => 1],
            [
                'title' => 'Tech Conference 2024',
                'slug' => 'tech-conference-2024',
                'description' => 'Annual technology conference',
                'short_description' => 'Tech conference bringing together industry leaders',
                'start_datetime' => $conferenceStart,
                'end_datetime' => $conferenceEnd,
                'registration_start_datetime' => $conferenceStart->copy()->subMonth(),
                'registration_end_datetime' => $conferenceStart->copy()->subDays(1)->setTime(23, 59, 59),
                'timezone' => 'America/New_York',
                'venue_name' => 'Convention Center',
                'venue_address' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'USA',
                'created_by' => 1,
            ]
        );

        // Generate a random side event time window within the conference
        $randomStart = Carbon::createFromTimestamp(
            rand($conferenceStart->timestamp, $conferenceEnd->timestamp - (2 * 3600))
        )->setTimezone($conferenceStart->timezone);

        $randomEnd = $randomStart->copy()->addHours(2);

        // Create Side Event
        $sideEvent = SideEvent::updateOrCreate(
            ['id' => 1],
            [
                'conference_id' => $conference->id,
                'title' => 'AI Workshop',
                'slug' => 'ai-workshop',
                'description' => 'Hands-on AI development workshop',
                'start_datetime' => $randomStart,
                'end_datetime' => $randomEnd,
                'venue_name' => 'Convention Center',
                'venue_address' => null,
                'room_number' => 'Room 305',
                'event_type' => 'workshop',
                'created_by' => 1,
            ]
        );

        // === Dynamically fetch users by user_type ===
        $siteManager = User::where('user_type', UserType::SITE_MANAGER)->first();
        $volunteer = User::where('user_type', UserType::VOLUNTEER)->first();

        // === Create Conference Users (pivot records) ===
        $conferenceUsers = [
            ['user' => $siteManager, 'role' => 'site_manager'],
            ['user' => $volunteer, 'role' => 'volunteer'],
        ];

        foreach ($conferenceUsers as $entry) {
            if ($entry['user']) {
                DB::table('conference_user')->updateOrInsert(
                    [
                        'conference_id' => $conference->id,
                        'user_id' => $entry['user']->id,
                    ],
                    [
                        'role' => $entry['role'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // === Create Luma Event ===
        LumaEvent::updateOrCreate(
            ['id' => 1],
            [
                'side_event_id' => $sideEvent->id,
                'luma_event_id' => config('luma.event.id'),
                'name' => 'sub0 symbiosis',
                'is_active' => true,
            ]
        );

        // === Create Luma API Key ===
        LumaApiKey::updateOrCreate(
            ['id' => 1],
            [
                'conference_id' => $conference->id,
                'api_key' => config('luma.event.api_key'),
                'is_active' => true,
            ]
        );
    }
}
