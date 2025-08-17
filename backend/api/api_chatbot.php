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
                    case 'conversations':
                        getConversations($_GET['student_id'] ?? '');
                        break;
                    case 'messages':
                        getMessages($_GET['conversation_id'] ?? '');
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
                    case 'send_message':
                        sendMessage($data);
                        break;
                    case 'start_conversation':
                        startConversation($data);
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

function getConversations($student_id) {
    global $pdo;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT c.*, 
               (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time
        FROM chat_conversations c 
        WHERE c.student_id = ? 
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$student_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'conversations' => $conversations]);
}

function getMessages($conversation_id) {
    global $pdo;
    
    if (!$conversation_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Conversation ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM chat_messages 
        WHERE conversation_id = ? 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

function startConversation($data) {
    global $pdo;
    
    $student_id = $data['student_id'] ?? '';
    $title = $data['title'] ?? 'New Conversation';
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Student ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO chat_conversations (student_id, title) 
            VALUES (?, ?)
        ");
        $stmt->execute([$student_id, $title]);
        
        $conversation_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'conversation_id' => $conversation_id,
            'message' => 'Conversation started successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function sendMessage($data) {
    global $pdo;
    
    $conversation_id = $data['conversation_id'] ?? '';
    $student_id = $data['student_id'] ?? '';
    $message = $data['message'] ?? '';
    $sender_type = $data['sender_type'] ?? 'user';
    
    if (!$conversation_id || !$student_id || !$message) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Conversation ID, Student ID, and message required']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Insert user message
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (conversation_id, message, sender_type) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$conversation_id, $message, $sender_type]);
        
        // Update conversation timestamp
        $stmt = $pdo->prepare("UPDATE chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$conversation_id]);
        
        $user_message_id = $pdo->lastInsertId();
        
        // Generate AI response (simple rule-based for now)
        $ai_response = generateAIResponse($message, $student_id);
        
        // Insert AI response
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (conversation_id, message, sender_type) 
            VALUES (?, ?, 'ai')
        ");
        $stmt->execute([$conversation_id, $ai_response]);
        
        $ai_message_id = $pdo->lastInsertId();
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'user_message_id' => $user_message_id,
            'ai_message_id' => $ai_message_id,
            'ai_response' => $ai_response
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function generateAIResponse($message, $student_id) {
    global $pdo;
    
    $message_lower = strtolower($message);
    
    // Book recommendation queries
    if (strpos($message_lower, 'recommend') !== false || strpos($message_lower, 'suggest') !== false) {
        return getBookRecommendationResponse($student_id);
    }
    
    // Book search queries
    if (strpos($message_lower, 'find') !== false || strpos($message_lower, 'search') !== false) {
        return "I can help you find books! Try searching by title, author, or genre in the main search bar. You can also browse by categories like Fiction, Science, Engineering, and more.";
    }
    
    // Borrowing status queries
    if (strpos($message_lower, 'borrowed') !== false || strpos($message_lower, 'due') !== false || strpos($message_lower, 'return') !== false) {
        return getBorrowingStatusResponse($student_id);
    }
    
    // Popular books queries
    if (strpos($message_lower, 'popular') !== false || strpos($message_lower, 'trending') !== false) {
        return "Here are some popular books in our library: 'The Great Gatsby', 'A Game of Thrones', 'The Fault in Our Stars', and 'How to Win Friends and Influence People'. Check them out in the 'Top Available for you' section!";
    }
    
    // Help queries
    if (strpos($message_lower, 'help') !== false || strpos($message_lower, 'how') !== false) {
        return "I'm here to help you with:\n• Book recommendations based on your reading history\n• Finding specific books or authors\n• Checking your borrowed books and due dates\n• Discovering popular books and new arrivals\n• Managing your bookmarks and reading preferences\n\nWhat would you like assistance with?";
    }
    
    // Greeting responses
    if (strpos($message_lower, 'hello') !== false || strpos($message_lower, 'hi') !== false || strpos($message_lower, 'hey') !== false) {
        return "Hello! I'm your library AI assistant. I can help you discover new books, manage your borrowings, and find exactly what you're looking for. What can I help you with today?";
    }
    
    // Default response
    return "I'm your library AI assistant! I can help you find books, get recommendations, check your borrowing status, and more. Try asking me about book recommendations, popular books, or how to find specific titles.";
}

function getBookRecommendationResponse($student_id) {
    global $pdo;
    
    try {
        // Get user's reading preferences and recommend books
        $stmt = $pdo->prepare("
            SELECT b.title, b.description, a.first_name, a.last_name, c.name as category
            FROM recommendations r
            JOIN books b ON r.book_id = b.id
            LEFT JOIN book_authors ba ON b.id = ba.book_id
            LEFT JOIN authors a ON ba.author_id = a.id
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE r.student_id = ? AND b.available_copies > 0
            ORDER BY r.recommendation_score DESC
            LIMIT 3
        ");
        $stmt->execute([$student_id]);
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($recommendations)) {
            return "Based on popular choices, I recommend checking out 'The Great Gatsby' for classic literature, 'A Game of Thrones' for fantasy, or 'How to Win Friends and Influence People' for personal development. These are all available in our library!";
        }
        
        $response = "Based on your reading preferences, I recommend:\n\n";
        foreach ($recommendations as $book) {
            $author = $book['first_name'] . ' ' . $book['last_name'];
            $response .= "📚 **{$book['title']}** by {$author}\n";
            $response .= "Category: {$book['category']}\n";
            if ($book['description']) {
                $response .= substr($book['description'], 0, 100) . "...\n";
            }
            $response .= "\n";
        }
        
        return $response . "Would you like to borrow any of these books?";
        
    } catch (Exception $e) {
        return "I'd love to give you personalized recommendations! Try browsing the 'Top Available for you' section or check out popular categories like Fiction, Science, or Business.";
    }
}

function getBorrowingStatusResponse($student_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT b.title, bo.due_date, bo.status,
                   DATEDIFF(bo.due_date, CURDATE()) as days_remaining
            FROM borrowings bo
            JOIN books b ON bo.book_id = b.id
            WHERE bo.student_id = ? AND bo.status = 'active'
            ORDER BY bo.due_date ASC
        ");
        $stmt->execute([$student_id]);
        $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($borrowings)) {
            return "You don't have any books currently borrowed. Browse our collection and find something interesting to read!";
        }
        
        $response = "Here are your currently borrowed books:\n\n";
        foreach ($borrowings as $book) {
            $days = (int)$book['days_remaining'];
            $status = $days > 0 ? "{$days} days left" : "Overdue";
            $response .= "📖 **{$book['title']}**\n";
            $response .= "Due: {$book['due_date']} ({$status})\n\n";
        }
        
        return $response . "Remember to return books on time to avoid fines!";
        
    } catch (Exception $e) {
        return "I can help you check your borrowed books! Visit the Library tab to see all your active borrowings and due dates.";
    }
}
?>
