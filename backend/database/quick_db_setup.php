<?php
header('Content-Type: text/plain');

// Database connection
$DB_HOST = 'localhost';
$DB_NAME = 'capstone_db';
$DB_USER = 'root';

try {
    echo "Setting up database with sample data...\n";
    
    // Insert sample books if none exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        echo "Inserting sample books...\n";
        $pdo->exec("DROP TABLE IF EXISTS book_authors, books, authors, categories");
    
        $pdo->exec("CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50) DEFAULT 'book'
        )");
    )");

    $pdo->exec("CREATE TABLE authors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL
    )");

    $pdo->exec("CREATE TABLE books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        category_id INT,
        description TEXT,
        publisher VARCHAR(255),
        publication_year INT,
        cover_image VARCHAR(500),
        available_copies INT DEFAULT 1,
        total_copies INT DEFAULT 1,
        rating DECIMAL(3,2) DEFAULT 4.0,
        total_ratings INT DEFAULT 100,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )");

    $pdo->exec("CREATE TABLE book_authors (
        book_id INT,
        author_id INT,
        FOREIGN KEY (book_id) REFERENCES books(id),
        FOREIGN KEY (author_id) REFERENCES authors(id)
    )");

    // Insert categories
    $categories = [
        ['Fiction', 'Fictional stories and novels', 'book'],
        ['Science Fiction', 'Futuristic and scientific fiction', 'rocket'],
        ['Mystery', 'Mystery and detective stories', 'search'],
        ['Romance', 'Love and romantic stories', 'heart'],
        ['Fantasy', 'Fantasy and magical stories', 'magic']
    ];

    foreach ($categories as $cat) {
        $pdo->prepare("INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)")
             ->execute($cat);
    }

    // Insert authors
    $authors = [
        ['J.K. Rowling'], ['Stephen King'], ['Agatha Christie'], ['Isaac Asimov'], ['Jane Austen'],
        ['George Orwell'], ['Harper Lee'], ['F. Scott Fitzgerald'], ['Ernest Hemingway'], ['Mark Twain']
    ];

    foreach ($authors as $author) {
        $pdo->prepare("INSERT INTO authors (name) VALUES (?)")->execute($author);
    }

    // Insert 15 books
    $books = [
        ['Harry Potter and the Philosopher\'s Stone', 5, 'A young wizard discovers his magical heritage', 'Bloomsbury', 1997, 'https://covers.openlibrary.org/b/isbn/9780747532699-L.jpg', 3, 5, 4.8, 1250],
        ['The Shining', 1, 'A family becomes winter caretakers of an isolated hotel', 'Doubleday', 1977, 'https://covers.openlibrary.org/b/isbn/9780385121675-L.jpg', 2, 3, 4.2, 890],
        ['Murder on the Orient Express', 3, 'Detective Poirot solves a murder on a train', 'William Morrow', 1934, 'https://covers.openlibrary.org/b/isbn/9780062693662-L.jpg', 4, 4, 4.5, 1100],
        ['Foundation', 2, 'The collapse and renewal of a galactic empire', 'Spectra', 1951, 'https://covers.openlibrary.org/b/isbn/9780553293357-L.jpg', 2, 3, 4.3, 750],
        ['Pride and Prejudice', 4, 'A romantic novel about manners and marriage', 'Penguin Classics', 1813, 'https://covers.openlibrary.org/b/isbn/9780141439518-L.jpg', 5, 6, 4.6, 2100],
        ['1984', 1, 'A dystopian social science fiction novel', 'Signet Classics', 1949, 'https://covers.openlibrary.org/b/isbn/9780451524935-L.jpg', 3, 4, 4.7, 1800],
        ['To Kill a Mockingbird', 1, 'A story of racial injustice and childhood innocence', 'Harper Perennial', 1960, 'https://covers.openlibrary.org/b/isbn/9780061120084-L.jpg', 4, 5, 4.8, 2500],
        ['The Great Gatsby', 1, 'The Jazz Age and the American Dream', 'Scribner', 1925, 'https://covers.openlibrary.org/b/isbn/9780743273565-L.jpg', 6, 7, 4.4, 1600],
        ['The Old Man and the Sea', 1, 'An aging fisherman\'s struggle with a giant marlin', 'Scribner', 1952, 'https://covers.openlibrary.org/b/isbn/9780684801223-L.jpg', 3, 4, 4.1, 950],
        ['Adventures of Huckleberry Finn', 1, 'A boy\'s journey down the Mississippi River', 'Dover Publications', 1884, 'https://covers.openlibrary.org/b/isbn/9780486280615-L.jpg', 2, 3, 4.2, 1200],
        ['The Da Vinci Code', 3, 'A mystery involving secret societies and religious history', 'Anchor', 2003, 'https://covers.openlibrary.org/b/isbn/9780307474278-L.jpg', 4, 5, 4.1, 2200],
        ['The Alchemist', 1, 'A shepherd\'s journey to find his personal legend', 'HarperOne', 1988, 'https://covers.openlibrary.org/b/isbn/9780061122415-L.jpg', 6, 8, 4.6, 3200],
        ['Dune', 2, 'A desert planet and political intrigue', 'Ace', 1965, 'https://covers.openlibrary.org/b/isbn/9780441172719-L.jpg', 2, 3, 4.4, 1500],
        ['The Hobbit', 5, 'A hobbit\'s unexpected journey', 'Houghton Mifflin Harcourt', 1937, 'https://covers.openlibrary.org/b/isbn/9780547928227-L.jpg', 4, 5, 4.7, 2800],
        ['Fahrenheit 451', 2, 'A dystopian future where books are banned', 'Simon & Schuster', 1953, 'https://covers.openlibrary.org/b/isbn/9781451673319-L.jpg', 3, 4, 4.3, 1300]
    ];

    foreach ($books as $i => $book) {
        $stmt = $pdo->prepare("INSERT INTO books (title, category_id, description, publisher, publication_year, cover_image, available_copies, total_copies, rating, total_ratings) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($book);
        
        // Link to author
        $book_id = $pdo->lastInsertId();
        $author_id = ($i % 10) + 1;
        $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)")
             ->execute([$book_id, $author_id]);
    }

    echo "✅ Database setup complete!\n";
    echo "✅ 15 books added\n";
    echo "✅ 5 categories added\n";
    echo "✅ 10 authors added\n";
    echo "\nBooks should now appear in mobile app!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
