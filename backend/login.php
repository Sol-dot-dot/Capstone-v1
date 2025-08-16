<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$student_id = strtoupper(trim($_POST['student_id'] ?? ''));
$password    = $_POST['password'] ?? '';

if (!$student_id || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Student ID and password required']);
    exit;
}

try {
    // Fetch student account
    $stmt = $pdo->prepare('SELECT password_hash FROM students WHERE student_id = ?');
    $stmt->execute([$student_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        exit;
    }

    // Get student email for tracking
    $stmt = $pdo->prepare('SELECT email FROM students WHERE student_id = ?');
    $stmt->execute([$student_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = $student_data['email'] ?? '';

    // Record login event in student_logins table
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare('INSERT INTO student_logins (student_id, email, ip_address, user_agent) VALUES (?, ?, ?, ?)');
    $stmt->execute([$student_id, $email, $ip_address, $user_agent]);

    echo json_encode(['success' => true, 'message' => 'Login successful']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
