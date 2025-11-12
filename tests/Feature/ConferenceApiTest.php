<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConferenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_conferences(): void
    {
        Conference::factory()->count(5)->create();

        $response = $this->getJson('/api/conferences');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_conferences_by_status(): void
    {
        // Create upcoming conference
        Conference::factory()->create([
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(15),
        ]);

        $response = $this->getJson('/api/conferences?status=upcoming');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_view_single_conference(): void
    {
        $conference = Conference::factory()->create();

        $response = $this->getJson("/api/conferences/{$conference->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $conference->id,
                'title' => $conference->title,
            ]);
    }

    public function test_administrator_can_create_conference(): void
    {
        $admin = User::factory()->administrator()->create();
        Sanctum::actingAs($admin);

        $conferenceData = [
            'title' => 'Test Conference',
            'start_datetime' => now()->addDays(10)->toDateTimeString(),
            'end_datetime' => now()->addDays(15)->toDateTimeString(),
            'city' => 'New York',
            'country' => 'USA',
        ];

        $response = $this->postJson('/api/conferences', $conferenceData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Conference created successfully',
            ]);

        $this->assertDatabaseHas('conferences', [
            'title' => 'Test Conference',
            'city' => 'New York',
        ]);
    }

    public function test_non_administrator_cannot_create_conference(): void
    {
        $user = User::factory()->create(); // general_attendee
        Sanctum::actingAs($user);

        $conferenceData = [
            'title' => 'Test Conference',
            'start_datetime' => now()->addDays(10)->toDateTimeString(),
            'end_datetime' => now()->addDays(15)->toDateTimeString(),
        ];

        $response = $this->postJson('/api/conferences', $conferenceData);

        $response->assertStatus(403);
    }

    public function test_administrator_can_update_conference(): void
    {
        $admin = User::factory()->administrator()->create();
        $conference = Conference::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/conferences/{$conference->id}", [
            'title' => 'Updated Conference Title',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Conference updated successfully',
            ]);

        $this->assertDatabaseHas('conferences', [
            'id' => $conference->id,
            'title' => 'Updated Conference Title',
        ]);
    }

    public function test_administrator_can_delete_conference(): void
    {
        $admin = User::factory()->administrator()->create();
        $conference = Conference::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/conferences/{$conference->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Conference deleted successfully',
            ]);

        $this->assertSoftDeleted('conferences', [
            'id' => $conference->id,
        ]);
    }

    public function test_validation_fails_when_end_date_before_start_date(): void
    {
        $admin = User::factory()->administrator()->create();
        Sanctum::actingAs($admin);

        $conferenceData = [
            'title' => 'Test Conference',
            'start_datetime' => now()->addDays(15)->toDateTimeString(),
            'end_datetime' => now()->addDays(10)->toDateTimeString(), // Before start
        ];

        $response = $this->postJson('/api/conferences', $conferenceData);

        $response->assertStatus(422);
    }

    public function test_slug_is_auto_generated_if_not_provided(): void
    {
        $admin = User::factory()->administrator()->create();
        Sanctum::actingAs($admin);

        $conferenceData = [
            'title' => 'Test Conference 2024',
            'start_datetime' => now()->addDays(10)->toDateTimeString(),
            'end_datetime' => now()->addDays(15)->toDateTimeString(),
        ];

        $response = $this->postJson('/api/conferences', $conferenceData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('conferences', [
            'title' => 'Test Conference 2024',
            'slug' => 'test-conference-2024',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_conference(): void
    {
        $conferenceData = [
            'title' => 'Test Conference',
            'start_datetime' => now()->addDays(10)->toDateTimeString(),
            'end_datetime' => now()->addDays(15)->toDateTimeString(),
        ];

        $response = $this->postJson('/api/conferences', $conferenceData);

        $response->assertStatus(401);
    }
}
