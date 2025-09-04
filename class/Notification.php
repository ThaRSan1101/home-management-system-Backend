<?php
/**
 * Notification.php
 *
 * Simple notification class for admin notification count functionality.
 * Handles creating notifications and getting admin notification count.
 *
 * Dependencies:
 * - db.php: Database connection
 */

require_once __DIR__ . '/../api/db.php';

/**
 * Class Notification
 *
 * Simple notification handler for admin dashboard.
 */
class Notification {
    protected $conn;
    
    public function __construct($dbConn = null) {
        if ($dbConn) {
            $this->conn = $dbConn;
        } else {
            $db = new DBConnector();
            $this->conn = $db->connect();
        }
    }



    /**
     * Mark only customer registration notifications as hidden.
     * This method specifically targets notifications with description = "New customer registered"
     * and leaves service booking notifications unaffected.
     *
     * @return array Status and message
     */
    public function markCustomerRegistrationNotificationsAsHidden() {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notification 
                SET admin_action = 'hidden' 
                WHERE admin_action = 'active' AND description = 'New customer registered'
            ");
            
            $result = $stmt->execute();
            $affectedRows = $stmt->rowCount();

            return [
                'status' => 'success', 
                'message' => "Marked {$affectedRows} customer registration notifications as hidden."
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get pending service booking count.
     *
     * @return array Status and count
     */
    public function getPendingServiceBookingCount() {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM service_booking 
                WHERE serbooking_status = 'pending'
            ");
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'status' => 'success',
                'count' => (int)$result['count']
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get pending subscription booking count.
     *
     * @return array Status and count
     */
    public function getPendingSubscriptionBookingCount() {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM subscription_booking 
                WHERE subbooking_status = 'pending'
            ");
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'status' => 'success',
                'count' => (int)$result['count']
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get new service booking notifications count.
     * Only counts notifications with description = "New service booking" and admin_action = "active"
     *
     * @return array Status and count
     */
    public function getNewServiceBookingNotificationCount() {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM notification 
                WHERE description = 'New service booking' AND admin_action = 'active'
            ");
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'status' => 'success',
                'count' => (int)$result['count']
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get new subscription service booking notifications count for admin.
     */
    public function getNewSubscriptionBookingNotificationCount() {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM notification 
                WHERE description = 'New subscription service booking' AND admin_action = 'active'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'status' => 'success',
                'count' => (int)$result['count']
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get customer registration notifications count.
     * Only counts notifications with description = "New customer registered" and admin_action = "active"
     *
     * @return array Status and count
     */
    public function getCustomerRegistrationNotificationCount() {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM notification 
                WHERE description = 'New customer registered' AND admin_action = 'active'
            ");
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'status' => 'success',
                'count' => (int)$result['count']
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }



    /**
     * Mark a single service booking notification as hidden by hiding the oldest one.
     * This method hides the oldest notification with description = "New service booking"
     * and admin_action = 'active'.
     *
     * @return array Status and message
     */
    public function markSingleServiceBookingNotificationAsHidden() {
        try {
            // First, get the oldest service booking notification
            $stmt = $this->conn->prepare("
                SELECT notification_id 
                FROM notification 
                WHERE admin_action = 'active' AND description = 'New service booking'
                ORDER BY notification_id ASC 
                LIMIT 1
            ");
            
            $stmt->execute();
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notification) {
                return [
                    'status' => 'success', 
                    'message' => "No active service booking notifications to hide."
                ];
            }
            
            // Hide the specific notification
            $updateStmt = $this->conn->prepare("
                UPDATE notification 
                SET admin_action = 'hidden' 
                WHERE notification_id = ?
            ");
            
            $result = $updateStmt->execute([$notification['notification_id']]);
            $affectedRows = $updateStmt->rowCount();

            return [
                'status' => 'success', 
                'message' => "Marked 1 service booking notification as hidden."
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get provider service request notifications count.
     * Only counts notifications with description = "You have a new service request" 
     * and provider_action = "active" for a specific provider.
     *
     * @param int $provider_id The provider's ID
     * @return array Status and count
     */
    public function getProviderServiceRequestNotificationCount($provider_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM notification 
                WHERE provider_id = ? AND description = 'You have a new service request' AND provider_action = 'active'
            ");
            
            $stmt->execute([$provider_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'status' => 'success',
                'count' => (int)$result['count']
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get provider subscription request notifications count.
     * Only counts notifications with description = "You have a new subscription service request"
     * and provider_action = "active" for a specific provider.
     */
    public function getProviderSubscriptionRequestNotificationCount($provider_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM notification 
                WHERE provider_id = ? AND description = 'You have a new subscription service request' AND provider_action = 'active'
            ");
            $stmt->execute([$provider_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'status' => 'success',
                'count' => (int)$result['count']
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Mark a single provider service request notification as hidden by hiding the oldest one.
     * This method hides the oldest notification with description = "You have a new service request"
     * and provider_action = 'active' for a specific provider.
     *
     * @param int $provider_id The provider's ID
     * @return array Status and message
     */
    public function markSingleProviderServiceRequestNotificationAsHidden($provider_id) {
        try {
            // First, get the oldest provider service request notification
            $stmt = $this->conn->prepare("
                SELECT notification_id 
                FROM notification 
                WHERE provider_id = ? AND provider_action = 'active' AND description = 'You have a new service request'
                ORDER BY notification_id ASC 
                LIMIT 1
            ");
            
            $stmt->execute([$provider_id]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notification) {
                return [
                    'status' => 'success', 
                    'message' => "No active service request notifications to hide."
                ];
            }
            
            // Hide the specific notification
            $updateStmt = $this->conn->prepare("
                UPDATE notification 
                SET provider_action = 'hidden' 
                WHERE notification_id = ?
            ");
            
            $result = $updateStmt->execute([$notification['notification_id']]);
            $affectedRows = $updateStmt->rowCount();

            return [
                'status' => 'success', 
                'message' => "Marked 1 service request notification as hidden."
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Resolve provider_id from a given user_id.
     *
     * @param int $user_id
     * @return array { status, provider_id|null, message? }
     */
    public function getProviderIdByUserId($user_id) {
        try {
            $stmt = $this->conn->prepare("SELECT provider_id FROM provider WHERE user_id = ? LIMIT 1");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['provider_id'])) {
                return ['status' => 'success', 'provider_id' => (int)$row['provider_id']];
            }
            return ['status' => 'error', 'message' => 'Provider not found for user', 'provider_id' => null];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage(), 'provider_id' => null];
        }
    }

    // ---------------- CANCELLATION NOTIFICATIONS ----------------

    /**
     * Count admin-visible canceled service booking notifications.
     */
    public function getAdminCanceledServiceBookingCount() {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM notification 
                WHERE description = 'Service booking is canceled' AND admin_action = 'active'
            ");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'count' => (int)$row['count']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Count admin-visible completed service booking notifications.
     */
    public function getAdminCompletedServiceBookingCount() {
        try {
            $stmt = $this->conn->prepare("\n                SELECT COUNT(*) as count\n                FROM notification \n                WHERE description = 'Service booking is completed' AND admin_action = 'active'\n            ");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'count' => (int)$row['count']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Count admin-visible 'Subscription service is completed' notifications.
     */
    public function getAdminSubscriptionCompletedCount() {
        try {
            $stmt = $this->conn->prepare("\n                SELECT COUNT(*) as count\n                FROM notification \n                WHERE description = 'Subscription service is completed' AND admin_action = 'active'\n            ");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'count' => (int)$row['count']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Hide oldest admin-visible canceled service booking notification.
     */
    public function hideSingleAdminCanceledServiceBooking() {
        try {
            $stmt = $this->conn->prepare("
                SELECT notification_id FROM notification
                WHERE description = 'Service booking is canceled' AND admin_action = 'active'
                ORDER BY notification_id ASC LIMIT 1
            ");
            $stmt->execute();
            $n = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$n) {
                return ['status' => 'success', 'message' => 'No active canceled notifications to hide.'];
            }
            $up = $this->conn->prepare("UPDATE notification SET admin_action = 'hidden' WHERE notification_id = ?");
            $up->execute([$n['notification_id']]);
            return ['status' => 'success', 'message' => 'Marked 1 canceled notification as hidden.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Count provider-visible canceled service booking notifications for a provider.
     */
    public function getProviderCanceledServiceBookingCount($provider_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM notification
                WHERE provider_id = ? AND description = 'Service booking is canceled' AND provider_action = 'active'
            ");
            $stmt->execute([$provider_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'count' => (int)$row['count']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Count provider-visible completed service booking notifications for a provider.
     */
    public function getProviderCompletedServiceBookingCount($provider_id) {
        try {
            $stmt = $this->conn->prepare("\n                SELECT COUNT(*) as count FROM notification\n                WHERE provider_id = ? AND description = 'Service booking is completed' AND provider_action = 'active'\n            ");
            $stmt->execute([$provider_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'count' => (int)$row['count']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Count provider-visible 'Subscription service is completed' notifications.
     */
    public function getProviderSubscriptionCompletedCount($provider_id) {
        try {
            $stmt = $this->conn->prepare("\n                SELECT COUNT(*) as count FROM notification\n                WHERE provider_id = ? AND description = 'Subscription service is completed' AND provider_action = 'active'\n            ");
            $stmt->execute([$provider_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'count' => (int)$row['count']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Hide oldest provider-visible canceled service booking notification for a provider.
     */
    public function hideSingleProviderCanceledServiceBooking($provider_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT notification_id FROM notification
                WHERE provider_id = ? AND description = 'Service booking is canceled' AND provider_action = 'active'
                ORDER BY notification_id ASC LIMIT 1
            ");
            $stmt->execute([$provider_id]);
            $n = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$n) {
                return ['status' => 'success', 'message' => 'No active canceled notifications to hide.'];
            }
            $up = $this->conn->prepare("UPDATE notification SET provider_action = 'hidden' WHERE notification_id = ?");
            $up->execute([$n['notification_id']]);
            return ['status' => 'success', 'message' => 'Marked 1 canceled notification as hidden.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Count customer-visible canceled service booking notifications for a user.
     */
    public function getCustomerCanceledServiceBookingCount($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM notification
                WHERE user_id = ? AND description = 'Service booking is canceled' AND customer_action = 'active'
            ");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'count' => (int)$row['count']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Count customer-visible completed service booking notifications for a user.
     */
    public function getCustomerCompletedServiceBookingCount($user_id) {
        try {
            $stmt = $this->conn->prepare("\n                SELECT COUNT(*) as count FROM notification\n                WHERE user_id = ? AND description = 'Service booking is completed' AND customer_action = 'active'\n            ");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'count' => (int)$row['count']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Count customer-visible 'Subscription service is completed' notifications.
     */
    public function getCustomerSubscriptionCompletedCount($user_id) {
        try {
            $stmt = $this->conn->prepare("\n                SELECT COUNT(*) as count FROM notification\n                WHERE user_id = ? AND description = 'Subscription service is completed' AND customer_action = 'active'\n            ");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'count' => (int)$row['count']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Hide oldest customer-visible canceled service booking notification for a user.
     */
    public function hideSingleCustomerCanceledServiceBooking($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT notification_id FROM notification
                WHERE user_id = ? AND description = 'Service booking is canceled' AND customer_action = 'active'
                ORDER BY notification_id ASC LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $n = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$n) {
                return ['status' => 'success', 'message' => 'No active canceled notifications to hide.'];
            }
            $up = $this->conn->prepare("UPDATE notification SET customer_action = 'hidden' WHERE notification_id = ?");
            $up->execute([$n['notification_id']]);
            return ['status' => 'success', 'message' => 'Marked 1 canceled notification as hidden.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // ---------------- FETCH ACTIVE NOTIFICATIONS (DETAILS) ----------------

    /**
     * Get admin-visible active notifications (new service bookings and canceled bookings).
     * Returns an array of { notification_id, description, created_at }.
     */
    public function getAdminActiveNotifications() {
        try {
            $stmt = $this->conn->prepare("
                SELECT notification_id, description, created_at
                FROM notification
                WHERE admin_action = 'active' AND description IN ('New service booking', 'New subscription service booking', 'Service booking is canceled', 'Service booking is completed', 'Subscription service is completed')
                ORDER BY notification_id DESC
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'data' => $rows];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get provider-visible active notifications for given provider.
     * Returns an array of { notification_id, description, created_at }.
     */
    public function getProviderActiveNotifications($provider_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT notification_id, description, created_at
                FROM notification
                WHERE provider_id = ? AND provider_action = 'active'
                AND description IN ('You have a new service request', 'You have a new subscription service request', 'Service booking is canceled', 'Service booking is completed', 'Subscription service is completed')
                ORDER BY notification_id DESC
            ");
            $stmt->execute([$provider_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'data' => $rows];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get customer-visible active notifications for given user.
     * Returns an array of { notification_id, description, created_at }.
     */
    public function getCustomerActiveNotifications($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT notification_id, description, created_at
                FROM notification
                WHERE user_id = ? AND customer_action = 'active'
                ORDER BY notification_id DESC
            ");
            $stmt->execute([$user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'data' => $rows];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Hide a notification for a specific role by notification_id.
     * $role in ['admin','provider','customer']
     */
    public function hideNotificationById($notification_id, $role) {
        try {
            if ($role === 'admin') {
                $stmt = $this->conn->prepare("UPDATE notification SET admin_action = 'hidden' WHERE notification_id = ?");
                $stmt->execute([$notification_id]);
            } elseif ($role === 'provider') {
                $stmt = $this->conn->prepare("UPDATE notification SET provider_action = 'hidden' WHERE notification_id = ?");
                $stmt->execute([$notification_id]);
            } elseif ($role === 'customer') {
                $stmt = $this->conn->prepare("UPDATE notification SET customer_action = 'hidden' WHERE notification_id = ?");
                $stmt->execute([$notification_id]);
            } else {
                return ['status' => 'error', 'message' => 'Invalid role'];
            }
            return ['status' => 'success', 'message' => 'Notification hidden'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
