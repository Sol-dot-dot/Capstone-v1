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
$code       = trim($_POST['code'] ?? '');

if (!$student_id || !$email || !$code) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields required']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT code, expires_at FROM email_verification_codes WHERE student_id = ? AND email = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$student_id, $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Verification code not found']);
        exit;
    }

    if ($row['code'] !== $code) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Incorrect code']);
        exit;
    }

    if (new DateTime() > new DateTime($row['expires_at'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code expired']);
        exit;
    }

    // Mark as verified for a short window to allow registration
    // First get the ID of the record we just verified
    $stmt_id = $pdo->prepare('SELECT id FROM email_verification_codes WHERE student_id = ? AND email = ? ORDER BY id DESC LIMIT 1');
    $stmt_id->execute([$student_id, $email]);
    $record = $stmt_id->fetch(PDO::FETCH_ASSOC);
    
    if ($record) {
        $newExpiry = (new DateTime('+5 minutes'))->format('Y-m-d H:i:s');
        $upd = $pdo->prepare('UPDATE email_verification_codes SET code = "VERIFIED", expires_at = ? WHERE id = ?');
        $upd->execute([$newExpiry, $record['id']]);
    }

    echo json_encode(['success' => true, 'message' => 'Code verified']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'debug' => $e->getMessage(), 'line' => $e->getLine(), 'file' => basename($e->getFile())]);
}
?>
