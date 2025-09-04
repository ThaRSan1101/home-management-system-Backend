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
     * Get admin notification count.
     *
     * @return array Status and count
     */
    public function getAdminNotificationCount() {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM notification 
                WHERE admin_action = 'active'
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
     * Mark all admin notifications as hidden.
     *
     * @return array Status and message
     */
    public function markAdminNotificationsAsHidden() {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notification 
                SET admin_action = 'hidden' 
                WHERE admin_action = 'active'
            ");
            
            $result = $stmt->execute();
            $affectedRows = $stmt->rowCount();

            return [
                'status' => 'success', 
                'message' => "Marked {$affectedRows} admin notifications as hidden."
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

}
