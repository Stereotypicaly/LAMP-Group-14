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

    if (!isset($body['userId'])) {
        respond(400, ['error' => 'User ID is required']);
    }

    if (!is_numeric($body['userId']) || (int) $body['userId'] <= 0) {
        respond(400, ['error' => 'Invalid user ID']);
    }

    $userIdToDisable = (int) $body['userId'];

    try {

        // Check if the user exists
        $stmt = $db->prepare(
            "SELECT ID FROM Users WHERE ID = :userId"
        );
        $stmt->execute([':userId' => $userIdToDisable]);
        $user = $stmt->fetch();

        if (!$user) {
            respond(404, ['error' => 'User not found']);
        }

        // Disable the user
        $stmt = $db->prepare(
            "UPDATE Users SET IsEnabled = 0 WHERE ID = :userId"
        );
        $stmt->execute([':userId' => $userIdToDisable]);

        respond(200, ['message' => 'User disabled successfully']);
    } catch (PDOException $e) {
        respond(500, ['error' => 'Database error']);
    }
} else {
    respond(405, ['error' => 'Method not allowed']);
}
