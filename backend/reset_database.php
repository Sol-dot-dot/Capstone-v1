<?php
// Database reset script for clean migration
require_once 'config.php';

try {
    echo "Starting database reset...\n";
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop all existing tables
    $tables = [
        'chat_messages', 'chat_conversations', 'notifications', 'recommendations',
        'reading_preferences', 'bookmarks', 'book_reviews', 'borrowings',
        'book_authors', 'books', 'authors', 'categories', 'admin_users',
        'login_history', 'student_logins', 'email_verification_codes',
        'students', 'student_records', 'users'
    ];
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Dropped table: $table\n";
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Database reset complete!\n";
    echo "Now run any PHP script to recreate tables with new schema.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
