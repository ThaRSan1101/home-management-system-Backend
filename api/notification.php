<?php
/**
 * notification.php
 *
 * Simple API endpoint for admin notification count.
 * Only handles GET request for admin notification count.
 *
 * CORS headers included for frontend integration with http://localhost:5173.
 */

require_once __DIR__ . '/../class/Notification.php';

// Set CORS and content headers for frontend integration
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Only handle GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

// Get action parameter
$action = $_GET['action'] ?? '';

// Initialize notification object
$notificationObj = new Notification();

try {
    if ($action === 'mark_customer_registration_hidden') {
        $result = $notificationObj->markCustomerRegistrationNotificationsAsHidden();
        echo json_encode($result);
    } elseif ($action === 'get_pending_service_count') {
        $result = $notificationObj->getPendingServiceBookingCount();
        echo json_encode($result);
    } elseif ($action === 'get_pending_subscription_count') {
        $result = $notificationObj->getPendingSubscriptionBookingCount();
        echo json_encode($result);
    } elseif ($action === 'get_new_service_booking_count') {
        $result = $notificationObj->getNewServiceBookingNotificationCount();
        echo json_encode($result);
    } elseif ($action === 'get_customer_registration_count') {
        $result = $notificationObj->getCustomerRegistrationNotificationCount();
        echo json_encode($result);
    } elseif ($action === 'mark_single_service_booking_hidden') {
        $result = $notificationObj->markSingleServiceBookingNotificationAsHidden();
        echo json_encode($result);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action. Supported actions: mark_customer_registration_hidden, get_pending_service_count, get_pending_subscription_count, get_new_service_booking_count, get_customer_registration_count, mark_single_service_booking_hidden']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
