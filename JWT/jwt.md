# JWT Security and Best Practices for PHP (firebase/php-jwt)

This document explains how to use JWT securely in your PHP backend using the `firebase/php-jwt` library. It summarizes best practices, common pitfalls, and recommendations for your Home Management System project.

---

## Why Use `firebase/php-jwt`?
- **Maintained by Firebase team**
- **Widely used and open source**
- **Stable, well-documented, and secure when used correctly**

---

## ✅ Best Practices Checklist

| Practice                        | Status  | Notes/Recommendations                      |
|----------------------------------|---------|--------------------------------------------|
| Use strong secret keys           | REQUIRED| Use a random, 256-bit key from env vars    |
| Set `exp` (expiration)           | YES     | Prevents long-term abuse                   |
| Set `iat` and `nbf`              | YES     | Time-based validation                      |
| Catch exceptions on decode       | YES     | Avoids backend crashes                     |
| Use HTTPS                        | REQUIRED| Prevent token leaks                        |
| Store in HTTP-only cookies       | YES     | Never use localStorage for sensitive data  |
| Use `credentials: 'include'`     | YES     | Ensures cookies sent with requests         |
| Hardcode algorithm (`HS256`)     | YES     | Prevents algorithm confusion attacks       |
| Return only safe fields          | YES     | No sensitive info in API responses         |

---

## ⚠️ Common JWT Security Pitfalls

1. **Not Validating the Token Properly**
   - Always use `JWT::decode($jwt, new Key($secret, 'HS256'))`.
   - Never trust a token without verifying the signature.

2. **Using a Weak Secret Key**
   - Do NOT use simple strings (e.g., `123456`).
   - Use a strong, random secret (at least 256 bits for HS256).
   - Store your secret in an environment variable, NOT in code.

3. **Not Checking Expiration**
   - Always set and check the `exp` (expiration) field.
   - The library will throw on expired tokens, but you should handle this in your code.

4. **Exposing Tokens to Client-Side JS**
   - Store JWTs in HTTP-only cookies.
   - Avoid localStorage/sessionStorage for sensitive data.

---

## 🔒 Example: Secure JWT Usage in PHP

```php
// .env or server environment:
// JWT_SECRET=your-long-random-secret-here

define('JWT_SECRET', getenv('JWT_SECRET'));

// Generating a token:
$payload = [
    'user_id' => $userId,
    'user_type' => $userType,
    // ...other claims
];
$jwt = JWT::encode($payload, JWT_SECRET, 'HS256');

// Decoding and verifying a token:
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
try {
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
    // Token is valid and not expired
} catch (Firebase\JWT\ExpiredException $e) {
    // Token expired
} catch (Exception $e) {
    // Invalid token
}
```

---

## 🔐 Cookie Security
- Always set cookies with `HttpOnly; Secure; SameSite=Strict` flags.
- Only send JWT cookies over HTTPS in production.

---

## 🚦 Implementation Notes for Your Project
- Your code uses `firebase/php-jwt` correctly for encode/decode.
- **Update your secret key:**
  - Replace `'YOUR_SECRET_KEY_HERE'` with a strong value from an environment variable.
- **Set cookies securely:**
  - Use `setcookie('token', $jwt, [..., 'httponly' => true, 'secure' => true, 'samesite' => 'Strict'])`.
- **Handle exceptions:**
  - Optionally catch `ExpiredException` separately for better error messages.
- **Deploy with HTTPS** in production.

---

## 📋 Summary
- `firebase/php-jwt` is safe and stable **if used correctly**.
- The biggest risks are weak secrets, improper validation, and insecure token storage.
- Follow the checklist above for a secure authentication system.

---

**For more details, see:**
- [firebase/php-jwt GitHub](https://github.com/firebase/php-jwt)
- [JWT.io Introduction](https://jwt.io/introduction/)
