<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\SideEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SideEventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_side_events_for_conference(): void
    {
        $conference = Conference::factory()->create();
        SideEvent::factory()->count(3)->create(['conference_id' => $conference->id]);

        $response = $this->getJson("/api/conferences/{$conference->id}/side-events");

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_view_single_side_event(): void
    {
        $conference = Conference::factory()->create();
        $sideEvent = SideEvent::factory()->create(['conference_id' => $conference->id]);

        $response = $this->getJson("/api/conferences/{$conference->id}/side-events/{$sideEvent->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $sideEvent->id,
                'title' => $sideEvent->title,
            ]);
    }

    public function test_administrator_can_create_side_event(): void
    {
        $admin = User::factory()->administrator()->create();
        $conference = Conference::factory()->create();
        Sanctum::actingAs($admin);

        $sideEventData = [
            'title' => 'Test Workshop',
            'start_datetime' => now()->addDays(11)->toDateTimeString(),
            'end_datetime' => now()->addDays(11)->addHours(2)->toDateTimeString(),
            'event_type' => 'workshop',
        ];

        $response = $this->postJson("/api/conferences/{$conference->id}/side-events", $sideEventData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Side event created successfully',
            ]);

        $this->assertDatabaseHas('side_events', [
            'title' => 'Test Workshop',
            'conference_id' => $conference->id,
        ]);
    }

    public function test_non_administrator_cannot_create_side_event(): void
    {
        $user = User::factory()->create();
        $conference = Conference::factory()->create();
        Sanctum::actingAs($user);

        $sideEventData = [
            'title' => 'Test Workshop',
            'start_datetime' => now()->addDays(11)->toDateTimeString(),
            'end_datetime' => now()->addDays(11)->addHours(2)->toDateTimeString(),
        ];

        $response = $this->postJson("/api/conferences/{$conference->id}/side-events", $sideEventData);

        $response->assertStatus(403);
    }

    public function test_administrator_can_update_side_event(): void
    {
        $admin = User::factory()->administrator()->create();
        $conference = Conference::factory()->create();
        $sideEvent = SideEvent::factory()->create(['conference_id' => $conference->id]);
        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/conferences/{$conference->id}/side-events/{$sideEvent->id}", [
            'title' => 'Updated Workshop Title',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('side_events', [
            'id' => $sideEvent->id,
            'title' => 'Updated Workshop Title',
        ]);
    }

    public function test_administrator_can_delete_side_event(): void
    {
        $admin = User::factory()->administrator()->create();
        $conference = Conference::factory()->create();
        $sideEvent = SideEvent::factory()->create(['conference_id' => $conference->id]);
        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/conferences/{$conference->id}/side-events/{$sideEvent->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('side_events', [
            'id' => $sideEvent->id,
        ]);
    }

    public function test_slug_is_unique_within_conference(): void
    {
        $admin = User::factory()->administrator()->create();
        $conference = Conference::factory()->create();
        Sanctum::actingAs($admin);

        // Create first side event
        $this->postJson("/api/conferences/{$conference->id}/side-events", [
            'title' => 'Test Workshop',
            'start_datetime' => now()->addDays(11)->toDateTimeString(),
            'end_datetime' => now()->addDays(11)->addHours(2)->toDateTimeString(),
        ]);

        // Create second with same title (should generate unique slug)
        $response = $this->postJson("/api/conferences/{$conference->id}/side-events", [
            'title' => 'Test Workshop',
            'start_datetime' => now()->addDays(12)->toDateTimeString(),
            'end_datetime' => now()->addDays(12)->addHours(2)->toDateTimeString(),
        ]);

        $response->assertStatus(201);

        // Should have test-workshop and test-workshop-1
        $this->assertDatabaseHas('side_events', ['slug' => 'test-workshop']);
        $this->assertDatabaseHas('side_events', ['slug' => 'test-workshop-1']);
    }
}
