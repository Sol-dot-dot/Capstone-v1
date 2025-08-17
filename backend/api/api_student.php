<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'profile';

try {
    switch ($action) {
        case 'profile':
            $student_id = $_GET['student_id'] ?? '';
            if (empty($student_id)) {
                echo json_encode(['success' => false, 'error' => 'Student ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT sr.*, s.email, s.last_login, s.account_status
                FROM student_records sr
                LEFT JOIN students s ON sr.student_id = s.student_id
                WHERE sr.student_id = ?
            ");
            $stmt->execute([$student_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($profile) {
                echo json_encode(['success' => true, 'profile' => $profile]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Student not found']);
            }
            break;
            
        case 'borrowings':
            $student_id = $_GET['student_id'] ?? '';
            if (empty($student_id)) {
                echo json_encode(['success' => false, 'error' => 'Student ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT b.*, bk.title, bk.cover_image, bk.isbn, a.name as author_name
                FROM borrowings b
                JOIN books bk ON b.book_id = bk.id
                LEFT JOIN book_authors ba ON bk.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                WHERE b.student_id = ?
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([$student_id]);
            $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'borrowings' => $borrowings]);
            break;
            
        case 'bookmarks':
            $student_id = $_GET['student_id'] ?? '';
            if (empty($student_id)) {
                echo json_encode(['success' => false, 'error' => 'Student ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT bm.*, b.title, b.cover_image, b.isbn, b.rating, a.name as author_name, c.name as category_name
                FROM bookmarks bm
                JOIN books b ON bm.book_id = b.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id
                LEFT JOIN authors a ON ba.author_id = a.id
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE bm.student_id = ?
                ORDER BY bm.created_at DESC
            ");
            $stmt->execute([$student_id]);
            $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'bookmarks' => $bookmarks]);
            break;
            
        case 'notifications':
            $student_id = $_GET['student_id'] ?? '';
            if (empty($student_id)) {
                echo json_encode(['success' => false, 'error' => 'Student ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM notifications
                WHERE student_id = ?
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$student_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;
            
        case 'dashboard_stats':
            $student_id = $_GET['student_id'] ?? '';
            if (empty($student_id)) {
                echo json_encode(['success' => false, 'error' => 'Student ID required']);
                break;
            }
            
            // Get active borrowings count
            $stmt = $pdo->prepare("SELECT COUNT(*) as active_borrowings FROM borrowings WHERE student_id = ? AND status = 'active'");
            $stmt->execute([$student_id]);
            $active_borrowings = $stmt->fetch()['active_borrowings'];
            
            // Get bookmarks count
            $stmt = $pdo->prepare("SELECT COUNT(*) as bookmarks_count FROM bookmarks WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $bookmarks_count = $stmt->fetch()['bookmarks_count'];
            
            // Get overdue books count
            $stmt = $pdo->prepare("SELECT COUNT(*) as overdue_count FROM borrowings WHERE student_id = ? AND status = 'overdue'");
            $stmt->execute([$student_id]);
            $overdue_count = $stmt->fetch()['overdue_count'];
            
            // Get unread notifications count
            $stmt = $pdo->prepare("SELECT COUNT(*) as unread_notifications FROM notifications WHERE student_id = ? AND is_read = FALSE");
            $stmt->execute([$student_id]);
            $unread_notifications = $stmt->fetch()['unread_notifications'];
            
            $stats = [
                'active_borrowings' => $active_borrowings,
                'bookmarks_count' => $bookmarks_count,
                'overdue_count' => $overdue_count,
                'unread_notifications' => $unread_notifications
            ];
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
