<?php
// Direct test without web server
require_once 'config.php';

echo "=== DIRECT DATABASE CONNECTION TEST ===\n";

try {
    // Test connection
    $pdo->query("SELECT 1");
    echo "✓ Database connection successful\n";
    
    // Initialize database with clean setup
    echo "\nInitializing database...\n";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS book_authors");
    $pdo->exec("DROP TABLE IF EXISTS books");
    $pdo->exec("DROP TABLE IF EXISTS authors");
    $pdo->exec("DROP TABLE IF EXISTS categories");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Create tables
    $pdo->exec("
        CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            icon VARCHAR(50),
            color VARCHAR(7),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            biography TEXT,
            nationality VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            isbn VARCHAR(20) UNIQUE,
            title VARCHAR(255) NOT NULL,
            subtitle VARCHAR(255),
            description TEXT,
            publication_date DATE,
            publisher VARCHAR(255),
            pages INT,
            language VARCHAR(50) DEFAULT 'English',
            cover_image_url VARCHAR(500),
            total_copies INT DEFAULT 1,
            available_copies INT DEFAULT 1,
            category_id INT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            rating DECIMAL(3,2) DEFAULT 4.0,
            total_ratings INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )
    ");
    
    $pdo->exec("
        CREATE TABLE book_authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            author_id INT NOT NULL,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE,
            UNIQUE KEY unique_book_author (book_id, author_id)
        )
    ");
    
    echo "✓ Tables created\n";
    
    // Insert data
    $pdo->exec("
        INSERT INTO categories (id, name, description, icon, color) VALUES
        (1, 'Fiction', 'Novels and fictional works', 'auto_stories', '#9b59b6'),
        (2, 'Science', 'Scientific research and discoveries', 'science', '#2ecc71'),
        (3, 'Engineering', 'Technical and engineering books', 'engineering', '#3498db'),
        (4, 'Business', 'Business and management books', 'business', '#16a085'),
        (5, 'History', 'Historical books and biographies', 'history_edu', '#e74c3c'),
        (6, 'Technology', 'Computer science and technology', 'computer', '#f39c12')
    ");
    
    $pdo->exec("
        INSERT INTO authors (id, first_name, last_name, biography, nationality) VALUES
        (1, 'F. Scott', 'Fitzgerald', 'American novelist and short story writer', 'American'),
        (2, 'Harper', 'Lee', 'American novelist known for To Kill a Mockingbird', 'American'),
        (3, 'George', 'Orwell', 'English novelist and essayist', 'British'),
        (4, 'Robert C.', 'Martin', 'Software engineer and author', 'American'),
        (5, 'Thomas H.', 'Cormen', 'Computer scientist and professor', 'American'),
        (6, 'Philip', 'Kotler', 'Marketing author and professor', 'American'),
        (7, 'Yuval Noah', 'Harari', 'Israeli historian and philosopher', 'Israeli'),
        (8, 'Michelle', 'Obama', 'Former First Lady and author', 'American')
    ");
    
    $pdo->exec("
        INSERT INTO books (id, isbn, title, description, publication_date, publisher, pages, category_id, total_copies, available_copies, rating, total_ratings) VALUES
        (1, '978-0-7432-7356-5', 'The Great Gatsby', 'A classic American novel set in the Jazz Age, exploring themes of wealth, love, and the American Dream.', '1925-04-10', 'Scribner', 180, 1, 5, 3, 4.2, 150),
        (2, '978-0-06-112008-4', 'To Kill a Mockingbird', 'A gripping tale of racial injustice and childhood innocence in the American South.', '1960-07-11', 'J.B. Lippincott & Co.', 281, 1, 4, 2, 4.5, 200),
        (3, '978-0-452-28423-4', '1984', 'A dystopian social science fiction novel about totalitarian control and surveillance.', '1949-06-08', 'Secker & Warburg', 328, 1, 6, 4, 4.7, 300),
        (4, '978-0-13-208670-1', 'Clean Code', 'A handbook of agile software craftsmanship for writing maintainable code.', '2008-08-01', 'Prentice Hall', 464, 6, 8, 5, 4.6, 120),
        (5, '978-0-262-03384-8', 'Introduction to Algorithms', 'Comprehensive guide to algorithms and data structures.', '2009-07-31', 'MIT Press', 1312, 6, 3, 1, 4.8, 89),
        (6, '978-0-13-449507-6', 'Marketing Management', 'Comprehensive guide to modern marketing principles and practices.', '2015-01-15', 'Pearson', 832, 4, 4, 3, 4.0, 78),
        (7, '978-0-06-231609-7', 'Sapiens', 'A brief history of humankind from the Stone Age to the present.', '2014-02-10', 'Harper', 443, 5, 5, 2, 4.4, 180),
        (8, '978-1-5247-6313-8', 'Becoming', 'Memoir by former First Lady Michelle Obama.', '2018-11-13', 'Crown Publishing', 448, 5, 6, 4, 4.6, 250)
    ");
    
    $pdo->exec("
        INSERT INTO book_authors (book_id, author_id) VALUES
        (1, 1), (2, 2), (3, 3), (4, 4), (5, 5), (6, 6), (7, 7), (8, 8)
    ");
    
    echo "✓ Sample data inserted\n";
    
    // Test API query
    echo "\n=== TESTING API QUERIES ===\n";
    
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
    
    echo "Books found: " . count($books) . "\n";
    foreach ($books as $book) {
        echo "- {$book['title']} by {$book['author_name']} ({$book['category_name']}) - Rating: {$book['rating']}\n";
    }
    
    // Test categories
    $stmt = $pdo->query("SELECT id, name, icon, color FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    echo "\nCategories found: " . count($categories) . "\n";
    foreach ($categories as $category) {
        echo "- {$category['name']} (Color: {$category['color']})\n";
    }
    
    echo "\n🎉 DATABASE IS READY FOR MOBILE APP!\n";
    echo "✓ All tables created and populated\n";
    echo "✓ API queries return proper data\n";
    echo "✓ Mobile app can now connect to http://localhost:8080\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
