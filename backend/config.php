<?php
$DB_HOST = 'localhost';
$DB_NAME = 'capstone_db';
$DB_USER = 'root';
$DB_PASS = '';

try {
    // First try to create database if it doesn't exist
    $temp_pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS);
    $temp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS $DB_NAME");
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Fallback to SQLite for development
    try {
        $pdo = new PDO("sqlite:capstone.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        die("Connection failed: " . $e2->getMessage());
    }
}
?>
