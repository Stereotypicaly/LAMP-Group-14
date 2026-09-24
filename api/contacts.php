<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    respond(405, [
        'error' => 'Method not allowed'
    ]);
}

$userId = requireActiveAuth();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getRequestBody();

    $firstName = isset($body['firstName']) && is_string($body['firstName'])
        ? trim($body['firstName'])
        : '';
    $lastName = isset($body['lastName']) && is_string($body['lastName'])
        ? trim($body['lastName'])
        : '';
    $emailAddress = isset($body['emailAddress']) && is_string($body['emailAddress'])
        ? trim($body['emailAddress'])
        : '';
    $phone = isset($body['phone']) && is_string($body['phone'])
        ? trim($body['phone'])
        : '';

    if (!$firstName || !$lastName || !$emailAddress || !$phone) {
        respond(400, [
            'error' => 'First name, last name, email address, and phone number are required'
        ]);
    }

    if (
        strlen($firstName) > 50 ||
        strlen($lastName) > 50 ||
        strlen($emailAddress) > 50 ||
        strlen($phone) > 20
    ) {
        respond(400, [
            'error' => 'One or more fields exceed the allowed length'
        ]);
    }

    if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        respond(400, [
            'error' => 'Enter a valid email address'
        ]);
    }

    try {
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
            'id' => (int) $db->lastInsertId(),
            'message' => 'Contact created successfully'
        ]);
    } catch (PDOException $e) {
        respond(500, [
            'error' => 'Unable to create contact'
        ]);
    }
}

try {
    $stmt = $db->prepare(
        'SELECT ID, FirstName, LastName, EmailAddress, Phone, DateCreated, DateUpdated
         FROM Contacts
         WHERE UserID = :userId
         ORDER BY LastName ASC, FirstName ASC, ID ASC'
    );

    $stmt->execute([
        ':userId' => $userId
    ]);

    respond(200, [
        'contacts' => $stmt->fetchAll()
    ]);
} catch (PDOException $e) {
    respond(500, [
        'error' => 'Unable to load contacts'
    ]);
}
