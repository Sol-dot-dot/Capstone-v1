<?php
require_once '../config.php';

try {
    echo "Adding cover_image column to books table...\n\n";
    
    // Add cover_image column to books table
    $pdo->exec("ALTER TABLE books ADD COLUMN cover_image VARCHAR(500) DEFAULT NULL");
    echo "✓ Added cover_image column to books table\n";
    
    // Update existing books with sample cover image URLs
    $coverImages = [
        1 => 'https://covers.openlibrary.org/b/isbn/9780743273565-L.jpg', // The Great Gatsby
        2 => 'https://covers.openlibrary.org/b/isbn/9780061120084-L.jpg', // To Kill a Mockingbird
        3 => 'https://covers.openlibrary.org/b/isbn/9780452284234-L.jpg', // 1984
        4 => 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg', // Clean Code
        5 => 'https://covers.openlibrary.org/b/isbn/9780544003415-L.jpg', // The Lord of the Rings
        6 => 'https://covers.openlibrary.org/b/isbn/9780439708180-L.jpg', // Harry Potter
        7 => 'https://covers.openlibrary.org/b/isbn/9780553103540-L.jpg', // A Game of Thrones
        8 => 'https://covers.openlibrary.org/b/isbn/9780316769174-L.jpg', // The Catcher in the Rye
    ];
    
    foreach ($coverImages as $bookId => $imageUrl) {
        $stmt = $pdo->prepare("UPDATE books SET cover_image = ? WHERE id = ?");
        $stmt->execute([$imageUrl, $bookId]);
        echo "✓ Updated book ID $bookId with cover image\n";
    }
    
    // Verify the updates
    echo "\n=== Verifying Cover Images ===\n";
    $stmt = $pdo->query("SELECT id, title, cover_image FROM books WHERE cover_image IS NOT NULL");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($books as $book) {
        echo "Book: {$book['title']} - Cover: {$book['cover_image']}\n";
    }
    
    echo "\n✅ Cover images successfully added to database!\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ cover_image column already exists\n";
        
        // Still update the cover images
        $coverImages = [
            1 => 'https://covers.openlibrary.org/b/isbn/9780743273565-L.jpg',
            2 => 'https://covers.openlibrary.org/b/isbn/9780061120084-L.jpg',
            3 => 'https://covers.openlibrary.org/b/isbn/9780452284234-L.jpg',
            4 => 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg',
            5 => 'https://covers.openlibrary.org/b/isbn/9780544003415-L.jpg',
            6 => 'https://covers.openlibrary.org/b/isbn/9780439708180-L.jpg',
            7 => 'https://covers.openlibrary.org/b/isbn/9780553103540-L.jpg',
            8 => 'https://covers.openlibrary.org/b/isbn/9780316769174-L.jpg',
        ];
        
        foreach ($coverImages as $bookId => $imageUrl) {
            $stmt = $pdo->prepare("UPDATE books SET cover_image = ? WHERE id = ?");
            $stmt->execute([$imageUrl, $bookId]);
            echo "✓ Updated book ID $bookId with cover image\n";
        }
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
