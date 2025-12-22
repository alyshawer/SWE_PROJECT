<?php
/**
 * Database singleton to provide a single PDO instance across the app
 * Singleton ensures a class has only one instance and provides global access to it.
 */
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Load config from config file or environment - match existing defaults
        $host = 'localhost';
        $dbname = 'freelance_db';
        $username = 'root';
        $password = '';

        try {
            $this->pdo = new PDO("mysql:host={$host};dbname={$dbname}", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // In CLI/dev show error; in production this should be handled more gracefully
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization (would break singleton pattern)
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    // Get singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Return PDO connection
    public function getConnection() {
        return $this->pdo;
    }
}
