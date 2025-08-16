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
                    case 'profile':
                        getProfile($_GET['student_id'] ?? '');
                        break;
                    case 'bookmarks':
                        getBookmarks($_GET['student_id'] ?? '');
                        break;
                    case 'notifications':
                        getNotifications($_GET['student_id'] ?? '', $_GET['mark_read'] ?? '');
                        break;
                    case 'stats':
                        getStats($_GET['student_id'] ?? '');
                        break;
                    case 'reading_stats':
                        getReadingStats($_GET['student_id'] ?? '');
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
                    case 'update_profile':
                        updateProfile($data);
                        break;
                    case 'mark_notification_read':
                        markNotificationRead($data);
                        break;
                    case 'mark_notifications_read':
                        markAllNotificationsRead($data);
                        break;
                    case 'update_preferences':
                        updateReadingPreferences($data);
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

function getProfile($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Get student profile
    $stmt = $pdo->prepare("
        SELECT sr.*, s.email, s.created_at as registration_date
        FROM student_records sr
        LEFT JOIN students s ON sr.student_id = s.student_id
        WHERE sr.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Student not found']);
        return;
    }
    
    // Get borrowing statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_borrowed,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as currently_borrowed,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as total_returned,
            COUNT(CASE WHEN status = 'active' AND due_date < CURDATE() THEN 1 END) as overdue_count,
            COALESCE(SUM(CASE WHEN fine_amount > 0 THEN fine_amount END), 0) as total_fines,
            COALESCE(SUM(CASE WHEN fine_amount > 0 THEN fine_paid END), 0) as fines_paid
        FROM borrowings 
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get favorite categories
    $stmt = $pdo->prepare("
        SELECT c.name, c.color, AVG(rp.preference_score) as avg_score
        FROM reading_preferences rp
        JOIN categories c ON rp.category_id = c.id
        WHERE rp.student_id = ?
        GROUP BY c.id
        ORDER BY avg_score DESC
        LIMIT 3
    ");
    $stmt->execute([$student_id]);
    $favorite_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $profile['stats'] = $stats;
    $profile['favorite_categories'] = $favorite_categories;
    $profile['outstanding_fines'] = (float)$stats['total_fines'] - (float)$stats['fines_paid'];
    
    echo json_encode(['success' => true, 'profile' => $profile]);
}

function getBookmarks($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT b.*, c.name as category_name, c.color as category_color,
               GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               COALESCE(AVG(br.rating), 0) as avg_rating,
               COUNT(br.id) as review_count,
               bm.created_at as bookmarked_date
        FROM bookmarks bm
        JOIN books b ON bm.book_id = b.id
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors ba ON b.id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.id
        LEFT JOIN book_reviews br ON b.id = br.book_id
        WHERE bm.student_id = ? AND b.status = 'active'
        GROUP BY b.id, bm.id
        ORDER BY bm.created_at DESC
    ");
    
    $stmt->execute([$student_id]);
    $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($bookmarks as &$bookmark) {
        $bookmark['avg_rating'] = round((float)$bookmark['avg_rating'], 1);
        $bookmark['is_available'] = $bookmark['available_copies'] > 0;
    }
    
    echo json_encode(['success' => true, 'bookmarks' => $bookmarks]);
}

function getNotifications($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE student_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$student_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark as read if requested
    if (isset($_GET['mark_read']) && $_GET['mark_read'] === 'true') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0");
        $stmt->execute([$student_id]);
    }
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
}

function getStats($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Get basic stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_borrowed,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_borrowings,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as total_returned
        FROM borrowings 
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $borrowing_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get bookmarks count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_bookmarks FROM bookmarks WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $bookmark_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get reviews count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_reviews FROM book_reviews WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $review_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats = array_merge($borrowing_stats, $bookmark_stats, $review_stats);
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

function getReadingStats($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Monthly borrowing stats (last 6 months)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(borrowed_date, '%Y-%m') as month,
            COUNT(*) as books_borrowed
        FROM borrowings 
        WHERE student_id = ? AND borrowed_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(borrowed_date, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute([$student_id]);
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Category preferences
    $stmt = $pdo->prepare("
        SELECT c.name, c.color, COUNT(b.id) as books_read
        FROM borrowings bo
        JOIN books b ON bo.book_id = b.id
        JOIN categories c ON b.category_id = c.id
        WHERE bo.student_id = ? AND bo.status = 'returned'
        GROUP BY c.id
        ORDER BY books_read DESC
    ");
    $stmt->execute([$student_id]);
    $category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reading streak (consecutive days with active borrowings)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT DATE(borrowed_date)) as reading_days
        FROM borrowings 
        WHERE student_id = ? AND borrowed_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$student_id]);
    $reading_streak = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'stats' => [
            'monthly_borrowing' => $monthly_stats,
            'category_preferences' => $category_stats,
            'reading_days_this_month' => (int)$reading_streak['reading_days']
        ]
    ]);
}

function updateProfile($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    $first_name = $data['first_name'] ?? '';
    $last_name = $data['last_name'] ?? '';
    $course = $data['course'] ?? '';
    $year_level = $data['year_level'] ?? '';
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE student_records 
            SET first_name = ?, last_name = ?, course = ?, year_level = ?, updated_at = CURRENT_TIMESTAMP
            WHERE student_id = ?
        ");
        $stmt->execute([$first_name, $last_name, $course, $year_level, $student_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Student not found or no changes made');
        }
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function markNotificationRead($data) {
    global $pdo;
    
    $notification_id = $data['notification_id'] ?? '';
    $student_id = $data['student_id'] ?? '';
    
    if (!$notification_id || !$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Notification ID and Student ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND student_id = ?
        ");
        $stmt->execute([$notification_id, $student_id]);
        
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function updateReadingPreferences($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    $preferences = $data['preferences'] ?? [];
    
    if (!$student_id || empty($preferences)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID and preferences required']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Clear existing preferences
        $stmt = $pdo->prepare("DELETE FROM reading_preferences WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        // Insert new preferences
        $stmt = $pdo->prepare("
            INSERT INTO reading_preferences (student_id, category_id, preference_score) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($preferences as $pref) {
            $stmt->execute([$student_id, $pref['category_id'], $pref['score']]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Reading preferences updated successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function markAllNotificationsRead($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0");
        $stmt->execute([$student_id]);
        
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
