<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$DB_HOST = 'localhost';
$DB_NAME = 'capstone_db';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL UNIQUE,
        first_name VARCHAR(100) DEFAULT 'Test',
        last_name VARCHAR(100) DEFAULT 'User',
        course VARCHAR(100) DEFAULT 'BSIT',
        year_level INT DEFAULT 3
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_logins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        email VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert test data
    $pdo->exec("INSERT IGNORE INTO student_records (student_id, first_name, last_name, course, year_level) VALUES 
        ('C22-0044', 'Rhodcelister', 'Duallo', 'BSIT', 3)");
    
    // Create test account with password 'test123'
    $password_hash = password_hash('test123', PASSWORD_BCRYPT);
    $pdo->exec("DELETE FROM students WHERE student_id = 'C22-0044'");
    $pdo->exec("INSERT INTO students (student_id, email, password_hash) VALUES 
        ('C22-0044', 'rhodcelisterduallo.sol@my.smciligan.edu.ph', '$password_hash')");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Database and test account created successfully',
        'login_credentials' => 'C22-0044 / test123',
        'tables_created' => ['student_records', 'students', 'student_logins']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
