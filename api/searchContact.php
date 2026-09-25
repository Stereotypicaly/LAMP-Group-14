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

    $searchTerm = isset($body['searchTerm']) ? clean($body['searchTerm']) : '';

    $category = isset($body['category']) ? clean($body['category']) : '';

    $allowedCategories = ['Family', 'Friends', 'Work', 'School'];


    if ($category !== '' && !in_array($category, $allowedCategories, true)) {
        respond(400, [
            'error' => 'Invalid category'
        ]);
    }

    if ($searchTerm === '' && $category === '') {
        respond(400, [
            'error' => 'Search term or category is required'
        ]);
    }

    try {

        $sql = 'SELECT ID as contactId, FirstName as firstName, LastName as lastName, Email as email, Phone as phone, Category as category, Favorite as favorite
                FROM Contacts
                WHERE UserID = :userId';

        $params = [':userId' => $userId];

        if ($searchTerm !== '') {

            $search = '%' . $searchTerm . '%';

            $sql .= ' AND (FirstName LIKE :searchFirstName OR LastName LIKE :searchLastName OR Email LIKE :searchEmail OR Phone LIKE :searchPhone)';

            $params[':searchFirstName'] = $search;
            $params[':searchLastName'] = $search;
            $params[':searchEmail'] = $search;
            $params[':searchPhone'] = $search;
        }



        if ($category !== '') {
            $sql .= ' AND Category = :category';
            $params[':category'] = $category;
        }

        $stmt = $db->prepare(
            $sql
        );

        $stmt->execute($params);

        $contacts = $stmt->fetchAll();

        respond(200, [
            'contacts' => $contacts
        ]);

    } catch (PDOException $e) {

        respond(500, [
            'error' => 'Database error'
        ]);
    }
} else {
    respond(405, ['error' => 'Method not allowed']);
}
