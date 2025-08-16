<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
    
    // Insert test data
    $pdo->exec("INSERT IGNORE INTO student_records (student_id) VALUES ('C22-0044')");
    
    $password_hash = password_hash('test123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT IGNORE INTO students (student_id, email, password_hash) VALUES 
        ('C22-0044', 'test@example.com', '$password_hash')");
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $student_id = $_POST['student_id'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($student_id === 'C22-0044' && $password === 'test123') {
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Server is running', 'test_login' => 'C22-0044/test123']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
