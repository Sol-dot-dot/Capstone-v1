<?php
session_start();
require_once __DIR__ . '/../backend/config.php';

$id = $_POST['id'] ?? null;
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email) {
    die('Invalid email');
}

try {
    if ($id) {
        // Update existing user
        if ($password) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE users SET email=?, password_hash=? WHERE id=?');
            $stmt->execute([$email, $hash, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET email=? WHERE id=?');
            $stmt->execute([$email, $id]);
        }
    } else {
        if (!$password) {
            die('Password required');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);
    }
    header('Location: users.php');
} catch (PDOException $e) {
    die('DB error: ' . $e->getMessage());
}
