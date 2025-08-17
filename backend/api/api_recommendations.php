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
                    case 'personalized':
                        getPersonalizedRecommendations($_GET['student_id'] ?? '');
                        break;
                    case 'trending':
                        getTrendingBooks();
                        break;
                    case 'similar':
                        getSimilarBooks($_GET['book_id'] ?? '');
                        break;
                    case 'category_based':
                        getCategoryBasedRecommendations($_GET['student_id'] ?? '');
                        break;
                    case 'rag_enhanced':
                        getRAGEnhancedRecommendations($_GET['student_id'] ?? '', $_GET['query'] ?? '');
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
                    case 'track_interaction':
                        trackRecommendationInteraction($data);
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

function getPersonalizedRecommendations($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Get user's reading preferences
    $stmt = $pdo->prepare("
        SELECT category_id, preference_score 
        FROM reading_preferences 
        WHERE student_id = ? 
        ORDER BY preference_score DESC
    ");
    $stmt->execute([$student_id]);
    $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($preferences)) {
        // Fallback to popular books if no preferences
        getTrendingBooks();
        return;
    }
    
    // Get books from preferred categories that user hasn't borrowed
    $category_ids = array_column($preferences, 'category_id');
    $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT b.*, c.name as category_name, c.color as category_color,
               GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               ba.popularity_score,
               COALESCE(AVG(br.rating), 0) as avg_rating,
               COUNT(br.id) as review_count,
               rp.preference_score
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors bauth ON b.id = bauth.book_id
        LEFT JOIN authors a ON bauth.author_id = a.id
        LEFT JOIN book_reviews br ON b.id = br.book_id
        LEFT JOIN book_analytics ba ON b.id = ba.book_id
        LEFT JOIN reading_preferences rp ON b.category_id = rp.category_id AND rp.student_id = ?
        WHERE b.category_id IN ($placeholders)
        AND b.status = 'active'
        AND b.available_copies > 0
        AND b.id NOT IN (
            SELECT book_id FROM borrowings 
            WHERE student_id = ? AND status = 'active'
        )
        GROUP BY b.id
        ORDER BY (rp.preference_score * 0.4 + ba.popularity_score * 0.3 + COALESCE(AVG(br.rating), 0) * 0.3) DESC
        LIMIT 10
    ");
    
    $params = array_merge([$student_id], $category_ids, [$student_id]);
    $stmt->execute($params);
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process recommendations
    foreach ($recommendations as &$book) {
        $book['avg_rating'] = round((float)$book['avg_rating'], 1);
        $book['recommendation_type'] = 'personalized';
        $book['confidence_score'] = round((float)$book['preference_score'], 2);
    }
    
    // Log recommendations
    logRecommendations($student_id, $recommendations, 'content_based');
    
    echo json_encode(['success' => true, 'recommendations' => $recommendations]);
}

function getTrendingBooks() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT b.*, c.name as category_name, c.color as category_color,
               GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               ba.popularity_score,
               ba.trending_score,
               COALESCE(AVG(br.rating), 0) as avg_rating,
               COUNT(br.id) as review_count
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors bauth ON b.id = bauth.book_id
        LEFT JOIN authors a ON bauth.author_id = a.id
        LEFT JOIN book_reviews br ON b.id = br.book_id
        LEFT JOIN book_analytics ba ON b.id = ba.book_id
        WHERE b.status = 'active' AND b.available_copies > 0
        GROUP BY b.id
        ORDER BY ba.trending_score DESC, ba.popularity_score DESC
        LIMIT 15
    ");
    $stmt->execute();
    $trending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($trending as &$book) {
        $book['avg_rating'] = round((float)$book['avg_rating'], 1);
        $book['recommendation_type'] = 'trending';
        $book['confidence_score'] = round((float)$book['trending_score'] / 100, 2);
    }
    
    echo json_encode(['success' => true, 'recommendations' => $trending]);
}

function getSimilarBooks($book_id) {
    global $pdo;
    
    if (!$book_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Book ID required']);
        return;
    }
    
    // Get the source book's category and keywords
    $stmt = $pdo->prepare("
        SELECT b.category_id, be.keywords 
        FROM books b
        LEFT JOIN book_embeddings be ON b.id = be.book_id
        WHERE b.id = ?
    ");
    $stmt->execute([$book_id]);
    $source_book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source_book) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Book not found']);
        return;
    }
    
    // Find similar books based on category and keywords
    $stmt = $pdo->prepare("
        SELECT DISTINCT b.*, c.name as category_name, c.color as category_color,
               GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               COALESCE(AVG(br.rating), 0) as avg_rating,
               COUNT(br.id) as review_count,
               ba.popularity_score
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors bauth ON b.id = bauth.book_id
        LEFT JOIN authors a ON bauth.author_id = a.id
        LEFT JOIN book_reviews br ON b.id = br.book_id
        LEFT JOIN book_analytics ba ON b.id = ba.book_id
        LEFT JOIN book_embeddings be ON b.id = be.book_id
        WHERE b.id != ?
        AND b.status = 'active'
        AND b.available_copies > 0
        AND (b.category_id = ? OR be.keywords LIKE ?)
        GROUP BY b.id
        ORDER BY 
            CASE WHEN b.category_id = ? THEN 2 ELSE 0 END +
            CASE WHEN be.keywords LIKE ? THEN 1 ELSE 0 END +
            ba.popularity_score * 0.1 DESC
        LIMIT 8
    ");
    
    $keywords_pattern = '%' . str_replace(',', '%', $source_book['keywords']) . '%';
    $stmt->execute([
        $book_id, 
        $source_book['category_id'], 
        $keywords_pattern,
        $source_book['category_id'],
        $keywords_pattern
    ]);
    $similar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($similar as &$book) {
        $book['avg_rating'] = round((float)$book['avg_rating'], 1);
        $book['recommendation_type'] = 'similar';
        $book['confidence_score'] = 0.75;
    }
    
    echo json_encode(['success' => true, 'recommendations' => $similar]);
}

function getRAGEnhancedRecommendations($student_id, $query = '') {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    // Get user preferences for context
    $stmt = $pdo->prepare("
        SELECT c.name, rp.preference_score 
        FROM reading_preferences rp
        JOIN categories c ON rp.category_id = c.id
        WHERE rp.student_id = ?
        ORDER BY rp.preference_score DESC
        LIMIT 3
    ");
    $stmt->execute([$student_id]);
    $user_preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enhanced search with semantic matching
    $search_query = '';
    $params = [];
    
    if (!empty($query)) {
        $search_query = "AND (b.title LIKE ? OR b.description LIKE ? OR be.content_summary LIKE ? OR be.keywords LIKE ?)";
        $query_pattern = '%' . $query . '%';
        $params = [$query_pattern, $query_pattern, $query_pattern, $query_pattern];
    }
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT b.*, c.name as category_name, c.color as category_color,
               GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               COALESCE(AVG(br.rating), 0) as avg_rating,
               COUNT(br.id) as review_count,
               ba.popularity_score,
               be.content_summary,
               be.keywords,
               rp.preference_score,
               MATCH(b.title, b.description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors bauth ON b.id = bauth.book_id
        LEFT JOIN authors a ON bauth.author_id = a.id
        LEFT JOIN book_reviews br ON b.id = br.book_id
        LEFT JOIN book_analytics ba ON b.id = ba.book_id
        LEFT JOIN book_embeddings be ON b.id = be.book_id
        LEFT JOIN reading_preferences rp ON b.category_id = rp.category_id AND rp.student_id = ?
        WHERE b.status = 'active'
        AND b.available_copies > 0
        $search_query
        GROUP BY b.id
        ORDER BY (
            COALESCE(rp.preference_score, 0.3) * 0.4 +
            ba.popularity_score * 0.3 +
            COALESCE(AVG(br.rating), 0) * 0.2 +
            COALESCE(relevance_score, 0) * 0.1
        ) DESC
        LIMIT 12
    ");
    
    $final_params = array_merge([$query ?: '', $student_id], $params);
    $stmt->execute($final_params);
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($recommendations as &$book) {
        $book['avg_rating'] = round((float)$book['avg_rating'], 1);
        $book['recommendation_type'] = 'rag_enhanced';
        $book['confidence_score'] = round(
            (float)$book['preference_score'] * 0.4 + 
            (float)$book['popularity_score'] * 0.3 + 
            (float)$book['avg_rating'] * 0.3, 2
        );
    }
    
    // Log RAG recommendations
    logRecommendations($student_id, $recommendations, 'rag_enhanced');
    
    echo json_encode([
        'success' => true, 
        'recommendations' => $recommendations,
        'user_context' => $user_preferences,
        'query' => $query
    ]);
}

function logRecommendations($student_id, $recommendations, $type) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO recommendation_history (student_id, book_id, recommendation_type, confidence_score)
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($recommendations as $book) {
        $stmt->execute([
            $student_id, 
            $book['id'], 
            $type, 
            $book['confidence_score'] ?? 0.5
        ]);
    }
}

function trackRecommendationInteraction($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    $book_id = $data['book_id'] ?? '';
    $interaction_type = $data['interaction_type'] ?? ''; // 'view', 'bookmark', 'borrow'
    
    if (!$student_id || !$book_id || !$interaction_type) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
        return;
    }
    
    try {
        // Update recommendation history
        $stmt = $pdo->prepare("
            UPDATE recommendation_history 
            SET was_borrowed = CASE WHEN ? = 'borrow' THEN TRUE ELSE was_borrowed END,
                was_bookmarked = CASE WHEN ? = 'bookmark' THEN TRUE ELSE was_bookmarked END
            WHERE student_id = ? AND book_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$interaction_type, $interaction_type, $student_id, $book_id]);
        
        // Update book analytics
        $stmt = $pdo->prepare("
            UPDATE book_analytics 
            SET total_views = total_views + CASE WHEN ? = 'view' THEN 1 ELSE 0 END,
                total_borrows = total_borrows + CASE WHEN ? = 'borrow' THEN 1 ELSE 0 END,
                total_bookmarks = total_bookmarks + CASE WHEN ? = 'bookmark' THEN 1 ELSE 0 END
            WHERE book_id = ?
        ");
        $stmt->execute([$interaction_type, $interaction_type, $interaction_type, $book_id]);
        
        echo json_encode(['success' => true, 'message' => 'Interaction tracked']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function updateReadingPreferences($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    $category_id = $data['category_id'] ?? '';
    $interaction_type = $data['interaction_type'] ?? '';
    
    if (!$student_id || !$category_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID and category ID required']);
        return;
    }
    
    try {
        // Update or insert preference based on interaction
        $score_adjustment = match($interaction_type) {
            'borrow' => 0.1,
            'bookmark' => 0.05,
            'view' => 0.02,
            'search' => 0.03,
            default => 0.01
        };
        
        $stmt = $pdo->prepare("
            INSERT INTO reading_preferences (student_id, category_id, preference_score, interaction_count)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                preference_score = LEAST(1.0, preference_score + ?),
                interaction_count = interaction_count + 1
        ");
        $stmt->execute([$student_id, $category_id, $score_adjustment, $score_adjustment]);
        
        echo json_encode(['success' => true, 'message' => 'Preferences updated']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
