<?php
require_once '../config.php';

try {
    echo "Setting up borrowing system database...\n";
    
    // Drop existing tables to recreate with new schema
    $pdo->exec("DROP TABLE IF EXISTS borrowings");
    $pdo->exec("DROP TABLE IF EXISTS student_borrowing_limits");
    $pdo->exec("DROP TABLE IF EXISTS fines");
    
    // Create borrowings table with new schema
    $pdo->exec("
        CREATE TABLE borrowings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id VARCHAR(20) NOT NULL,
            book_id INT NOT NULL,
            book_code VARCHAR(50) NOT NULL,
            borrowed_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            due_date DATETIME NOT NULL,
            returned_date DATETIME NULL,
            status ENUM('active', 'returned', 'overdue') NOT NULL DEFAULT 'active',
            semester VARCHAR(20) NOT NULL,
            academic_year VARCHAR(10) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_student_id (student_id),
            INDEX idx_book_id (book_id),
            INDEX idx_book_code (book_code),
            INDEX idx_status (status),
            INDEX idx_semester (semester, academic_year),
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        )
    ");
    
    // Create student borrowing limits table
    $pdo->exec("
        CREATE TABLE student_borrowing_limits (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id VARCHAR(20) NOT NULL,
            semester VARCHAR(20) NOT NULL,
            academic_year VARCHAR(10) NOT NULL,
            books_borrowed_total INT NOT NULL DEFAULT 0,
            books_borrowed_current INT NOT NULL DEFAULT 0,
            max_books_per_semester INT NOT NULL DEFAULT 20,
            max_books_concurrent INT NOT NULL DEFAULT 3,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_semester (student_id, semester, academic_year),
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
        )
    ");
    
    // Create fines table
    $pdo->exec("
        CREATE TABLE fines (
            id INT PRIMARY KEY AUTO_INCREMENT,
            borrowing_id INT NOT NULL,
            student_id VARCHAR(20) NOT NULL,
            fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            days_overdue INT NOT NULL DEFAULT 0,
            daily_fine_rate DECIMAL(5,2) NOT NULL DEFAULT 3.00,
            status ENUM('pending', 'paid', 'waived') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (borrowing_id) REFERENCES borrowings(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
        )
    ");
    
    // Update books table to include book codes
    $pdo->exec("
        ALTER TABLE books 
        ADD COLUMN book_code VARCHAR(50) UNIQUE NULL AFTER isbn,
        ADD COLUMN location VARCHAR(100) NULL AFTER book_code,
        ADD COLUMN loan_duration_days INT NOT NULL DEFAULT 3 AFTER location
    ");
    
    // Add sample book codes to existing books
    $stmt = $pdo->query("SELECT id FROM books ORDER BY id");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($books as $index => $book) {
        $bookCode = sprintf("BK-%03d-%03d", ($index + 1), rand(100, 999));
        $location = "Section " . chr(65 + ($index % 6)); // A, B, C, D, E, F
        
        $updateStmt = $pdo->prepare("
            UPDATE books 
            SET book_code = ?, location = ?, loan_duration_days = 3 
            WHERE id = ?
        ");
        $updateStmt->execute([$bookCode, $location, $book['id']]);
    }
    
    // Initialize borrowing limits for existing students
    $currentSemester = "Fall";
    $currentYear = "2024";
    
    $stmt = $pdo->query("SELECT student_id FROM students");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($students as $student) {
        $insertStmt = $pdo->prepare("
            INSERT INTO student_borrowing_limits 
            (student_id, semester, academic_year, books_borrowed_total, books_borrowed_current) 
            VALUES (?, ?, ?, 0, 0)
            ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
        ");
        $insertStmt->execute([$student['student_id'], $currentSemester, $currentYear]);
    }
    
    // Create borrowing system configuration table
    $pdo->exec("
        CREATE TABLE borrowing_config (
            id INT PRIMARY KEY AUTO_INCREMENT,
            config_key VARCHAR(50) UNIQUE NOT NULL,
            config_value VARCHAR(255) NOT NULL,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default configuration
    $configs = [
        ['max_books_per_semester', '20', 'Maximum books a student can borrow per semester'],
        ['max_books_concurrent', '3', 'Maximum books a student can have at one time'],
        ['loan_duration_days', '3', 'Default loan duration in days'],
        ['daily_fine_rate', '3.00', 'Fine amount per day for overdue books'],
        ['current_semester', 'Fall', 'Current academic semester'],
        ['current_academic_year', '2024', 'Current academic year']
    ];
    
    foreach ($configs as $config) {
        $stmt = $pdo->prepare("
            INSERT INTO borrowing_config (config_key, config_value, description) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
        ");
        $stmt->execute($config);
    }
    
    echo "✅ Borrowing system database setup completed successfully!\n";
    echo "📚 Book codes generated for all existing books\n";
    echo "👥 Borrowing limits initialized for all students\n";
    echo "⚙️ System configuration set with school rules\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up borrowing system: " . $e->getMessage() . "\n";
}
?>
