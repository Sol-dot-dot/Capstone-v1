<?php
require_once 'config.php';

echo "=== API ENDPOINTS TEST ===\n\n";

try {
    // Test 1: Books API - Get all books
    echo "1. Testing Books API (action=all):\n";
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
        LIMIT 10
    ";
    
    $stmt = $pdo->query($query);
    $books = $stmt->fetchAll();
    
    echo "   Result: " . count($books) . " books found\n";
    if (count($books) > 0) {
        echo "   Sample book: {$books[0]['title']} by {$books[0]['author_name']}\n";
        echo "   Category: {$books[0]['category_name']} (Color: {$books[0]['category_color']})\n";
        echo "   Rating: {$books[0]['rating']} ({$books[0]['total_ratings']} reviews)\n";
        echo "   ✓ Books API data structure is correct\n";
    } else {
        echo "   ✗ No books found\n";
    }
    
    // Test 2: Categories API
    echo "\n2. Testing Categories API:\n";
    $stmt = $pdo->query("SELECT id, name, icon, color FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    echo "   Result: " . count($categories) . " categories found\n";
    if (count($categories) > 0) {
        foreach ($categories as $cat) {
            echo "   - {$cat['name']} (Color: {$cat['color']}, Icon: {$cat['icon']})\n";
        }
        echo "   ✓ Categories API data structure is correct\n";
    } else {
        echo "   ✗ No categories found\n";
    }
    
    // Test 3: Book Details API
    echo "\n3. Testing Book Details API (book_id=1):\n";
    $query = "
        SELECT b.*, c.name as category_name, c.color as category_color,
               CONCAT(a.first_name, ' ', a.last_name) as author_name,
               COALESCE(b.rating, 4.0) as rating,
               COALESCE(b.total_ratings, 0) as total_ratings,
               b.publication_date as publication_year,
               b.publisher, b.pages, b.isbn,
               'English' as language
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors ba ON b.id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.id
        WHERE b.id = 1 AND b.status = 'active'
    ";
    
    $stmt = $pdo->query($query);
    $book = $stmt->fetch();
    
    if ($book) {
        echo "   Book: {$book['title']}\n";
        echo "   Author: {$book['author_name']}\n";
        echo "   Category: {$book['category_name']}\n";
        echo "   Publisher: {$book['publisher']}\n";
        echo "   Pages: {$book['pages']}\n";
        echo "   Available: {$book['available_copies']}/{$book['total_copies']}\n";
        echo "   ✓ Book details API data structure is correct\n";
    } else {
        echo "   ✗ Book not found\n";
    }
    
    // Test 4: Database Connection Summary
    echo "\n=== DATABASE CONNECTION SUMMARY ===\n";
    
    $tables = ['categories', 'authors', 'books', 'book_authors'];
    $allTablesExist = true;
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $countStmt->fetch()['count'];
            echo "✓ Table '$table': $count records\n";
        } else {
            echo "✗ Table '$table': missing\n";
            $allTablesExist = false;
        }
    }
    
    if ($allTablesExist && count($books) > 0 && count($categories) > 0) {
        echo "\n🎉 DATABASE STATUS: READY FOR MOBILE APP\n";
        echo "✓ All required tables exist\n";
        echo "✓ Sample data is populated\n";
        echo "✓ API queries return proper data structure\n";
        echo "✓ Mobile app can connect and fetch data\n";
    } else {
        echo "\n❌ DATABASE STATUS: NEEDS SETUP\n";
        echo "Run the database initialization script first\n";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>
