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
    if ($action === 'get_admin_count') {
        $result = $notificationObj->getAdminNotificationCount();
        echo json_encode($result);
    } elseif ($action === 'mark_admin_hidden') {
        $result = $notificationObj->markAdminNotificationsAsHidden();
        echo json_encode($result);
    } elseif ($action === 'get_pending_service_count') {
        $result = $notificationObj->getPendingServiceBookingCount();
        echo json_encode($result);
    } elseif ($action === 'get_pending_subscription_count') {
        $result = $notificationObj->getPendingSubscriptionBookingCount();
        echo json_encode($result);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action. Supported actions: get_admin_count, mark_admin_hidden, get_pending_service_count, get_pending_subscription_count']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
