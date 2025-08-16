<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'history':
                        getBorrowingHistory($_GET['student_id'] ?? '');
                        break;
                    case 'active':
                        getActiveBorrowings($_GET['student_id'] ?? '');
                        break;
                    case 'overdue':
                        getOverdueBooks($_GET['student_id'] ?? '');
                        break;
                    case 'check_availability':
                        checkBookAvailability($_GET['book_id'] ?? '');
                        break;
                    default:
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'Invalid action']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action required']);
            }
            break;
        
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['action'])) {
                switch ($data['action']) {
                    case 'borrow':
                        borrowBook($data);
                        break;
                    case 'return':
                        returnBook($data);
                        break;
                    case 'renew':
                        renewBook($data);
                        break;
                    case 'pay_fine':
                        payFine($data);
                        break;
                    default:
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'Invalid action']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Action required']);
            }
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
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
