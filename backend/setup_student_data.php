<?php
require_once 'config.php';

try {
    echo "Setting up student data for profile testing...\n";
    
    // Create student tables if they don't exist
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
            fine_amount DECIMAL(10,2) DEFAULT 0.00
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
    
    // Insert sample student records
    $pdo->exec("
        INSERT IGNORE INTO student_records (student_id, first_name, last_name, course, year_level) VALUES
        ('2021-001234', 'John', 'Doe', 'Computer Science', 3),
        ('2021-001235', 'Jane', 'Smith', 'Information Technology', 2),
        ('2021-001236', 'Mike', 'Johnson', 'Software Engineering', 4)
    ");
    
    // Insert sample students (registered users)
    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
    $pdo->exec("
        INSERT IGNORE INTO students (student_id, email, first_name, last_name, password_hash) VALUES
        ('2021-001234', 'john.doe@university.edu', 'John', 'Doe', '$password_hash'),
        ('2021-001235', 'jane.smith@university.edu', 'Jane', 'Smith', '$password_hash'),
        ('2021-001236', 'mike.johnson@university.edu', 'Mike', 'Johnson', '$password_hash')
    ");
    
    // Insert sample bookmarks
    $pdo->exec("
        INSERT IGNORE INTO bookmarks (student_id, book_id) VALUES
        ('2021-001234', 1),
        ('2021-001234', 3),
        ('2021-001235', 2),
        ('2021-001235', 4)
    ");
    
    // Insert sample borrowings
    $pdo->exec("
        INSERT IGNORE INTO borrowings (student_id, book_id, due_date, status) VALUES
        ('2021-001234', 2, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'active'),
        ('2021-001235', 1, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'active'),
        ('2021-001234', 4, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'returned')
    ");
    
    // Insert sample notifications
    $pdo->exec("
        INSERT IGNORE INTO notifications (student_id, title, message, type, is_read) VALUES
        ('2021-001234', 'Book Due Soon', 'Your book \"To Kill a Mockingbird\" is due in 3 days.', 'reminder', FALSE),
        ('2021-001234', 'New Recommendation', 'Based on your reading history, you might like \"Animal Farm\".', 'recommendation', FALSE),
        ('2021-001235', 'Welcome!', 'Welcome to the Book Borrowing System!', 'info', TRUE)
    ");
    
    echo "✓ Student tables created\n";
    echo "✓ Sample student data inserted\n";
    echo "✓ Profile data ready for testing\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
