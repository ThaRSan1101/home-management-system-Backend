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
    } elseif ($action === 'get_admin_canceled_service_count') {
        $result = $notificationObj->getAdminCanceledServiceBookingCount();
        echo json_encode($result);
    } elseif ($action === 'get_admin_completed_service_count') {
        $result = $notificationObj->getAdminCompletedServiceBookingCount();
        echo json_encode($result);
    } elseif ($action === 'hide_single_admin_canceled_service') {
        $result = $notificationObj->hideSingleAdminCanceledServiceBooking();
        echo json_encode($result);
    } elseif ($action === 'get_admin_active_notifications') {
        $result = $notificationObj->getAdminActiveNotifications();
        echo json_encode($result);
    } elseif ($action === 'get_provider_id_by_user') {
        $user_id = $_GET['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
            exit;
        }
        $result = $notificationObj->getProviderIdByUserId($user_id);
        echo json_encode($result);
    } elseif ($action === 'get_provider_service_request_count') {
        $provider_id = $_GET['provider_id'] ?? null;
        if (!$provider_id) {
            echo json_encode(['status' => 'error', 'message' => 'Provider ID is required.']);
            exit;
        }
        $result = $notificationObj->getProviderServiceRequestNotificationCount($provider_id);
        echo json_encode($result);
    } elseif ($action === 'mark_single_provider_service_request_hidden') {
        $provider_id = $_GET['provider_id'] ?? null;
        if (!$provider_id) {
            echo json_encode(['status' => 'error', 'message' => 'Provider ID is required.']);
            exit;
        }
        $result = $notificationObj->markSingleProviderServiceRequestNotificationAsHidden($provider_id);
        echo json_encode($result);
    } elseif ($action === 'get_provider_canceled_service_count') {
        $provider_id = $_GET['provider_id'] ?? null;
        if (!$provider_id) {
            echo json_encode(['status' => 'error', 'message' => 'Provider ID is required.']);
            exit;
        }
        $result = $notificationObj->getProviderCanceledServiceBookingCount($provider_id);
        echo json_encode($result);
    } elseif ($action === 'get_provider_completed_service_count') {
        $provider_id = $_GET['provider_id'] ?? null;
        if (!$provider_id) {
            echo json_encode(['status' => 'error', 'message' => 'Provider ID is required.']);
            exit;
        }
        $result = $notificationObj->getProviderCompletedServiceBookingCount($provider_id);
        echo json_encode($result);
    } elseif ($action === 'hide_single_provider_canceled_service') {
        $provider_id = $_GET['provider_id'] ?? null;
        if (!$provider_id) {
            echo json_encode(['status' => 'error', 'message' => 'Provider ID is required.']);
            exit;
        }
        $result = $notificationObj->hideSingleProviderCanceledServiceBooking($provider_id);
        echo json_encode($result);
    } elseif ($action === 'get_provider_active_notifications') {
        $provider_id = $_GET['provider_id'] ?? null;
        if (!$provider_id) {
            echo json_encode(['status' => 'error', 'message' => 'Provider ID is required.']);
            exit;
        }
        $result = $notificationObj->getProviderActiveNotifications($provider_id);
        echo json_encode($result);
    } elseif ($action === 'get_customer_canceled_service_count') {
        $user_id = $_GET['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
            exit;
        }
        $result = $notificationObj->getCustomerCanceledServiceBookingCount($user_id);
        echo json_encode($result);
    } elseif ($action === 'get_customer_completed_service_count') {
        $user_id = $_GET['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
            exit;
        }
        $result = $notificationObj->getCustomerCompletedServiceBookingCount($user_id);
        echo json_encode($result);
    } elseif ($action === 'hide_single_customer_canceled_service') {
        $user_id = $_GET['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
            exit;
        }
        $result = $notificationObj->hideSingleCustomerCanceledServiceBooking($user_id);
        echo json_encode($result);
    } elseif ($action === 'get_customer_active_notifications') {
        $user_id = $_GET['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
            exit;
        }
        $result = $notificationObj->getCustomerActiveNotifications($user_id);
        echo json_encode($result);
    } elseif ($action === 'hide_notification_by_id') {
        $notification_id = $_GET['notification_id'] ?? null;
        $role = $_GET['role'] ?? null;
        if (!$notification_id || !$role) {
            echo json_encode(['status' => 'error', 'message' => 'notification_id and role are required.']);
            exit;
        }
        $result = $notificationObj->hideNotificationById((int)$notification_id, $role);
        echo json_encode($result);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action. Supported actions include admin/provider/customer canceled service endpoints.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
