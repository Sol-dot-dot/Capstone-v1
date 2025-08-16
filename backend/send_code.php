<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$student_id = strtoupper(trim($_POST['student_id'] ?? ''));
$email      = strtolower(trim($_POST['email'] ?? ''));

// Basic validations
if (!$student_id || !$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Student ID and email required']);
    exit;
}

// Email pattern check (firstname.lastname@my.smciligan.edu.ph)
if (!preg_match('/^[a-z]+\.[a-z]+@my\.smciligan\.edu\.ph$/', $email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid school email format']);
    exit;
}

try {
    // Validate student ID exists in master list
    $stmt = $pdo->prepare('SELECT id FROM student_records WHERE student_id = ?');
    $stmt->execute([$student_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'ID number not found in school records']);
        exit;
    }

    // Generate 6-digit code
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = (new DateTime('+5 minutes'))->format('Y-m-d H:i:s');

    // Insert or update existing code record (MySQL compatible)
    $stmt = $pdo->prepare('INSERT INTO email_verification_codes (student_id, email, code, expires_at)
            VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE code = VALUES(code), expires_at = VALUES(expires_at)');
    $stmt->execute([$student_id, $email, $code, $expires_at]);

    // Send email via PHPMailer
    require_once __DIR__ . '/vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = $SMTP_USER;
        $mail->Password   = $SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $SMTP_PORT;
        
        // Enable debugging for troubleshooting (can be removed in production)
        $mail->SMTPDebug = 0; // Set to 2 for verbose debugging if needed
        $mail->Debugoutput = 'html';

        //Recipients
        $mail->setFrom($SMTP_FROM, 'Winsurfs');
        $mail->addAddress($email);

        //Content
        $mail->isHTML(false);
        $mail->Subject = 'Winsurfs Verification Code';
        $mail->Body    = "Your Winsurfs verification code is: $code\nThis code will expire in 5 minutes.";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Verification code sent']);
    } catch (Exception $e) {
        http_response_code(500);
        // Provide more detailed error information for debugging
        $error_message = 'Email sending failed';
        if (strpos($e->getMessage(), 'SMTP connect()') !== false) {
            $error_message = 'SMTP connection failed - check server settings';
        } elseif (strpos($e->getMessage(), 'SMTP AUTH') !== false) {
            $error_message = 'SMTP authentication failed - check credentials';
        }
        echo json_encode(['success' => false, 'error' => $error_message, 'debug' => $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'debug' => $e->getMessage(), 'line' => $e->getLine(), 'file' => basename($e->getFile())]);
}
?>
