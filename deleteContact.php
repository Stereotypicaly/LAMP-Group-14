<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if (!isset($_SERVER['REQUEST_METHOD'])) {
    respond(400, ['error' => 'Invalid request method']);
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = requireAuth();
$db = getDB();

if ($method === 'POST') {

    $body = getRequestBody(); //retrieve the request body as an associative array

    if (!isset($body['contactId'])) { // Check if contactId is provided
        respond(400, ['error' => 'Contact ID is required']);
    }

    if (!is_numeric($body['contactId']) || (int) $body['contactId'] <= 0) {  // Validate that contactId is a positive integer
        respond(400, ['error' => 'Invalid contact ID']);
    }

    $contactId = (int) $body['contactId'];

    try {
        // Check if the contact exists and belongs to the user
        $stmt = $db->prepare(
            "SELECT * FROM Contacts WHERE ID = :contactId AND UserID = :userId"
        );
        $stmt->execute([':contactId' => $contactId, ':userId' => $userId]);
        $contact = $stmt->fetch();

        if (!$contact) {
            respond(404, ['error' => 'Contact not found or does not belong to the user']);
        }

        // Delete the contact
        $stmt = $db->prepare(
            "DELETE FROM Contacts WHERE ID = :contactId AND UserID = :userId"
        );
        $stmt->execute([':contactId' => $contactId, ':userId' => $userId]);

        respond(200, ['message' => 'Contact deleted successfully']);
    } catch (PDOException $e) {
        respond(500, ['error' => 'Database error']);
    }
} else {
    respond(405, ['error' => 'Method not allowed']);
}

