<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Test endpoint - just return success
echo json_encode([
    'success' => true, 
    'message' => 'Server is running!',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
