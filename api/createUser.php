<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

requireAdmin();
$body = getRequestBody();

if (!is_string($body['username'] ?? null) || !is_string($body['password'] ?? null)
    || !is_string($body['firstName'] ?? null) || !is_string($body['lastName'] ?? null)) {
    respond(400, ['error' => 'Username, password, first name, and last name are required']);
}

$username = clean($body['username']);
$password = $body['password'];
$firstName = clean($body['firstName']);
$lastName = clean($body['lastName']);
$role = $body['role'] ?? 'user';

if (!$username || !$password || !$firstName || !$lastName) {
    respond(400, ['error' => 'Username, password, first name, and last name are required']);
}

if (strlen($username) > 50 || strlen($firstName) > 50 || strlen($lastName) > 50) {
    respond(400, ['error' => 'Names and username must be 50 characters or fewer']);
}

if (!in_array($role, ['user', 'admin'], true)) {
    respond(400, ['error' => 'Choose a valid user type']);
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
         VALUES (:username, :password, :firstName, :lastName, :isAdmin, 0)'
    );
    $stmt->execute([
        ':username' => $username,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':firstName' => $firstName,
        ':lastName' => $lastName,
        ':isAdmin' => $role === 'admin' ? 1 : 0
    ]);

    respond(201, ['message' => 'User created successfully', 'id' => (int) $db->lastInsertId()]);
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error']);
}
