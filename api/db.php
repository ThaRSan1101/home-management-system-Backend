<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'ServiceHub';

function getDbConnection() {
    global $host, $user, $pass, $db;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("DB Connection failed: " . $e->getMessage());
    }
}
?>
