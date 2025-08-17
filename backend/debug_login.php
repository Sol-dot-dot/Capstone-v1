<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== LOGIN DEBUG ===\n";

try {
    require_once 'config.php';
    echo "✓ Config loaded\n";
    
    // Test database connection
    $stmt = $pdo->query("SELECT 1");
    echo "✓ Database connected\n";
    
    // Check if we're using SQLite or MySQL
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Database type: $driver\n";
    
    // Check tables
    if ($driver === 'sqlite') {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    } else {
        $stmt = $pdo->query("SHOW TABLES");
    }
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    
    // Check if students table exists
    if (in_array('students', $tables)) {
        echo "✓ Students table exists\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
        $count = $stmt->fetch()['count'];
        echo "Student count: $count\n";
        
        if ($count == 0) {
            echo "Creating test student...\n";
            $password_hash = password_hash('test123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO students (student_id, email, password_hash, account_status) VALUES (?, ?, ?, ?)");
            $stmt->execute(['C22-0044', 'test@example.com', $password_hash, 'active']);
            echo "✓ Test student created\n";
        } else {
            $stmt = $pdo->query("SELECT student_id, email, account_status FROM students LIMIT 3");
            $students = $stmt->fetchAll();
            foreach ($students as $student) {
                echo "Student: {$student['student_id']} - {$student['email']} - {$student['account_status']}\n";
            }
        }
    } else {
        echo "✗ Students table missing - need to run database setup\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
