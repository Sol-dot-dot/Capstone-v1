<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
$DB_HOST = 'localhost';
$DB_NAME = 'capstone_db';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If database fails, return hardcoded data
    $hardcoded_books = [
        [
            'id' => 1,
            'title' => 'Harry Potter and the Philosopher\'s Stone',
            'author_name' => 'J.K. Rowling',
            'category_name' => 'Fantasy',
            'description' => 'A young wizard discovers his magical heritage',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780747532699-L.jpg',
            'rating' => 4.8,
            'available_copies' => 3,
            'total_copies' => 5
        ],
        [
            'id' => 2,
            'title' => 'The Shining',
            'author_name' => 'Stephen King',
            'category_name' => 'Fiction',
            'description' => 'A family becomes winter caretakers of an isolated hotel',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780385121675-L.jpg',
            'rating' => 4.2,
            'available_copies' => 2,
            'total_copies' => 3
        ],
        [
            'id' => 3,
            'title' => 'Murder on the Orient Express',
            'author_name' => 'Agatha Christie',
            'category_name' => 'Mystery',
            'description' => 'Detective Poirot solves a murder on a train',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780062693662-L.jpg',
            'rating' => 4.5,
            'available_copies' => 4,
            'total_copies' => 4
        ],
        [
            'id' => 4,
            'title' => 'Pride and Prejudice',
            'author_name' => 'Jane Austen',
            'category_name' => 'Romance',
            'description' => 'A romantic novel about manners and marriage',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780141439518-L.jpg',
            'rating' => 4.6,
            'available_copies' => 5,
            'total_copies' => 6
        ],
        [
            'id' => 5,
            'title' => '1984',
            'author_name' => 'George Orwell',
            'category_name' => 'Fiction',
            'description' => 'A dystopian social science fiction novel',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780451524935-L.jpg',
            'rating' => 4.7,
            'available_copies' => 3,
            'total_copies' => 4
        ]
    ];
    
    echo json_encode(['success' => true, 'books' => $hardcoded_books]);
    exit;
}

$action = $_GET['action'] ?? 'all';

switch ($action) {
    case 'all':
    case 'popular':
        try {
            $stmt = $pdo->query("
                SELECT b.*, c.name as category_name, a.name as author_name
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                ORDER BY b.rating DESC
                LIMIT 20
            ");
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'books' => $books]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'categories':
        try {
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'categories' => $categories]);
        } catch (Exception $e) {
            $hardcoded_categories = [
                ['id' => 1, 'name' => 'Fiction', 'icon' => 'book'],
                ['id' => 2, 'name' => 'Science Fiction', 'icon' => 'rocket'],
                ['id' => 3, 'name' => 'Mystery', 'icon' => 'search'],
                ['id' => 4, 'name' => 'Romance', 'icon' => 'heart'],
                ['id' => 5, 'name' => 'Fantasy', 'icon' => 'magic']
            ];
            echo json_encode(['success' => true, 'categories' => $hardcoded_categories]);
        }
        break;
        
    case 'recommendations':
        // Return some sample recommendations
        $recommendations = [
            [
                'id' => 1,
                'title' => 'The Hobbit',
                'author_name' => 'J.R.R. Tolkien',
                'rating' => 4.7,
                'reason' => 'Based on your interest in Fantasy'
            ],
            [
                'id' => 2,
                'title' => 'Dune',
                'author_name' => 'Frank Herbert',
                'rating' => 4.4,
                'reason' => 'Popular in Science Fiction'
            ]
        ];
        echo json_encode(['success' => true, 'recommendations' => $recommendations]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>
