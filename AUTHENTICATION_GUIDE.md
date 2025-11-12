# Authentication Guide

## Overview

The Conference Management API now includes authentication endpoints using Laravel Sanctum. Users can login with their credentials to receive an API token for accessing protected endpoints.

## Test Users

The database seeder creates 6 test users (one for each user type):

| User Type | Email | Password | Role Description |
|-----------|-------|----------|------------------|
| Administrator | `admin@example.com` | `password` | Full access to all features |
| Administrator Assistant | `assistant@example.com` | `password` | Can manage staff assignments |
| Site Manager | `manager@example.com` | `password` | Can manage volunteers |
| Volunteer | `volunteer@example.com` | `password` | Staff member for events |
| VIP Attendee | `vip@example.com` | `password` | Premium attendee |
| General Attendee | `user@example.com` | `password` | Standard attendee |

## Creating Test Users

To create all test users in your database:

```bash
php artisan migrate:fresh --seed
```

This will create the database tables and seed all 6 test users.

## API Endpoints

### 1. Login

**POST** `/api/login`

Login with email and password to receive an authentication token.

**Request Body:**
```json
{
    "email": "admin@example.com",
    "password": "password",
    "device_name": "postman"
}
```

**Response (200):**
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "user_type": "administrator"
    },
    "token": "1|abc123xyz..."
}
```

**Error Response (422):**
```json
{
    "message": "The provided credentials are incorrect.",
    "errors": {
        "email": [
            "The provided credentials are incorrect."
        ]
    }
}
```

### 2. Get Current User Info

**GET** `/api/me`

Get the authenticated user's information.

**Headers:**
```
Authorization: Bearer {your_token}
```

**Response (200):**
```json
{
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "user_type": "administrator"
    }
}
```

### 3. Logout

**POST** `/api/logout`

Revoke the current authentication token.

**Headers:**
```
Authorization: Bearer {your_token}
```

**Response (200):**
```json
{
    "message": "Logged out successfully"
}
```

## Using Authentication in Postman

### Method 1: Manual Token Setup

1. **Login to get token:**
   - Open the "Authentication" folder in the Postman collection
   - Select the appropriate login request (e.g., "Login - Administrator")
   - Click "Send"
   - Copy the `token` value from the response

2. **Set the token in Postman:**
   - Go to the collection variables
   - Paste the token into the `auth_token` variable
   - All authenticated requests will now use this token automatically

### Method 2: Quick Login Examples

The Postman collection includes pre-configured login requests for each user type:

- **Login - Administrator**: Full access user
- **Login - Administrator Assistant**: Staff management user
- **Login - Site Manager**: Volunteer management user
- **Login - Volunteer**: Basic staff user
- **Login - VIP Attendee**: Premium attendee
- **Login - General Attendee**: Standard attendee

Simply select the user type you want to test with, send the request, and copy the token.

## Testing Workflow

### Complete Testing Flow:

1. **Start your Laravel server:**
   ```bash
   php artisan serve
   ```

2. **Seed the database** (if not already done):
   ```bash
   php artisan migrate:fresh --seed
   ```

3. **Login as Administrator:**
   - Use: `admin@example.com` / `password`
   - Copy the returned token

4. **Set token in Postman:**
   - Paste token into `auth_token` variable

5. **Test protected endpoints:**
   - Create Conference
   - Create Side Event
   - Manage staff assignments

6. **Test different user types:**
   - Login as different users to test authorization
   - Site Manager should be able to attach volunteers
   - General user should get 403 errors on admin endpoints

## Authorization Rules Summary

| Action | Administrator | Admin Assistant | Site Manager | Volunteer | Attendees |
|--------|--------------|----------------|--------------|-----------|-----------|
| Create/Edit Conference | ✅ | ❌ | ❌ | ❌ | ❌ |
| Create/Edit Side Event | ✅ | ❌ | ❌ | ❌ | ❌ |
| Attach Site Manager | ✅ | ✅ | ❌ | ❌ | ❌ |
| Attach Volunteer | ✅ | ✅ | ✅ | ❌ | ❌ |
| View Conferences | ✅ | ✅ | ✅ | ✅ | ✅ |
| View Side Events | ✅ | ✅ | ✅ | ✅ | ✅ |

## Example: Complete Conference Creation Flow

```bash
# 1. Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Response includes token: "1|abc123..."

# 2. Create Conference
curl -X POST http://localhost:8000/api/conferences \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Tech Summit 2024",
    "start_datetime": "2024-12-01 09:00:00",
    "end_datetime": "2024-12-03 18:00:00",
    "city": "San Francisco",
    "country": "USA"
  }'

# 3. View created conference
curl -X GET http://localhost:8000/api/conferences/1

# 4. Logout
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer 1|abc123..."
```

## Security Notes

- Tokens are stored in the `personal_access_tokens` table
- Each token is tied to a specific user and device
- Tokens remain valid until explicitly revoked via logout
- Always use HTTPS in production
- Never commit `.env` files or expose tokens in logs

## Troubleshooting

### "Unauthenticated" Error (401)
- Token not provided in Authorization header
- Token is invalid or expired
- Token format incorrect (must be `Bearer {token}`)

### "Unauthorized" Error (403)
- Valid token but insufficient permissions
- User type doesn't match required role for endpoint
- Example: General user trying to create conference

### "The provided credentials are incorrect" (422)
- Wrong email or password
- Check email spelling and password
- Verify user exists in database

---

**Total Endpoints:** 32 (8 authentication + 24 conference/event management)

**Postman Collection:** `Conference_API.postman_collection.json`

For API details, see `API_DOCUMENTATION.md`