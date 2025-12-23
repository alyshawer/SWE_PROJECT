<?php
use PHPUnit\Framework\TestCase;

abstract class TestCaseBase extends TestCase
{
    /** @var PDO */
    protected $pdo;

    protected function setUp(): void
    {
        // Set up in-memory SQLite and inject into Database singleton
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Make Database singleton use this PDO
        if (class_exists('Database')) {
            Database::setInstanceForTesting($this->pdo);
        }

        // Create minimal schema needed for tests
        $this->createSchema();
    }

    protected function createSchema()
    {
        // users
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            email TEXT UNIQUE,
            password TEXT,
            type TEXT,
            name TEXT,
            phone TEXT,
            is_deletable INTEGER DEFAULT 1,
            isActive INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // jobs
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT,
            description TEXT,
            budget REAL,
            client_id INTEGER,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // applications
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id INTEGER,
            freelancer_id INTEGER,
            proposal TEXT,
            completion_time TEXT,
            bid_amount REAL,
            status TEXT,
            job_status TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // payments
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            projectId INTEGER,
            clientId INTEGER,
            freelancerId INTEGER,
            amount REAL,
            status TEXT,
            paymentMethod TEXT,
            transactionId TEXT,
            completedAt DATETIME,
            platform_fee REAL,
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // system config (used by payment revenue tracking)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS system_config (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            config_key TEXT UNIQUE,
            config_value TEXT,
            updated_at DATETIME
        )");

        // freelancer_profiles
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS freelancer_profiles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            skills TEXT,
            past_projects TEXT,
            portfolio_link TEXT,
            cv_filename TEXT,
            bio TEXT,
            hourly_rate REAL,
            totalEarned REAL DEFAULT 0,
            completedProjects INTEGER DEFAULT 0,
            availability TEXT DEFAULT 'available',
            updated_at DATETIME
        )");

        // client_profiles (used by register flow)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS client_profiles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER UNIQUE,
            companyName TEXT,
            totalSpent REAL DEFAULT 0,
            postedProjects INTEGER DEFAULT 0,
            updated_at DATETIME
        )");

        // audit logs
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            userId INTEGER,
            action TEXT,
            ipAddress TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    protected function insertUser($username, $email, $password = 'Abcdef12', $type = 'client', $name = 'Test', $phone = null, $is_deletable = 1)
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password, type, name, phone, is_deletable) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$username, $email, $hashed, $type, $name, $phone, $is_deletable]);
        return (int)$this->pdo->lastInsertId();
    }
}
