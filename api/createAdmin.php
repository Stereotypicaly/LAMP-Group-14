<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

requireAdmin();
$body = getRequestBody();
$username = clean($body['username'] ?? '');
$password = $body['password'] ?? '';
$firstName = clean($body['firstName'] ?? '');
$lastName = clean($body['lastName'] ?? '');

if (!$username || !$password || !$firstName || !$lastName) {
    respond(400, ['error' => 'Username, password, first name, and last name are required']);
}

try {
    $db = getDB();
    $existing = $db->prepare('SELECT ID FROM Users WHERE Username = :username LIMIT 1');
    $existing->execute([':username' => $username]);
    if ($existing->fetch()) {
        respond(409, ['error' => 'Username already exists']);
    }

    $stmt = $db->prepare(
        'INSERT INTO Users (Username, Password, FirstName, LastName, IsAdmin, IsDisabled)
         VALUES (:username, :password, :firstName, :lastName, 1, 0)'
    );
    $stmt->execute([
        ':username' => $username,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':firstName' => $firstName,
        ':lastName' => $lastName
    ]);
    respond(201, ['message' => 'Admin user created successfully']);
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error']);
}
