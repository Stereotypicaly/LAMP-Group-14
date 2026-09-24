<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

requireAdmin();
$searchTerm = clean(getRequestBody()['searchTerm'] ?? '');

if (!$searchTerm) {
    respond(400, ['error' => 'Search term is required']);
}

try {
    $search = '%' . $searchTerm . '%';
    $stmt = getDB()->prepare(
        'SELECT Users.ID AS userId, Users.Username AS username,
                Users.FirstName AS userFirstName, Users.LastName AS userLastName,
                CASE WHEN Users.IsDisabled = 1 THEN 0 ELSE 1 END AS isEnabled,
                Users.IsAdmin AS isAdmin, Contacts.ID AS contactId,
                Contacts.FirstName AS contactFirstName, Contacts.LastName AS contactLastName,
                Contacts.EmailAddress AS email, Contacts.Phone AS phone
         FROM Users
         LEFT JOIN Contacts ON Users.ID = Contacts.UserID
         WHERE Users.Username LIKE :usernameSearch
            OR Users.FirstName LIKE :userFirstNameSearch
            OR Users.LastName LIKE :userLastNameSearch
            OR Contacts.FirstName LIKE :contactFirstNameSearch
            OR Contacts.LastName LIKE :contactLastNameSearch
            OR Contacts.EmailAddress LIKE :emailSearch
            OR Contacts.Phone LIKE :phoneSearch'
    );
    $stmt->execute([
        ':usernameSearch' => $search,
        ':userFirstNameSearch' => $search,
        ':userLastNameSearch' => $search,
        ':contactFirstNameSearch' => $search,
        ':contactLastNameSearch' => $search,
        ':emailSearch' => $search,
        ':phoneSearch' => $search
    ]);
    respond(200, ['results' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error']);
}
