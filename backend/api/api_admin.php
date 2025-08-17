<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

require_once '../config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'dashboard_stats':
            // Get comprehensive dashboard statistics
            $stats = [];
            
            // Student statistics
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
            $stats['total_students'] = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE account_status = 'active'");
            $stats['active_students'] = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM student_logins WHERE DATE(login_time) = CURDATE()");
            $stats['logins_today'] = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM student_logins WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stats['logins_week'] = $stmt->fetch()['count'];
            
            // Book statistics
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
            $stats['total_books'] = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("SELECT SUM(available_copies) as count FROM books");
            $stats['available_books'] = $stmt->fetch()['count'] ?? 0;
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE status = 'active'");
            $stats['active_borrowings'] = $stmt->fetch()['count'] ?? 0;
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE status = 'overdue'");
            $stats['overdue_books'] = $stmt->fetch()['count'] ?? 0;
            
            // Category statistics
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
            $stats['total_categories'] = $stmt->fetch()['count'];
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'recent_students':
            $limit = $_GET['limit'] ?? 10;
            $stmt = $pdo->prepare("
                SELECT s.student_id, s.email, sr.first_name, sr.last_name, s.account_status, s.created_at,
                (SELECT COUNT(*) FROM student_logins WHERE student_id = s.student_id) as login_count,
                (SELECT MAX(login_time) FROM student_logins WHERE student_id = s.student_id) as last_login
                FROM students s 
                LEFT JOIN student_records sr ON s.student_id = sr.student_id
                ORDER BY s.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'students' => $students]);
            break;
            
        case 'recent_logins':
            $limit = $_GET['limit'] ?? 20;
            $stmt = $pdo->prepare("
                SELECT sl.*, sr.first_name, sr.last_name 
                FROM student_logins sl 
                LEFT JOIN student_records sr ON sl.student_id = sr.student_id 
                ORDER BY sl.login_time DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'logins' => $logins]);
            break;
            
        case 'books_management':
            $stmt = $pdo->query("
                SELECT b.*, c.name as category_name, c.color as category_color,
                       CONCAT(a.first_name, ' ', a.last_name) as author_name,
                       COALESCE(b.rating, 0.0) as rating,
                       COALESCE(b.total_ratings, 0) as total_ratings
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                ORDER BY b.created_at DESC
            ");
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'books' => $books]);
            break;
            
        case 'categories_management':
            $stmt = $pdo->query("
                SELECT c.*, COUNT(b.id) as book_count
                FROM categories c
                LEFT JOIN books b ON c.id = b.category_id
                GROUP BY c.id
                ORDER BY c.name
            ");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'categories' => $categories]);
            break;
            
        case 'borrowings_management':
            $stmt = $pdo->query("
                SELECT br.*, b.title as book_title, sr.first_name, sr.last_name,
                       DATEDIFF(CURDATE(), br.borrowed_date) as days_borrowed
                FROM borrowings br
                LEFT JOIN books b ON br.book_id = b.id
                LEFT JOIN student_records sr ON br.student_id = sr.student_id
                ORDER BY br.borrowed_date DESC
                LIMIT 50
            ");
            $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'borrowings' => $borrowings]);
            break;
            
        case 'update_student_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $student_id = $_POST['student_id'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if (!$student_id || !$status) {
                throw new Exception('Student ID and status required');
            }
            
            $stmt = $pdo->prepare("UPDATE students SET account_status = ? WHERE student_id = ?");
            $stmt->execute([$status, $student_id]);
            
            echo json_encode(['success' => true, 'message' => 'Student status updated']);
            break;
            
        case 'add_book':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $title = $_POST['title'] ?? '';
            $isbn = $_POST['isbn'] ?? '';
            $category_id = $_POST['category_id'] ?? '';
            $description = $_POST['description'] ?? '';
            $total_copies = $_POST['total_copies'] ?? 1;
            $author_first = $_POST['author_first'] ?? '';
            $author_last = $_POST['author_last'] ?? '';
            
            if (!$title || !$category_id || !$author_first || !$author_last) {
                throw new Exception('Title, category, and author are required');
            }
            
            $pdo->beginTransaction();
            
            // Insert book
            $stmt = $pdo->prepare("
                INSERT INTO books (title, isbn, category_id, description, total_copies, available_copies) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $isbn, $category_id, $description, $total_copies, $total_copies]);
            $book_id = $pdo->lastInsertId();
            
            // Check if author exists
            $stmt = $pdo->prepare("SELECT id FROM authors WHERE first_name = ? AND last_name = ?");
            $stmt->execute([$author_first, $author_last]);
            $author = $stmt->fetch();
            
            if (!$author) {
                // Create new author
                $stmt = $pdo->prepare("INSERT INTO authors (first_name, last_name) VALUES (?, ?)");
                $stmt->execute([$author_first, $author_last]);
                $author_id = $pdo->lastInsertId();
            } else {
                $author_id = $author['id'];
            }
            
            // Link book to author
            $stmt = $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
            $stmt->execute([$book_id, $author_id]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Book added successfully', 'book_id' => $book_id]);
            break;
            
        case 'add_category':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $name = $_POST['name'] ?? '';
            $color = $_POST['color'] ?? '#3498db';
            
            if (!$name) {
                throw new Exception('Category name is required');
            }
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, color) VALUES (?, ?)");
            $stmt->execute([$name, $color]);
            
            echo json_encode(['success' => true, 'message' => 'Category added successfully']);
            break;
            
        case 'delete_book':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $book_id = $_POST['book_id'] ?? '';
            
            if (!$book_id) {
                throw new Exception('Book ID is required');
            }
            
            $pdo->beginTransaction();
            
            // Delete book-author relationships
            $stmt = $pdo->prepare("DELETE FROM book_authors WHERE book_id = ?");
            $stmt->execute([$book_id]);
            
            // Delete the book
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
            break;
            
        case 'delete_category':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $category_id = $_POST['category_id'] ?? '';
            
            if (!$category_id) {
                throw new Exception('Category ID is required');
            }
            
            // Check if category has books
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM books WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $book_count = $stmt->fetch()['count'];
            
            if ($book_count > 0) {
                throw new Exception('Cannot delete category with existing books. Please reassign books first.');
            }
            
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            break;
            
        case 'return_book':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $borrowing_id = $_POST['borrowing_id'] ?? '';
            
            if (!$borrowing_id) {
                throw new Exception('Borrowing ID is required');
            }
            
            $pdo->beginTransaction();
            
            // Get borrowing details
            $stmt = $pdo->prepare("SELECT book_id FROM borrowings WHERE id = ?");
            $stmt->execute([$borrowing_id]);
            $borrowing = $stmt->fetch();
            
            if (!$borrowing) {
                throw new Exception('Borrowing not found');
            }
            
            // Update borrowing status
            $stmt = $pdo->prepare("UPDATE borrowings SET status = 'returned', returned_date = NOW() WHERE id = ?");
            $stmt->execute([$borrowing_id]);
            
            // Increase available copies
            $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
            $stmt->execute([$borrowing['book_id']]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Book returned successfully']);
            break;
            
        case 'send_reminder':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $borrowing_id = $_POST['borrowing_id'] ?? '';
            
            if (!$borrowing_id) {
                throw new Exception('Borrowing ID is required');
            }
            
            // In a real implementation, you would send an email here
            // For now, we'll just log the reminder
            $stmt = $pdo->prepare("
                INSERT INTO admin_logs (action, details, created_at) 
                VALUES ('reminder_sent', CONCAT('Reminder sent for borrowing ID: ', ?), NOW())
            ");
            $stmt->execute([$borrowing_id]);
            
            echo json_encode(['success' => true, 'message' => 'Reminder sent successfully']);
            break;
            
        case 'find_borrowing_by_book':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $bookCode = $_POST['book_code'] ?? '';
            
            if (!$bookCode) {
                throw new Exception('Book code is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT bo.*, b.title, b.book_code,
                       CONCAT(sr.first_name, ' ', sr.last_name) as student_name,
                       s.student_id, s.email,
                       DATEDIFF(CURDATE(), bo.due_date) as days_overdue
                FROM borrowings bo
                JOIN books b ON bo.book_id = b.id
                JOIN students s ON bo.student_id = s.student_id
                LEFT JOIN student_records sr ON s.student_id = sr.student_id
                WHERE b.book_code = ? AND bo.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$bookCode]);
            $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($borrowing) {
                $borrowing['days_overdue'] = max(0, (int)$borrowing['days_overdue']);
                $borrowing['fine_amount'] = $borrowing['days_overdue'] * 3.00;
                echo json_encode(['success' => true, 'borrowing' => $borrowing]);
                ?>
                <a href="student_borrowing.php" class="nav-link">👤 Process Borrowing</a>
                <a href="book_returns.php" class="nav-link">📖 Returns</a>
                <?php
            } else {
                echo json_encode(['success' => false, 'error' => 'No active borrowing found for this book']);
            }
            break;
            
        case 'search_student':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $searchTerm = $_POST['search_term'] ?? '';
            
            if (!$searchTerm) {
                throw new Exception('Search term is required');
            }
            
            // Search by student ID or name
            $stmt = $pdo->prepare("
                SELECT s.*, sr.first_name, sr.last_name
                FROM students s
                LEFT JOIN student_records sr ON s.student_id = sr.student_id
                WHERE s.student_id LIKE ? OR 
                      CONCAT(sr.first_name, ' ', sr.last_name) LIKE ? OR
                      sr.first_name LIKE ? OR
                      sr.last_name LIKE ?
                LIMIT 1
            ");
            $searchPattern = '%' . $searchTerm . '%';
            $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                throw new Exception('Student not found');
            }
            
            // Get borrowing eligibility
            $stmt = $pdo->prepare("
                SELECT * FROM student_borrowing_limits 
                WHERE student_id = ? AND semester = 'Fall' AND academic_year = '2024'
            ");
            $stmt->execute([$student['student_id']]);
            $limits = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$limits) {
                // Create limits record if doesn't exist
                $stmt = $pdo->prepare("
                    INSERT INTO student_borrowing_limits 
                    (student_id, semester, academic_year, books_borrowed_total, books_borrowed_current) 
                    VALUES (?, 'Fall', '2024', 0, 0)
                ");
                $stmt->execute([$student['student_id']]);
                
                $limits = [
                    'books_borrowed_total' => 0,
                    'books_borrowed_current' => 0,
                    'max_books_per_semester' => 20,
                    'max_books_concurrent' => 3
                ];
            }
            
            // Check pending fines
            $stmt = $pdo->prepare("
                SELECT SUM(fine_amount) as total_fines 
                FROM fines 
                WHERE student_id = ? AND status = 'pending'
            ");
            $stmt->execute([$student['student_id']]);
            $fineResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalFines = $fineResult['total_fines'] ?? 0;
            
            $eligibility = [
                'can_borrow' => true,
                'reasons' => [],
                'current_books' => $limits['books_borrowed_current'],
                'max_concurrent' => $limits['max_books_concurrent'],
                'semester_total' => $limits['books_borrowed_total'],
                'max_semester' => $limits['max_books_per_semester'],
                'pending_fines' => $totalFines
            ];
            
            // Check eligibility rules
            if ($limits['books_borrowed_current'] >= $limits['max_books_concurrent']) {
                $eligibility['can_borrow'] = false;
                $eligibility['reasons'][] = 'Maximum concurrent books limit reached (' . $limits['max_books_concurrent'] . ')';
            }
            
            if ($limits['books_borrowed_total'] >= $limits['max_books_per_semester']) {
                $eligibility['can_borrow'] = false;
                $eligibility['reasons'][] = 'Semester borrowing limit reached (' . $limits['max_books_per_semester'] . ')';
            }
            
            if ($totalFines > 0) {
                $eligibility['can_borrow'] = false;
                $eligibility['reasons'][] = 'Outstanding fines: $' . number_format($totalFines, 2);
            }
            
            echo json_encode([
                'success' => true, 
                'student' => $student,
                'eligibility' => $eligibility
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
