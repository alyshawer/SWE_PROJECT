<?php
// TEST SUMMARY:
// This file tests db_functions.php helpers:
// - Validation helpers: validateEmail, validatePassword, validateUsername, etc.
// - User helpers: insertUser, checkUser (password verify)
use PHPUnit\Framework\TestCase;

class DbFunctionsTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // create minimal users table
        $this->pdo->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            email TEXT UNIQUE,
            password TEXT,
            type TEXT,
            name TEXT,
            phone TEXT,
            is_deletable INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // inject into Database singleton for functions that use Database::getInstance
        Database::setInstanceForTesting($this->pdo);
    }

    public function testValidationHelpers()
    {
        require_once __DIR__ . '/../app/helpers/db_functions.php';

        $this->assertTrue(validateEmail('test@example.com'));
        $this->assertFalse(validateEmail('not-an-email'));

        $this->assertTrue((bool)validatePassword('Abcdef12'));
        $this->assertFalse((bool)validatePassword('short'));

        $this->assertTrue((bool)validateUsername('user_1'));
        $this->assertFalse((bool)validateUsername('no spaces'));

        $this->assertTrue((bool)validateName("O'Neil"));
        $this->assertFalse((bool)validateName('x'));

        $this->assertTrue((bool)validatePhone('+1 (555) 555-5555'));
        $this->assertFalse((bool)validatePhone('abc'));
    }

    public function testInsertAndCheckUser()
    {
        require_once __DIR__ . '/../app/helpers/db_functions.php';

        $result = insertUser($this->pdo, 'testuser', 'testuser@example.com', 'Abcdef12', 'client', 'Test User', '+1555555');
        $this->assertTrue($result, 'insertUser should return true on success');

        // checkUser should return user array when correct password provided
        $user = checkUser($this->pdo, 'testuser@example.com', 'Abcdef12');
        $this->assertIsArray($user);
        $this->assertEquals('testuser', $user['username']);

        // wrong password
        $no = checkUser($this->pdo, 'testuser@example.com', 'wrongpass');
        $this->assertFalse($no);
    }
}
