<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if (!isset($_SERVER['REQUEST_METHOD'])) {
    respond(400, ['error' => 'Invalid request method']);
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = requireAdmin();
$db = getDB();

if ($method == 'POST') {

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
            'SELECT
                Users.ID AS userId,
                Users.Username AS username,
                Users.FirstName AS userFirstName,
                Users.LastName AS userLastName,
                Users.IsEnabled AS isEnabled,
                Users.IsAdmin AS isAdmin,

                Contacts.ID AS contactId,
                Contacts.FirstName AS contactFirstName,
                Contacts.LastName AS contactLastName,
                Contacts.Email AS email,
                Contacts.Phone AS phone

             FROM Users

             LEFT JOIN Contacts
                ON Users.ID = Contacts.UserID

             WHERE
                Users.Username LIKE :searchUsername
                OR Users.FirstName LIKE :searchUserFirstName
                OR Users.LastName LIKE :searchUserLastName
                OR Contacts.FirstName LIKE :searchContactFirstName
                OR Contacts.LastName LIKE :searchContactLastName
                OR Contacts.Email LIKE :searchEmail
                OR Contacts.Phone LIKE :searchPhone'
        );

        $stmt->execute([
            ':searchUsername' => $search,
            ':searchUserFirstName' => $search,
            ':searchUserLastName' => $search,
            ':searchContactFirstName' => $search,
            ':searchContactLastName' => $search,
            ':searchEmail' => $search,
            ':searchPhone' => $search
        ]);

        $results = $stmt->fetchAll();

        respond(200, ['results' => $results]);
    } catch (PDOException $e) {
        respond(500, ['error' => 'Database error']);
    }
} else {
    respond(405, ['error' => 'Method not allowed']);
}