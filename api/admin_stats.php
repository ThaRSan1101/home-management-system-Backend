<?php
// admin_stats.php - Returns counts for admin dashboard overview
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');

try {
    $db = new DBConnector();
    $conn = $db->connect();
    // Customers
    $stmt1 = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'customer'");
    $customerCount = $stmt1->fetchColumn();
    // Providers
    $stmt2 = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'provider'");
    $providerCount = $stmt2->fetchColumn();
    // Total Bookings (service + subscription)
    $stmt3 = $conn->query("SELECT COUNT(*) FROM service_booking");
    $serviceBookings = $stmt3->fetchColumn();
    $stmt4 = $conn->query("SELECT COUNT(*) FROM subscription_booking");
    $subscriptionBookings = $stmt4->fetchColumn();
    $totalBookings = $serviceBookings + $subscriptionBookings;
    // Completed Bookings (status = 'complete')
    $stmt5 = $conn->query("SELECT COUNT(*) FROM service_booking WHERE serbooking_status = 'complete'");
    $completedService = $stmt5->fetchColumn();
    $stmt6 = $conn->query("SELECT COUNT(*) FROM subscription_booking WHERE subbooking_status = 'complete'");
    $completedSubscription = $stmt6->fetchColumn();
    $completedBookings = $completedService + $completedSubscription;
    echo json_encode([
        'status' => 'success',
        'customers' => (int)$customerCount,
        'providers' => (int)$providerCount,
        'totalBookings' => (int)$totalBookings,
        'completedBookings' => (int)$completedBookings
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
