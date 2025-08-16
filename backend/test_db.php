<?php
// Simple database test script
require_once 'config.php';

try {
    echo "Database connection: SUCCESS\n";
    
    // Test if tables exist
    $tables = ['student_records', 'books', 'categories', 'authors', 'borrowings'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "$table: {$result['count']} records\n";
    }
    
    echo "Database test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Database test failed: " . $e->getMessage() . "\n";
}
?>
