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
                    case 'search':
                        performSearch($_GET);
                        break;
                    case 'filters':
                        getSearchFilters();
                        break;
                    case 'suggestions':
                        getSearchSuggestions($_GET['query'] ?? '');
                        break;
                    case 'advanced':
                        performAdvancedSearch($_GET);
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
                    case 'log_search':
                        logSearch($data);
                        break;
                    case 'semantic_search':
                        performSemanticSearch($data);
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

function performSearch($params) {
    global $pdo;
    
    $query = $params['query'] ?? '';
    $category = $params['category'] ?? '';
    $author = $params['author'] ?? '';
    $min_rating = $params['min_rating'] ?? 0;
    $availability = $params['availability'] ?? '';
    $sort_by = $params['sort_by'] ?? 'relevance';
    $limit = min(50, max(1, (int)($params['limit'] ?? 20)));
    $offset = max(0, (int)($params['offset'] ?? 0));
    
    // Build WHERE conditions
    $conditions = ["b.status = 'active'"];
    $search_params = [];
    
    if (!empty($query)) {
        $conditions[] = "(b.title LIKE ? OR b.description LIKE ? OR CONCAT(a.first_name, ' ', a.last_name) LIKE ?)";
        $query_pattern = '%' . $query . '%';
        $search_params = array_merge($search_params, [$query_pattern, $query_pattern, $query_pattern]);
    }
    
    if (!empty($category)) {
        $conditions[] = "c.name = ?";
        $search_params[] = $category;
    }
    
    if (!empty($author)) {
        $conditions[] = "CONCAT(a.first_name, ' ', a.last_name) LIKE ?";
        $search_params[] = '%' . $author . '%';
    }
    
    if ($min_rating > 0) {
        $conditions[] = "COALESCE(AVG(br.rating), 0) >= ?";
        $search_params[] = $min_rating;
    }
    
    if ($availability === 'available') {
        $conditions[] = "b.available_copies > 0";
    } elseif ($availability === 'unavailable') {
        $conditions[] = "b.available_copies = 0";
    }
    
    // Build ORDER BY clause
    $order_clause = match($sort_by) {
        'title' => 'b.title ASC',
        'author' => 'a.last_name ASC, a.first_name ASC',
        'rating' => 'COALESCE(AVG(br.rating), 0) DESC',
        'newest' => 'b.created_at DESC',
        'popularity' => 'ba.popularity_score DESC',
        default => !empty($query) ? 
            "MATCH(b.title, b.description) AGAINST(? IN NATURAL LANGUAGE MODE) DESC" : 
            'b.created_at DESC'
    };
    
    if ($sort_by === 'relevance' && !empty($query)) {
        $search_params[] = $query;
    }
    
    $where_clause = implode(' AND ', $conditions);
    
    // Get total count
    $count_sql = "
        SELECT COUNT(DISTINCT b.id) as total
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors ba_rel ON b.id = ba_rel.book_id
        LEFT JOIN authors a ON ba_rel.author_id = a.id
        LEFT JOIN book_reviews br ON b.id = br.book_id
        LEFT JOIN book_analytics ba ON b.id = ba.book_id
        WHERE $where_clause
    ";
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($search_params);
    $total_results = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get search results
    $search_sql = "
        SELECT DISTINCT b.*, c.name as category_name, c.color as category_color,
               GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               COALESCE(AVG(br.rating), 0) as avg_rating,
               COUNT(DISTINCT br.id) as review_count,
               ba.popularity_score,
               ba.total_borrows,
               ba.total_bookmarks
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors ba_rel ON b.id = ba_rel.book_id
        LEFT JOIN authors a ON ba_rel.author_id = a.id
        LEFT JOIN book_reviews br ON b.id = br.book_id
        LEFT JOIN book_analytics ba ON b.id = ba.book_id
        WHERE $where_clause
        GROUP BY b.id
        ORDER BY $order_clause
        LIMIT $limit OFFSET $offset
    ";
    
    $final_params = $search_params;
    if ($sort_by === 'relevance' && !empty($query) && count($search_params) > 3) {
        // Remove the last query parameter added for ORDER BY if it's already in WHERE
        array_pop($final_params);
    }
    
    $stmt = $pdo->prepare($search_sql);
    $stmt->execute($final_params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process results
    foreach ($results as &$book) {
        $book['avg_rating'] = round((float)$book['avg_rating'], 1);
        $book['is_available'] = $book['available_copies'] > 0;
        $book['popularity_score'] = round((float)$book['popularity_score'], 1);
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => (int)$total_results,
        'page' => floor($offset / $limit) + 1,
        'per_page' => $limit,
        'query' => $query,
        'filters_applied' => [
            'category' => $category,
            'author' => $author,
            'min_rating' => $min_rating,
            'availability' => $availability,
            'sort_by' => $sort_by
        ]
    ]);
}

function getSearchFilters() {
    global $pdo;
    
    // Get available categories
    $stmt = $pdo->prepare("SELECT name, color FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get popular authors
    $stmt = $pdo->prepare("
        SELECT CONCAT(a.first_name, ' ', a.last_name) as name, COUNT(ba.book_id) as book_count
        FROM authors a
        JOIN book_authors ba ON a.id = ba.author_id
        JOIN books b ON ba.book_id = b.id
        WHERE b.status = 'active'
        GROUP BY a.id
        HAVING book_count > 0
        ORDER BY book_count DESC, a.last_name ASC
        LIMIT 20
    ");
    $stmt->execute();
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get rating ranges
    $rating_ranges = [
        ['label' => '4+ Stars', 'value' => 4],
        ['label' => '3+ Stars', 'value' => 3],
        ['label' => '2+ Stars', 'value' => 2],
        ['label' => '1+ Stars', 'value' => 1]
    ];
    
    // Get availability options
    $availability_options = [
        ['label' => 'Available Now', 'value' => 'available'],
        ['label' => 'Currently Borrowed', 'value' => 'unavailable'],
        ['label' => 'All Books', 'value' => 'all']
    ];
    
    // Get sort options
    $sort_options = [
        ['label' => 'Relevance', 'value' => 'relevance'],
        ['label' => 'Title A-Z', 'value' => 'title'],
        ['label' => 'Author', 'value' => 'author'],
        ['label' => 'Highest Rated', 'value' => 'rating'],
        ['label' => 'Newest First', 'value' => 'newest'],
        ['label' => 'Most Popular', 'value' => 'popularity']
    ];
    
    echo json_encode([
        'success' => true,
        'filters' => [
            'categories' => $categories,
            'authors' => $authors,
            'rating_ranges' => $rating_ranges,
            'availability' => $availability_options,
            'sort_options' => $sort_options
        ]
    ]);
}

function getSearchSuggestions($query) {
    global $pdo;
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'suggestions' => []]);
        return;
    }
    
    $suggestions = [];
    
    // Book title suggestions
    $stmt = $pdo->prepare("
        SELECT DISTINCT title as suggestion, 'book' as type
        FROM books 
        WHERE title LIKE ? AND status = 'active'
        ORDER BY title
        LIMIT 5
    ");
    $stmt->execute(['%' . $query . '%']);
    $book_suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Author suggestions
    $stmt = $pdo->prepare("
        SELECT DISTINCT CONCAT(first_name, ' ', last_name) as suggestion, 'author' as type
        FROM authors 
        WHERE CONCAT(first_name, ' ', last_name) LIKE ?
        ORDER BY last_name
        LIMIT 5
    ");
    $stmt->execute(['%' . $query . '%']);
    $author_suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Category suggestions
    $stmt = $pdo->prepare("
        SELECT DISTINCT name as suggestion, 'category' as type
        FROM categories 
        WHERE name LIKE ?
        ORDER BY name
        LIMIT 3
    ");
    $stmt->execute(['%' . $query . '%']);
    $category_suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $suggestions = array_merge($book_suggestions, $author_suggestions, $category_suggestions);
    
    echo json_encode(['success' => true, 'suggestions' => $suggestions]);
}

function performAdvancedSearch($params) {
    global $pdo;
    
    $title = $params['title'] ?? '';
    $author = $params['author'] ?? '';
    $category = $params['category'] ?? '';
    $isbn = $params['isbn'] ?? '';
    $year_from = $params['year_from'] ?? '';
    $year_to = $params['year_to'] ?? '';
    $min_rating = $params['min_rating'] ?? 0;
    $max_rating = $params['max_rating'] ?? 5;
    $availability = $params['availability'] ?? '';
    
    $conditions = ["b.status = 'active'"];
    $search_params = [];
    
    if (!empty($title)) {
        $conditions[] = "b.title LIKE ?";
        $search_params[] = '%' . $title . '%';
    }
    
    if (!empty($author)) {
        $conditions[] = "CONCAT(a.first_name, ' ', a.last_name) LIKE ?";
        $search_params[] = '%' . $author . '%';
    }
    
    if (!empty($category)) {
        $conditions[] = "c.name = ?";
        $search_params[] = $category;
    }
    
    if (!empty($isbn)) {
        $conditions[] = "b.isbn LIKE ?";
        $search_params[] = '%' . $isbn . '%';
    }
    
    if (!empty($year_from)) {
        $conditions[] = "YEAR(b.publication_date) >= ?";
        $search_params[] = $year_from;
    }
    
    if (!empty($year_to)) {
        $conditions[] = "YEAR(b.publication_date) <= ?";
        $search_params[] = $year_to;
    }
    
    if ($min_rating > 0) {
        $conditions[] = "COALESCE(AVG(br.rating), 0) >= ?";
        $search_params[] = $min_rating;
    }
    
    if ($max_rating < 5) {
        $conditions[] = "COALESCE(AVG(br.rating), 0) <= ?";
        $search_params[] = $max_rating;
    }
    
    if ($availability === 'available') {
        $conditions[] = "b.available_copies > 0";
    } elseif ($availability === 'unavailable') {
        $conditions[] = "b.available_copies = 0";
    }
    
    $where_clause = implode(' AND ', $conditions);
    
    $sql = "
        SELECT DISTINCT b.*, c.name as category_name, c.color as category_color,
               GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               COALESCE(AVG(br.rating), 0) as avg_rating,
               COUNT(DISTINCT br.id) as review_count,
               ba.popularity_score
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors ba_rel ON b.id = ba_rel.book_id
        LEFT JOIN authors a ON ba_rel.author_id = a.id
        LEFT JOIN book_reviews br ON b.id = br.book_id
        LEFT JOIN book_analytics ba ON b.id = ba.book_id
        WHERE $where_clause
        GROUP BY b.id
        ORDER BY ba.popularity_score DESC, b.title ASC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($search_params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as &$book) {
        $book['avg_rating'] = round((float)$book['avg_rating'], 1);
        $book['is_available'] = $book['available_copies'] > 0;
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results),
        'search_criteria' => $params
    ]);
}

function logSearch($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    $query = $data['query'] ?? '';
    $search_type = $data['search_type'] ?? 'general';
    $results_count = $data['results_count'] ?? 0;
    $clicked_book_id = $data['clicked_book_id'] ?? null;
    
    if (!$student_id || !$query) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID and query required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO search_history (student_id, search_query, search_type, results_count, clicked_book_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $query, $search_type, $results_count, $clicked_book_id]);
        
        echo json_encode(['success' => true, 'message' => 'Search logged']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function performSemanticSearch($data) {
    global $pdo;
    
    $query = $data['query'] ?? '';
    $student_id = $data['student_id'] ?? '';
    
    if (!$query) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Query required']);
        return;
    }
    
    // Enhanced semantic search using book embeddings and user preferences
    $sql = "
        SELECT DISTINCT b.*, c.name as category_name, c.color as category_color,
               GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') as authors,
               COALESCE(AVG(br.rating), 0) as avg_rating,
               COUNT(DISTINCT br.id) as review_count,
               ba.popularity_score,
               be.content_summary,
               be.keywords,
               rp.preference_score,
               (
                   MATCH(b.title, b.description) AGAINST(? IN NATURAL LANGUAGE MODE) * 0.4 +
                   CASE WHEN be.content_summary LIKE ? THEN 0.3 ELSE 0 END +
                   CASE WHEN be.keywords LIKE ? THEN 0.2 ELSE 0 END +
                   COALESCE(rp.preference_score, 0.3) * 0.1
               ) as semantic_score
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors ba_rel ON b.id = ba_rel.book_id
        LEFT JOIN authors a ON ba_rel.author_id = a.id
        LEFT JOIN book_reviews br ON b.id = br.book_id
        LEFT JOIN book_analytics ba ON b.id = ba.book_id
        LEFT JOIN book_embeddings be ON b.id = be.book_id
        LEFT JOIN reading_preferences rp ON b.category_id = rp.category_id AND rp.student_id = ?
        WHERE b.status = 'active'
        AND (
            MATCH(b.title, b.description) AGAINST(? IN NATURAL LANGUAGE MODE)
            OR be.content_summary LIKE ?
            OR be.keywords LIKE ?
        )
        GROUP BY b.id
        HAVING semantic_score > 0
        ORDER BY semantic_score DESC
        LIMIT 15
    ";
    
    $query_pattern = '%' . $query . '%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $query, $query_pattern, $query_pattern, $student_id,
        $query, $query_pattern, $query_pattern
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as &$book) {
        $book['avg_rating'] = round((float)$book['avg_rating'], 1);
        $book['semantic_score'] = round((float)$book['semantic_score'], 3);
        $book['is_available'] = $book['available_copies'] > 0;
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'query' => $query,
        'search_type' => 'semantic'
    ]);
}
?>
