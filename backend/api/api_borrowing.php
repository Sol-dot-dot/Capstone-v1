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
            
            $available = ($book['available_copies'] - $book['current_borrowings']) > 0;
            $book['is_available'] = $available;
            
            echo json_encode(['success' => true, 'book' => $book]);
            break;

        case 'borrow_books':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $studentId = $_POST['student_id'] ?? '';
            $bookCodesJson = $_POST['book_codes'] ?? '';
            
            if (!$studentId || !$bookCodesJson) {
                throw new Exception('Student ID and book codes are required');
            }
            
            $bookCodes = json_decode($bookCodesJson, true);
            if (!is_array($bookCodes) || empty($bookCodes)) {
                throw new Exception('Invalid book codes format');
            }
            
            if (count($bookCodes) > 3) {
                throw new Exception('Maximum 3 books can be borrowed at once');
            }
            
            $pdo->beginTransaction();
            
            try {
                // Check student eligibility
                $stmt = $pdo->prepare("
                    SELECT books_borrowed_current, books_borrowed_total 
                    FROM student_borrowing_limits 
                    WHERE student_id = ? AND semester = 'Fall' AND academic_year = '2024'
                ");
                $stmt->execute([$studentId]);
                $limits = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$limits) {
                    $stmt = $pdo->prepare("
                        INSERT INTO student_borrowing_limits 
                        (student_id, semester, academic_year, books_borrowed_total, books_borrowed_current) 
                        VALUES (?, 'Fall', '2024', 0, 0)
                    ");
                    $stmt->execute([$studentId]);
                    $limits = ['books_borrowed_current' => 0, 'books_borrowed_total' => 0];
                }
                
                if ($limits['books_borrowed_current'] + count($bookCodes) > 3) {
                    throw new Exception('Would exceed maximum concurrent books limit (3)');
                }
                
                if ($limits['books_borrowed_total'] + count($bookCodes) > 20) {
                    throw new Exception('Would exceed semester borrowing limit (20)');
                }
                
                // Check for pending fines
                $stmt = $pdo->prepare("SELECT SUM(fine_amount) as total FROM fines WHERE student_id = ? AND status = 'pending'");
                $stmt->execute([$studentId]);
                $fines = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($fines['total'] > 0) {
                    throw new Exception('Student has pending fines: $' . number_format($fines['total'], 2));
                }
                
                $borrowedBooks = [];
                foreach ($bookCodes as $bookCode) {
                    // Get book details
                    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_code = ?");
                    $stmt->execute([$bookCode]);
                    $book = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$book) {
                        throw new Exception("Book not found: $bookCode");
                    }
                    
                    // Check availability
                    $stmt = $pdo->prepare("SELECT COUNT(*) as borrowed FROM borrowings WHERE book_id = ? AND status = 'active'");
                    $stmt->execute([$book['id']]);
                    $borrowed = $stmt->fetch(PDO::FETCH_ASSOC)['borrowed'];
                    
                    if ($borrowed >= $book['available_copies']) {
                        throw new Exception("Book not available: " . $book['title']);
                    }
                    
                    // Create borrowing record
                    $dueDate = date('Y-m-d', strtotime('+3 days'));
                    $stmt = $pdo->prepare("
                        INSERT INTO borrowings (student_id, book_id, borrowed_date, due_date, status, semester, academic_year) 
                        VALUES (?, ?, NOW(), ?, 'active', 'Fall', '2024')
                    ");
                    $stmt->execute([$studentId, $book['id'], $dueDate]);
                    
                    $borrowedBooks[] = $book['title'];
                }
                
                // Update student limits
                $stmt = $pdo->prepare("
                    UPDATE student_borrowing_limits 
                    SET books_borrowed_current = books_borrowed_current + ?, 
                        books_borrowed_total = books_borrowed_total + ? 
                    WHERE student_id = ? AND semester = 'Fall' AND academic_year = '2024'
                ");
                $stmt->execute([count($bookCodes), count($bookCodes), $studentId]);
                
                $pdo->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Successfully borrowed ' . count($bookCodes) . ' book(s)',
                    'books' => $borrowedBooks
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'get_borrowed_books':
            $studentId = $_GET['student_id'] ?? '';
            if (!$studentId) {
                throw new Exception('Student ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT bo.*, b.title, b.book_code, b.isbn,
                       CONCAT(a.first_name, ' ', a.last_name) as author_name,
                       DATEDIFF(CURDATE(), bo.due_date) as days_overdue
                FROM borrowings bo
                JOIN books b ON bo.book_id = b.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                WHERE bo.student_id = ? AND bo.status = 'active'
                ORDER BY bo.due_date ASC
            ");
            $stmt->execute([$studentId]);
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($books as &$book) {
                $book['days_overdue'] = max(0, (int)$book['days_overdue']);
                $book['fine_amount'] = $book['days_overdue'] * 3.00;
            }
            
            echo json_encode(['success' => true, 'borrowed_books' => $books]);
            break;

        case 'get_fines':
            $studentId = $_GET['student_id'] ?? '';
            if (!$studentId) {
                throw new Exception('Student ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT f.*, b.title, b.book_code 
                FROM fines f
                JOIN borrowings bo ON f.borrowing_id = bo.id
                JOIN books b ON bo.book_id = b.id
                WHERE f.student_id = ? AND f.status = 'pending'
                ORDER BY f.created_date DESC
            ");
            $stmt->execute([$studentId]);
            $fines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'fines' => $fines]);
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
            
            try {
                // Get borrowing details
                $stmt = $pdo->prepare("
                    SELECT * FROM borrowings 
                    WHERE id = ? AND student_id = ? AND status = 'active'
                ");
                $stmt->execute([$borrowingId, $studentId]);
                $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$borrowing) {
                    throw new Exception('Borrowing record not found');
                }
                
                // Calculate fine if overdue
                $fine = calculateFine($borrowing['due_date']);
                
                // Update borrowing record
                $stmt = $pdo->prepare("
                    UPDATE borrowings 
                    SET status = 'returned', returned_date = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$borrowingId]);
                
                // Create fine record if applicable
                if ($fine['fine_amount'] > 0) {
                    $stmt = $pdo->prepare("
                        INSERT INTO fines (student_id, borrowing_id, fine_amount, reason, status, created_date) 
                        VALUES (?, ?, ?, 'Overdue return', 'pending', NOW())
                    ");
                    $stmt->execute([$studentId, $borrowingId, $fine['fine_amount']]);
                }
                
                // Update student limits
                $stmt = $pdo->prepare("
                    UPDATE student_borrowing_limits 
                    SET books_borrowed_current = books_borrowed_current - 1 
                    WHERE student_id = ? AND semester = 'Fall' AND academic_year = '2024'
                ");
                $stmt->execute([$studentId]);
                
                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'fine_amount' => $fine['fine_amount'],
                    'days_overdue' => $fine['days_overdue']
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
        
        default:
            throw new Exception('Invalid action: ' . $action);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getBorrowingHistory($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT bo.*, b.title, b.isbn, c.name as category_name,
               GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               DATEDIFF(COALESCE(bo.returned_date, CURDATE()), bo.due_date) as days_overdue
        FROM borrowings bo
        JOIN books b ON bo.book_id = b.id
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors ba ON b.id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.id
        WHERE bo.student_id = ?
        GROUP BY bo.id
        ORDER BY bo.borrowed_date DESC
    ");
    
    $stmt->execute([$student_id]);
    $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($borrowings as &$borrowing) {
        $borrowing['is_overdue'] = $borrowing['status'] === 'active' && $borrowing['days_overdue'] > 0;
        $borrowing['days_overdue'] = max(0, (int)$borrowing['days_overdue']);
    }
    
    echo json_encode(['success' => true, 'borrowings' => $borrowings]);
}

function getActiveBorrowings($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT bo.*, b.title, b.isbn, c.name as category_name, c.color as category_color,
               GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               DATEDIFF(bo.due_date, CURDATE()) as days_remaining,
               DATEDIFF(CURDATE(), bo.due_date) as days_overdue
        FROM borrowings bo
        JOIN books b ON bo.book_id = b.id
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors ba ON b.id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.id
        WHERE bo.student_id = ? AND bo.status = 'active'
        GROUP BY bo.id
        ORDER BY bo.due_date ASC
    ");
    
    $stmt->execute([$student_id]);
    $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($borrowings as &$borrowing) {
        $borrowing['is_overdue'] = (int)$borrowing['days_overdue'] > 0;
        $borrowing['days_remaining'] = max(0, (int)$borrowing['days_remaining']);
        $borrowing['days_overdue'] = max(0, (int)$borrowing['days_overdue']);
        
        if ($borrowing['is_overdue']) {
            $borrowing['status_display'] = 'Overdue';
            $borrowing['status_color'] = '#e74c3c';
        } elseif ($borrowing['days_remaining'] <= 3) {
            $borrowing['status_display'] = 'Due Soon';
            $borrowing['status_color'] = '#f39c12';
        } else {
            $borrowing['status_display'] = 'Active';
            $borrowing['status_color'] = '#27ae60';
        }
    }
    
    echo json_encode(['success' => true, 'borrowings' => $borrowings]);
}

function getOverdueBooks($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT bo.*, b.title, b.isbn,
               GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               DATEDIFF(CURDATE(), bo.due_date) as days_overdue,
               (DATEDIFF(CURDATE(), bo.due_date) * 5.00) as fine_amount
        FROM borrowings bo
        JOIN books b ON bo.book_id = b.id
        LEFT JOIN book_authors ba ON b.id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.id
        WHERE bo.student_id = ? AND bo.status = 'active' AND bo.due_date < CURDATE()
        GROUP BY bo.id
        ORDER BY bo.due_date ASC
    ");
    
    $stmt->execute([$student_id]);
    $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($overdue as &$book) {
        $book['days_overdue'] = (int)$book['days_overdue'];
        $book['fine_amount'] = round((float)$book['fine_amount'], 2);
    }
    
    echo json_encode(['success' => true, 'overdue_books' => $overdue]);
}

function checkBookAvailability($book_id) {
    global $pdo;
    
    if (!$book_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Book ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT available_copies, total_copies FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Book not found']);
        return;
    }
    
    $available = (int)$book['available_copies'] > 0;
    echo json_encode([
        'success' => true, 
        'available' => $available,
        'available_copies' => (int)$book['available_copies'],
        'total_copies' => (int)$book['total_copies']
    ]);
}

function borrowBook($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    $book_id = $data['book_id'] ?? '';
    
    if (!$student_id || !$book_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID and Book ID required']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Check if student exists
        $stmt = $pdo->prepare("SELECT status FROM student_records WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            throw new Exception('Student not found');
        }
        
        if ($student['status'] !== 'active') {
            throw new Exception('Student account is not active');
        }
        
        // Check if book is available
        $stmt = $pdo->prepare("SELECT available_copies, title FROM books WHERE id = ? AND status = 'active'");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();
        
        if (!$book) {
            throw new Exception('Book not found or not available');
        }
        
        if ((int)$book['available_copies'] <= 0) {
            throw new Exception('Book is currently not available');
        }
        
        // Check if student already has this book
        $stmt = $pdo->prepare("SELECT id FROM borrowings WHERE student_id = ? AND book_id = ? AND status = 'active'");
        $stmt->execute([$student_id, $book_id]);
        if ($stmt->fetch()) {
            throw new Exception('You already have this book borrowed');
        }
        
        // Check borrowing limit (max 5 active borrowings)
        $stmt = $pdo->prepare("SELECT COUNT(*) as active_count FROM borrowings WHERE student_id = ? AND status = 'active'");
        $stmt->execute([$student_id]);
        $count = $stmt->fetch();
        
        if ((int)$count['active_count'] >= 5) {
            throw new Exception('You have reached the maximum borrowing limit (5 books)');
        }
        
        // Create borrowing record
        $due_date = date('Y-m-d', strtotime('+30 days'));
        $stmt = $pdo->prepare("
            INSERT INTO borrowings (student_id, book_id, borrowed_date, due_date, status) 
            VALUES (?, ?, NOW(), ?, 'active')
        ");
        $stmt->execute([$student_id, $book_id, $due_date]);
        
        // Update book availability
        $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
        $stmt->execute([$book_id]);
        
        // Add notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (student_id, title, message, type) 
            VALUES (?, 'Book Borrowed', ?, 'info')
        ");
        $stmt->execute([$student_id, "You have successfully borrowed '{$book['title']}'. Due date: $due_date"]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Book borrowed successfully',
            'due_date' => $due_date
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function returnBook($data) {
    global $pdo;
    
    $borrowing_id = $data['borrowing_id'] ?? '';
    $student_id = $data['student_id'] ?? '';
    
    if (!$borrowing_id || !$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Borrowing ID and Student ID required']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Get borrowing details
        $stmt = $pdo->prepare("
            SELECT bo.*, b.title 
            FROM borrowings bo 
            JOIN books b ON bo.book_id = b.id 
            WHERE bo.id = ? AND bo.student_id = ? AND bo.status = 'active'
        ");
        $stmt->execute([$borrowing_id, $student_id]);
        $borrowing = $stmt->fetch();
        
        if (!$borrowing) {
            throw new Exception('Borrowing record not found or already returned');
        }
        
        // Calculate fine if overdue
        $fine_amount = 0;
        $days_overdue = max(0, (strtotime('now') - strtotime($borrowing['due_date'])) / (60 * 60 * 24));
        if ($days_overdue > 0) {
            $fine_amount = $days_overdue * 5.00; // ₱5 per day
        }
        
        // Update borrowing record
        $stmt = $pdo->prepare("
            UPDATE borrowings 
            SET status = 'returned', returned_date = NOW(), fine_amount = ? 
            WHERE id = ?
        ");
        $stmt->execute([$fine_amount, $borrowing_id]);
        
        // Update book availability
        $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
        $stmt->execute([$borrowing['book_id']]);
        
        // Add notification
        $message = "You have successfully returned '{$borrowing['title']}'.";
        if ($fine_amount > 0) {
            $message .= " Fine amount: ₱" . number_format($fine_amount, 2);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (student_id, title, message, type) 
            VALUES (?, 'Book Returned', ?, ?)
        ");
        $stmt->execute([$student_id, $message, $fine_amount > 0 ? 'warning' : 'info']);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Book returned successfully',
            'fine_amount' => $fine_amount,
            'days_overdue' => (int)$days_overdue
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function renewBook($data) {
    global $pdo;
    
    $borrowing_id = $data['borrowing_id'] ?? '';
    $student_id = $data['student_id'] ?? '';
    
    if (!$borrowing_id || !$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Borrowing ID and Student ID required']);
        return;
    }
    
    try {
        // Get borrowing details
        $stmt = $pdo->prepare("
            SELECT bo.*, b.title 
            FROM borrowings bo 
            JOIN books b ON bo.book_id = b.id 
            WHERE bo.id = ? AND bo.student_id = ? AND bo.status = 'active'
        ");
        $stmt->execute([$borrowing_id, $student_id]);
        $borrowing = $stmt->fetch();
        
        if (!$borrowing) {
            throw new Exception('Borrowing record not found');
        }
        
        if ((int)$borrowing['renewal_count'] >= 2) {
            throw new Exception('Maximum renewal limit reached (2 renewals)');
        }
        
        // Check if overdue
        if (strtotime($borrowing['due_date']) < strtotime('now')) {
            throw new Exception('Cannot renew overdue books. Please return and pay fine first.');
        }
        
        // Extend due date by 15 days
        $new_due_date = date('Y-m-d', strtotime($borrowing['due_date'] . ' +15 days'));
        
        $stmt = $pdo->prepare("
            UPDATE borrowings 
            SET due_date = ?, renewal_count = renewal_count + 1 
            WHERE id = ?
        ");
        $stmt->execute([$new_due_date, $borrowing_id]);
        
        // Add notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (student_id, title, message, type) 
            VALUES (?, 'Book Renewed', ?, 'info')
        ");
        $stmt->execute([$student_id, "'{$borrowing['title']}' has been renewed. New due date: $new_due_date"]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Book renewed successfully',
            'new_due_date' => $new_due_date,
            'renewal_count' => (int)$borrowing['renewal_count'] + 1
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function payFine($data) {
    global $pdo;
    
    $borrowing_id = $data['borrowing_id'] ?? '';
    $student_id = $data['student_id'] ?? '';
    $amount = $data['amount'] ?? 0;
    
    if (!$borrowing_id || !$student_id || !$amount) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Borrowing ID, Student ID, and amount required']);
        return;
    }
    
    try {
        // Update borrowing record
        $stmt = $pdo->prepare("
            UPDATE borrowings 
            SET fine_paid = fine_paid + ?, fine_status = CASE WHEN fine_paid + ? >= fine_amount THEN 'paid' ELSE 'partial' END
            WHERE id = ? AND student_id = ?
        ");
        $stmt->execute([$amount, $amount, $borrowing_id, $student_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Borrowing record not found');
        }
        
        // Add notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (student_id, title, message, type) 
            VALUES (?, 'Fine Payment', ?, 'info')
        ");
        $stmt->execute([$student_id, "Fine payment of ₱" . number_format($amount, 2) . " has been processed."]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Fine payment processed successfully',
            'amount_paid' => $amount
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
