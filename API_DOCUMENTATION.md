# VALET API Documentation

## Base URL
```
https://your-domain.com/api
```

## Authentication

The VALET API uses **Laravel Sanctum** for token-based authentication.

### Login
Authenticate and receive an access token.

**Endpoint:** `POST /login`
**Rate Limit:** 5 requests per minute
**Authentication:** Not required

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "your-password"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "user",
    "role_display": "User",
    "employee_id": "EMP001",
    "department": "IT",
    "is_active": true
  },
  "abilities": [
    "parking:view",
    "feedbacks:create",
    "feedbacks:view-own"
  ]
}
```

**Error Responses:**
- **401 Unauthorized** - Invalid credentials or deactivated account
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```
- **422 Validation Error** - Missing or invalid fields
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

### Using the Token

Include the token in all subsequent requests using the `Authorization` header:

```
Authorization: Bearer 1|AbCdEfGhIjKlMnOpQrStUvWxYz...
```

**Example (JavaScript/Axios):**
```javascript
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
```

**Example (Flutter/Dart):**
```dart
final response = await http.get(
  Uri.parse('https://your-domain.com/api/profile'),
  headers: {
    'Authorization': 'Bearer $token',
    'Accept': 'application/json',
  },
);
```

---

## User Roles & Abilities

The API uses role-based access control. Each role has specific abilities:

| Role | Abilities |
|------|-----------|
| **admin** | Full access to all resources |
| **ssd** | Manage parking, vehicles, users, feedbacks |
| **security** | View parking, vehicles; verify vehicles |
| **user** | View parking, create/view own feedbacks |

---

## Authentication Endpoints

### Logout
Revoke the current access token.

**Endpoint:** `POST /logout`
**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logged out"
}
```

---

### Get Profile
Retrieve the authenticated user's profile.

**Endpoint:** `GET /profile`
**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "user",
    "role_display": "User",
    "employee_id": "EMP001",
    "department": "IT",
    "is_active": true
  }
}
```

---

### Validate Token
Check if the current token is valid.

**Endpoint:** `GET /validate`
**Authentication:** Required

**Success Response (200):**
```json
{
  "success": true,
  "message": "Token valid",
  "user": {
    "id": 1,
    "name": "John Doe",
    "role": "user"
  }
}
```

---

## Parking Endpoints

### Get All Parking Spaces
Retrieve all parking spaces with occupancy status.

**Endpoint:** `GET /parking`
**Authentication:** Required
**Ability Required:** `parking:view`

**Success Response (200):**
```json
{
  "success": true,
  "spaces": [
    {
      "id": 1,
      "sensor_id": "401",
      "is_occupied": false,
      "distance_cm": 150,
      "floor_level": "4th Floor",
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 14:22:10"
    }
  ]
}
```

---

### Get Parking Statistics
Get occupancy statistics.

**Endpoint:** `GET /parking/stats`
**Authentication:** Required
**Ability Required:** `parking:view`

**Success Response (200):**
```json
{
  "success": true,
  "stats": {
    "total_spaces": 50,
    "occupied": 32,
    "available": 18,
    "occupancy_rate": 64
  }
}
```

---

### Get Parking by Floor
Get parking spaces for a specific floor.

**Endpoint:** `GET /parking/floor/{floorLevel}`
**Authentication:** Required
**Ability Required:** `parking:view`

**Parameters:**
- `floorLevel` (path) - Floor level (e.g., "4th Floor")

**Success Response (200):**
```json
{
  "success": true,
  "floor": "4th Floor",
  "spaces": [...]
}
```

---

### Update Parking Space (IoT Sensors)
Update parking space data (typically called by IoT sensors).

**Endpoint:** `POST /parking`
**Authentication:** Required (for authenticated) or Public endpoint available
**Ability Required:** `parking:manage` (if authenticated)

**Public Endpoint:** `POST /public/parking`
**Rate Limit:** 60 requests per minute

**Request Body:**
```json
{
  "sensor_id": "401",
  "is_occupied": true,
  "distance_cm": 45,
  "floor_level": "4th Floor"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Parking space updated",
  "space": {
    "id": 1,
    "sensor_id": "401",
    "is_occupied": true,
    "distance_cm": 45,
    "floor_level": "4th Floor"
  }
}
```

---

## Feedback Endpoints

### Get Feedbacks
Retrieve feedbacks with optional filtering.

**Endpoint:** `GET /feedbacks`
**Authentication:** Required
**Ability Required:** `feedbacks:view` or `feedbacks:view-own`

**Query Parameters:**
- `type` - Filter by type: `general`, `bug`, `feature`, `parking`
- `status` - Filter by status: `pending`, `reviewed`, `resolved`
- `search` - Search in message/email
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15)

**Success Response (200):**
```json
{
  "success": true,
  "feedbacks": [
    {
      "id": 1,
      "type": "bug",
      "message": "App crashes on login",
      "rating": 3,
      "email": "user@example.com",
      "status": "pending",
      "created_at": "2024-01-15 10:30:00"
    }
  ],
  "pagination": {
    "total": 45,
    "current_page": 1,
    "per_page": 15,
    "last_page": 3
  }
}
```

---

### Create Feedback
Submit new feedback.

**Endpoint:** `POST /feedbacks`
**Authentication:** Required
**Ability Required:** `feedbacks:create`

**Request Body:**
```json
{
  "type": "bug",
  "message": "App crashes when viewing parking details",
  "rating": 3,
  "email": "user@example.com",
  "issues": ["crash", "parking_view"],
  "device_info": {
    "platform": "Android",
    "version": "1.0.0",
    "device_model": "Samsung Galaxy S21"
  }
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Feedback submitted successfully",
  "feedback": {
    "id": 10,
    "type": "bug",
    "status": "pending",
    "created_at": "2024-01-15 14:30:00"
  }
}
```

---

### Get Feedback Statistics
Get feedback statistics by type and status.

**Endpoint:** `GET /feedbacks/stats`
**Authentication:** Required
**Ability Required:** `feedbacks:view`

**Success Response (200):**
```json
{
  "success": true,
  "stats": {
    "total": 150,
    "by_type": {
      "general": 50,
      "bug": 30,
      "feature": 40,
      "parking": 30
    },
    "by_status": {
      "pending": 20,
      "reviewed": 50,
      "resolved": 80
    }
  }
}
```

---

## User Endpoints

### Get Users
Retrieve all users (Admin/SSD only).

**Endpoint:** `GET /users`
**Authentication:** Required
**Ability Required:** `users:view`

**Query Parameters:**
- `search` - Search by name, email, or employee_id
- `role` - Filter by role: `admin`, `ssd`, `security`, `user`
- `status` - Filter by status: `active`, `inactive`

**Success Response (200):**
```json
{
  "success": true,
  "users": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "user",
      "role_display": "User",
      "employee_id": "EMP001",
      "department": "IT",
      "is_active": true,
      "created_at": "2024-01-10 09:00:00"
    }
  ]
}
```

**Note:** Passwords are **never** returned in API responses for security.

---

### Get User Statistics
Get user statistics by role.

**Endpoint:** `GET /users/stats`
**Authentication:** Required
**Ability Required:** `users:view`

**Success Response (200):**
```json
{
  "success": true,
  "stats": {
    "total_users": 100,
    "active_users": 85,
    "inactive_users": 15,
    "by_role": {
      "admin": 2,
      "ssd": 5,
      "security": 10,
      "user": 83
    }
  }
}
```

---

## Error Handling

All endpoints follow a consistent error response format:

**Error Response:**
```json
{
  "success": false,
  "message": "Error description"
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized (invalid/missing token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests (rate limit exceeded)
- `500` - Internal Server Error

---

## Rate Limiting

Rate limits are enforced on certain endpoints:

| Endpoint | Limit |
|----------|-------|
| `POST /login` | 5 requests/minute |
| `POST /public/*` | 60 requests/minute |
| All authenticated endpoints | 60 requests/minute |

When rate limited, you'll receive a `429 Too Many Requests` response:

```json
{
  "message": "Too Many Attempts."
}
```

**Response Headers:**
- `X-RateLimit-Limit` - Total requests allowed
- `X-RateLimit-Remaining` - Remaining requests
- `Retry-After` - Seconds until rate limit resets

---

## Testing Credentials (Development Only)

**DO NOT USE IN PRODUCTION**

Default test accounts:
- Admin: `admin@valet.com` / `password123`
- SSD: `ssd@valet.com` / `password123`
- Security: `security@valet.com` / `password123`
- User: `user@valet.com` / `password123`

---

## Security Best Practices

1. **Store tokens securely** - Use secure storage (Keychain/Keystore)
2. **HTTPS only** - Never send tokens over HTTP
3. **Token expiration** - Implement token refresh logic
4. **Logout on session end** - Always call `/logout` to revoke tokens
5. **Handle 401 errors** - Redirect to login when token expires
6. **Don't log tokens** - Never log tokens in debug/crash reports

---

## Example Mobile Integration (Flutter)

```dart
class ApiService {
  final String baseUrl = 'https://your-domain.com/api';
  String? _token;

  Future<void> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      _token = data['token'];
      // Store token securely
      await secureStorage.write(key: 'auth_token', value: _token);
    }
  }

  Future<List<ParkingSpace>> getParkingSpaces() async {
    final response = await http.get(
      Uri.parse('$baseUrl/parking'),
      headers: {
        'Authorization': 'Bearer $_token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return (data['spaces'] as List)
          .map((json) => ParkingSpace.fromJson(json))
          .toList();
    }
    throw Exception('Failed to load parking spaces');
  }

  Future<void> logout() async {
    await http.post(
      Uri.parse('$baseUrl/logout'),
      headers: {'Authorization': 'Bearer $_token'},
    );
    await secureStorage.delete(key: 'auth_token');
    _token = null;
  }
}
```

---

## Changelog

### v1.0.0 (Current)
- Implemented Sanctum token authentication
- Added role-based abilities/scopes
- Removed password exposure from API responses
- Added rate limiting on login and public endpoints
- Enhanced security with proper token management

---

## Support

For API issues or questions, contact your development team or file an issue in the project repository.
