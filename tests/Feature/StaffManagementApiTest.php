<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\SideEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffManagementApiTest extends TestCase
{
    use RefreshDatabase;

    // Conference Staff Tests
    public function test_admin_can_attach_site_manager_to_conference(): void
    {
        $admin = User::factory()->administrator()->create();
        $siteManager = User::factory()->siteManager()->create();
        $conference = Conference::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/conferences/{$conference->id}/site-managers", [
            'user_id' => $siteManager->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('conference_user', [
            'conference_id' => $conference->id,
            'user_id' => $siteManager->id,
            'role' => 'site_manager',
        ]);
    }

    public function test_cannot_attach_non_site_manager_as_site_manager(): void
    {
        $admin = User::factory()->administrator()->create();
        $volunteer = User::factory()->volunteer()->create();
        $conference = Conference::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/conferences/{$conference->id}/site-managers", [
            'user_id' => $volunteer->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => "User must be of type 'site_manager' to be assigned as a site manager.",
            ]);
    }

    public function test_site_manager_can_attach_volunteer_to_conference(): void
    {
        $siteManager = User::factory()->siteManager()->create();
        $volunteer = User::factory()->volunteer()->create();
        $conference = Conference::factory()->create();
        Sanctum::actingAs($siteManager);

        $response = $this->postJson("/api/conferences/{$conference->id}/volunteers", [
            'user_id' => $volunteer->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('conference_user', [
            'conference_id' => $conference->id,
            'user_id' => $volunteer->id,
            'role' => 'volunteer',
        ]);
    }

    public function test_can_list_conference_site_managers(): void
    {
        $admin = User::factory()->administrator()->create();
        $conference = Conference::factory()->create();
        $siteManagers = User::factory()->siteManager()->count(3)->create();

        foreach ($siteManagers as $sm) {
            DB::table('conference_user')->insert([
                'conference_id' => $conference->id,
                'user_id' => $sm->id,
                'role' => 'site_manager',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/conferences/{$conference->id}/site-managers");

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_detach_site_manager_from_conference(): void
    {
        $admin = User::factory()->administrator()->create();
        $siteManager = User::factory()->siteManager()->create();
        $conference = Conference::factory()->create();

        DB::table('conference_user')->insert([
            'conference_id' => $conference->id,
            'user_id' => $siteManager->id,
            'role' => 'site_manager',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/conferences/{$conference->id}/site-managers", [
            'user_id' => $siteManager->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('conference_user', [
            'conference_id' => $conference->id,
            'user_id' => $siteManager->id,
            'role' => 'site_manager',
        ]);
    }

    // Side Event Staff Tests
    public function test_cannot_attach_staff_to_side_event_without_conference_assignment(): void
    {
        $admin = User::factory()->administrator()->create();
        $volunteer = User::factory()->volunteer()->create();
        $conference = Conference::factory()->create();
        $sideEvent = SideEvent::factory()->create(['conference_id' => $conference->id]);
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/conferences/{$conference->id}/side-events/{$sideEvent->id}/volunteers", [
            'user_id' => $volunteer->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'User must be attached to the conference first before being assigned to a side event.',
            ]);
    }

    public function test_can_attach_volunteer_to_side_event_after_conference_assignment(): void
    {
        $admin = User::factory()->administrator()->create();
        $volunteer = User::factory()->volunteer()->create();
        $conference = Conference::factory()->create();
        $sideEvent = SideEvent::factory()->create(['conference_id' => $conference->id]);

        // First attach to conference
        DB::table('conference_user')->insert([
            'conference_id' => $conference->id,
            'user_id' => $volunteer->id,
            'role' => 'volunteer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/conferences/{$conference->id}/side-events/{$sideEvent->id}/volunteers", [
            'user_id' => $volunteer->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('side_event_user', [
            'side_event_id' => $sideEvent->id,
            'user_id' => $volunteer->id,
            'role' => 'volunteer',
        ]);
    }

    public function test_cannot_assign_volunteer_with_time_conflict(): void
    {
        $admin = User::factory()->administrator()->create();
        $volunteer = User::factory()->volunteer()->create();
        $conference = Conference::factory()->create([
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(15),
        ]);

        // Create two overlapping side events
        $sideEvent1 = SideEvent::factory()->create([
            'conference_id' => $conference->id,
            'start_datetime' => now()->addDays(11)->setTime(10, 0),
            'end_datetime' => now()->addDays(11)->setTime(12, 0),
        ]);

        $sideEvent2 = SideEvent::factory()->create([
            'conference_id' => $conference->id,
            'start_datetime' => now()->addDays(11)->setTime(11, 0),
            'end_datetime' => now()->addDays(11)->setTime(13, 0),
        ]);

        // Attach volunteer to conference
        DB::table('conference_user')->insert([
            'conference_id' => $conference->id,
            'user_id' => $volunteer->id,
            'role' => 'volunteer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attach volunteer to first side event
        DB::table('side_event_user')->insert([
            'side_event_id' => $sideEvent1->id,
            'user_id' => $volunteer->id,
            'role' => 'volunteer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        // Try to attach to second overlapping side event
        $response = $this->postJson("/api/conferences/{$conference->id}/side-events/{$sideEvent2->id}/volunteers", [
            'user_id' => $volunteer->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'User has a time conflict with another side event in this conference.',
            ]);
    }

    public function test_can_list_available_volunteers_for_side_event(): void
    {
        $admin = User::factory()->administrator()->create();
        $conference = Conference::factory()->create([
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(15),
        ]);

        $sideEvent = SideEvent::factory()->create([
            'conference_id' => $conference->id,
            'start_datetime' => now()->addDays(11)->setTime(10, 0),
            'end_datetime' => now()->addDays(11)->setTime(12, 0),
        ]);

        // Create 2 volunteers and attach to conference
        $volunteer1 = User::factory()->volunteer()->create();
        $volunteer2 = User::factory()->volunteer()->create();

        DB::table('conference_user')->insert([
            ['conference_id' => $conference->id, 'user_id' => $volunteer1->id, 'role' => 'volunteer', 'created_at' => now(), 'updated_at' => now()],
            ['conference_id' => $conference->id, 'user_id' => $volunteer2->id, 'role' => 'volunteer', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/conferences/{$conference->id}/side-events/{$sideEvent->id}/available-volunteers");

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_available_volunteers_excludes_already_attached(): void
    {
        $admin = User::factory()->administrator()->create();
        $conference = Conference::factory()->create([
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(15),
        ]);

        $sideEvent = SideEvent::factory()->create([
            'conference_id' => $conference->id,
            'start_datetime' => now()->addDays(11)->setTime(10, 0),
            'end_datetime' => now()->addDays(11)->setTime(12, 0),
        ]);

        // Create 3 volunteers and attach all to conference
        $volunteer1 = User::factory()->volunteer()->create();
        $volunteer2 = User::factory()->volunteer()->create();
        $volunteer3 = User::factory()->volunteer()->create();

        DB::table('conference_user')->insert([
            ['conference_id' => $conference->id, 'user_id' => $volunteer1->id, 'role' => 'volunteer', 'created_at' => now(), 'updated_at' => now()],
            ['conference_id' => $conference->id, 'user_id' => $volunteer2->id, 'role' => 'volunteer', 'created_at' => now(), 'updated_at' => now()],
            ['conference_id' => $conference->id, 'user_id' => $volunteer3->id, 'role' => 'volunteer', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Attach volunteer1 to THIS side event
        DB::table('side_event_user')->insert([
            'side_event_id' => $sideEvent->id,
            'user_id' => $volunteer1->id,
            'role' => 'volunteer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/conferences/{$conference->id}/side-events/{$sideEvent->id}/available-volunteers");

        $response->assertStatus(200)
            ->assertJsonCount(2); // Only volunteer2 and volunteer3 should be available

        // Verify volunteer1 is NOT in the response
        $availableIds = collect($response->json())->pluck('id')->toArray();
        $this->assertNotContains($volunteer1->id, $availableIds);
        $this->assertContains($volunteer2->id, $availableIds);
        $this->assertContains($volunteer3->id, $availableIds);
    }

    public function test_general_user_cannot_manage_staff(): void
    {
        $user = User::factory()->create(); // general_attendee
        $conference = Conference::factory()->create();
        $volunteer = User::factory()->volunteer()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/conferences/{$conference->id}/volunteers", [
            'user_id' => $volunteer->id,
        ]);

        $response->assertStatus(403);
    }
}
