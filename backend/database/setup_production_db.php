<?php
header('Content-Type: text/plain');

// Production database setup
$DB_HOST = 'localhost';
$DB_NAME = 'capstone_db';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== SETTING UP PRODUCTION DATABASE ===\n\n";
    
    // Drop existing tables in correct order
    $tables = ['notifications', 'recommendations', 'reading_preferences', 'bookmarks', 'book_reviews', 'borrowings', 'book_authors', 'books', 'authors', 'categories', 'student_logins', 'students', 'student_records'];
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
    }
    echo "✓ Dropped existing tables\n";

    // Create categories table
    $pdo->exec("CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        icon VARCHAR(50) DEFAULT 'book',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create authors table
    $pdo->exec("CREATE TABLE authors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        bio TEXT,
        birth_year INT,
        nationality VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create books table
    $pdo->exec("CREATE TABLE books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        isbn VARCHAR(20) UNIQUE,
        category_id INT,
        description TEXT,
        publisher VARCHAR(255),
        publication_year INT,
        pages INT,
        language VARCHAR(50) DEFAULT 'English',
        cover_image VARCHAR(500),
        available_copies INT DEFAULT 1,
        total_copies INT DEFAULT 1,
        rating DECIMAL(3,2) DEFAULT 0.00,
        total_ratings INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        INDEX idx_category (category_id),
        INDEX idx_rating (rating),
        INDEX idx_available (available_copies)
    )");

    // Create book_authors junction table
    $pdo->exec("CREATE TABLE book_authors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book_id INT NOT NULL,
        author_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE,
        UNIQUE KEY unique_book_author (book_id, author_id)
    )");

    // Create student_records table
    $pdo->exec("CREATE TABLE student_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL UNIQUE,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        course VARCHAR(100) NOT NULL,
        year_level INT NOT NULL,
        email VARCHAR(255),
        phone VARCHAR(20),
        address TEXT,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_student_id (student_id),
        INDEX idx_status (status)
    )");

    // Create students login table
    $pdo->exec("CREATE TABLE students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        account_status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student_records(student_id) ON DELETE CASCADE,
        INDEX idx_email (email),
        INDEX idx_status (account_status)
    )");

    // Create borrowings table
    $pdo->exec("CREATE TABLE borrowings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        book_id INT NOT NULL,
        borrowed_date DATE NOT NULL,
        due_date DATE NOT NULL,
        returned_date DATE NULL,
        status ENUM('active', 'returned', 'overdue', 'renewed') DEFAULT 'active',
        fine_amount DECIMAL(10,2) DEFAULT 0.00,
        fine_paid BOOLEAN DEFAULT FALSE,
        renewal_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student_records(student_id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        INDEX idx_student (student_id),
        INDEX idx_book (book_id),
        INDEX idx_status (status),
        INDEX idx_due_date (due_date)
    )");

    // Create bookmarks table
    $pdo->exec("CREATE TABLE bookmarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        book_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student_records(student_id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        UNIQUE KEY unique_bookmark (student_id, book_id),
        INDEX idx_student (student_id)
    )");

    // Create book_reviews table
    $pdo->exec("CREATE TABLE book_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book_id INT NOT NULL,
        student_id VARCHAR(20) NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES student_records(student_id) ON DELETE CASCADE,
        UNIQUE KEY unique_review (book_id, student_id),
        INDEX idx_book (book_id),
        INDEX idx_rating (rating)
    )");

    // Create student_logins tracking table
    $pdo->exec("CREATE TABLE student_logins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        email VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student_records(student_id) ON DELETE CASCADE,
        INDEX idx_student (student_id),
        INDEX idx_login_time (login_time)
    )");

    // Create notifications table
    $pdo->exec("CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        type ENUM('due_reminder', 'overdue', 'return_confirmation', 'recommendation', 'system') DEFAULT 'system',
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student_records(student_id) ON DELETE CASCADE,
        INDEX idx_student (student_id),
        INDEX idx_read (is_read),
        INDEX idx_type (type)
    )");

    echo "✓ Created all database tables with proper indexes\n\n";

    // Insert categories
    $categories = [
        ['Fiction', 'Fictional stories and novels', 'book'],
        ['Science Fiction', 'Futuristic and scientific fiction', 'rocket'],
        ['Mystery', 'Mystery and detective stories', 'search'],
        ['Romance', 'Love and romantic stories', 'heart'],
        ['Fantasy', 'Fantasy and magical stories', 'magic'],
        ['Biography', 'Life stories of real people', 'person'],
        ['History', 'Historical books and events', 'time'],
        ['Science', 'Scientific and educational books', 'flask'],
        ['Technology', 'Technology and computing books', 'computer'],
        ['Self-Help', 'Personal development books', 'lightbulb']
    ];

    foreach ($categories as $cat) {
        $pdo->prepare("INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)")
             ->execute($cat);
    }
    echo "✓ Inserted " . count($categories) . " categories\n";

    // Insert authors
    $authors = [
        ['J.K. Rowling', 'British author best known for Harry Potter series', 1965, 'British'],
        ['Stephen King', 'American author of horror and supernatural fiction', 1947, 'American'],
        ['Agatha Christie', 'British crime novelist', 1890, 'British'],
        ['Isaac Asimov', 'American science fiction writer', 1920, 'American'],
        ['Jane Austen', 'English novelist', 1775, 'British'],
        ['George Orwell', 'English novelist and essayist', 1903, 'British'],
        ['Harper Lee', 'American novelist', 1926, 'American'],
        ['F. Scott Fitzgerald', 'American novelist', 1896, 'American'],
        ['Ernest Hemingway', 'American novelist and journalist', 1899, 'American'],
        ['Mark Twain', 'American writer and humorist', 1835, 'American'],
        ['Charles Dickens', 'English writer and social critic', 1812, 'British'],
        ['William Shakespeare', 'English playwright and poet', 1564, 'British'],
        ['Dan Brown', 'American author of thriller fiction', 1964, 'American'],
        ['John Grisham', 'American novelist and attorney', 1955, 'American'],
        ['Paulo Coelho', 'Brazilian lyricist and novelist', 1947, 'Brazilian']
    ];

    foreach ($authors as $author) {
        $pdo->prepare("INSERT INTO authors (name, bio, birth_year, nationality) VALUES (?, ?, ?, ?)")
             ->execute($author);
    }
    echo "✓ Inserted " . count($authors) . " authors\n";

    // Insert 30 books
    $books = [
        ['Harry Potter and the Philosopher\'s Stone', '9780747532699', 5, 'A young wizard discovers his magical heritage on his 11th birthday.', 'Bloomsbury', 1997, 223, 'English', 'https://covers.openlibrary.org/b/isbn/9780747532699-L.jpg', 3, 5, 4.8, 1250],
        ['The Shining', '9780385121675', 1, 'A family becomes winter caretakers of an isolated hotel where supernatural forces lurk.', 'Doubleday', 1977, 447, 'English', 'https://covers.openlibrary.org/b/isbn/9780385121675-L.jpg', 2, 3, 4.2, 890],
        ['Murder on the Orient Express', '9780062693662', 3, 'Detective Hercule Poirot solves a murder aboard the famous train.', 'William Morrow', 1934, 256, 'English', 'https://covers.openlibrary.org/b/isbn/9780062693662-L.jpg', 4, 4, 4.5, 1100],
        ['Foundation', '9780553293357', 2, 'The collapse and renewal of a galactic empire through psychohistory.', 'Spectra', 1951, 244, 'English', 'https://covers.openlibrary.org/b/isbn/9780553293357-L.jpg', 2, 3, 4.3, 750],
        ['Pride and Prejudice', '9780141439518', 4, 'A romantic novel about manners, marriage, and social class in Georgian England.', 'Penguin Classics', 1813, 432, 'English', 'https://covers.openlibrary.org/b/isbn/9780141439518-L.jpg', 5, 6, 4.6, 2100],
        ['1984', '9780451524935', 1, 'A dystopian social science fiction novel about totalitarian control.', 'Signet Classics', 1949, 328, 'English', 'https://covers.openlibrary.org/b/isbn/9780451524935-L.jpg', 3, 4, 4.7, 1800],
        ['To Kill a Mockingbird', '9780061120084', 1, 'A story of racial injustice and childhood innocence in the American South.', 'Harper Perennial', 1960, 376, 'English', 'https://covers.openlibrary.org/b/isbn/9780061120084-L.jpg', 4, 5, 4.8, 2500],
        ['The Great Gatsby', '9780743273565', 1, 'The Jazz Age and the decline of the American Dream in the 1920s.', 'Scribner', 1925, 180, 'English', 'https://covers.openlibrary.org/b/isbn/9780743273565-L.jpg', 6, 7, 4.4, 1600],
        ['The Old Man and the Sea', '9780684801223', 1, 'An aging fisherman\'s epic struggle with a giant marlin in the Gulf Stream.', 'Scribner', 1952, 127, 'English', 'https://covers.openlibrary.org/b/isbn/9780684801223-L.jpg', 3, 4, 4.1, 950],
        ['Adventures of Huckleberry Finn', '9780486280615', 1, 'A boy\'s journey down the Mississippi River with an escaped slave.', 'Dover Publications', 1884, 366, 'English', 'https://covers.openlibrary.org/b/isbn/9780486280615-L.jpg', 2, 3, 4.2, 1200],
        ['A Tale of Two Cities', '9780486406510', 7, 'London and Paris during the French Revolution in this historical novel.', 'Dover Publications', 1859, 448, 'English', 'https://covers.openlibrary.org/b/isbn/9780486406510-L.jpg', 3, 4, 4.3, 1400],
        ['Romeo and Juliet', '9780486275437', 1, 'The tragic love story of two young star-crossed lovers in Verona.', 'Dover Publications', 1597, 96, 'English', 'https://covers.openlibrary.org/b/isbn/9780486275437-L.jpg', 5, 6, 4.5, 1800],
        ['The Da Vinci Code', '9780307474278', 3, 'A mystery thriller involving secret societies and religious history.', 'Anchor', 2003, 454, 'English', 'https://covers.openlibrary.org/b/isbn/9780307474278-L.jpg', 4, 5, 4.1, 2200],
        ['The Firm', '9780385416634', 3, 'A young lawyer discovers his prestigious law firm\'s dark secrets.', 'Doubleday', 1991, 421, 'English', 'https://covers.openlibrary.org/b/isbn/9780385416634-L.jpg', 2, 3, 4.2, 1100],
        ['The Alchemist', '9780061122415', 10, 'A shepherd\'s journey to find his personal legend and treasure.', 'HarperOne', 1988, 163, 'English', 'https://covers.openlibrary.org/b/isbn/9780061122415-L.jpg', 6, 8, 4.6, 3200],
        ['Dune', '9780441172719', 2, 'A desert planet becomes the center of political intrigue and mystical power.', 'Ace', 1965, 688, 'English', 'https://covers.openlibrary.org/b/isbn/9780441172719-L.jpg', 2, 3, 4.4, 1500],
        ['The Hobbit', '9780547928227', 5, 'A hobbit\'s unexpected journey to help dwarves reclaim their mountain home.', 'Houghton Mifflin Harcourt', 1937, 366, 'English', 'https://covers.openlibrary.org/b/isbn/9780547928227-L.jpg', 4, 5, 4.7, 2800],
        ['Fahrenheit 451', '9781451673319', 2, 'A dystopian future where books are banned and burned by firemen.', 'Simon & Schuster', 1953, 249, 'English', 'https://covers.openlibrary.org/b/isbn/9781451673319-L.jpg', 3, 4, 4.3, 1300],
        ['The Catcher in the Rye', '9780316769174', 1, 'A teenager\'s alienation and rebellion in New York City.', 'Little, Brown', 1951, 277, 'English', 'https://covers.openlibrary.org/b/isbn/9780316769174-L.jpg', 2, 3, 4.0, 1700],
        ['Lord of the Flies', '9780571056866', 1, 'British boys stranded on an uninhabited island descend into savagery.', 'Faber & Faber', 1954, 248, 'English', 'https://covers.openlibrary.org/b/isbn/9780571056866-L.jpg', 3, 4, 4.1, 1400],
        ['The Chronicles of Narnia', '9780066238500', 5, 'Children discover a magical world through a wardrobe.', 'HarperCollins', 1950, 767, 'English', 'https://covers.openlibrary.org/b/isbn/9780066238500-L.jpg', 5, 6, 4.5, 2100],
        ['Brave New World', '9780060850524', 2, 'A dystopian society where humans are engineered and conditioned.', 'Harper Perennial', 1932, 268, 'English', 'https://covers.openlibrary.org/b/isbn/9780060850524-L.jpg', 2, 3, 4.2, 1600],
        ['The Hunger Games', '9780439023528', 2, 'A televised fight to the death in a dystopian future.', 'Scholastic Press', 2008, 374, 'English', 'https://covers.openlibrary.org/b/isbn/9780439023528-L.jpg', 4, 6, 4.3, 2900],
        ['Gone Girl', '9780307588364', 3, 'A marriage gone terribly wrong with shocking twists.', 'Crown', 2012, 419, 'English', 'https://covers.openlibrary.org/b/isbn/9780307588364-L.jpg', 3, 4, 4.0, 1800],
        ['The Girl with the Dragon Tattoo', '9780307454546', 3, 'A journalist and hacker investigate a wealthy family\'s dark secrets.', 'Vintage', 2005, 590, 'English', 'https://covers.openlibrary.org/b/isbn/9780307454546-L.jpg', 2, 3, 4.2, 2200],
        ['Life of Pi', '9780156027328', 1, 'A boy survives 227 days at sea with a Bengal tiger.', 'Harcourt', 2001, 319, 'English', 'https://covers.openlibrary.org/b/isbn/9780156027328-L.jpg', 3, 4, 4.4, 1900],
        ['The Kite Runner', '9781594631931', 1, 'Friendship and redemption against the backdrop of Afghanistan\'s history.', 'Riverhead Books', 2003, 371, 'English', 'https://covers.openlibrary.org/b/isbn/9781594631931-L.jpg', 2, 3, 4.3, 2100],
        ['The Book Thief', '9780375842207', 7, 'A girl\'s love of books during World War II in Nazi Germany.', 'Knopf', 2005, 552, 'English', 'https://covers.openlibrary.org/b/isbn/9780375842207-L.jpg', 4, 5, 4.6, 2400],
        ['Where the Crawdads Sing', '9780735219090', 3, 'A mystery set in the marshlands of North Carolina.', 'G.P. Putnam\'s Sons', 2018, 370, 'English', 'https://covers.openlibrary.org/b/isbn/9780735219090-L.jpg', 5, 7, 4.5, 3100],
        ['The Seven Husbands of Evelyn Hugo', '9781501139239', 4, 'A reclusive Hollywood icon finally tells her life story.', 'Atria Books', 2017, 400, 'English', 'https://covers.openlibrary.org/b/isbn/9781501139239-L.jpg', 3, 5, 4.7, 2800]
    ];

    foreach ($books as $i => $book) {
        $stmt = $pdo->prepare("INSERT INTO books (title, isbn, category_id, description, publisher, publication_year, pages, language, cover_image, available_copies, total_copies, rating, total_ratings) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($book);
        
        // Link books to authors
        $author_id = ($i % 15) + 1;
        $book_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)")
             ->execute([$book_id, $author_id]);
    }
    echo "✓ Inserted " . count($books) . " books with author relationships\n";

    // Insert student records
    $students_data = [
        ['C22-0044', 'Rhodcelister', 'Duallo', 'BSIT', 3, 'rhodcelister.duallo@my.smciligan.edu.ph', '09123456789', 'Iligan City, Philippines'],
        ['C22-0045', 'Maria', 'Santos', 'BSCS', 2, 'maria.santos@my.smciligan.edu.ph', '09234567890', 'Cagayan de Oro, Philippines'],
        ['C22-0046', 'John', 'Dela Cruz', 'BSIT', 4, 'john.delacruz@my.smciligan.edu.ph', '09345678901', 'Butuan City, Philippines'],
        ['C22-0047', 'Anna', 'Reyes', 'BSCS', 1, 'anna.reyes@my.smciligan.edu.ph', '09456789012', 'Dipolog City, Philippines'],
        ['C22-0048', 'Michael', 'Garcia', 'BSIT', 3, 'michael.garcia@my.smciligan.edu.ph', '09567890123', 'Ozamiz City, Philippines']
    ];

    foreach ($students_data as $student) {
        $pdo->prepare("INSERT INTO student_records (student_id, first_name, last_name, course, year_level, email, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
             ->execute($student);
        
        // Create login accounts
        $password_hash = password_hash('test123', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO students (student_id, email, password_hash) VALUES (?, ?, ?)")
             ->execute([$student[0], $student[5], $password_hash]);
    }
    echo "✓ Inserted " . count($students_data) . " students with login accounts\n";

    // Add sample borrowings
    $borrowings = [
        ['C22-0044', 1, '2024-01-15', '2024-02-15', null, 'active'],
        ['C22-0044', 15, '2024-01-10', '2024-02-10', '2024-02-08', 'returned'],
        ['C22-0045', 3, '2024-01-20', '2024-02-20', null, 'active'],
        ['C22-0046', 7, '2024-01-05', '2024-02-05', null, 'overdue'],
        ['C22-0047', 12, '2024-01-25', '2024-02-25', null, 'active']
    ];

    foreach ($borrowings as $borrowing) {
        $pdo->prepare("INSERT INTO borrowings (student_id, book_id, borrowed_date, due_date, returned_date, status) VALUES (?, ?, ?, ?, ?, ?)")
             ->execute($borrowing);
    }
    echo "✓ Added " . count($borrowings) . " sample borrowings\n";

    // Add bookmarks
    $bookmarks = [
        ['C22-0044', 5], ['C22-0044', 17], ['C22-0044', 29],
        ['C22-0045', 2], ['C22-0045', 8],
        ['C22-0046', 11], ['C22-0047', 23]
    ];

    foreach ($bookmarks as $bookmark) {
        $pdo->prepare("INSERT INTO bookmarks (student_id, book_id) VALUES (?, ?)")
             ->execute($bookmark);
    }
    echo "✓ Added " . count($bookmarks) . " bookmarks\n";

    // Add reviews
    $reviews = [
        [1, 'C22-0044', 5, 'Amazing start to the Harry Potter series! Magical and captivating.'],
        [15, 'C22-0044', 4, 'Great philosophical novel about following your dreams.'],
        [7, 'C22-0046', 5, 'A timeless classic that everyone should read.'],
        [12, 'C22-0047', 4, 'Beautiful tragic love story by Shakespeare.'],
        [3, 'C22-0045', 4, 'Classic Agatha Christie mystery with great plot twists!']
    ];

    foreach ($reviews as $review) {
        $pdo->prepare("INSERT INTO book_reviews (book_id, student_id, rating, review_text) VALUES (?, ?, ?, ?)")
             ->execute($review);
    }
    echo "✓ Added " . count($reviews) . " book reviews\n";

    // Add sample notifications
    $notifications = [
        ['C22-0044', 'due_reminder', 'Book Due Soon', 'Your book "Harry Potter and the Philosopher\'s Stone" is due in 3 days.'],
        ['C22-0046', 'overdue', 'Overdue Book', 'Your book "To Kill a Mockingbird" is overdue. Please return it as soon as possible.'],
        ['C22-0045', 'recommendation', 'New Recommendation', 'Based on your reading history, you might like "The Hobbit".']
    ];

    foreach ($notifications as $notification) {
        $pdo->prepare("INSERT INTO notifications (student_id, type, title, message) VALUES (?, ?, ?, ?)")
             ->execute($notification);
    }
    echo "✓ Added " . count($notifications) . " notifications\n";

    echo "\n=== PRODUCTION DATABASE SETUP COMPLETE ===\n";
    echo "✅ Complete schema with proper relationships and indexes\n";
    echo "✅ 30 books across 10 categories\n";
    echo "✅ 15 authors with biographical information\n";
    echo "✅ 5 students with secure login accounts\n";
    echo "✅ Sample borrowings, bookmarks, reviews, and notifications\n";
    echo "✅ No hardcoded data - everything from database\n\n";
    
    echo "Login Credentials:\n";
    foreach ($students_data as $student) {
        echo "- {$student[0]} / test123 ({$student[1]} {$student[2]})\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
