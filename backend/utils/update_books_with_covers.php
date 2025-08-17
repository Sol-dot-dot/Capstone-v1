<?php
require_once '../config.php';

try {
    echo "Adding cover images to database...\n\n";
    
    // First, add cover_image column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE books ADD COLUMN cover_image VARCHAR(500) DEFAULT NULL");
        echo "✓ Added cover_image column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ cover_image column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Update books with real cover image URLs
    $coverUpdates = [
        ['id' => 1, 'title' => 'The Great Gatsby', 'cover' => 'https://covers.openlibrary.org/b/isbn/9780743273565-L.jpg'],
        ['id' => 2, 'title' => 'To Kill a Mockingbird', 'cover' => 'https://covers.openlibrary.org/b/isbn/9780061120084-L.jpg'],
        ['id' => 3, 'title' => '1984', 'cover' => 'https://covers.openlibrary.org/b/isbn/9780452284234-L.jpg'],
        ['id' => 4, 'title' => 'Clean Code', 'cover' => 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg'],
        ['id' => 5, 'title' => 'The Lord of the Rings', 'cover' => 'https://covers.openlibrary.org/b/isbn/9780544003415-L.jpg'],
        ['id' => 6, 'title' => 'Harry Potter and the Philosopher\'s Stone', 'cover' => 'https://covers.openlibrary.org/b/isbn/9780439708180-L.jpg'],
        ['id' => 7, 'title' => 'A Game of Thrones', 'cover' => 'https://covers.openlibrary.org/b/isbn/9780553103540-L.jpg'],
        ['id' => 8, 'title' => 'The Catcher in the Rye', 'cover' => 'https://covers.openlibrary.org/b/isbn/9780316769174-L.jpg'],
    ];
    
    foreach ($coverUpdates as $book) {
        $stmt = $pdo->prepare("UPDATE books SET cover_image = ? WHERE id = ?");
        $result = $stmt->execute([$book['cover'], $book['id']]);
        if ($result) {
            echo "✓ Updated '{$book['title']}' with cover image\n";
        }
    }
    
    // Verify updates
    echo "\n=== Verification ===\n";
    $stmt = $pdo->query("SELECT id, title, cover_image FROM books WHERE cover_image IS NOT NULL ORDER BY id");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Books with cover images: " . count($books) . "\n";
    foreach ($books as $book) {
        echo "- ID {$book['id']}: {$book['title']}\n";
        echo "  Cover: {$book['cover_image']}\n";
    }
    
    echo "\n✅ Cover images successfully added to database!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
