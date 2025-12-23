<?php
require __DIR__ . '/../app/helpers/db_functions.php';
if (!class_exists('Database')) { require __DIR__ . '/../app/core/Database.php'; }

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Database::setInstanceForTesting($pdo);
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, email TEXT UNIQUE, password TEXT, type TEXT, name TEXT, phone TEXT, is_deletable INTEGER DEFAULT 1, isActive INTEGER DEFAULT 1, created_at DATETIME)');
$stmt = $pdo->prepare('INSERT INTO users (username,email,password,type,name,is_deletable) VALUES (?,?,?,?,?,?)');
$stmt->execute(['todelete','todelete@example.com','hash','client','To Delete',1]);
$id = $pdo->lastInsertId();
var_dump(deleteUser($pdo, $id));
var_dump((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
