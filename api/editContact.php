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
$firstName = clean($body['FirstName'] ?? $body['firstName'] ?? '');
$lastName = clean($body['LastName'] ?? $body['lastName'] ?? '');
$emailAddress = clean($body['emailAddress'] ?? $body['email'] ?? '');
$phone = clean($body['phone'] ?? '');

if (!is_numeric($contactId) || (int) $contactId <= 0) {
    respond(400, ['error' => 'Invalid contact ID']);
}

if (!$firstName || !$lastName || !$emailAddress || !$phone) {
    respond(400, ['error' => 'Contact ID, first name, last name, email, and phone cannot be empty']);
}

if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['error' => 'Invalid email format']);
}

try {
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE Contacts
         SET FirstName = :firstName, LastName = :lastName,
             EmailAddress = :emailAddress, Phone = :phone
         WHERE ID = :contactId AND UserID = :userId'
    );
    $stmt->execute([
        ':firstName' => $firstName,
        ':lastName' => $lastName,
        ':emailAddress' => $emailAddress,
        ':phone' => $phone,
        ':contactId' => (int) $contactId,
        ':userId' => $userId
    ]);

    if ($stmt->rowCount() === 0) {
        $exists = $db->prepare('SELECT ID FROM Contacts WHERE ID = :contactId AND UserID = :userId');
        $exists->execute([':contactId' => (int) $contactId, ':userId' => $userId]);
        if (!$exists->fetch()) {
            respond(404, ['error' => 'Contact not found']);
        }
    }

    respond(200, [
        'message' => 'Contact updated successfully',
        'contactId' => (int) $contactId,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $emailAddress,
        'phone' => $phone
    ]);
} catch (PDOException $e) {
    respond(500, ['error' => 'Failed to update contact']);
}
