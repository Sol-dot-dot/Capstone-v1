<?php
header('Content-Type: application/json');
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$student_id = strtoupper(trim($_POST['student_id'] ?? ''));
$email      = strtolower(trim($_POST['email'] ?? ''));
$password   = $_POST['password'] ?? '';

if (!$student_id || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields required']);
    exit;
}

// enforce password rules
if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/', $password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password too weak']);
    exit;
}

if (!preg_match('/^[a-z]+\.[a-z]+@my\.smciligan\.edu\.ph$/', $email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid school email format']);
    exit;
}

try {
    // ensure student record exists
    $stmt = $pdo->prepare('SELECT id FROM student_records WHERE student_id = ?');
    $stmt->execute([$student_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'ID number not found in school records']);
        exit;
    }

    // ensure email was verified recently
    $stmt = $pdo->prepare('SELECT code, expires_at FROM email_verification_codes WHERE student_id = ? AND email = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$student_id, $email]);
    $ver = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ver || $ver['code'] !== 'VERIFIED' || new DateTime() > new DateTime($ver['expires_at'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email not verified or session expired']);
        exit;
    }

    // check duplicates
    $stmt = $pdo->prepare('SELECT 1 FROM students WHERE student_id = ? OR email = ?');
    $stmt->execute([$student_id, $email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Account already exists']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO students (student_id, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$student_id, $email, $hash]);

    // cleanup verification token after successful registration
    $del = $pdo->prepare('DELETE FROM email_verification_codes WHERE student_id = ? AND email = ?');
    $del->execute([$student_id, $email]);

    echo json_encode(['success' => true, 'message' => 'Registration successful']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'debug' => $e->getMessage(), 'line' => $e->getLine(), 'file' => basename($e->getFile())]);
}
?>
