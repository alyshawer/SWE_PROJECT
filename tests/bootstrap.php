<?php
// Minimal bootstrap for running tests
// Set up autoload
require __DIR__ . '/../vendor/autoload.php';

// Define APP_PATH used by controllers
if (!defined('APP_PATH')) {
    define('APP_PATH', realpath(__DIR__ . '/../app'));
}

// Ensure sessions don't error in tests
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Ensure project classes are available (project is not PSR-4 autoloaded fully)
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/UserModel.php';
require_once __DIR__ . '/../app/models/FreelancerModel.php';
require_once __DIR__ . '/../app/helpers/db_functions.php';
require_once __DIR__ . '/../app/core/BaseController.php';

// Simple environment for tests
putenv('DB_DSN=sqlite::memory:');

// Create in-memory DB schema for tests if needed
// You can expand this to create required tables for tests

