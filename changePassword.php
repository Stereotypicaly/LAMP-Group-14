<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if (!isset($_SERVER['REQUEST_METHOD'])) {
    respond(400, ['error' => 'Invalid request method']);
}

$method = $_SERVER['REQUEST_METHOD'];
requireAdmin();
$db = getDB();

if ($method === 'POST') {

    $body = getRequestBody();

    if (!isset($body['userId']) || !isset($body['newPassword'])) {
        respond(400, ['error' => 'All fields are required']);
    }

    if (!is_numeric($body['userId']) || (int) $body['userId'] <= 0) {
        respond(400, ['error' => 'Invalid user ID']);
    }

    if (empty($body['newPassword'])) {
        respond(400, ['error' => 'New password cannot be empty']);
    }

    $userId = (int) $body['userId'];
    $newPassword = $body['newPassword'];

    try {
        // Check if the user exists
        $stmt = $db->prepare(
            "SELECT ID FROM Users WHERE ID = :userId LIMIT 1"
        );
        $stmt->execute([':userId' => $userId]);

        if (!$stmt->fetch()) {
            respond(404, ['error' => 'User not found']);
        }

        // Update the password
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            "UPDATE Users SET Password = :password WHERE ID = :userId"
        );
        $stmt->execute([':password' => $hashedNewPassword, ':userId' => $userId]);

        respond(200, ['message' => 'Password changed successfully']);
    } catch (PDOException $e) {
        respond(500, ['error' => 'Database error']);
    }
} else {
    respond(405, ['error' => 'Method not allowed']);
}
