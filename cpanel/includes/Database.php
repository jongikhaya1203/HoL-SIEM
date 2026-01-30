<?php
/**
 * Database Connection Class for CPanel
 * Singleton pattern for database connection management
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $config = [];

    private function __construct() {
        // Load configuration
        $configFile = __DIR__ . '/../../config/database.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            // Default configuration
            $this->config = [
                'host' => 'localhost',
                'port' => '3307',
                'database' => 'network_security_scanner',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            ];
        }

        $this->connect();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['port'] ?? '3306',
                $this->config['database'],
                $this->config['charset'] ?? 'utf8mb4'
            );

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options'] ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            // Connection failed - will be handled by calling code
            $this->connection = null;
        }
    }

    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Check if connected
     */
    public function isConnected() {
        return $this->connection !== null;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
