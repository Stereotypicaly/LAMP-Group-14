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

    $searchTerm = isset($body['searchTerm']) ? clean($body['searchTerm']) : '';

    $category = isset($body['category']) ? clean($body['category']) : '';

    $allowedCategories = ['Family', 'Friends', 'Work', 'School'];

    $hasFavoriteFilter = array_key_exists('favorite', $body);

    $favorite = null;

    if ($hasFavoriteFilter) {

        $favorite = filter_var(
            $body['favorite'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($favorite === null) {
            respond(400, [
                'error' => 'Invalid favorite value'
            ]);
        }
    }


    if ($category !== '' && !in_array($category, $allowedCategories, true)) {
        respond(400, [
            'error' => 'Invalid category'
        ]);
    }

    if ($searchTerm === '' && $category === '' && !$hasFavoriteFilter) {
        respond(400, [
            'error' => 'Search term or filter is required'
        ]);
    }

    $sortBy = isset($body['sortBy']) ? clean($body['sortBy']) : 'firstName';

    $sortOrder = isset($body['sortOrder']) ? strtoupper(clean($body['sortOrder'])) : 'ASC';

    $allowedSortBy = ['firstName' => 'FirstName', 'lastName' => 'LastName', 'dateAdded' => 'DateCreated'];

    if (!isset($allowedSortBy[$sortBy])) {
        respond(400, [
            'error' => 'Invalid sortBy value'
        ]);
    }

    if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
        respond(400, [
            'error' => 'Invalid sortOrder value'
        ]);
    }

    $sortColumn = $allowedSortBy[$sortBy];

    try {

        $sql = 'SELECT ID as contactId, FirstName as firstName, LastName as lastName, EmailAddress as emailAddress, Phone as phone, Category as category, Favorite as favorite, DateCreated as dateAdded, DateUpdated as dateUpdated
                FROM Contacts
                WHERE UserID = :userId';

        $params = [':userId' => $userId];

        if ($searchTerm !== '') {

            $search = '%' . $searchTerm . '%';

            $sql .= ' AND (FirstName LIKE :searchFirstName OR LastName LIKE :searchLastName OR EmailAddress LIKE :searchEmail OR Phone LIKE :searchPhone)';

            $params[':searchFirstName'] = $search;
            $params[':searchLastName'] = $search;
            $params[':searchEmail'] = $search;
            $params[':searchPhone'] = $search;
        }



        if ($category !== '') {
            $sql .= ' AND Category = :category';
            $params[':category'] = $category;
        }

        if ($hasFavoriteFilter) {
        $sql .= ' AND Favorite = :favorite';
        $params[':favorite'] = $favorite ? 1 : 0;
     }

        $sql .= " ORDER BY {$sortColumn} {$sortOrder}";

        $stmt = $db->prepare($sql);

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
