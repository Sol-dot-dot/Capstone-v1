<?php
session_start();
require_once __DIR__ . '/../backend/config.php';

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    header('Location: login.php?error=1');
    exit;
}

$stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    header('Location: login.php?error=1');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['email'] = $email;
header('Location: dashboard.php');
