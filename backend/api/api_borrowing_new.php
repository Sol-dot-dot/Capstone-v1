<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper function to get current semester config
function getCurrentSemesterConfig($pdo) {
    $stmt = $pdo->query("SELECT config_key, config_value FROM borrowing_config");
    $config = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['config_key']] = $row['config_value'];
    }
    return $config;
}

// Helper function to calculate fines
function calculateFine($dueDate, $dailyRate = 3.00) {
    $now = new DateTime();
    $due = new DateTime($dueDate);
    
    if ($now <= $due) {
        return ['days_overdue' => 0, 'fine_amount' => 0.00];
    }
    
    $daysOverdue = $now->diff($due)->days;
    $fineAmount = $daysOverdue * $dailyRate;
    
    return ['days_overdue' => $daysOverdue, 'fine_amount' => $fineAmount];
}

try {
    switch ($action) {
        case 'scan_book':
            // Scan book by code and get book details
            $bookCode = $_POST['book_code'] ?? $_GET['book_code'] ?? '';
            
            if (!$bookCode) {
                throw new Exception('Book code is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT b.*, c.name as category_name, c.color as category_color,
                       CONCAT(a.first_name, ' ', a.last_name) as author_name,
                       (SELECT COUNT(*) FROM borrowings WHERE book_id = b.id AND status = 'active') as current_borrowings
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                WHERE b.book_code = ?
            ");
            $stmt->execute([$bookCode]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$book) {
                throw new Exception('Book not found with code: ' . $bookCode);
            }
            
            // Check if book is available
            $available = ($book['available_copies'] - $book['current_borrowings']) > 0;
            $book['is_available'] = $available;
            
            echo json_encode(['success' => true, 'book' => $book]);
            break;
            
        case 'check_borrowing_eligibility':
            $studentId = $_POST['student_id'] ?? $_GET['student_id'] ?? '';
            
            if (!$studentId) {
                throw new Exception('Student ID is required');
            }
            
            $config = getCurrentSemesterConfig($pdo);
            
            // Get student's current borrowing status
            $stmt = $pdo->prepare("
                SELECT * FROM student_borrowing_limits 
                WHERE student_id = ? AND semester = ? AND academic_year = ?
            ");
            $stmt->execute([$studentId, $config['current_semester'], $config['current_academic_year']]);
            $limits = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$limits) {
                // Create limits record if doesn't exist
                $stmt = $pdo->prepare("
                    INSERT INTO student_borrowing_limits 
                    (student_id, semester, academic_year, books_borrowed_total, books_borrowed_current) 
                    VALUES (?, ?, ?, 0, 0)
                ");
                $stmt->execute([$studentId, $config['current_semester'], $config['current_academic_year']]);
                
                $limits = [
                    'books_borrowed_total' => 0,
                    'books_borrowed_current' => 0,
                    'max_books_per_semester' => $config['max_books_per_semester'],
                    'max_books_concurrent' => $config['max_books_concurrent']
                ];
            }
            
            // Check pending fines
            $stmt = $pdo->prepare("
                SELECT SUM(fine_amount) as total_fines 
                FROM fines 
                WHERE student_id = ? AND status = 'pending'
            ");
            $stmt->execute([$studentId]);
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
            
            echo json_encode(['success' => true, 'eligibility' => $eligibility]);
            break;
            
        case 'borrow_books':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $studentId = $_POST['student_id'] ?? '';
            $bookCodes = json_decode($_POST['book_codes'] ?? '[]', true);
            
            if (!$studentId || empty($bookCodes)) {
                throw new Exception('Student ID and book codes are required');
            }
            
            if (count($bookCodes) > 3) {
                throw new Exception('Cannot borrow more than 3 books at once');
            }
            
            $config = getCurrentSemesterConfig($pdo);
            
            $pdo->beginTransaction();
            
            // Check eligibility again
            $stmt = $pdo->prepare("
                SELECT * FROM student_borrowing_limits 
                WHERE student_id = ? AND semester = ? AND academic_year = ?
            ");
            $stmt->execute([$studentId, $config['current_semester'], $config['current_academic_year']]);
            $limits = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($limits['books_borrowed_current'] + count($bookCodes) > $limits['max_books_concurrent']) {
                throw new Exception('Would exceed concurrent borrowing limit');
            }
            
            if ($limits['books_borrowed_total'] + count($bookCodes) > $limits['max_books_per_semester']) {
                throw new Exception('Would exceed semester borrowing limit');
            }
            
            $borrowedBooks = [];
            $borrowedDate = new DateTime();
            $dueDate = clone $borrowedDate;
            $dueDate->add(new DateInterval('P' . $config['loan_duration_days'] . 'D'));
            
            foreach ($bookCodes as $bookCode) {
                // Get book details
                $stmt = $pdo->prepare("SELECT * FROM books WHERE book_code = ?");
                $stmt->execute([$bookCode]);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$book) {
                    throw new Exception('Book not found: ' . $bookCode);
                }
                
                // Check availability
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as borrowed FROM borrowings 
                    WHERE book_id = ? AND status = 'active'
                ");
                $stmt->execute([$book['id']]);
                $currentBorrowings = $stmt->fetch(PDO::FETCH_ASSOC)['borrowed'];
                
                if (($book['available_copies'] - $currentBorrowings) <= 0) {
                    throw new Exception('Book not available: ' . $book['title']);
                }
                
                // Create borrowing record
                $stmt = $pdo->prepare("
                    INSERT INTO borrowings 
                    (student_id, book_id, book_code, borrowed_date, due_date, semester, academic_year) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $studentId, 
                    $book['id'], 
                    $bookCode, 
                    $borrowedDate->format('Y-m-d H:i:s'), 
                    $dueDate->format('Y-m-d H:i:s'),
                    $config['current_semester'],
                    $config['current_academic_year']
                ]);
                
                $borrowedBooks[] = [
                    'id' => $pdo->lastInsertId(),
                    'book_code' => $bookCode,
                    'title' => $book['title'],
                    'due_date' => $dueDate->format('Y-m-d H:i:s')
                ];
            }
            
            // Update student borrowing limits
            $stmt = $pdo->prepare("
                UPDATE student_borrowing_limits 
                SET books_borrowed_total = books_borrowed_total + ?, 
                    books_borrowed_current = books_borrowed_current + ?
                WHERE student_id = ? AND semester = ? AND academic_year = ?
            ");
            $stmt->execute([
                count($bookCodes), 
                count($bookCodes), 
                $studentId, 
                $config['current_semester'], 
                $config['current_academic_year']
            ]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Books borrowed successfully',
                'borrowed_books' => $borrowedBooks,
                'due_date' => $dueDate->format('Y-m-d H:i:s')
            ]);
            break;
            
        case 'get_borrowed_books':
            $studentId = $_GET['student_id'] ?? '';
            
            if (!$studentId) {
                throw new Exception('Student ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT br.*, b.title, b.book_code, b.cover_image,
                       CONCAT(a.first_name, ' ', a.last_name) as author_name,
                       c.name as category_name, c.color as category_color,
                       DATEDIFF(NOW(), br.due_date) as days_overdue
                FROM borrowings br
                LEFT JOIN books b ON br.book_id = b.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE br.student_id = ? AND br.status = 'active'
                ORDER BY br.due_date ASC
            ");
            $stmt->execute([$studentId]);
            $borrowedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate fines for overdue books
            foreach ($borrowedBooks as &$book) {
                if ($book['days_overdue'] > 0) {
                    $book['status'] = 'overdue';
                    $fineInfo = calculateFine($book['due_date']);
                    $book['fine_amount'] = $fineInfo['fine_amount'];
                } else {
                    $book['fine_amount'] = 0;
                }
            }
            
            echo json_encode(['success' => true, 'borrowed_books' => $borrowedBooks]);
            break;
            
        case 'return_book':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $borrowingId = $_POST['borrowing_id'] ?? '';
            $studentId = $_POST['student_id'] ?? '';
            
            if (!$borrowingId || !$studentId) {
                throw new Exception('Borrowing ID and Student ID are required');
            }
            
            $pdo->beginTransaction();
            
            // Get borrowing details
            $stmt = $pdo->prepare("
                SELECT * FROM borrowings 
                WHERE id = ? AND student_id = ? AND status = 'active'
            ");
            $stmt->execute([$borrowingId, $studentId]);
            $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$borrowing) {
                throw new Exception('Active borrowing not found');
            }
            
            // Calculate fine if overdue
            $fineInfo = calculateFine($borrowing['due_date']);
            
            // Update borrowing status
            $stmt = $pdo->prepare("
                UPDATE borrowings 
                SET status = 'returned', returned_date = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$borrowingId]);
            
            // Create fine record if overdue
            if ($fineInfo['days_overdue'] > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO fines 
                    (borrowing_id, student_id, fine_amount, days_overdue, daily_fine_rate) 
                    VALUES (?, ?, ?, ?, 3.00)
                ");
                $stmt->execute([
                    $borrowingId, 
                    $studentId, 
                    $fineInfo['fine_amount'], 
                    $fineInfo['days_overdue']
                ]);
            }
            
            // Update student borrowing limits
            $config = getCurrentSemesterConfig($pdo);
            $stmt = $pdo->prepare("
                UPDATE student_borrowing_limits 
                SET books_borrowed_current = books_borrowed_current - 1
                WHERE student_id = ? AND semester = ? AND academic_year = ?
            ");
            $stmt->execute([$studentId, $config['current_semester'], $config['current_academic_year']]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Book returned successfully',
                'fine_amount' => $fineInfo['fine_amount'],
                'days_overdue' => $fineInfo['days_overdue']
            ]);
            break;
            
        case 'get_fines':
            $studentId = $_GET['student_id'] ?? '';
            
            if (!$studentId) {
                throw new Exception('Student ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT f.*, br.book_code, b.title as book_title
                FROM fines f
                LEFT JOIN borrowings br ON f.borrowing_id = br.id
                LEFT JOIN books b ON br.book_id = b.id
                WHERE f.student_id = ?
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([$studentId]);
            $fines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalPending = 0;
            foreach ($fines as $fine) {
                if ($fine['status'] === 'pending') {
                    $totalPending += $fine['fine_amount'];
                }
            }
            
            echo json_encode([
                'success' => true, 
                'fines' => $fines,
                'total_pending' => $totalPending
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
