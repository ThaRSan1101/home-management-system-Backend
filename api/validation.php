<?php
// validation.php - Centralized backend validation functions for Home Management System
// Usage: include or require this file in API endpoints for input validation

function validate_name($name) {
    return preg_match('/^[A-Za-z ]+$/', trim($name));
}

function validate_address($address) {
    return strlen(trim($address)) >= 4;
}

function validate_phone($phone) {
    return preg_match('/^\d{10}$/', $phone);
}

function validate_date_not_past($date) {
    $today = date('Y-m-d');
    return $date >= $today;
}

function validate_time($time) {
    return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time);
}

function validate_card($card) {
    // Remove spaces, must be 16 digits
    $num = preg_replace('/\s+/', '', $card);
    if (!preg_match('/^\d{16}$/', $num)) return false;
    // Luhn algorithm
    $sum = 0; $alt = false;
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

function validate_cvv($cvv) {
    return preg_match('/^\d{3}$/', $cvv);
}

// Usage Example:
// if (!validate_name($input['name'])) { $errors['name'] = 'Invalid name'; }
