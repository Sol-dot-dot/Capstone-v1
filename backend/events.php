<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

try {
    $stmt = $pdo->query('SELECT le.id, u.email, le.login_time FROM login_events le JOIN users u ON le.user_id = u.id ORDER BY le.login_time DESC LIMIT 50');
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'events' => $events]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
