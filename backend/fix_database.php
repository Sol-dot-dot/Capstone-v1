<?php
require_once 'config.php';

try {
    echo "Fixing database schema and adding sample data...\n";
    
    // Drop and recreate tables to ensure clean schema
    $pdo->exec("DROP TABLE IF EXISTS book_authors");
    $pdo->exec("DROP TABLE IF EXISTS books");
    $pdo->exec("DROP TABLE IF EXISTS authors");
    $pdo->exec("DROP TABLE IF EXISTS categories");
    
    // Create categories table
    $pdo->exec("
        CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50) DEFAULT 'book',
            color VARCHAR(7) DEFAULT '#3498db'
        )
    ");
    
    // Create authors table
    $pdo->exec("
        CREATE TABLE authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            biography TEXT,
            nationality VARCHAR(100)
        )
    ");
    
    // Create books table
    $pdo->exec("
        CREATE TABLE books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            isbn VARCHAR(20),
            title VARCHAR(255) NOT NULL,
            subtitle VARCHAR(255),
            description TEXT,
            publication_date DATE,
            publisher VARCHAR(255),
            pages INT,
            category_id INT,
            total_copies INT DEFAULT 1,
            available_copies INT DEFAULT 1,
            rating DECIMAL(3,2) DEFAULT 4.0,
            total_ratings INT DEFAULT 0,
            status ENUM('active','inactive') DEFAULT 'active',
            cover_image VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    ");
    
    // Create book_authors junction table
    $pdo->exec("
        CREATE TABLE book_authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            author_id INT NOT NULL,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
        )
    ");
    
    // Insert categories
    $pdo->exec("
        INSERT INTO categories (id, name, description, icon, color) VALUES
        (1, 'Fiction', 'Novels and fictional works', 'auto_stories', '#9b59b6'),
        (2, 'Science', 'Scientific research and discoveries', 'science', '#2ecc71'),
        (3, 'Engineering', 'Technical and engineering books', 'engineering', '#3498db'),
        (4, 'Business', 'Business and management books', 'business', '#16a085'),
        (5, 'History', 'Historical books and biographies', 'history_edu', '#e74c3c'),
        (6, 'Technology', 'Computer science and technology', 'computer', '#f39c12')
    ");
    
    // Insert authors
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
    
    // Insert books
    $pdo->exec("
        INSERT INTO books (id, isbn, title, description, publication_date, publisher, pages, category_id, total_copies, available_copies, rating, total_ratings, cover_image) VALUES
        (1, '978-0-7432-7356-5', 'The Great Gatsby', 'A classic American novel set in the Jazz Age, exploring themes of wealth, love, and the American Dream.', '1925-04-10', 'Scribner', 180, 1, 5, 3, 4.2, 150, null),
        (2, '978-0-06-112008-4', 'To Kill a Mockingbird', 'A gripping tale of racial injustice and childhood innocence in the American South.', '1960-07-11', 'J.B. Lippincott & Co.', 281, 1, 4, 2, 4.5, 200, null),
        (3, '978-0-452-28423-4', '1984', 'A dystopian social science fiction novel about totalitarian control and surveillance.', '1949-06-08', 'Secker & Warburg', 328, 1, 6, 4, 4.7, 300, null),
        (4, '978-0-13-208670-1', 'Clean Code', 'A handbook of agile software craftsmanship for writing maintainable code.', '2008-08-01', 'Prentice Hall', 464, 6, 8, 5, 4.6, 120, null),
        (5, '978-0-262-03384-8', 'Introduction to Algorithms', 'Comprehensive guide to algorithms and data structures.', '2009-07-31', 'MIT Press', 1312, 6, 3, 1, 4.8, 89, null),
        (6, '978-0-13-449507-6', 'Marketing Management', 'Comprehensive guide to modern marketing principles and practices.', '2015-01-15', 'Pearson', 832, 4, 4, 3, 4.0, 78, null),
        (7, '978-0-06-231609-7', 'Sapiens', 'A brief history of humankind from the Stone Age to the present.', '2014-02-10', 'Harper', 443, 5, 5, 2, 4.4, 180, null),
        (8, '978-1-5247-6313-8', 'Becoming', 'Memoir by former First Lady Michelle Obama.', '2018-11-13', 'Crown Publishing', 448, 5, 6, 4, 4.6, 250, null)
    ");
    
    // Link books to authors
    $pdo->exec("
        INSERT INTO book_authors (book_id, author_id) VALUES
        (1, 1), (2, 2), (3, 3), (4, 4), (5, 5), (6, 6), (7, 7), (8, 8)
    ");
    
    echo "Database fixed and populated successfully!\n";
    echo "Added 6 categories, 8 authors, and 8 books with proper relationships.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
