<?php
/**
 * Database singleton to provide a single PDO instance across the app
 * Singleton ensures a class has only one instance and provides global access to it.
 */
class Database {
    private static $instance = null;
    private $pdo;

    /**
     * Accept an optional PDO for easier testing. If no PDO provided, build from env or defaults.
     */
    public function __construct(PDO $pdo = null) {
        if ($pdo !== null) {
            $this->pdo = $pdo;
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return;
        }

        // Allow configuring DSN via DB_DSN env var (e.g., sqlite::memory: for tests)
        $dsn = getenv('DB_DSN');
        if ($dsn) {
            try {
                $this->pdo = new PDO($dsn);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return;
            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }
        }

        // Fallback to defaults used by the app (MySQL)
        $host = 'localhost';
        $dbname = 'freelance_db';
        $username = 'root';
        $password = '';

        try {
            $this->pdo = new PDO("mysql:host={$host};dbname={$dbname}", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
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

    // Helper to set a PDO instance for testing
    public static function setInstanceForTesting(PDO $pdo) {
        self::$instance = new Database($pdo);
    }

    // Return PDO connection
    public function getConnection() {
        return $this->pdo;
    }
}
