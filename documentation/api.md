# API Documentation for Home Management System

## API Overview

**REST API Architecture:**
- **Stateless HTTP**: Each request contains all info needed (JWT cookies)
- **HTTP Methods**: GET, POST, PATCH, OPTIONS
- **JSON Communication**: All data exchange in JSON
- **CORS Enabled**: Frontend integration with security headers

## Authentication & Security

### JWT Authentication
- JWT tokens stored in HTTP-only cookies
- Role-based access: Admin, Provider, Customer
- Authentication middleware: `require_auth()`

```php
$user = require_auth();
if ($user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}
```

### Security Features
- Input sanitization with `htmlspecialchars()`
- SQL injection prevention with prepared statements
- CORS headers on all endpoints
- Password hashing with `password_verify()`

## CRUD Operations

### CREATE (POST)
- `POST /login.php` - User login
- `POST /register.php` - User registration
- `POST /service_booking.php` - Create booking
- `POST /contact_us.php` - Contact form

### READ (GET)
- `GET /me.php` - Current user info
- `GET /customer_dashboard.php` - Customer stats
- `GET /service_booking.php` - Get bookings
- `GET /admin_customers.php` - All customers (admin)

### UPDATE (PATCH/POST)
- `PATCH /service_booking.php` - Update booking status
- `POST /update_customer_profile.php` - Update profile
- `POST /provider_status.php` - Change provider status

### DELETE (Soft Delete)
- Booking cancellations (status changes)
- Logout (cookie deletion)

## Key API Endpoints

### Authentication
```javascript
// Login
POST /login.php
{
  "email": "user@example.com",
  "password": "password123"
}

// Response
{
  "status": "success",
  "user_type": "customer",
  "user_id": 123,
  "user_details": { ... }
}
```

### Booking Management
```javascript
// Create booking
POST /service_booking.php
{
  "service_category_id": 1,
  "service_date": "2025-01-15",
  "service_address": "123 Main St"
}

// Update booking
PATCH /service_booking.php
{
  "action": "cancel",
  "service_book_id": 123,
  "cancel_reason": "Changed mind"
}
```

### Dashboard Stats
```javascript
// Get customer dashboard
GET /customer_dashboard.php

// Response
{
  "status": "success",
  "data": {
    "upcoming_bookings": 5,
    "active_subscriptions": 2,
    "feedback_given": 8,
    "total_services_used": 15
  }
}
```

## If-Else Logic Patterns

### Input Validation
```php
if (!$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields missing']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}
```

### Method Routing
```php
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Handle GET requests
} elseif ($method === 'POST') {
    // Handle POST requests
} elseif ($method === 'PATCH') {
    // Handle PATCH requests
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
```

### Action-Based Logic
```php
if (isset($input['action']) && $input['action'] === 'cancel') {
    // Handle cancellation
} elseif (isset($input['action']) && $input['action'] === 'accept') {
    // Handle acceptance
} else {
    // Default action
}
```

## Database Patterns

### Prepared Statements
```php
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND user_type = ?");
$stmt->execute([$email, $userType]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Transaction Handling
```php
try {
    $this->conn->beginTransaction();
    $stmt1->execute([$param1]);
    $stmt2->execute([$param2]);
    $this->conn->commit();
    return ['status' => 'success'];
} catch (PDOException $e) {
    $this->conn->rollBack();
    return ['status' => 'error', 'message' => 'Transaction failed'];
}
```

## Frontend Integration

### Request with Credentials
```javascript
const response = await axios.post('/api/login.php', 
  { email, password },
  { 
    withCredentials: true,
    headers: { 'Content-Type': 'application/json' }
  }
);
```

### Query Parameters
```javascript
// GET with filters
GET /service_booking.php?user_id=123&status=pending&page=1&limit=10
```

## Response Format

### Success Response
```json
{
  "status": "success",
  "message": "Operation completed",
  "data": { ... }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Descriptive error message"
}
```

## HTTP Status Codes
- `200`: Success
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden  
- `405`: Method Not Allowed
- `500`: Server Error

## API File Structure

```
api/
├── login.php, logout.php, register.php           # Authentication
├── customer_dashboard.php, provider_dashboard.php # Dashboard stats
├── service_booking.php, subscription_booking.php # Booking management
├── admin_customers.php, add_provider.php         # Admin operations
├── contact_us.php, notification.php              # Communication
├── service_review.php, subscription_review.php   # Reviews
└── auth.php, db.php, validation.php              # Utilities
```

Your API implements a secure REST architecture with proper authentication, CRUD operations, extensive if-else logic for routing and validation, and stateless HTTP communication using JWT tokens.