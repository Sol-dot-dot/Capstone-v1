<?php
require_once 'config.php';

try {
    echo "=== DATABASE CONNECTION TEST ===\n";
    echo "Testing database connection...\n";
    
    // Test connection
    $stmt = $pdo->query("SELECT 1");
    echo "✓ Database connection successful\n\n";
    
    // Check tables exist
    echo "=== CHECKING TABLES ===\n";
    $tables = ['categories', 'authors', 'books', 'book_authors'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' missing\n";
        }
    }
    
    echo "\n=== CHECKING DATA ===\n";
    
    // Check categories
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $count = $stmt->fetch()['count'];
    echo "Categories: $count records\n";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, name, color FROM categories LIMIT 3");
        while ($row = $stmt->fetch()) {
            echo "  - {$row['name']} (ID: {$row['id']}, Color: {$row['color']})\n";
        }
    }
    
    // Check authors
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM authors");
    $count = $stmt->fetch()['count'];
    echo "\nAuthors: $count records\n";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, first_name, last_name FROM authors LIMIT 3");
        while ($row = $stmt->fetch()) {
            echo "  - {$row['first_name']} {$row['last_name']} (ID: {$row['id']})\n";
        }
    }
    
    // Check books
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    $count = $stmt->fetch()['count'];
    echo "\nBooks: $count records\n";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, title, category_id, available_copies, total_copies FROM books LIMIT 3");
        while ($row = $stmt->fetch()) {
            echo "  - {$row['title']} (ID: {$row['id']}, Category: {$row['category_id']}, Available: {$row['available_copies']}/{$row['total_copies']})\n";
        }
    }
    
    // Check book_authors relationships
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM book_authors");
    $count = $stmt->fetch()['count'];
    echo "\nBook-Author relationships: $count records\n";
    
    echo "\n=== TESTING API QUERIES ===\n";
    
    // Test the exact query used by api_books.php for 'all' action
    $query = "
        SELECT b.*, c.name as category_name, c.color as category_color,
               CONCAT(a.first_name, ' ', a.last_name) as author_name,
               COALESCE(b.rating, 4.0) as rating,
               COALESCE(b.total_ratings, 0) as total_ratings
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors ba ON b.id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.id
        WHERE b.status = 'active'
        ORDER BY b.id DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->query($query);
    $books = $stmt->fetchAll();
    
    echo "API Query Result: " . count($books) . " books found\n";
    foreach ($books as $book) {
        echo "  - {$book['title']} by {$book['author_name']} ({$book['category_name']}) - Rating: {$book['rating']}\n";
    }
    
    // Test categories query
    echo "\n=== TESTING CATEGORIES API ===\n";
    $stmt = $pdo->query("SELECT id, name, icon, color FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    echo "Categories API Result: " . count($categories) . " categories found\n";
    foreach ($categories as $category) {
        echo "  - {$category['name']} (Color: {$category['color']}, Icon: {$category['icon']})\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    echo "Database is properly set up and ready for the mobile app!\n";
    
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
}
?>
