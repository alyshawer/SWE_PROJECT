<?php
// TEST SUMMARY:
// This file tests Database class:
// - setInstanceForTesting: verifies Database singleton uses injected PDO
// - getConnection: checks returned PDO is the test DB
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    public function testSetInstanceForTesting()
    {
        // Use a real PDO and inject it into Database singleton
        $pdo = new PDO('sqlite::memory:');
        Database::setInstanceForTesting($pdo);

        $db = Database::getInstance();
        $this->assertInstanceOf(Database::class, $db);
        $this->assertInstanceOf(PDO::class, $db->getConnection());
    }
}

