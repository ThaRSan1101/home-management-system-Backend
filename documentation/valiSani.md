# Validation & Sanitization Documentation

## Overview

Two-layer validation system: frontend for UX, backend for security. All forms use consistent patterns for validation and sanitization.

## Validation Patterns

### **Registration Form**
```javascript
// Frontend (Register.jsx)
const nameRegex = /^[A-Za-z ]+$/;
const emailRegex = /^[a-zA-Z0-9._]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
const phoneRegex = /^\d{10}$/;
const nicRegex = /^(\d{12}|\d{9}[Vv])$/;

// Password validation
const validatePassword = (password) => {
  const err = [];
  if (password.length < 8) err.push('Min 8 characters');
  if (!/[A-Z]/.test(password)) err.push('At least one uppercase');
  if (!/[a-z]/.test(password)) err.push('At least one lowercase');
  if (!/\d/.test(password)) err.push('At least one number');
  if (!/[!@#$%^&*]/.test(password)) err.push('At least one special char');
  return err;
};
```

```php
// Backend (User.php)
if (!preg_match('/^[A-Za-z ]+$/', $fullName)) {
    return ['status' => 'error', 'message' => 'Full name can only contain letters and spaces.'];
}

if (!preg_match('/^[a-zA-Z0-9._]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    return ['status' => 'error', 'message' => 'Enter a valid email address'];
}

if (!preg_match('/^\d{10}$/', $phone)) {
    return ['status' => 'error', 'message' => 'Phone number must be exactly 10 digits.'];
}

if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $password)) {
    return ['status' => 'error', 'message' => 'Password must be at least 8 characters with letter, number, and special character.'];
}
```

### **Login Form**
```javascript
// Frontend
if (!/\S+@\S+\.\S+/.test(formData.email)) newErrors.email = 'Invalid email format';
if (!formData.password) newErrors.password = 'Password is required';
```

```php
// Backend
if (!$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
}

$email = htmlspecialchars(strtolower(trim($email)), ENT_QUOTES, 'UTF-8');
$password = htmlspecialchars(trim($password), ENT_QUOTES, 'UTF-8');
```

### **Service Booking Form**
```javascript
// Frontend validation
if (!/^[A-Za-z ]+$/.test(form.name.trim())) 
  errs.name = 'Name must contain only letters and spaces';

if (!/^\d{10}$/.test(form.phone)) errs.phone = 'Phone must be 10 digits';

// Date cannot be in past
const today = new Date();
const selected = new Date(form.date);
if (selected < today) errs.date = 'Date cannot be in the past';

// Phone input sanitization
<input onChange={e => setForm(f => ({ 
  ...f, 
  phone: e.target.value.replace(/\D/g, '') // Only digits
}))} />
```

```php
// Backend validation
$required = ['service_category_id', 'user_id', 'customer_name', 'service_date', 'service_time', 'service_address', 'phoneNo', 'amount'];

foreach ($required as $field) {
    if (empty($data[$field])) {
        return ['status' => 'error', 'message' => "Missing required field: $field."];
    }
}
```

### **Contact Form**
```javascript
// Frontend
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
if (!emailRegex.test(formData.email)) {
  toast.error('Please enter a valid email address.');
}

if (formData.phone.trim() && !/^\d{10}$/.test(formData.phone.trim())) {
  toast.error('Please enter a valid 10-digit phone number.');
}
```

```php
// Backend
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format.']);
    exit();
}
```

## Sanitization Functions

### **Backend Sanitization**
```php
// Standard sanitization
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sanitize_email($email) {
    return htmlspecialchars(strtolower(trim($email)), ENT_QUOTES, 'UTF-8');
}

// Applied in all forms
$name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars(strtolower(trim($data['email'])), ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars(trim($data['phone_number']), ENT_QUOTES, 'UTF-8');
```

### **Profile Updates**
```javascript
// Frontend - phone numbers only
const handleEditChange = (e) => {
  if (name === 'phone') {
    const numeric = value.replace(/[^0-9]/g, '');
    setEditData((prev) => ({ ...prev, [name]: numeric }));
  }
};
```

```php
// Backend - uniqueness checks
$checkEmail = $this->conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
$checkEmail->execute([strtolower(trim($data['email'])), $userId]);
if ($checkEmail->fetch()) {
    return ['status' => 'error', 'message' => 'Email already exists.'];
}
```

## Validation Library (validation.php)

```php
function validate_name($name) {
    return preg_match('/^[A-Za-z ]+$/', trim($name));
}

function validate_phone($phone) {
    return preg_match('/^\d{10}$/', $phone);
}

function validate_address($address) {
    return strlen(trim($address)) >= 4;
}

function validate_date_not_past($date) {
    return $date >= date('Y-m-d');
}

function validate_card($card) {
    $num = preg_replace('/\s+/', '', $card);
    if (!preg_match('/^\d{16}$/', $num)) return false;
    // Luhn algorithm validation
    $sum = 0; $alt = false;
    for ($i = strlen($num) - 1; $i >= 0; $i--) {
        $n = intval($num[$i]);
        if ($alt) { $n *= 2; if ($n > 9) $n -= 9; }
        $sum += $n; $alt = !$alt;
    }
    return $sum % 10 === 0;
}
```

## Security Features

### **XSS Prevention**
```php
// All user input sanitized
$name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars(strtolower(trim($data['email'])), ENT_QUOTES, 'UTF-8');

// Email content sanitization
$body = 'Hello, <strong>' . htmlspecialchars($customerName) . '</strong>';
```

### **SQL Injection Prevention**
```php
// Prepared statements everywhere
$stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? AND user_type = ?");
$stmt->execute([$email, $userType]);
```

### **Error Handling**
```javascript
// Frontend error display
{errors.email && <span className="auth-error">{errors.email}</span>}

// Error collection
const validateForm = () => {
  const newErrors = {};
  if (!formData.email) newErrors.email = 'Email required';
  setErrors(newErrors);
  return Object.keys(newErrors).length === 0;
};
```

```php
// Backend error responses
if (!$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit;
}
```

## Form Validation Summary

| Form | Frontend | Backend | Sanitization |
|------|----------|---------|--------------|
| **Registration** | Name, Email, Phone, NIC, Password rules | Regex validation + duplicates | htmlspecialchars, trim, strtolower |
| **Login** | Email format, Required fields | Input validation | htmlspecialchars, trim |
| **Booking** | Name, Phone, Date validation | Required fields | Standard sanitization |
| **Contact** | Email, Phone (optional) | filter_var validation | trim, htmlspecialchars |
| **Profile** | Phone numbers only | OTP + uniqueness | Full sanitization |
| **Admin** | All fields + business rules | Comprehensive validation | Complete sanitization |

## Validation Rules Applied
- ✅ Two-layer validation (frontend + backend)
- ✅ Regex patterns for all inputs
- ✅ Length restrictions and business rules
- ✅ Uniqueness checks for emails/NICs
- ✅ XSS and SQL injection prevention
- ✅ Consistent error handling patterns