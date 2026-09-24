<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

requireAdmin();
$body = getRequestBody();
$userId = $body['userId'] ?? null;
$newPassword = $body['newPassword'] ?? '';

if (!is_numeric($userId) || (int) $userId <= 0) {
    respond(400, ['error' => 'Invalid user ID']);
}

if (!is_string($newPassword) || $newPassword === '') {
    respond(400, ['error' => 'New password cannot be empty']);
}

try {
    $stmt = getDB()->prepare('UPDATE Users SET Password = :password WHERE ID = :userId');
    $stmt->execute([
        ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':userId' => (int) $userId
    ]);
    if ($stmt->rowCount() === 0) {
        respond(404, ['error' => 'User not found']);
    }
    respond(200, ['message' => 'Password changed successfully']);
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error']);
}
