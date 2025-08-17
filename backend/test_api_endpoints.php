<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

echo "=== TESTING API ENDPOINTS ===\n\n";

try {
    // Test books API
    echo "1. Testing Books API...\n";
    $stmt = $pdo->query("
        SELECT b.*, c.name as category_name, c.color as category_color,
               CONCAT(a.first_name, ' ', a.last_name) as author_name,
               COALESCE(b.rating, 4.0) as rating,
               COALESCE(b.total_ratings, 0) as total_ratings,
               b.cover_image
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_authors ba ON b.id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.id
        WHERE 1=1
        ORDER BY b.id DESC
        LIMIT 5
    ");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Books found: " . count($books) . "\n";
    if (count($books) > 0) {
        echo "Sample book: " . $books[0]['title'] . " by " . $books[0]['author_name'] . "\n";
    }
    
    // Test categories API
    echo "\n2. Testing Categories API...\n";
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Categories found: " . count($categories) . "\n";
    if (count($categories) > 0) {
        echo "Sample category: " . $categories[0]['name'] . " (" . $categories[0]['color'] . ")\n";
    }
    
    // Test student profile
    echo "\n3. Testing Student Profile...\n";
    $student_id = 'C22-0044';
    
    // Check student record
    $stmt = $pdo->prepare("SELECT * FROM student_records WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student_record) {
        echo "Student record found: " . $student_record['first_name'] . " " . $student_record['last_name'] . "\n";
        echo "Course: " . ($student_record['course'] ?? 'Not set') . "\n";
        echo "Year: " . ($student_record['year_level'] ?? 'Not set') . "\n";
    } else {
        echo "No student record found for $student_id\n";
    }
    
    // Check student auth
    $stmt = $pdo->prepare("SELECT student_id, email, account_status FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student_auth = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student_auth) {
        echo "Student auth found: " . $student_auth['email'] . " (Status: " . $student_auth['account_status'] . ")\n";
    } else {
        echo "No student auth found for $student_id\n";
    }
    
    echo "\n=== API TEST COMPLETE ===\n";
    echo "Database is ready for API calls!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
