<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'all';

try {
    switch ($action) {
        case 'all':
        case 'popular':
            $stmt = $pdo->query("
                SELECT b.*, c.name as category_name, c.color as category_color,
                       CONCAT(a.first_name, ' ', a.last_name) as author_name,
                       COALESCE(b.rating, 4.0) as rating,
                       COALESCE(b.total_ratings, 0) as total_ratings,
                       b.cover_image
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                WHERE 1=1
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
            
        case 'search':
            $query = $_GET['q'] ?? '';
            if (empty($query)) {
                echo json_encode(['success' => false, 'error' => 'Search query required']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT b.*, c.name as category_name, a.name as author_name
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                WHERE b.title LIKE ? OR a.name LIKE ? OR c.name LIKE ?
                ORDER BY b.rating DESC
                LIMIT 20
            ");
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'books' => $books]);
            break;
            
        case 'by_category':
            $category_id = $_GET['category_id'] ?? 0;
            $stmt = $pdo->prepare("
                SELECT b.*, c.name as category_name, a.name as author_name
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                WHERE b.category_id = ?
                ORDER BY b.rating DESC
            ");
            $stmt->execute([$category_id]);
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'books' => $books]);
            break;
            
        case 'recommendations':
            $student_id = $_GET['student_id'] ?? '';
            if (empty($student_id)) {
                echo json_encode(['success' => false, 'error' => 'Student ID required']);
                break;
            }
            
            // Get recommendations based on user's borrowing history and ratings
            $stmt = $pdo->prepare("
                SELECT DISTINCT b.*, c.name as category_name, a.name as author_name,
                       'Based on your reading history' as reason
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                WHERE b.category_id IN (
                    SELECT DISTINCT b2.category_id
                    FROM borrowings br
                    JOIN books b2 ON br.book_id = b2.id
                    WHERE br.student_id = ?
                )
                AND b.id NOT IN (
                    SELECT book_id FROM borrowings WHERE student_id = ?
                )
                ORDER BY b.rating DESC
                LIMIT 10
            ");
            $stmt->execute([$student_id, $student_id]);
            $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'recommendations' => $recommendations]);
            break;
            
        case 'details':
            $bookId = $_GET['book_id'] ?? '';
            if (empty($bookId)) {
                echo json_encode(['success' => false, 'error' => 'Book ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT b.*, c.name as category_name, c.color as category_color,
                       CONCAT(a.first_name, ' ', a.last_name) as author_name,
                       COALESCE(b.rating, 4.0) as rating,
                       COALESCE(b.total_ratings, 0) as total_ratings,
                       b.publication_date as publication_year,
                       b.publisher, b.pages, b.isbn,
                       b.cover_image,
                       'English' as language
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                WHERE b.id = ? AND b.status = 'active'
            ");
            $stmt->execute([$bookId]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($book) {
                echo json_encode(['success' => true, 'book' => $book]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Book not found']);
            }
            break;
            
        case 'reviews':
            $book_id = $_GET['book_id'] ?? 0;
            $stmt = $pdo->prepare("
                SELECT br.*, sr.first_name, sr.last_name
                FROM book_reviews br
                JOIN student_records sr ON br.student_id = sr.student_id
                WHERE br.book_id = ?
                ORDER BY br.created_at DESC
            ");
            $stmt->execute([$book_id]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'reviews' => $reviews]);
            break;
            
        case 'toggle_bookmark':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'POST method required']);
                break;
            }
            
            $student_id = $_POST['student_id'] ?? '';
            $book_id = $_POST['book_id'] ?? 0;
            
            if (empty($student_id) || empty($book_id)) {
                echo json_encode(['success' => false, 'error' => 'Student ID and Book ID required']);
                break;
            }
            
            // Check if bookmark exists
            $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE student_id = ? AND book_id = ?");
            $stmt->execute([$student_id, $book_id]);
            $bookmark = $stmt->fetch();
            
            if ($bookmark) {
                // Remove bookmark
                $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE student_id = ? AND book_id = ?");
                $stmt->execute([$student_id, $book_id]);
                echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Bookmark removed']);
            } else {
                // Add bookmark
                $stmt = $pdo->prepare("INSERT INTO bookmarks (student_id, book_id) VALUES (?, ?)");
                $stmt->execute([$student_id, $book_id]);
                echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Bookmark added']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
