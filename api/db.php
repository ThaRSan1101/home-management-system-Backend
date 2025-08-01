<?php
/**
 * db.php
 *
 * Centralized database connection class for the Home Management System backend.
 *
 * - Encapsulates PDO connection logic to MySQL database.
 * - Used by backend classes and API endpoints for secure, consistent DB access.
 *
 * SECURITY NOTE: In production, credentials should be stored securely (e.g., environment variables), not hardcoded.
 *
 * Used by: All backend PHP classes requiring DB access (User, Admin, Provider, Customer, etc.)
 */

/**
 * Class DBConnector
 *
 * Handles creation of a PDO connection to the MySQL database.
 *
 * Properties:
 *   - $host: Database server hostname
 *   - $db: Database name
 *   - $user: Database username
 *   - $pass: Database password
 *   - $charset: Character set for connection
 *   - $pdo: PDO instance (private)
 */
class DBConnector {
    private $host = 'localhost';
    private $db = 'ServiceHub';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';
    private $pdo;

    /**
     * Establishes and returns a PDO connection to the database.
     *
     * @return PDO Active PDO connection for DB operations
     * @throws PDOException If connection fails
     *
     * Connection options:
     *   - Throws exceptions on DB errors
     *   - Uses associative array fetch mode
     *   - Disables emulated prepares for security
     */
    public function connect() {
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }

        return $this->pdo;
    }
}
?>
