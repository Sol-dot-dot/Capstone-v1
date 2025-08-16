<?php
require_once 'config.php';

try {
    echo "Testing Profile API with sample student data...\n\n";
    
    // First, ensure we have student data
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL UNIQUE,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            course VARCHAR(100),
            year_level INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            password_hash VARCHAR(255) NOT NULL,
            account_status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookmarks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL,
            book_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_bookmark (student_id, book_id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS borrowings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL,
            book_id INT NOT NULL,
            borrowed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            due_date DATE NOT NULL,
            returned_date TIMESTAMP NULL,
            status ENUM('active', 'returned', 'overdue', 'lost') DEFAULT 'active',
            renewal_count INT DEFAULT 0,
            fine_amount DECIMAL(10,2) DEFAULT 0.00,
            fine_paid DECIMAL(10,2) DEFAULT 0.00
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'warning', 'reminder', 'recommendation') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS book_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL,
            book_id INT NOT NULL,
            rating INT NOT NULL,
            review_text TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert sample data
    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
    
    $pdo->exec("
        INSERT IGNORE INTO student_records (student_id, first_name, last_name, course, year_level) VALUES
        ('2021-001234', 'John', 'Doe', 'Computer Science', 3),
        ('2021-001235', 'Jane', 'Smith', 'Information Technology', 2)
    ");
    
    $pdo->exec("
        INSERT IGNORE INTO students (student_id, email, password_hash) VALUES
        ('2021-001234', 'john.doe@university.edu', '$password_hash'),
        ('2021-001235', 'jane.smith@university.edu', '$password_hash')
    ");
    
    $pdo->exec("
        INSERT IGNORE INTO bookmarks (student_id, book_id) VALUES
        ('2021-001234', 1),
        ('2021-001234', 3)
    ");
    
    $pdo->exec("
        INSERT IGNORE INTO borrowings (student_id, book_id, due_date, status) VALUES
        ('2021-001234', 2, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'active'),
        ('2021-001234', 4, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'returned')
    ");
    
    $pdo->exec("
        INSERT IGNORE INTO notifications (student_id, title, message, type, is_read) VALUES
        ('2021-001234', 'Book Due Soon', 'Your book \"To Kill a Mockingbird\" is due in 3 days.', 'reminder', FALSE),
        ('2021-001234', 'New Recommendation', 'Based on your reading history, you might like \"Animal Farm\".', 'recommendation', FALSE)
    ");
    
    $pdo->exec("
        INSERT IGNORE INTO book_reviews (student_id, book_id, rating, review_text) VALUES
        ('2021-001234', 1, 5, 'Great book!'),
        ('2021-001234', 3, 4, 'Very interesting read.')
    ");
    
    echo "✓ Sample data inserted\n\n";
    
    // Test profile API
    $test_student_id = '2021-001234';
    
    // Test profile endpoint
    echo "=== Testing Profile Endpoint ===\n";
    $stmt = $pdo->prepare("
        SELECT sr.*, s.email, s.created_at as registration_date
        FROM student_records sr
        LEFT JOIN students s ON sr.student_id = s.student_id
        WHERE sr.student_id = ?
    ");
    $stmt->execute([$test_student_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($profile) {
        echo "Profile found:\n";
        echo "- Name: {$profile['first_name']} {$profile['last_name']}\n";
        echo "- Student ID: {$profile['student_id']}\n";
        echo "- Course: {$profile['course']}\n";
        echo "- Year Level: {$profile['year_level']}\n";
        echo "- Email: {$profile['email']}\n\n";
    } else {
        echo "❌ Profile not found\n\n";
    }
    
    // Test stats endpoint
    echo "=== Testing Stats Endpoint ===\n";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_borrowed,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_borrowings,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as total_returned
        FROM borrowings 
        WHERE student_id = ?
    ");
    $stmt->execute([$test_student_id]);
    $borrowing_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_bookmarks FROM bookmarks WHERE student_id = ?");
    $stmt->execute([$test_student_id]);
    $bookmark_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_reviews FROM book_reviews WHERE student_id = ?");
    $stmt->execute([$test_student_id]);
    $review_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Stats:\n";
    echo "- Total Borrowed: {$borrowing_stats['total_borrowed']}\n";
    echo "- Active Borrowings: {$borrowing_stats['active_borrowings']}\n";
    echo "- Total Returned: {$borrowing_stats['total_returned']}\n";
    echo "- Total Bookmarks: {$bookmark_stats['total_bookmarks']}\n";
    echo "- Total Reviews: {$review_stats['total_reviews']}\n\n";
    
    // Test notifications
    echo "=== Testing Notifications ===\n";
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$test_student_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Notifications count: " . count($notifications) . "\n";
    foreach ($notifications as $notification) {
        echo "- {$notification['title']}: {$notification['message']}\n";
    }
    
    echo "\n✅ Profile API test completed successfully!\n";
    echo "Student ID '2021-001234' is ready for testing in mobile app.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
