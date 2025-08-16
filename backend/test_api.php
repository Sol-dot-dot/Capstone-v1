<?php
// Simple API test endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'Book Borrowing System API is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoints' => [
        'books' => '/api_books.php',
        'borrowing' => '/api_borrowing.php', 
        'profile' => '/api_profile.php'
    ]
]);
?>
