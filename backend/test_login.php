<?php
require_once 'config.php';

echo "=== LOGIN SYSTEM DIAGNOSTIC ===\n\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $stmt = $pdo->query("SELECT 1");
    echo "✓ Database connection successful\n\n";
    
    // Check if students table exists
    echo "2. Checking students table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'students'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Students table exists\n";
        
        // Check student records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
        $count = $stmt->fetch()['count'];
        echo "✓ Found $count student records\n";
        
        if ($count > 0) {
            // Show sample students
            $stmt = $pdo->query("SELECT student_id, email, account_status FROM students LIMIT 3");
            $students = $stmt->fetchAll();
            echo "Sample students:\n";
            foreach ($students as $student) {
                echo "  - {$student['student_id']} ({$student['email']}) - {$student['account_status']}\n";
            }
        }
    } else {
        echo "✗ Students table does not exist\n";
    }
    
    echo "\n3. Checking student_logins table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'student_logins'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Student_logins table exists\n";
    } else {
        echo "✗ Student_logins table does not exist\n";
    }
    
    // Test login with known credentials
    echo "\n4. Testing login functionality...\n";
    $test_student = 'C22-0044';
    $test_password = 'test123';
    
    $stmt = $pdo->prepare('SELECT password_hash FROM students WHERE student_id = ?');
    $stmt->execute([$test_student]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ Found test student: $test_student\n";
        if (password_verify($test_password, $user['password_hash'])) {
            echo "✓ Password verification successful\n";
        } else {
            echo "✗ Password verification failed\n";
            echo "Stored hash: {$user['password_hash']}\n";
        }
    } else {
        echo "✗ Test student not found: $test_student\n";
        echo "Creating test student...\n";
        
        $password_hash = password_hash($test_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO students (student_id, email, password_hash, account_status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$test_student, 'test@example.com', $password_hash, 'active']);
        echo "✓ Test student created\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
?>
