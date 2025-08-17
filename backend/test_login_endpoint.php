<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

// Handle GET request - show status and create test user if needed
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Check database connection
        $stmt = $pdo->query("SELECT 1");
        
        // Check if students table exists and create if needed
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id VARCHAR(20) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                account_status VARCHAR(20) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS student_logins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id VARCHAR(20) NOT NULL,
                email VARCHAR(255),
                ip_address VARCHAR(45),
                user_agent TEXT,
                login_time DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(20) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                account_status VARCHAR(20) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS student_logins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(20) NOT NULL,
                email VARCHAR(255),
                ip_address VARCHAR(45),
                user_agent TEXT,
                login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // Check if test student exists, create if not
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE student_id = ?");
        $stmt->execute(['C22-0044']);
        $exists = $stmt->fetch()['count'] > 0;
        
        if (!$exists) {
            $password_hash = password_hash('test123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO students (student_id, email, password_hash, account_status) VALUES (?, ?, ?, ?)");
            $stmt->execute(['C22-0044', 'test@example.com', $password_hash, 'active']);
        }
        
        // Get student count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
        $student_count = $stmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Login system ready',
            'database_type' => $driver,
            'student_count' => $student_count,
            'test_credentials' => 'C22-0044 / test123'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database setup failed: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Handle POST request - test login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = strtoupper(trim($_POST['student_id'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    if (!$student_id || !$password) {
        echo json_encode(['success' => false, 'error' => 'Student ID and password required']);
        exit;
    }
    
    try {
        // Fetch student account
        $stmt = $pdo->prepare('SELECT password_hash FROM students WHERE student_id = ?');
        $stmt->execute([$student_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
            exit;
        }
        
        // Get student email for tracking
        $stmt = $pdo->prepare('SELECT email FROM students WHERE student_id = ?');
        $stmt->execute([$student_id]);
        $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $email = $student_data['email'] ?? '';
        
        // Record login event
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'test';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare('INSERT INTO student_logins (student_id, email, ip_address, user_agent) VALUES (?, ?, ?, ?)');
        $stmt->execute([$student_id, $email, $ip_address, $user_agent]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Login successful',
            'student_id' => $student_id,
            'email' => $email
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
}
?>
