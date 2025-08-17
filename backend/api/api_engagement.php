<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'reading_streak':
                        getReadingStreak($_GET['student_id'] ?? '');
                        break;
                    case 'achievements':
                        getAchievements($_GET['student_id'] ?? '');
                        break;
                    case 'leaderboard':
                        getLeaderboard();
                        break;
                    case 'reading_goals':
                        getReadingGoals($_GET['student_id'] ?? '');
                        break;
                    case 'analytics':
                        getReadingAnalytics($_GET['student_id'] ?? '');
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
                    case 'start_reading_session':
                        startReadingSession($data);
                        break;
                    case 'end_reading_session':
                        endReadingSession($data);
                        break;
                    case 'set_reading_goal':
                        setReadingGoal($data);
                        break;
                    case 'log_engagement':
                        logEngagement($data);
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

function getReadingStreak($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Calculate current reading streak
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT DATE(borrowed_date)) as days_with_activity,
            MAX(borrowed_date) as last_activity,
            DATEDIFF(CURDATE(), MAX(borrowed_date)) as days_since_last
        FROM borrowings 
        WHERE student_id = ? 
        AND borrowed_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$student_id]);
    $streak_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get weekly reading pattern
    $stmt = $pdo->prepare("
        SELECT 
            DAYOFWEEK(borrowed_date) as day_of_week,
            COUNT(*) as books_borrowed
        FROM borrowings 
        WHERE student_id = ? 
        AND borrowed_date >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
        GROUP BY DAYOFWEEK(borrowed_date)
        ORDER BY day_of_week
    ");
    $stmt->execute([$student_id]);
    $weekly_pattern = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate streak score
    $streak_score = min(100, $streak_data['days_with_activity'] * 3.33);
    $is_active_streak = $streak_data['days_since_last'] <= 2;
    
    echo json_encode([
        'success' => true,
        'streak' => [
            'current_streak' => $is_active_streak ? $streak_data['days_with_activity'] : 0,
            'longest_streak' => $streak_data['days_with_activity'],
            'streak_score' => round($streak_score, 1),
            'last_activity' => $streak_data['last_activity'],
            'is_active' => $is_active_streak,
            'weekly_pattern' => $weekly_pattern
        ]
    ]);
}

function getAchievements($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Get user stats for achievement calculation
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_borrowed,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as books_completed,
            COUNT(DISTINCT category_id) as categories_explored,
            COALESCE(AVG(CASE WHEN br.rating IS NOT NULL THEN br.rating END), 0) as avg_rating_given
        FROM borrowings b
        LEFT JOIN books bk ON b.book_id = bk.id
        LEFT JOIN book_reviews br ON b.book_id = br.book_id AND b.student_id = br.student_id
        WHERE b.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Define achievements
    $achievements = [
        [
            'id' => 'first_book',
            'title' => 'First Steps',
            'description' => 'Borrowed your first book',
            'icon' => '📚',
            'unlocked' => $stats['total_borrowed'] >= 1,
            'progress' => min(100, $stats['total_borrowed'] * 100),
            'target' => 1
        ],
        [
            'id' => 'bookworm',
            'title' => 'Bookworm',
            'description' => 'Read 10 books',
            'icon' => '🐛',
            'unlocked' => $stats['books_completed'] >= 10,
            'progress' => min(100, ($stats['books_completed'] / 10) * 100),
            'target' => 10
        ],
        [
            'id' => 'explorer',
            'title' => 'Genre Explorer',
            'description' => 'Explored 5 different categories',
            'icon' => '🗺️',
            'unlocked' => $stats['categories_explored'] >= 5,
            'progress' => min(100, ($stats['categories_explored'] / 5) * 100),
            'target' => 5
        ],
        [
            'id' => 'critic',
            'title' => 'Book Critic',
            'description' => 'Average rating above 4.0',
            'icon' => '⭐',
            'unlocked' => $stats['avg_rating_given'] >= 4.0,
            'progress' => min(100, ($stats['avg_rating_given'] / 4.0) * 100),
            'target' => 4.0
        ],
        [
            'id' => 'speed_reader',
            'title' => 'Speed Reader',
            'description' => 'Read 5 books in one month',
            'icon' => '⚡',
            'unlocked' => false, // Would need monthly tracking
            'progress' => 0,
            'target' => 5
        ]
    ];
    
    echo json_encode(['success' => true, 'achievements' => $achievements]);
}

function getLeaderboard() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            sr.first_name,
            sr.last_name,
            sr.student_id,
            COUNT(b.id) as books_read,
            COUNT(DISTINCT bk.category_id) as categories_explored,
            COALESCE(AVG(br.rating), 0) as avg_rating,
            COUNT(CASE WHEN b.borrowed_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as recent_activity
        FROM student_records sr
        LEFT JOIN borrowings b ON sr.student_id = b.student_id AND b.status = 'returned'
        LEFT JOIN books bk ON b.book_id = bk.id
        LEFT JOIN book_reviews br ON b.book_id = br.book_id AND b.student_id = br.student_id
        GROUP BY sr.student_id
        HAVING books_read > 0
        ORDER BY books_read DESC, recent_activity DESC
        LIMIT 20
    ");
    $stmt->execute();
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add rankings and scores
    foreach ($leaderboard as $index => &$entry) {
        $entry['rank'] = $index + 1;
        $entry['score'] = ($entry['books_read'] * 10) + ($entry['categories_explored'] * 5) + ($entry['recent_activity'] * 3);
        $entry['avg_rating'] = round((float)$entry['avg_rating'], 1);
    }
    
    echo json_encode(['success' => true, 'leaderboard' => $leaderboard]);
}

function getReadingGoals($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Get current month progress
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as books_this_month
        FROM borrowings 
        WHERE student_id = ? 
        AND borrowed_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ");
    $stmt->execute([$student_id]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Default monthly goal
    $monthly_goal = 3;
    $progress_percentage = min(100, ($progress['books_this_month'] / $monthly_goal) * 100);
    
    $goals = [
        [
            'id' => 'monthly_reading',
            'title' => 'Monthly Reading Goal',
            'description' => "Read $monthly_goal books this month",
            'type' => 'monthly',
            'target' => $monthly_goal,
            'current' => (int)$progress['books_this_month'],
            'progress' => round($progress_percentage, 1),
            'deadline' => date('Y-m-t'),
            'status' => $progress['books_this_month'] >= $monthly_goal ? 'completed' : 'active'
        ]
    ];
    
    echo json_encode(['success' => true, 'goals' => $goals]);
}

function getReadingAnalytics($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Monthly reading trend (last 6 months)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(borrowed_date, '%Y-%m') as month,
            COUNT(*) as books_borrowed
        FROM borrowings 
        WHERE student_id = ? 
        AND borrowed_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(borrowed_date, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute([$student_id]);
    $monthly_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Category distribution
    $stmt = $pdo->prepare("
        SELECT 
            c.name as category,
            c.color,
            COUNT(b.id) as book_count,
            ROUND((COUNT(b.id) * 100.0 / (SELECT COUNT(*) FROM borrowings WHERE student_id = ?)), 1) as percentage
        FROM borrowings bo
        JOIN books b ON bo.book_id = b.id
        JOIN categories c ON b.category_id = c.id
        WHERE bo.student_id = ?
        GROUP BY c.id
        ORDER BY book_count DESC
    ");
    $stmt->execute([$student_id, $student_id]);
    $category_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reading velocity (average days to complete)
    $stmt = $pdo->prepare("
        SELECT AVG(DATEDIFF(returned_date, borrowed_date)) as avg_reading_days
        FROM borrowings 
        WHERE student_id = ? 
        AND status = 'returned' 
        AND returned_date IS NOT NULL
    ");
    $stmt->execute([$student_id]);
    $velocity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'analytics' => [
            'monthly_trend' => $monthly_trend,
            'category_distribution' => $category_distribution,
            'avg_reading_days' => round((float)$velocity['avg_reading_days'], 1),
            'total_books' => array_sum(array_column($category_distribution, 'book_count'))
        ]
    ]);
}

function startReadingSession($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    $book_id = $data['book_id'] ?? '';
    
    if (!$student_id || !$book_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID and Book ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reading_sessions (student_id, book_id, session_start)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$student_id, $book_id]);
        
        $session_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'session_id' => $session_id,
            'message' => 'Reading session started'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function endReadingSession($data) {
    global $pdo;
    
    $session_id = $data['session_id'] ?? '';
    $pages_read = $data['pages_read'] ?? 0;
    $engagement_score = $data['engagement_score'] ?? 0.5;
    $notes = $data['notes'] ?? '';
    
    if (!$session_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Session ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE reading_sessions 
            SET session_end = CURRENT_TIMESTAMP,
                pages_read = ?,
                engagement_score = ?,
                notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$pages_read, $engagement_score, $notes, $session_id]);
        
        echo json_encode(['success' => true, 'message' => 'Reading session ended']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function setReadingGoal($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    $goal_type = $data['goal_type'] ?? 'monthly';
    $target = $data['target'] ?? 3;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // For now, we'll store goals in a simple way
    // In a full implementation, you'd have a reading_goals table
    
    echo json_encode([
        'success' => true, 
        'message' => 'Reading goal set successfully',
        'goal' => [
            'type' => $goal_type,
            'target' => $target,
            'student_id' => $student_id
        ]
    ]);
}

function logEngagement($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    $book_id = $data['book_id'] ?? '';
    $engagement_type = $data['engagement_type'] ?? '';
    $engagement_value = $data['engagement_value'] ?? 0;
    
    if (!$student_id || !$book_id || !$engagement_type) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
        return;
    }
    
    try {
        // Update book analytics based on engagement
        $stmt = $pdo->prepare("
            UPDATE book_analytics 
            SET total_views = total_views + CASE WHEN ? = 'view' THEN 1 ELSE 0 END,
                popularity_score = popularity_score + ?
            WHERE book_id = ?
        ");
        $stmt->execute([$engagement_type, $engagement_value, $book_id]);
        
        // Update user preferences based on engagement
        $stmt = $pdo->prepare("
            SELECT category_id FROM books WHERE id = ?
        ");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($book) {
            $preference_boost = match($engagement_type) {
                'long_view' => 0.02,
                'bookmark' => 0.05,
                'review' => 0.03,
                default => 0.01
            };
            
            $stmt = $pdo->prepare("
                INSERT INTO reading_preferences (student_id, category_id, preference_score, interaction_count)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    preference_score = LEAST(1.0, preference_score + ?),
                    interaction_count = interaction_count + 1
            ");
            $stmt->execute([$student_id, $book['category_id'], $preference_boost, $preference_boost]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Engagement logged']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
