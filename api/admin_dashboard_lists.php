<?php
// admin_dashboard_lists.php - Returns latest customers, providers, service bookings, and subscription bookings
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');

try {
    $db = new DBConnector();
    $conn = $db->connect();
    // Latest 5 customers
    $stmt1 = $conn->query("SELECT user_id, name FROM users WHERE user_type = 'customer' ORDER BY registered_date DESC LIMIT 5");
    $customers = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    // Latest 5 providers
    $stmt2 = $conn->query("SELECT user_id, name FROM users WHERE user_type = 'provider' ORDER BY registered_date DESC LIMIT 5");
    $providers = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    // Latest 5 service bookings
    $stmt3 = $conn->query("SELECT service_book_id, customer_name, service_date FROM service_booking ORDER BY serbooking_date DESC LIMIT 5");
    $serviceBookings = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    // Latest 5 subscription bookings
    $stmt4 = $conn->query("SELECT subbook_id, customer_name, sub_date FROM subscription_booking ORDER BY subbooking_date DESC LIMIT 5");
    $subscriptionBookings = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'status' => 'success',
        'customers' => $customers,
        'providers' => $providers,
        'serviceBookings' => $serviceBookings,
        'subscriptionBookings' => $subscriptionBookings
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
