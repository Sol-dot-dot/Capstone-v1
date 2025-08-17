<?php
require_once 'config.php';

echo "=== FIXING LOGIN ISSUE ===\n\n";

try {
    // Check database type
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Database type: $driver\n";
    
    // Create students table if it doesn't exist
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
    
    echo "✓ Tables created/verified\n";
    
    // Check if test student exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE student_id = ?");
    $stmt->execute(['C22-0044']);
    $exists = $stmt->fetch()['count'] > 0;
    
    if (!$exists) {
        echo "Creating test student account...\n";
        $password_hash = password_hash('test123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO students (student_id, email, password_hash, account_status) VALUES (?, ?, ?, ?)");
        $stmt->execute(['C22-0044', 'test@example.com', $password_hash, 'active']);
        echo "✓ Test student created: C22-0044 / test123\n";
    } else {
        echo "✓ Test student already exists: C22-0044\n";
    }
    
    // Test login functionality
    echo "\nTesting login...\n";
    $stmt = $pdo->prepare('SELECT password_hash FROM students WHERE student_id = ?');
    $stmt->execute(['C22-0044']);
    $user = $stmt->fetch();
    
    if ($user && password_verify('test123', $user['password_hash'])) {
        echo "✓ Login test successful\n";
    } else {
        echo "✗ Login test failed\n";
    }
    
    echo "\n=== LOGIN SYSTEM READY ===\n";
    echo "Use credentials: C22-0044 / test123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
