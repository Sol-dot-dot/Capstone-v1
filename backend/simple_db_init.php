<?php
require_once 'config.php';

try {
    echo "Initializing database with sample data...\n";
    
    // Check if books exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        echo "Adding sample books...\n";
        
        // Insert sample books directly
        $pdo->exec("
            INSERT INTO books (id, title, description, category_id, total_copies, available_copies, rating, total_ratings, status) VALUES
            (1, 'The Great Gatsby', 'A classic American novel about the Jazz Age', 1, 5, 3, 4.2, 150, 'active'),
            (2, 'To Kill a Mockingbird', 'A gripping tale of racial injustice and childhood innocence', 1, 4, 2, 4.5, 200, 'active'),
            (3, 'Introduction to Algorithms', 'Comprehensive guide to computer algorithms', 3, 3, 1, 4.8, 89, 'active'),
            (4, 'Clean Code', 'A handbook of agile software craftsmanship', 3, 6, 4, 4.6, 120, 'active'),
            (5, 'The Psychology of Programming', 'Understanding how programmers think', 2, 2, 1, 4.1, 45, 'active'),
            (6, 'Digital Marketing Fundamentals', 'Modern marketing in the digital age', 4, 4, 3, 4.0, 78, 'active'),
            (7, '1984', 'Dystopian social science fiction novel', 1, 5, 2, 4.7, 300, 'active'),
            (8, 'Data Structures and Algorithms', 'Essential programming concepts', 3, 3, 2, 4.4, 95, 'active')
        ");
        
        echo "Sample books added successfully!\n";
    } else {
        echo "Books already exist in database ($count books found)\n";
    }
    
    echo "Database setup complete!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
