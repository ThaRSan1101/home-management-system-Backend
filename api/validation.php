<?php
/**
 * validation.php
 *
 * Centralized validation functions for the Home Management System backend.
 * Provides reusable validation utilities for API endpoints and form processing.
 *
 * VALIDATION CATEGORIES:
 * =====================
 * - Personal Information: Names, addresses, phone numbers
 * - Date/Time: Service dates, appointment times
 * - Payment: Credit card numbers, expiry dates, CVV codes
 *
 * USAGE:
 * ======
 * Include or require this file in API endpoints that need input validation:
 * 
 * require_once __DIR__ . '/validation.php';
 * 
 * if (!validate_name($input['name'])) {
 *     $errors['name'] = 'Invalid name format';
 * }
 *
 * SECURITY FEATURES:
 * ==================
 * - Input sanitization with trim()
 * - Regex pattern matching for format validation
 * - Luhn algorithm for credit card validation
 * - Date validation to prevent past bookings
 *
 * DEPENDENCIES:
 * =============
 * None - Pure PHP validation functions
 */

// validation.php - Centralized backend validation functions for Home Management System
// Usage: include or require this file in API endpoints for input validation

/**
 * Validate person's name format.
 * 
 * @param string $name Name to validate
 * @return bool True if valid, false otherwise
 * 
 * Rules:
 * - Only letters (A-Z, a-z) and spaces allowed
 * - Whitespace is trimmed before validation
 * 
 * Usage: validate_name("John Doe") returns true
 */
function validate_name($name) {
    return preg_match('/^[A-Za-z ]+$/', trim($name));
}

/**
 * Validate address format and minimum length.
 * 
 * @param string $address Address to validate
 * @return bool True if valid, false otherwise
 * 
 * Rules:
 * - Minimum 4 characters after trimming whitespace
 * - Ensures address has sufficient detail
 * 
 * Usage: validate_address("123 Main St") returns true
 */
function validate_address($address) {
    return strlen(trim($address)) >= 4;
}

/**
 * Validate phone number format.
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 * 
 * Rules:
 * - Exactly 10 digits required
 * - No spaces, dashes, or other characters allowed
 * 
 * Usage: validate_phone("1234567890") returns true
 */
function validate_phone($phone) {
    return preg_match('/^\d{10}$/', $phone);
}

/**
 * Validate that a date is not in the past.
 * 
 * @param string $date Date string in Y-m-d format
 * @return bool True if date is today or future, false if past
 * 
 * Rules:
 * - Date must be today or in the future
 * - Prevents booking services for past dates
 * 
 * Usage: validate_date_not_past("2024-12-25") returns true if today is before Dec 25, 2024
 */
function validate_date_not_past($date) {
    $today = date('Y-m-d');
    return $date >= $today;
}

/**
 * Validate time format.
 * 
 * @param string $time Time string to validate
 * @return bool True if valid format, false otherwise
 * 
 * Rules:
 * - Accepts HH:MM or HH:MM:SS format
 * - Uses 24-hour format (00:00 to 23:59)
 * 
 * Usage: validate_time("14:30") returns true
 */
function validate_time($time) {
    return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time);
}

/**
 * Validate credit card number using Luhn algorithm.
 * 
 * @param string $card Credit card number (spaces allowed)
 * @return bool True if valid card number, false otherwise
 * 
 * Rules:
 * - Must be exactly 16 digits after removing spaces
 * - Must pass Luhn checksum algorithm validation
 * - Provides mathematical verification of card number validity
 * 
 * Usage: validate_card("4532 1234 5678 9012") returns true/false based on Luhn check
 */
function validate_card($card) {
    // Remove spaces, must be 16 digits
    $num = preg_replace('/\s+/', '', $card);
    if (!preg_match('/^\d{16}$/', $num)) return false;
    
    // Luhn algorithm for credit card validation
    $sum = 0; 
    $alt = false;
    for ($i = strlen($num) - 1; $i >= 0; $i--) {
        $n = intval($num[$i]);
        if ($alt) {
            $n *= 2;
            if ($n > 9) $n -= 9;
        }
        $sum += $n;
        $alt = !$alt;
    }
    return $sum % 10 === 0;
}

/**
 * Validate credit card expiry date.
 * 
 * @param string $expiry Expiry date in MM/YY format
 * @return bool True if valid and not expired, false otherwise
 * 
 * Rules:
 * - Must be in MM/YY format (e.g., "12/25")
 * - Month must be 01-12
 * - Date must be in the future (not expired)
 * 
 * Usage: validate_expiry("12/25") returns true if current date is before December 2025
 */
function validate_expiry($expiry) {
    if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) return false;
    $parts = explode('/', $expiry);
    $mm = intval($parts[0]);
    $yy = intval($parts[1]);
    if ($mm < 1 || $mm > 12) return false;
    $now = getdate();
    $exp_month = $mm;
    $exp_year = 2000 + $yy;
    if ($exp_year < $now['year'] || ($exp_year == $now['year'] && $exp_month < $now['mon'])) return false;
    return true;
}

/**
 * Validate credit card CVV code.
 * 
 * @param string $cvv CVV code to validate
 * @return bool True if valid format, false otherwise
 * 
 * Rules:
 * - Must be exactly 3 digits
 * - No letters or special characters allowed
 * 
 * Usage: validate_cvv("123") returns true
 */
function validate_cvv($cvv) {
    return preg_match('/^\d{3}$/', $cvv);
}

/**
 * USAGE EXAMPLES:
 * ===============
 * 
 * // Validate user input in API endpoints
 * if (!validate_name($input['name'])) { 
 *     $errors['name'] = 'Name can only contain letters and spaces'; 
 * }
 * 
 * if (!validate_phone($input['phone'])) { 
 *     $errors['phone'] = 'Phone number must be exactly 10 digits'; 
 * }
 * 
 * if (!validate_date_not_past($input['service_date'])) { 
 *     $errors['service_date'] = 'Service date cannot be in the past'; 
 * }
 * 
 * if (!validate_card($input['card_number'])) { 
 *     $errors['card_number'] = 'Invalid credit card number'; 
 * }
 * 
 * // Check if any validation errors occurred
 * if (!empty($errors)) {
 *     echo json_encode(['status' => 'error', 'errors' => $errors]);
 *     exit;
 * }
 */
