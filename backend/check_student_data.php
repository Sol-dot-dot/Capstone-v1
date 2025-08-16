<?php
require_once 'config.php';

try {
    echo "Checking student data for C22-0044...\n\n";
    
    // Check student_records table
    echo "=== Student Records Table ===\n";
    $stmt = $pdo->prepare("SELECT * FROM student_records WHERE student_id = ?");
    $stmt->execute(['C22-0044']);
    $student_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student_record) {
        echo "✓ Found in student_records:\n";
        print_r($student_record);
    } else {
        echo "❌ Not found in student_records\n";
    }
    
    echo "\n=== Students Table ===\n";
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute(['C22-0044']);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "✓ Found in students:\n";
        print_r($student);
    } else {
        echo "❌ Not found in students\n";
    }
    
    // Check borrowings
    echo "\n=== Borrowings ===\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowings WHERE student_id = ?");
    $stmt->execute(['C22-0044']);
    $borrowings = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Borrowings count: " . $borrowings['count'] . "\n";
    
    // Check bookmarks
    echo "\n=== Bookmarks ===\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookmarks WHERE student_id = ?");
    $stmt->execute(['C22-0044']);
    $bookmarks = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Bookmarks count: " . $bookmarks['count'] . "\n";
    
    // Check notifications
    echo "\n=== Notifications ===\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE student_id = ?");
    $stmt->execute(['C22-0044']);
    $notifications = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Notifications count: " . $notifications['count'] . "\n";
    
    // If no data exists, create it
    if (!$student_record) {
        echo "\n=== Creating Data for C22-0044 ===\n";
        
        // Insert into student_records
        $pdo->exec("
            INSERT IGNORE INTO student_records (student_id, first_name, last_name, course, year_level) 
            VALUES ('C22-0044', 'John', 'Dela Cruz', 'BSIT', 4)
        ");
        
        // Insert into students
        $password_hash = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT IGNORE INTO students (student_id, email, password_hash) 
            VALUES ('C22-0044', 'john.delacruz@my.smciligan.edu.ph', '$password_hash')
        ");
        
        // Add some sample borrowings
        $pdo->exec("
            INSERT IGNORE INTO borrowings (student_id, book_id, due_date, status) VALUES
            ('C22-0044', 1, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'active'),
            ('C22-0044', 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'returned')
        ");
        
        // Add bookmarks
        $pdo->exec("
            INSERT IGNORE INTO bookmarks (student_id, book_id) VALUES
            ('C22-0044', 3),
            ('C22-0044', 4)
        ");
        
        // Add notifications
        $pdo->exec("
            INSERT IGNORE INTO notifications (student_id, title, message, type, is_read) VALUES
            ('C22-0044', 'Welcome!', 'Welcome to the Book Borrowing System!', 'info', FALSE),
            ('C22-0044', 'Book Available', 'A book you bookmarked is now available.', 'info', FALSE)
        ");
        
        echo "✓ Sample data created for C22-0044\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
