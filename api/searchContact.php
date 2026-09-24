<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

$userId = requireActiveAuth();
$body = getRequestBody();
$searchTerm = clean($body['searchTerm'] ?? '');

if (!$searchTerm) {
    respond(400, ['error' => 'Search term is required']);
}

try {
    $search = '%' . $searchTerm . '%';
    $stmt = getDB()->prepare(
        'SELECT ID AS contactId, FirstName AS firstName, LastName AS lastName,
                EmailAddress AS email, Phone AS phone
         FROM Contacts
         WHERE UserID = :userId AND (
             FirstName LIKE :firstNameSearch OR LastName LIKE :lastNameSearch OR
             EmailAddress LIKE :emailSearch OR Phone LIKE :phoneSearch
         )'
    );
    $stmt->execute([
        ':userId' => $userId,
        ':firstNameSearch' => $search,
        ':lastNameSearch' => $search,
        ':emailSearch' => $search,
        ':phoneSearch' => $search
    ]);
    respond(200, ['contacts' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error']);
}
