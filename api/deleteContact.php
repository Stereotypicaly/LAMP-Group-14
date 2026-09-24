<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

$userId = requireActiveAuth();
$body = getRequestBody();
$contactId = $body['contactId'] ?? null;

if (!is_numeric($contactId) || (int) $contactId <= 0) {
    respond(400, ['error' => 'Invalid contact ID']);
}

try {
    $stmt = getDB()->prepare('DELETE FROM Contacts WHERE ID = :contactId AND UserID = :userId');
    $stmt->execute([':contactId' => (int) $contactId, ':userId' => $userId]);

    if ($stmt->rowCount() === 0) {
        respond(404, ['error' => 'Contact not found or does not belong to the user']);
    }

    respond(200, ['message' => 'Contact deleted successfully']);
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error']);
}
