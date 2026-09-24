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
        !isset($body['searchTerm'])
    ) {

        respond(400, [
            'error' => 'Search term is required'
        ]);
    }

    $searchTerm = clean($body['searchTerm']);

    if (!$searchTerm) {

        respond(400, [
            'error' => 'Search term cannot be empty'
        ]);
    }

    $search = '%' . $searchTerm . '%';

    try {

        $stmt = $db->prepare(
            'SELECT ID as contactId, FirstName as firstName, LastName as lastName, Email as email, Phone as phone
             FROM Contacts
             WHERE UserID = :userId AND
                   (FirstName LIKE :searchFirstName OR
                    LastName LIKE :searchLastName OR
                    Email LIKE :searchEmail OR
                    Phone LIKE :searchPhone)'
        );

        $stmt->execute([
            ':userId' => $userId,
            ':searchFirstName' => $search,
            ':searchLastName' => $search,
            ':searchEmail' => $search,
            ':searchPhone' => $search
        ]);

        $contacts = $stmt->fetchAll();

        respond(200, [
            'contacts' => $contacts
        ]);

    } catch (PDOException $e) {

        respond(500, [
            'error' => 'Database error: '
        ]);
    }
} else {
    respond(405, ['error' => 'Method not allowed']);
}