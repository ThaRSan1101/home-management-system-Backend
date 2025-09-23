<?php
/**
 * notification.php
 *
 * Comprehensive notification management API endpoint for the Home Management System.
 * Handles notification operations for administrators, providers, and customers.
 *
 * SUPPORTED ACTIONS:
 * ==================
 * 
 * ADMIN NOTIFICATIONS:
 * - mark_customer_registration_hidden: Hide all customer registration notifications
 * - get_pending_service_count: Count of pending service bookings
 * - get_pending_subscription_count: Count of pending subscription bookings
 * - get_new_service_booking_count: Count of new service booking notifications
 * - get_new_subscription_booking_count: Count of new subscription booking notifications
 * - get_customer_registration_count: Count of customer registration notifications
 * - mark_single_service_booking_hidden: Hide single service booking notification
 * - get_admin_canceled_service_count: Count of canceled service bookings for admin
 * - get_admin_completed_service_count: Count of completed service bookings for admin
 * - get_admin_subscription_completed_count: Count of completed subscriptions for admin
 * - hide_single_admin_canceled_service: Hide single canceled service notification (admin)
 * - get_admin_active_notifications: Get all active notifications for admin
 *
 * PROVIDER NOTIFICATIONS:
 * - get_provider_id_by_user: Get provider ID from user ID
 * - get_provider_service_request_count: Count of service requests for provider
 * - get_provider_subscription_request_count: Count of subscription requests for provider
 * - mark_single_provider_service_request_hidden: Hide provider service request notification
 * - get_provider_canceled_service_count: Count of canceled services for provider
 * - get_provider_completed_service_count: Count of completed services for provider
 * - get_provider_subscription_completed_count: Count of completed subscriptions for provider
 * - hide_single_provider_canceled_service: Hide single canceled service notification (provider)
 * - get_provider_active_notifications: Get all active notifications for provider
 *
 * CUSTOMER NOTIFICATIONS:
 * - get_customer_canceled_service_count: Count of canceled services for customer
 * - get_customer_completed_service_count: Count of completed services for customer
 * - get_customer_subscription_completed_count: Count of completed subscriptions for customer
 * - hide_single_customer_canceled_service: Hide single canceled service notification (customer)
 * - get_customer_active_notifications: Get all active notifications for customer
 *
 * UNIVERSAL ACTIONS:
 * - hide_notification_by_id: Hide specific notification by ID and role
 *
 * USAGE:
 * ======
 * Make GET requests with action parameter and any required parameters:
 * - GET /api/notification.php?action=get_admin_active_notifications
 * - GET /api/notification.php?action=get_provider_service_request_count&provider_id=123
 * - GET /api/notification.php?action=hide_notification_by_id&notification_id=456&role=admin
 *
 * CORS headers included for frontend integration with http://localhost:5173.
 * All responses are in JSON format with status and data/message fields.
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
    } elseif ($action === 'get_new_subscription_booking_count') {
        $result = $notificationObj->getNewSubscriptionBookingNotificationCount();
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
    } elseif ($action === 'get_admin_subscription_completed_count') {
        $result = $notificationObj->getAdminSubscriptionCompletedCount();
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
    } elseif ($action === 'get_provider_subscription_request_count') {
        $provider_id = $_GET['provider_id'] ?? null;
        if (!$provider_id) {
            echo json_encode(['status' => 'error', 'message' => 'Provider ID is required.']);
            exit;
        }
        $result = $notificationObj->getProviderSubscriptionRequestNotificationCount($provider_id);
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
    } elseif ($action === 'get_provider_subscription_completed_count') {
        $provider_id = $_GET['provider_id'] ?? null;
        if (!$provider_id) {
            echo json_encode(['status' => 'error', 'message' => 'Provider ID is required.']);
            exit;
        }
        $result = $notificationObj->getProviderSubscriptionCompletedCount($provider_id);
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
    } elseif ($action === 'get_customer_subscription_completed_count') {
        $user_id = $_GET['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
            exit;
        }
        $result = $notificationObj->getCustomerSubscriptionCompletedCount($user_id);
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
