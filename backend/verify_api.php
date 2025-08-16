<?php
header('Content-Type: application/json');
require_once 'config.php';

// Initialize database if needed
try {
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'books'");
    if ($stmt->rowCount() == 0) {
        // Create tables and insert data
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("DROP TABLE IF EXISTS book_authors, books, authors, categories");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        $pdo->exec("CREATE TABLE categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, description TEXT, icon VARCHAR(50), color VARCHAR(7))");
        $pdo->exec("CREATE TABLE authors (id INT AUTO_INCREMENT PRIMARY KEY, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, biography TEXT, nationality VARCHAR(100))");
        $pdo->exec("CREATE TABLE books (id INT AUTO_INCREMENT PRIMARY KEY, isbn VARCHAR(20), title VARCHAR(255) NOT NULL, description TEXT, publication_date DATE, publisher VARCHAR(255), pages INT, category_id INT, total_copies INT DEFAULT 1, available_copies INT DEFAULT 1, status ENUM('active','inactive') DEFAULT 'active', rating DECIMAL(3,2) DEFAULT 4.0, total_ratings INT DEFAULT 0, FOREIGN KEY (category_id) REFERENCES categories(id))");
        $pdo->exec("CREATE TABLE book_authors (id INT AUTO_INCREMENT PRIMARY KEY, book_id INT NOT NULL, author_id INT NOT NULL, FOREIGN KEY (book_id) REFERENCES books(id), FOREIGN KEY (author_id) REFERENCES authors(id))");
        
        $pdo->exec("INSERT INTO categories VALUES (1,'Fiction','Novels','auto_stories','#9b59b6'),(2,'Science','Scientific books','science','#2ecc71'),(3,'Technology','Tech books','computer','#f39c12'),(4,'Business','Business books','business','#16a085'),(5,'History','Historical books','history_edu','#e74c3c')");
        $pdo->exec("INSERT INTO authors VALUES (1,'F. Scott','Fitzgerald','American novelist','American'),(2,'Harper','Lee','American novelist','American'),(3,'George','Orwell','English novelist','British'),(4,'Robert C.','Martin','Software engineer','American')");
        $pdo->exec("INSERT INTO books VALUES (1,'978-0-7432-7356-5','The Great Gatsby','A classic American novel','1925-04-10','Scribner',180,1,5,3,'active',4.2,150),(2,'978-0-06-112008-4','To Kill a Mockingbird','A gripping tale','1960-07-11','Lippincott',281,1,4,2,'active',4.5,200),(3,'978-0-452-28423-4','1984','Dystopian novel','1949-06-08','Warburg',328,1,6,4,'active',4.7,300),(4,'978-0-13-208670-1','Clean Code','Software craftsmanship','2008-08-01','Prentice Hall',464,3,8,5,'active',4.6,120)");
        $pdo->exec("INSERT INTO book_authors VALUES (1,1,1),(2,2,2),(3,3,3),(4,4,4)");
    }
    
    // Test API endpoints
    $action = $_GET['action'] ?? 'test';
    
    switch ($action) {
        case 'all':
            $stmt = $pdo->query("
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
                LIMIT 20
            ");
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'books' => $books]);
            break;
            
        case 'categories':
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'categories' => $categories]);
            break;
            
        case 'details':
            $book_id = $_GET['book_id'] ?? 1;
            $stmt = $pdo->prepare("
                SELECT b.*, c.name as category_name, c.color as category_color,
                       CONCAT(a.first_name, ' ', a.last_name) as author_name,
                       COALESCE(b.rating, 4.0) as rating,
                       COALESCE(b.total_ratings, 0) as total_ratings
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                WHERE b.id = ? AND b.status = 'active'
            ");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'book' => $book]);
            break;
            
        default:
            echo json_encode([
                'success' => true, 
                'message' => 'API is working',
                'endpoints' => [
                    'books' => '?action=all',
                    'categories' => '?action=categories', 
                    'book_details' => '?action=details&book_id=1'
                ]
            ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
