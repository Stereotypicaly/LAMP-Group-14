<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

$userId = requireActiveAuth();
$body = getRequestBody();
$firstName = clean($body['FirstName'] ?? $body['firstName'] ?? '');
$lastName = clean($body['LastName'] ?? $body['lastName'] ?? '');
$emailAddress = clean($body['emailAddress'] ?? $body['email'] ?? '');
$phone = clean($body['phone'] ?? '');

if (!$firstName || !$lastName || !$emailAddress || !$phone) {
    respond(400, ['error' => 'First name, last name, email, and phone are required']);
}

if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['error' => 'Invalid email format']);
}

try {
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO Contacts (UserID, FirstName, LastName, EmailAddress, Phone)
         VALUES (:userId, :firstName, :lastName, :emailAddress, :phone)'
    );
    $stmt->execute([
        ':userId' => $userId,
        ':firstName' => $firstName,
        ':lastName' => $lastName,
        ':emailAddress' => $emailAddress,
        ':phone' => $phone
    ]);

    respond(201, [
        'message' => 'Contact added successfully',
        'contactId' => (int) $db->lastInsertId(),
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $emailAddress,
        'phone' => $phone
    ]);
} catch (PDOException $e) {
    respond(500, ['error' => 'Failed to add contact']);
}
