<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'profile':
                        getProfile($_GET['student_id'] ?? '');
                        break;
                    case 'stats':
                        getStats($_GET['student_id'] ?? '');
                        break;
                    case 'notifications':
                        getNotifications($_GET['student_id'] ?? '');
                        break;
                    default:
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'Invalid action']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action required']);
            }
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getProfile($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // First ensure tables exist and create sample data if needed
    createTablesAndData($student_id);
    
    // Get student profile with proper join
    $stmt = $pdo->prepare("
        SELECT 
            sr.student_id,
            sr.first_name,
            sr.last_name,
            sr.course,
            sr.year_level,
            s.email,
            s.created_at as registration_date
        FROM student_records sr
        LEFT JOIN students s ON sr.student_id = s.student_id
        WHERE sr.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        // Create the student record if it doesn't exist
        $stmt = $pdo->prepare("
            INSERT INTO student_records (student_id, first_name, last_name, course, year_level) 
            VALUES (?, 'John', 'Dela Cruz', 'BSIT', 4)
        ");
        $stmt->execute([$student_id]);
        
        $password_hash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO students (student_id, email, password_hash) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$student_id, $student_id . '@my.smciligan.edu.ph', $password_hash]);
        
        // Retry getting the profile
        $stmt = $pdo->prepare("
            SELECT 
                sr.student_id,
                sr.first_name,
                sr.last_name,
                sr.course,
                sr.year_level,
                s.email,
                s.created_at as registration_date
            FROM student_records sr
            LEFT JOIN students s ON sr.student_id = s.student_id
            WHERE sr.student_id = ?
        ");
        $stmt->execute([$student_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['success' => true, 'profile' => $profile]);
}

function getStats($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Ensure tables exist
    createTablesAndData($student_id);
    
    // Get borrowing statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_borrowed,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_borrowings,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as total_returned
        FROM borrowings 
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $borrowing_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get bookmarks count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_bookmarks FROM bookmarks WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $bookmark_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get reviews count (create table if not exists)
    try {
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
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_reviews FROM book_reviews WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $review_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $review_stats = ['total_reviews' => 0];
    }
    
    $stats = array_merge($borrowing_stats, $bookmark_stats, $review_stats);
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

function getNotifications($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Ensure tables exist
    createTablesAndData($student_id);
    
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE student_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$student_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
}

function createTablesAndData($student_id) {
    global $pdo;
    
    try {
        // Create tables if they don't exist
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
        
        // Check if student has any data, if not create sample data
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_records WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists['count'] == 0) {
            // Insert sample data for this student
            $pdo->exec("
                INSERT IGNORE INTO student_records (student_id, first_name, last_name, course, year_level) 
                VALUES ('$student_id', 'John', 'Dela Cruz', 'BSIT', 4)
            ");
            
            $password_hash = password_hash('password123', PASSWORD_DEFAULT);
            $pdo->exec("
                INSERT IGNORE INTO students (student_id, email, password_hash) 
                VALUES ('$student_id', '$student_id@my.smciligan.edu.ph', '$password_hash')
            ");
            
            // Add sample borrowings
            $pdo->exec("
                INSERT IGNORE INTO borrowings (student_id, book_id, due_date, status) VALUES
                ('$student_id', 1, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'active'),
                ('$student_id', 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'returned'),
                ('$student_id', 3, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'returned')
            ");
            
            // Add bookmarks
            $pdo->exec("
                INSERT IGNORE INTO bookmarks (student_id, book_id) VALUES
                ('$student_id', 3),
                ('$student_id', 4),
                ('$student_id', 1)
            ");
            
            // Add notifications
            $pdo->exec("
                INSERT IGNORE INTO notifications (student_id, title, message, type, is_read) VALUES
                ('$student_id', 'Welcome!', 'Welcome to the Book Borrowing System!', 'info', FALSE),
                ('$student_id', 'Book Due Soon', 'Your borrowed book is due in 3 days.', 'reminder', FALSE),
                ('$student_id', 'New Recommendation', 'Check out our new arrivals in your favorite category.', 'recommendation', TRUE)
            ");
        }
        
    } catch (Exception $e) {
        // Ignore errors for now
    }
}
?>
