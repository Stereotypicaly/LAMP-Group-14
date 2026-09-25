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

    $body = getRequestBody();

    if (
        !isset($body['contactId']) ||
        !isset($body['FirstName']) ||
        !isset($body['LastName']) ||
        !isset($body['email']) ||
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
    $FirstName = clean($body['FirstName']);
    $LastName = clean($body['LastName']);
    $email = clean($body['email']);
    $phone = clean($body['phone']);
    $category = isset($body['category']) ? clean($body['category']) : null;
    $favorite = isset($body['favorite']) ? filter_var($body['favorite'], FILTER_VALIDATE_BOOLEAN) : false;

    if (!$contactId || !$FirstName || !$LastName || !$email || !$phone) {

        respond(400, [
            'error' => 'Contact ID, first name, last name, email, and phone cannot be empty'
        ]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        respond(400, [
            'error' => 'Invalid email format'
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
                 Email = :email,
                 Phone = :phone,
                 Category = :category,
                 Favorite = :favorite
             WHERE ID = :contactId AND UserID = :userId'
        );

        $stmt->execute([
            ':firstName' => $FirstName,
            ':lastName' => $LastName,
            ':email' => $email,
            ':phone' => $phone,
            ':category' => $category,
            ':favorite' => $favorite ? 1 : 0,
            ':contactId' => $contactId,
            ':userId' => $userId
        ]);

        respond(200, [
            'message' => 'Contact updated successfully',
            'contactId' => $contactId,
            'firstName' => $FirstName,
            'lastName' => $LastName,
            'email' => $email,
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
