<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

requireAdmin();
$userId = getRequestBody()['userId'] ?? null;

if (!is_numeric($userId) || (int) $userId <= 0) {
    respond(400, ['error' => 'Invalid user ID']);
}

try {
    $stmt = getDB()->prepare('UPDATE Users SET IsDisabled = 1 WHERE ID = :userId');
    $stmt->execute([':userId' => (int) $userId]);
    if ($stmt->rowCount() === 0) {
        respond(404, ['error' => 'User not found']);
    }
    respond(200, ['message' => 'User disabled successfully']);
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error']);
}
