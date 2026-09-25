<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, ['error' => 'Method not allowed']);
}

$adminId = requireAdmin();

try {
    $stmt = getDB()->query(
        'SELECT ID, FirstName, LastName, Username, DateCreated, DateUpdated, IsDisabled
         FROM Users
         ORDER BY LastName ASC, FirstName ASC, ID ASC'
    );

    respond(200, [
        'users' => $stmt->fetchAll(),
        'currentUserId' => $adminId
    ]);
} catch (PDOException $e) {
    respond(500, ['error' => 'Unable to load users']);
}
