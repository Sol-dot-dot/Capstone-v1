<?php
require_once 'config.php';

try {
    // Create test student account
    $password_hash = password_hash('test123', PASSWORD_BCRYPT);
    
    // Insert or update student account
    $pdo->exec("INSERT INTO students (student_id, email, password_hash) VALUES 
        ('C22-0044', 'test@example.com', '$password_hash')
        ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Test account created',
        'credentials' => 'C22-0044 / test123'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
