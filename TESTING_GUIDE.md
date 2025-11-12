# Testing Guide

## Test Results Summary

✅ **All 29 tests passing!**

### Test Coverage

#### Conference API Tests (10 tests)
- ✅ List conferences with pagination
- ✅ Filter conferences by status (active/upcoming/past)
- ✅ View single conference details
- ✅ Administrator can create conference
- ✅ Non-administrator cannot create conference
- ✅ Administrator can update conference
- ✅ Administrator can delete conference
- ✅ Validation fails for invalid date ranges
- ✅ Slug auto-generation
- ✅ Authentication required for protected endpoints

#### Side Event API Tests (7 tests)
- ✅ List side events for a conference
- ✅ View single side event details
- ✅ Administrator can create side event
- ✅ Non-administrator cannot create side event
- ✅ Administrator can update side event
- ✅ Administrator can delete side event
- ✅ Slug uniqueness within conference

#### Staff Management API Tests (10 tests)
- ✅ Admin can attach site manager to conference
- ✅ Validation prevents wrong user type assignment
- ✅ Site manager can attach volunteer to conference
- ✅ List conference site managers
- ✅ Detach site manager from conference
- ✅ Cannot attach staff to side event without conference assignment
- ✅ Can attach staff after conference assignment
- ✅ Time conflict detection works correctly
- ✅ List available staff for side events
- ✅ Authorization prevents unauthorized access

---

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test File
```bash
php artisan test --filter=ConferenceApiTest
php artisan test --filter=SideEventApiTest
php artisan test --filter=StaffManagementApiTest
```

### Run Specific Test Method
```bash
php artisan test --filter=test_administrator_can_create_conference
```

### Run with Coverage (requires Xdebug or PCOV)
```bash
php artisan test --coverage
```

### Run in Parallel (faster)
```bash
php artisan test --parallel
```

---

## Test Database

Tests use SQLite in-memory database by default. Check `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

---

## Postman Collection

### Import Collection

1. Open Postman
2. Click "Import"
3. Select `Conference_API.postman_collection.json`
4. Collection will be imported with all endpoints

### Configure Environment Variables

Set these variables in Postman:

| Variable | Example Value | Description |
|----------|---------------|-------------|
| `base_url` | `http://localhost:8000` | Your API base URL |
| `auth_token` | `1\|abc123...` | Sanctum token after login |
| `conference_id` | `1` | ID of conference for testing |
| `side_event_id` | `1` | ID of side event for testing |

### Get Authentication Token

To get a token for testing:

1. Create a user (administrator) using Laravel Tinker:

```bash
php artisan tinker
```

```php
$user = \App\Models\User::factory()->administrator()->create([
    'email' => 'admin@example.com',
    'password' => bcrypt('password')
]);

$token = $user->createToken('test-token')->plainTextToken;
echo $token;
```

2. Copy the token and set it as `auth_token` in Postman environment variables

### Using the Collection

The collection is organized into 4 folders:

1. **Conferences** - CRUD operations for conferences
2. **Side Events** - CRUD operations for side events
3. **Conference Staff** - Manage site managers and volunteers for conferences
4. **Side Event Staff** - Manage staff for side events + availability checks

Most endpoints require authentication (automatically handled via collection auth).

---

## Manual Testing Workflow

### 1. Create Administrator User
```bash
php artisan tinker
```

```php
$admin = \App\Models\User::factory()->administrator()->create([
    'name' => 'Admin User',
    'email' => 'admin@test.com',
    'password' => bcrypt('password')
]);

$token = $admin->createToken('api-token')->plainTextToken;
echo "Token: " . $token;
```

### 2. Create Site Manager and Volunteer
```php
$siteManager = \App\Models\User::factory()->siteManager()->create([
    'email' => 'manager@test.com',
    'password' => bcrypt('password')
]);

$volunteer = \App\Models\User::factory()->volunteer()->create([
    'email' => 'volunteer@test.com',
    'password' => bcrypt('password')
]);

echo "Site Manager ID: " . $siteManager->id . "\n";
echo "Volunteer ID: " . $volunteer->id . "\n";
```

### 3. Test Complete Workflow

#### Step 1: Create Conference (Admin)
```bash
POST /api/conferences
Authorization: Bearer {token}

{
    "title": "Tech Summit 2024",
    "start_datetime": "2024-12-01 09:00:00",
    "end_datetime": "2024-12-03 18:00:00",
    "city": "San Francisco",
    "country": "USA"
}
```

#### Step 2: Create Side Event (Admin)
```bash
POST /api/conferences/1/side-events

{
    "title": "AI Workshop",
    "start_datetime": "2024-12-01 10:00:00",
    "end_datetime": "2024-12-01 12:00:00",
    "event_type": "workshop"
}
```

#### Step 3: Attach Site Manager to Conference
```bash
POST /api/conferences/1/site-managers

{
    "user_id": 2
}
```

#### Step 4: Attach Volunteer to Conference
```bash
POST /api/conferences/1/volunteers

{
    "user_id": 3
}
```

#### Step 5: Attach Volunteer to Side Event
```bash
POST /api/conferences/1/side-events/1/volunteers

{
    "user_id": 3
}
```

#### Step 6: Check Available Volunteers
```bash
GET /api/conferences/1/side-events/1/available-volunteers
```

---

## Common Test Scenarios

### Scenario 1: Time Conflict Detection

Create two overlapping side events and try to assign same volunteer:

```php
// In tinker
$conference = \App\Models\Conference::factory()->create();

$event1 = \App\Models\SideEvent::factory()->create([
    'conference_id' => $conference->id,
    'start_datetime' => '2024-12-01 10:00:00',
    'end_datetime' => '2024-12-01 12:00:00',
]);

$event2 = \App\Models\SideEvent::factory()->create([
    'conference_id' => $conference->id,
    'start_datetime' => '2024-12-01 11:00:00',  // Overlaps!
    'end_datetime' => '2024-12-01 13:00:00',
]);

$volunteer = \App\Models\User::factory()->volunteer()->create();

// Attach to conference first
DB::table('conference_user')->insert([
    'conference_id' => $conference->id,
    'user_id' => $volunteer->id,
    'role' => 'volunteer',
    'created_at' => now(),
    'updated_at' => now(),
]);

// Attach to first event - SUCCESS
DB::table('side_event_user')->insert([
    'side_event_id' => $event1->id,
    'user_id' => $volunteer->id,
    'role' => 'volunteer',
    'created_at' => now(),
    'updated_at' => now(),
]);

// Try to attach to second event - SHOULD FAIL with time conflict message
```

### Scenario 2: Authorization Hierarchy

Test that site managers can manage volunteers but not other site managers:

```php
$siteManager = \App\Models\User::factory()->siteManager()->create();
$token = $siteManager->createToken('test')->plainTextToken;

// This should work:
POST /api/conferences/1/volunteers with $token

// This should fail (403):
POST /api/conferences/1/site-managers with $token
```

### Scenario 3: Conference-to-Side-Event Requirement

Verify volunteers must be on conference before side event:

```php
// Without conference assignment - SHOULD FAIL
POST /api/conferences/1/side-events/1/volunteers
{"user_id": 5}

// With conference assignment - SHOULD SUCCEED
POST /api/conferences/1/volunteers
{"user_id": 5}

POST /api/conferences/1/side-events/1/volunteers
{"user_id": 5}
```

---

## Troubleshooting

### Tests Failing

1. **Database issues**: Run `php artisan migrate:fresh`
2. **Cache issues**: Run `php artisan config:clear && php artisan cache:clear`
3. **Missing dependencies**: Run `composer install`

### API Returns 401

- Check if token is valid and not expired
- Ensure `Authorization: Bearer {token}` header is set
- Token format should be `{id}|{hash}`

### API Returns 403

- Check user's `user_type` matches required permission
- Review middleware requirements in `routes/api.php`
- Ensure user account is not disabled

### Time Conflict Not Detecting

- Verify datetime formats are consistent (Y-m-d H:i:s)
- Check timezone settings in conference
- Ensure side events belong to same conference

---

## Additional Resources

- API Documentation: `API_DOCUMENTATION.md`
- Postman Collection: `Conference_API.postman_collection.json`
- Laravel Testing Docs: https://laravel.com/docs/testing
- Sanctum Docs: https://laravel.com/docs/sanctum