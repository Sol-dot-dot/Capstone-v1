<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    // Test database connection
    $stmt = $pdo->query("SELECT 1");
    
    // Check if students table exists
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    if ($driver === 'sqlite') {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='students'");
    } else {
        $stmt = $pdo->query("SHOW TABLES LIKE 'students'");
    }
    
    $students_table_exists = $stmt->rowCount() > 0;
    
    if ($students_table_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
        $student_count = $stmt->fetch()['count'];
    } else {
        $student_count = 0;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Backend server is running',
        'database_type' => $driver,
        'students_table_exists' => $students_table_exists,
        'student_count' => $student_count,
        'endpoints' => [
            'login' => '/auth/login.php',
            'register' => '/auth/register_student.php',
            'books' => '/api/api_books.php',
            'test_login' => '/test_login_endpoint.php'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
