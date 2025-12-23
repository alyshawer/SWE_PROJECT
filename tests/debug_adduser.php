<?php
require __DIR__ . '/../app/helpers/db_functions.php';
if (!defined('APP_PATH')) define('APP_PATH', realpath(__DIR__ . '/../app'));
if (!class_exists('Database')) require __DIR__ . '/../app/core/Database.php';
if (!class_exists('BaseController')) require __DIR__ . '/../app/core/BaseController.php';
if (!class_exists('AdminController')) require __DIR__ . '/../app/controllers/AdminController.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Database::setInstanceForTesting($pdo);
$pdo->exec("CREATE TABLE users (
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
// insert admin
$hashed = password_hash('Abcdef12', PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (username,email,password,type,name,is_deletable) VALUES (?,?,?,?,?,?)');
$stmt->execute(['adminuser','admin@example.com',$hashed,'admin','Admin',0]);
$adminId = $pdo->lastInsertId();

// simulate session and POST
if (session_status() === PHP_SESSION_NONE) @session_start();
$_SESSION['user_id'] = $adminId; $_SESSION['username']='adminuser'; $_SESSION['type']='admin';

$_POST = ['username'=>'newuser','email'=>'newuser@example.com','password'=>'Abcdef12','type'=>'client','name'=>'New User','phone'=>'+1234567890'];

class TestableAdminController extends AdminController { public $lastRedirect = null; protected function redirect($url){$this->lastRedirect=$url;} }
$controller = new TestableAdminController($pdo);
$controller->addUser();

echo "Last redirect: " . ($controller->lastRedirect ?? 'none') . PHP_EOL;
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute(['newuser']);
$user = $stmt->fetch();
var_dump($user);
