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
}
