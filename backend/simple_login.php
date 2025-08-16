<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Simple hardcoded login for testing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Test credentials
    if ($student_id === 'C22-0044' && $password === 'test123') {
        echo json_encode(['success' => true, 'message' => 'Login successful']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials. Use C22-0044 / test123']);
    }
} else {
    echo json_encode(['success' => true, 'message' => 'Server is running! Use C22-0044 / test123 to login']);
}
?>
