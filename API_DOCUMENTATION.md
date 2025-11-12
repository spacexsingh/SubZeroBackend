# API Documentation

## Overview
This API provides comprehensive endpoints for managing conferences, side events, and staff assignments with role-based access control.

## Authentication
All protected endpoints require Laravel Sanctum authentication:
```
Authorization: Bearer {token}
```

## User Types & Permissions
- **Administrator**: Full control over conferences and side events
- **Administrator Assistant**: Manage inventory and staff
- **Site Manager**: Manage volunteers
- **Volunteer**: Handle wristbands and points redemption
- **VIP Attendee**: Premium attendee features
- **General Attendee**: Basic attendee features

---

## Conference Endpoints

### 1. List Conferences
**GET** `/api/conferences`

**Query Parameters:**
- `status` (optional): `active`, `upcoming`, `past`
- `search` (optional): Search by title
- `per_page` (optional): Items per page (default: 15)

**Access:** Public

---

### 2. Get Conference Details
**GET** `/api/conferences/{conference}`

**Access:** Public

**Response includes:** Conference details, creator, side events, site managers, volunteers

---

### 3. Create Conference
**POST** `/api/conferences`

**Access:** Administrator only

**Required Fields:**
- `title`: string (max: 255)
- `start_datetime`: date
- `end_datetime`: date (must be after start_datetime)

**Optional Fields:**
- `slug`: string (auto-generated if not provided)
- `description`: text
- `short_description`: text
- `registration_start_datetime`: date
- `registration_end_datetime`: date
- `timezone`: string (default: UTC)
- `venue_name`: string
- `venue_address`: string
- `city`: string
- `state`: string
- `country`: string

---

### 4. Update Conference
**PUT** `/api/conferences/{conference}`

**Access:** Administrator only

**Fields:** Same as Create (all optional for update)

---

### 5. Delete Conference
**DELETE** `/api/conferences/{conference}`

**Access:** Administrator only

---

## Side Event Endpoints

### 1. List Side Events
**GET** `/api/conferences/{conference}/side-events`

**Query Parameters:**
- `event_type` (optional): Filter by event type

**Access:** Public

---

### 2. Get Side Event Details
**GET** `/api/conferences/{conference}/side-events/{sideEvent}`

**Access:** Public

---

### 3. Create Side Event
**POST** `/api/conferences/{conference}/side-events`

**Access:** Administrator only

**Required Fields:**
- `title`: string (max: 255)
- `start_datetime`: date
- `end_datetime`: date (must be after start_datetime)

**Optional Fields:**
- `slug`: string (auto-generated if not provided)
- `description`: text
- `venue_name`: string
- `venue_address`: string
- `room_number`: string
- `event_type`: string
- `sort_order`: integer

---

### 4. Update Side Event
**PUT** `/api/conferences/{conference}/side-events/{sideEvent}`

**Access:** Administrator only

---

### 5. Delete Side Event
**DELETE** `/api/conferences/{conference}/side-events/{sideEvent}`

**Access:** Administrator only

---

## Conference Staff Management

### 1. Attach Site Manager to Conference
**POST** `/api/conferences/{conference}/site-managers`

**Access:** Administrator, Administrator Assistant

**Body:**
```json
{
  "user_id": 123
}
```

**Validations:**
- User must exist in system
- User must have `site_manager` user type
- User cannot already be assigned

---

### 2. Remove Site Manager from Conference
**DELETE** `/api/conferences/{conference}/site-managers`

**Access:** Administrator, Administrator Assistant

**Body:**
```json
{
  "user_id": 123
}
```

---

### 3. Attach Volunteer to Conference
**POST** `/api/conferences/{conference}/volunteers`

**Access:** Administrator, Administrator Assistant, Site Manager

**Body:**
```json
{
  "user_id": 123
}
```

**Validations:**
- User must exist in system
- User must have `volunteer` user type

---

### 4. Remove Volunteer from Conference
**DELETE** `/api/conferences/{conference}/volunteers`

**Access:** Administrator, Administrator Assistant, Site Manager

---

### 5. List Conference Site Managers
**GET** `/api/conferences/{conference}/site-managers`

**Access:** Authenticated users

---

### 6. List Conference Volunteers
**GET** `/api/conferences/{conference}/volunteers`

**Access:** Authenticated users

---

## Side Event Staff Management

### 1. Attach Site Manager to Side Event
**POST** `/api/conferences/{conference}/side-events/{sideEvent}/site-managers`

**Access:** Administrator, Administrator Assistant

**Body:**
```json
{
  "user_id": 123
}
```

**Validations:**
- User must be attached to conference first
- User must have `site_manager` user type
- No time conflicts with other side events
- User cannot already be assigned

---

### 2. Remove Site Manager from Side Event
**DELETE** `/api/conferences/{conference}/side-events/{sideEvent}/site-managers`

**Access:** Administrator, Administrator Assistant

---

### 3. Attach Volunteer to Side Event
**POST** `/api/conferences/{conference}/side-events/{sideEvent}/volunteers`

**Access:** Administrator, Administrator Assistant, Site Manager

**Body:**
```json
{
  "user_id": 123
}
```

**Validations:**
- User must be attached to conference first
- User must have `volunteer` user type
- No time conflicts with other side events

---

### 4. Remove Volunteer from Side Event
**DELETE** `/api/conferences/{conference}/side-events/{sideEvent}/volunteers`

**Access:** Administrator, Administrator Assistant, Site Manager

---

### 5. List Side Event Site Managers
**GET** `/api/conferences/{conference}/side-events/{sideEvent}/site-managers`

**Access:** Authenticated users

**Returns:** All site managers assigned to this side event

---

### 6. List Side Event Volunteers
**GET** `/api/conferences/{conference}/side-events/{sideEvent}/volunteers`

**Access:** Authenticated users

**Returns:** All volunteers assigned to this side event

---

### 7. List Available Site Managers
**GET** `/api/conferences/{conference}/side-events/{sideEvent}/available-site-managers`

**Access:** Authenticated users

**Returns:** Site managers attached to conference who have no time conflicts with other side events

---

### 8. List Available Volunteers
**GET** `/api/conferences/{conference}/side-events/{sideEvent}/available-volunteers`

**Access:** Authenticated users

**Returns:** Volunteers attached to conference who have no time conflicts with other side events

---

## Business Rules

1. **Conference to Side Event Hierarchy**
   - Side events belong to conferences
   - Deleting a conference deletes all its side events

2. **Staff Assignment Rules**
   - Staff must be added to conference BEFORE being assigned to side events
   - Only users with appropriate user_type can be assigned to roles
   - Site managers and volunteers cannot have time conflicts within the same conference

3. **Time Conflict Detection**
   - System automatically checks for overlapping time slots
   - Staff cannot be assigned to multiple side events at the same time
   - Conflicts are checked during assignment

4. **Authorization Hierarchy**
   - Administrator: Full control
   - Administrator + Admin Assistant: Can manage site managers
   - Administrator + Admin Assistant + Site Manager: Can manage volunteers
   - Each role can perform actions of lower roles

---

## Error Responses

All endpoints return appropriate HTTP status codes:

- `200`: Success
- `201`: Created
- `400`: Bad Request (validation failed or business rule violated)
- `401`: Unauthenticated
- `403`: Unauthorized (insufficient permissions)
- `404`: Not Found

Error response format:
```json
{
  "message": "Error description here"
}
```

---

## Next Steps

1. Run migrations: `php artisan migrate`
2. Set up authentication (Laravel Sanctum)
3. Create test users with different user types
4. Test API endpoints with tools like Postman or Insomnia