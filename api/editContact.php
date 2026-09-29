<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if (!isset($_SERVER['REQUEST_METHOD'])) {
    respond(400, ['error' => 'Invalid request method']);
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = requireActiveAuth();
$db = getDB();

if ($method === 'POST') {

    $body = getRequestBody();

    if (
        !isset($body['contactId']) ||
        !isset($body['firstName']) ||
        !isset($body['lastName']) ||
        !isset($body['emailAddress']) ||
        !isset($body['phone']) ||
        !isset($body['category']) ||
        !isset($body['favorite'])
    ) {

        respond(400, [
            'error' => 'All fields are required'
        ]);
    }

    if (!is_numeric($body['contactId']) || (int) $body['contactId'] <= 0) {
        respond(400, [
            'error' => 'Invalid contact ID'
        ]);
    }
    $contactId = (int) $body['contactId'];
    $firstName = clean($body['firstName']);
    $lastName = clean($body['lastName']);
    $emailAddress = clean($body['emailAddress']);
    $phoneInput = clean($body['phone']);
    $phone = normalizePhoneNumber($phoneInput);
    $category = isset($body['category']) ? clean($body['category']) : null;
    $category = $category === '' ? null : $category;
    $favorite = isset($body['favorite']) ? filter_var($body['favorite'], FILTER_VALIDATE_BOOLEAN) : false;

    if (!$contactId || !$firstName || !$lastName || !$emailAddress || !$phoneInput) {

        respond(400, [
            'error' => 'Contact ID, first name, last name, email, and phone cannot be empty'
        ]);
    }

    if ($phone === null) {

        respond(400, [
            'error' => 'Enter a valid 10-digit phone number'
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
            'error' => 'Invalid email format'
        ]);
    }

    if ($category !== null && !in_array($category, ['Family', 'Friends', 'Work', 'School'], true)) {

        respond(400, [
            'error' => 'Invalid category'
        ]);
    }

    try {
    // Check if the contact exists and belongs to the user
        $stmt = $db->prepare(
            'SELECT ID
             FROM Contacts
             WHERE ID = :contactId AND UserID = :userId'
        );

        $stmt->execute([
            ':contactId' => $contactId,
            ':userId' => $userId
        ]);

        if (!$stmt->fetch()) {
            respond(404, [
                'error' => 'Contact not found'
            ]);
        }

    //update the contact in the database
        $stmt = $db->prepare(
            'UPDATE Contacts
             SET FirstName = :firstName,
                 LastName = :lastName,
                 EmailAddress = :email,
                 Phone = :phone,
                 Category = :category,
                 Favorite = :favorite
             WHERE ID = :contactId AND UserID = :userId'
        );

        $stmt->execute([
            ':firstName' => $firstName,
            ':lastName' => $lastName,
            ':email' => $emailAddress,
            ':phone' => $phone,
            ':category' => $category,
            ':favorite' => $favorite ? 1 : 0,
            ':contactId' => $contactId,
            ':userId' => $userId
        ]);

        respond(200, [
            'message' => 'Contact updated successfully',
            'contactId' => $contactId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'emailAddress' => $emailAddress,
            'phone' => $phone,
            'category' => $category,
            'favorite' => $favorite
        ]);

    } catch (PDOException $e) {
        respond(500, [
            'error' => 'Failed to update contact'
        ]);
    }

} else {
    
    respond(405, [
        'error' => 'Method not allowed'
    ]);
}
