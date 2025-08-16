<?php
// Direct database initialization script
$DB_HOST = 'localhost';
$DB_NAME = 'capstone_db';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Drop existing tables if they exist
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables = ['chat_messages', 'chat_conversations', 'notifications', 'recommendations',
               'reading_preferences', 'bookmarks', 'book_reviews', 'borrowings',
               'book_authors', 'books', 'authors', 'categories', 'admin_users',
               'login_history', 'student_logins', 'email_verification_codes',
               'students', 'student_records', 'users'];
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Dropped existing tables\n";
    
    // Create new schema
    $schema_sql = "
    -- Student records table
    CREATE TABLE student_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL UNIQUE,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        course VARCHAR(100),
        year_level INT,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    -- Students table for app registration
    CREATE TABLE students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student_records(student_id) ON DELETE CASCADE
    );

    -- Email verification codes
    CREATE TABLE email_verification_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        email VARCHAR(255) NOT NULL,
        code VARCHAR(10) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_student_email (student_id, email)
    );

    -- Admin users
    CREATE TABLE admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Categories
    CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        icon VARCHAR(50),
        color VARCHAR(7),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Authors
    CREATE TABLE authors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        biography TEXT,
        nationality VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Books
    CREATE TABLE books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        isbn VARCHAR(20) UNIQUE,
        title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(255),
        description TEXT,
        publication_date DATE,
        publisher VARCHAR(255),
        pages INT,
        category_id INT,
        total_copies INT DEFAULT 1,
        available_copies INT DEFAULT 1,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        INDEX idx_title (title),
        INDEX idx_isbn (isbn),
        INDEX idx_category (category_id)
    );

    -- Book authors junction table
    CREATE TABLE book_authors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book_id INT NOT NULL,
        author_id INT NOT NULL,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE,
        UNIQUE KEY unique_book_author (book_id, author_id)
    );

    -- Borrowings
    CREATE TABLE borrowings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        book_id INT NOT NULL,
        borrowed_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        due_date DATE NOT NULL,
        returned_date DATETIME NULL,
        status ENUM('active','returned','overdue') DEFAULT 'active',
        renewal_count INT DEFAULT 0,
        fine_amount DECIMAL(10,2) DEFAULT 0.00,
        fine_paid DECIMAL(10,2) DEFAULT 0.00,
        fine_status ENUM('none','pending','partial','paid') DEFAULT 'none',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student_records(student_id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        INDEX idx_student_status (student_id, status),
        INDEX idx_due_date (due_date)
    );
    ";
    
    // Execute schema - split by semicolon and execute each statement
    $statements = explode(';', $schema_sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && substr($statement, 0, 2) !== '--') {
            try {
                $pdo->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "Created database schema\n";
    
    // Insert sample data
    $pdo->exec("INSERT INTO admin_users (email, password_hash) VALUES 
        ('admin@library.com', '" . password_hash('admin123', PASSWORD_BCRYPT) . "')");
    
    $pdo->exec("INSERT INTO student_records (student_id, first_name, last_name, course, year_level) VALUES 
        ('C22-0044', 'Rhodcelister', 'Duallo', 'BSIT', 3),
        ('C22-0055', 'Jane', 'Cruz', 'BSCS', 2),
        ('C21-0123', 'John', 'Doe', 'BSIT', 4),
        ('C23-0001', 'Maria', 'Santos', 'BSCS', 1)");
    
    $pdo->exec("INSERT INTO categories (name, description, icon, color) VALUES
        ('Fiction', 'Novels and fictional works', 'auto_stories', '#9b59b6'),
        ('Science', 'Scientific research and discoveries', 'science', '#2ecc71'),
        ('Engineering', 'Technical and engineering books', 'engineering', '#3498db'),
        ('Business', 'Business and management books', 'business', '#16a085')");
    
    $pdo->exec("INSERT INTO authors (first_name, last_name, biography, nationality) VALUES
        ('F. Scott', 'Fitzgerald', 'American novelist', 'American'),
        ('George R.R.', 'Martin', 'American novelist', 'American'),
        ('John', 'Green', 'American author', 'American'),
        ('Dale', 'Carnegie', 'American writer', 'American')");
    
    $pdo->exec("INSERT INTO books (isbn, title, description, category_id, total_copies, available_copies) VALUES
        ('978-0-7432-7356-5', 'The Great Gatsby', 'Classic American novel', 1, 5, 3),
        ('978-0-553-10354-0', 'A Game of Thrones', 'Epic fantasy novel', 1, 3, 1),
        ('978-0-14-242417-9', 'The Fault in Our Stars', 'Young adult romance', 1, 4, 2),
        ('978-0-671-02111-7', 'How to Win Friends and Influence People', 'Self-help book', 4, 6, 4)");
    
    $pdo->exec("INSERT INTO book_authors (book_id, author_id) VALUES (1,1), (2,2), (3,3), (4,4)");
    
    echo "Inserted sample data\n";
    echo "Database initialization complete!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
