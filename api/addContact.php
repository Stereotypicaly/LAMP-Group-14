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
        !isset($body['firstName']) ||
        !isset($body['lastName']) ||
        !isset($body['emailAddress']) ||
        !isset($body['phone'])
    ) {

        respond(400, [
            'error' => 'First name, last name, email, and phone are required'
        ]);
    }

    $firstName = clean($body['firstName']);
    $lastName = clean($body['lastName']);
    $emailAddress = clean($body['emailAddress']);
    $phoneInput = clean($body['phone']);
    $phone = normalizePhoneNumber($phoneInput);
    $category = isset($body['category']) ? clean($body['category']) : null;
    $category = $category === '' ? null : $category;
    $favorite = isset($body['favorite']) ? filter_var($body['favorite'], FILTER_VALIDATE_BOOLEAN) : false;

    if (!$firstName || !$lastName || !$emailAddress || !$phoneInput) {

        respond(400, [
            'error' => 'First name, last name, email, and phone cannot be empty'
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

        $stmt = $db->prepare(
            //has to be the same as the table name in the database and the column names have to match the database column names
            'INSERT INTO Contacts (UserID, FirstName, LastName, EmailAddress, Phone, Category, Favorite)
             VALUES (:userId, :firstName, :lastName, :email, :phone, :category, :favorite)'
        );

        $stmt->execute([
            ':userId' => $userId,
            ':firstName' => $firstName,
            ':lastName' => $lastName,
            ':email' => $emailAddress,
            ':phone' => $phone,
            ':category' => $category,
            ':favorite' => $favorite ? 1 : 0
        ]);

        respond(201, [
            'message' => 'Contact added successfully',
            'contactId' => $db->lastInsertId(),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'emailAddress' => $emailAddress,
            'phone' => $phone,
            'category' => $category,
            'favorite' => $favorite
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
