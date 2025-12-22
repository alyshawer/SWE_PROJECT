<?php
// Bootstrap Database singleton and provide $pdo for legacy code
require_once __DIR__ . '/../core/Database.php';

try {
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Load all database functions
require_once __DIR__ . '/../helpers/db_functions.php';
?>

