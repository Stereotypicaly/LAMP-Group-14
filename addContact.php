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
        !isset($body['FirstName']) ||
        !isset($body['LastName']) ||
        !isset($body['email']) ||
        !isset($body['phone'])
    ) {

        respond(400, [
            'error' => 'First name, last name, email, and phone are required'
        ]);
    }

    $FirstName = clean($body['FirstName']);
    $LastName = clean($body['LastName']);
    $email = clean($body['email']);
    $phone = clean($body['phone']);

    if (!$FirstName || !$LastName || !$email || !$phone) {

        respond(400, [
            'error' => 'First name, last name, email, and phone cannot be empty'
        ]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        respond(400, [
            'error' => 'Invalid email format'
        ]);
    }

    try {

        $stmt = $db->prepare(
            //has to be the same as the table name in the database and the column names have to match the database column names
            'INSERT INTO Contacts (UserID, FirstName, LastName, Email, Phone)
             VALUES (:userId, :firstName, :lastName, :email, :phone)'
        );

        $stmt->execute([
            ':userId' => $userId,
            ':firstName' => $FirstName,
            ':lastName' => $LastName,
            ':email' => $email,
            ':phone' => $phone
        ]);

        respond(201, [
            'message' => 'Contact added successfully',
            'contactId' => $db->lastInsertId(),
            'firstName' => $FirstName,
            'lastName' => $LastName,
            'email' => $email,
            'phone' => $phone
        ]);

    } catch (PDOException $e) {

        respond(500, [
            'error' => 'Failed to add contact'
        ]);
    }
} else {

    respond(405, [
        'error' => 'Method not allowed'
    ]);
}