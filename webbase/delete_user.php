<?php
session_start();
require_once __DIR__ . '/../backend/config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: users.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
$stmt->execute([$id]);
header('Location: users.php');
