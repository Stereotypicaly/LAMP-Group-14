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
    $phoneSearchTerm = preg_replace('/\D/', '', $searchTerm);
    $phoneSearch = '%' . $phoneSearchTerm . '%';
    $stmt = getDB()->prepare(
        'SELECT ID, FirstName, LastName, EmailAddress, Phone, DateCreated, DateUpdated
         FROM Contacts
         WHERE UserID = :userId AND (
             FirstName LIKE :firstNameSearch OR LastName LIKE :lastNameSearch OR
             EmailAddress LIKE :emailSearch OR
             (:hasPhoneSearch = 1 AND Phone LIKE :phoneSearch)
         )
         ORDER BY LastName ASC, FirstName ASC, ID ASC'
    );
    $stmt->execute([
        ':userId' => $userId,
        ':firstNameSearch' => $search,
        ':lastNameSearch' => $search,
        ':emailSearch' => $search,
        ':hasPhoneSearch' => $phoneSearchTerm === '' ? 0 : 1,
        ':phoneSearch' => $phoneSearch
    ]);
    respond(200, ['contacts' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error']);
}
