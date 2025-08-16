<?php
// Quick database and login setup
$DB_HOST = 'localhost';
$DB_NAME = 'capstone_db';
$DB_USER = 'root';
$DB_PASS = '';

echo "Setting up database and login system...\n";

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create essential tables for login
    $pdo->exec("DROP TABLE IF EXISTS students");
    $pdo->exec("DROP TABLE IF EXISTS student_records");
    
    $pdo->exec("
        CREATE TABLE student_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL UNIQUE,
            first_name VARCHAR(100) DEFAULT '',
            last_name VARCHAR(100) DEFAULT '',
            course VARCHAR(100) DEFAULT '',
            year_level INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert test student
    $pdo->exec("INSERT INTO student_records (student_id, first_name, last_name, course, year_level) VALUES 
        ('C22-0044', 'Rhodcelister', 'Duallo', 'BSIT', 3)");
    
    // Create a test account for login (password: test123)
    $password_hash = password_hash('test123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO students (student_id, email, password_hash) VALUES 
        ('C22-0044', 'rhodcelisterduallo.sol@my.smciligan.edu.ph', '$password_hash')");
    
    echo "✅ Database setup complete!\n";
    echo "📱 Test login: C22-0044 / test123\n";
    echo "📧 Email: rhodcelisterduallo.sol@my.smciligan.edu.ph\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
