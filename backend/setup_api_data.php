<?php
require_once 'config.php';

echo "=== SETTING UP API DATA ===\n\n";

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Database type: $driver\n";
    
    // Create all necessary tables
    if ($driver === 'sqlite') {
        // Categories table
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            color VARCHAR(7) DEFAULT '#3498db',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Authors table
        $pdo->exec("CREATE TABLE IF NOT EXISTS authors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Books table
        $pdo->exec("CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            isbn VARCHAR(20),
            category_id INT,
            description TEXT,
            total_copies INT DEFAULT 1,
            available_copies INT DEFAULT 1,
            rating DECIMAL(3,2) DEFAULT 0.0,
            total_ratings INT DEFAULT 0,
            cover_image VARCHAR(500),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )");
        
        // Book authors junction table
        $pdo->exec("CREATE TABLE IF NOT EXISTS book_authors (
            book_id INT,
            author_id INT,
            PRIMARY KEY (book_id, author_id),
            FOREIGN KEY (book_id) REFERENCES books(id),
            FOREIGN KEY (author_id) REFERENCES authors(id)
        )");
        
        // Student records table
        $pdo->exec("CREATE TABLE IF NOT EXISTS student_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id VARCHAR(20) NOT NULL UNIQUE,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            course VARCHAR(100),
            year_level VARCHAR(20),
            phone VARCHAR(20),
            address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
    } else {
        // MySQL versions
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            color VARCHAR(7) DEFAULT '#3498db',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            isbn VARCHAR(20),
            category_id INT,
            description TEXT,
            total_copies INT DEFAULT 1,
            available_copies INT DEFAULT 1,
            rating DECIMAL(3,2) DEFAULT 0.0,
            total_ratings INT DEFAULT 0,
            cover_image VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS book_authors (
            book_id INT,
            author_id INT,
            PRIMARY KEY (book_id, author_id),
            FOREIGN KEY (book_id) REFERENCES books(id),
            FOREIGN KEY (author_id) REFERENCES authors(id)
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS student_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL UNIQUE,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            course VARCHAR(100),
            year_level VARCHAR(20),
            phone VARCHAR(20),
            address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    echo "✓ Tables created\n";
    
    // Insert sample categories
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    if ($stmt->fetch()['count'] == 0) {
        echo "Adding categories...\n";
        $categories = [
            ['Fiction', '#9b59b6'],
            ['Science', '#e74c3c'],
            ['Technology', '#3498db'],
            ['Business', '#16a085'],
            ['History', '#f39c12'],
            ['Biography', '#2ecc71']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, color) VALUES (?, ?)");
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
        echo "✓ Categories added\n";
    }
    
    // Insert sample authors
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM authors");
    if ($stmt->fetch()['count'] == 0) {
        echo "Adding authors...\n";
        $authors = [
            ['F. Scott', 'Fitzgerald'],
            ['Harper', 'Lee'],
            ['Robert C.', 'Martin'],
            ['George', 'Orwell'],
            ['Philip', 'Kotler'],
            ['Steve', 'Jobs'],
            ['Bill', 'Gates'],
            ['Elon', 'Musk']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO authors (first_name, last_name) VALUES (?, ?)");
        foreach ($authors as $author) {
            $stmt->execute($author);
        }
        echo "✓ Authors added\n";
    }
    
    // Insert sample books
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    if ($stmt->fetch()['count'] == 0) {
        echo "Adding books...\n";
        $books = [
            ['The Great Gatsby', '978-0-7432-7356-5', 1, 'A classic American novel', 5, 3, 4.2, 150],
            ['To Kill a Mockingbird', '978-0-06-112008-4', 1, 'A gripping tale of racial injustice', 4, 2, 4.5, 200],
            ['Clean Code', '978-0-13-235088-4', 3, 'A handbook of agile software craftsmanship', 6, 4, 4.6, 120],
            ['1984', '978-0-452-28423-4', 1, 'A dystopian social science fiction novel', 5, 2, 4.7, 300],
            ['Marketing Management', '978-0-13-267150-9', 4, 'The definitive guide to marketing', 4, 3, 4.0, 78],
            ['Steve Jobs Biography', '978-1-4516-4853-9', 6, 'The exclusive biography of Steve Jobs', 3, 1, 4.8, 250]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO books (title, isbn, category_id, description, total_copies, available_copies, rating, total_ratings) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($books as $book) {
            $stmt->execute($book);
        }
        echo "✓ Books added\n";
        
        // Link books to authors
        $book_authors = [
            [1, 1], [2, 2], [3, 3], [4, 4], [5, 5], [6, 6]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
        foreach ($book_authors as $ba) {
            $stmt->execute($ba);
        }
        echo "✓ Book-author relationships added\n";
    }
    
    // Add student record for test user
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_records WHERE student_id = ?");
    $stmt->execute(['C22-0044']);
    if ($stmt->fetch()['count'] == 0) {
        echo "Adding student record...\n";
        $stmt = $pdo->prepare("INSERT INTO student_records (student_id, first_name, last_name, course, year_level, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['C22-0044', 'John', 'Doe', 'Computer Science', '4th Year', '+1234567890', '123 Main St, City']);
        echo "✓ Student record added\n";
    }
    
    echo "\n=== API DATA SETUP COMPLETE ===\n";
    echo "Books available: ";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    echo $stmt->fetch()['count'] . "\n";
    
    echo "Categories available: ";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    echo $stmt->fetch()['count'] . "\n";
    
    echo "Student records: ";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM student_records");
    echo $stmt->fetch()['count'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
