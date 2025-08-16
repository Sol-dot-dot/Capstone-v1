<?php
session_start();
header('Content-Type: application/json');

// Fixed admin credentials
define('ADMIN_EMAIL', 'admin@admin.com');
define('ADMIN_PASSWORD', 'adminpassword1');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    // Get POST data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check credentials against fixed admin account
    if ($email !== ADMIN_EMAIL || $password !== ADMIN_PASSWORD) {
        // Log failed login attempt
        error_log("Admin login failed for email: $email at " . date('Y-m-d H:i:s'));
        throw new Exception('Invalid email or password');
    }

    // Create admin session
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_email'] = $email;
    $_SESSION['admin_login_time'] = time();

    // Log successful login
    error_log("Admin login successful for email: $email at " . date('Y-m-d H:i:s'));

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => 'admin_dashboard.php'
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
