# JWT Authentication Documentation for Home Management System

## What is JWT?

JWT (JSON Web Token) is like a digital ID card for your users. When someone logs into your website, you give them this special token that proves who they are. Think of it like a wristband at a concert - once you have it, you can access different areas without showing your ticket again.

## How JWT Works in Your System

### 1. **User Logs In** 
- User enters email and password on your website
- Your server checks if the credentials are correct
- If correct, server creates a JWT token and sends it back

### 2. **Token Storage**
- The JWT token is stored as a secure cookie in the user's browser
- Cookie name: `token`
- Cookie is HTTP-only (JavaScript cannot access it for security)

### 3. **Authentication Check**
- When user tries to access protected pages, browser automatically sends the cookie
- Your server reads the token and checks if it's valid
- If valid, user gets access; if not, user gets an error

## Your JWT Setup

### Library Used
- **firebase/php-jwt** - A trusted PHP library for creating and reading JWT tokens
- Location: `backend/home-management-system-Backend/vendor/firebase/php-jwt/`

### Key Files in Your System

#### 1. **auth.php** - The Heart of JWT System
Location: `backend/home-management-system-Backend/api/auth.php`

This file contains 3 main functions:

**generate_jwt($payload)**
- Creates a new JWT token
- Takes user information (user_id, email, user_type)
- Adds expiration time (1 hour)
- Returns the token string

**validate_jwt($jwt)**
- Checks if a token is valid and not expired
- Returns user information if valid, false if invalid

**require_auth()**
- Used by protected pages to ensure user is logged in
- Automatically checks the token cookie
- Stops access and shows error if token is missing/invalid

#### 2. **login.php** - Where JWT Tokens are Created
Location: `backend/home-management-system-Backend/api/login.php`

When user logs in:
1. Validates email and password
2. Creates JWT token with user information
3. Sets secure cookie with the token
4. Sends success response to frontend

#### 3. **logout.php** - Where JWT Tokens are Removed
Location: `backend/home-management-system-Backend/api/logout.php`

When user logs out:
1. Deletes the JWT cookie by setting it to expire in the past
2. User is logged out and needs to login again

### What Information is Stored in JWT Token

Your JWT token contains:
```
{
  "user_id": 123,           // Database ID of the user
  "email": "user@email.com", // User's email
  "user_type": "customer",   // Role: admin, provider, or customer  
  "provider_id": 45,         // Only for provider users
  "iat": 1234567890,         // When token was created
  "exp": 1234571490          // When token expires (1 hour later)
}
```

## Security Settings

### Current Security Features ✅
- **Strong Algorithm**: Uses HS256 (secure encryption method)
- **Token Expiration**: Tokens expire after 1 hour
- **HTTP-Only Cookies**: JavaScript cannot steal tokens
- **CORS Protection**: Only your frontend domain can access the API
- **Input Sanitization**: All user inputs are cleaned before processing

### Security Improvements Needed ⚠️
- **Secret Key**: Currently uses `'YOUR_SECRET_KEY_HERE'` - should be changed to a strong, random secret
- **HTTPS**: Should use HTTPS in production (currently HTTP for development)
- **Environment Variables**: Secret key should be stored in environment variables, not in code

## How Different User Types Work

### Customer Users
- Can access customer dashboard
- Can book services and subscriptions
- Can view their profile and bookings

### Provider Users
- Can access provider dashboard  
- Can manage their services
- Can view their bookings and earnings
- JWT includes `provider_id` for database queries

### Admin Users
- Can access admin dashboard
- Can manage all customers and providers
- Can switch to other user accounts (impersonation feature)
- Has highest level of access

## Protected Pages (Require JWT)

All these API endpoints check for valid JWT token:
- `customer_dashboard.php`
- `provider_dashboard.php`
- `admin_customers.php`
- `admin_stats.php`
- `get_providers.php`
- `service_booking.php`
- `subscription_booking.php`
- `update_customer_profile.php`
- `update_provider_profile.php`
- `switch_user.php` (admin only)
- And many more...

## Frontend Integration

### How Frontend Uses JWT

1. **Login Request**
```javascript
// Frontend sends login request
const response = await axios.post(
  'http://localhost/project-root/backend/home-management-system-Backend/api/login.php',
  { email: email, password: password },
  { withCredentials: true }  // This sends cookies
);
```

2. **Automatic Authentication**
```javascript
// Frontend checks if user is logged in
axios.get('http://localhost/project-root/backend/home-management-system-Backend/api/me.php', 
  { withCredentials: true }
)
```

3. **All API Calls**
- Frontend uses `withCredentials: true` in all axios requests
- This automatically sends the JWT cookie with every request
- No need to manually handle tokens in JavaScript

## Error Handling

### Common JWT Errors
- **401 Unauthorized**: Token is missing or invalid
- **403 Forbidden**: User doesn't have permission for that action
- **Token Expired**: User needs to login again

### What Happens When Token Expires
1. User gets 401 error from server
2. Frontend redirects user to login page
3. User must login again to get new token

## Admin Features

### User Switching (Impersonation)
- Admins can "become" another user temporarily
- Uses `switch_user.php` API
- Creates new JWT token for target user
- Used for customer support and testing

## Development vs Production

### Current Setup (Development)
- HTTP protocol (not HTTPS)
- Localhost domains allowed
- Simple secret key
- 1-hour token expiration

### Production Recommendations
- Use HTTPS everywhere
- Strong, random secret key stored in environment variables
- Restrict CORS to your actual domain
- Consider shorter token expiration (30 minutes)
- Add token refresh mechanism
- Add logging for security events

## Cookie Settings Explained

```php
setcookie('token', $token, [
    'expires' => time() + 3600,    // Expires in 1 hour
    'path' => '/',                 // Available on entire website  
    'domain' => '',                // Current domain only
    'secure' => false,             // Set to true for HTTPS
    'httponly' => true,            // JavaScript cannot access
    'samesite' => 'Lax'           // CSRF protection
]);
```

## Troubleshooting Common Issues

### "Unauthorized" Error
- Check if user is logged in
- Check if token cookie exists in browser
- Verify token hasn't expired
- Make sure `withCredentials: true` is set in frontend requests

### "Invalid Token" Error  
- Token may be corrupted
- Secret key might have changed
- Token format is incorrect
- User needs to login again

### CORS Errors
- Check if frontend domain is allowed in CORS headers
- Verify `withCredentials: true` is set
- Check if preflight OPTIONS request is handled

## Security Best Practices Summary

1. ✅ **Use strong secret keys** (needs improvement - currently using placeholder)
2. ✅ **Set token expiration** (1 hour)
3. ✅ **Use HTTP-only cookies** 
4. ✅ **Validate all inputs**
5. ✅ **Use CORS protection**
6. ⚠️ **Use HTTPS in production** (currently HTTP for development)
7. ⚠️ **Store secrets in environment variables** (currently in code)
8. ✅ **Handle errors properly**
9. ✅ **Use established JWT library** (firebase/php-jwt)

## File Structure Summary

```
backend/home-management-system-Backend/
├── api/
│   ├── auth.php              # JWT functions (generate, validate, require)
│   ├── login.php             # Creates JWT on login
│   ├── logout.php            # Removes JWT on logout  
│   ├── me.php                # Gets current user info
│   └── [protected endpoints] # All require JWT authentication
├── class/
│   └── User.php              # Handles login logic
└── vendor/
    └── firebase/
        └── php-jwt/          # JWT library
```

This JWT system provides secure authentication for your Home Management System, allowing users to login once and access all their permitted features without repeatedly entering credentials.
