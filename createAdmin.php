<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if (!isset($_SERVER['REQUEST_METHOD'])) {
    respond(400, ['error' => 'Invalid request method']);
}

$method = $_SERVER['REQUEST_METHOD'];
requireAdmin();
$db = getDB();

if ($method === 'POST') {

    $body = getRequestBody();

    if (
        !isset($body['username']) ||
        !isset($body['password']) ||
        !isset($body['firstName']) ||
        !isset($body['lastName'])
    ) {
        respond(400, ['error' => 'Username, password, first name, and last name are required']);
    }

    $username = clean($body['username']);
    $password = $body['password']; // Password should not be cleaned
    $firstName = clean($body['firstName']);
    $lastName = clean($body['lastName']);

    if (!$username || !$password || !$firstName || !$lastName) {
        respond(400, ['error' => 'Username, password, first name, and last name cannot be empty']);
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check if the username already exists
        $stmt = $db->prepare(
            "SELECT ID FROM Users WHERE Username = :username LIMIT 1"
        );
        $stmt->execute([':username' => $username]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            respond(409, ['error' => 'Username already exists']);
        }

        // Insert the new admin user
        $stmt = $db->prepare(
            "INSERT INTO Users (Username, Password, FirstName, LastName, IsAdmin, IsEnabled) 
             VALUES (:username, :password, :firstName, :lastName, 1, 1)"
        );
        $stmt->execute([
            ':username' => $username,
            ':password' => $hashedPassword,
            ':firstName' => $firstName,
            ':lastName' => $lastName
        ]);

        respond(201, ['message' => 'Admin user created successfully']);
    } catch (PDOException $e) {
        respond(500, ['error' => 'Database error']);
    }
} else {
    respond(405, ['error' => 'Method not allowed']);
}