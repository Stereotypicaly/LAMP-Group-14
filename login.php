<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

$method = $_SERVER['REQUEST_METHOD'];

$db = getDB();

if ($method === 'POST') {

    $body = getRequestBody();

    if (
        !isset($body['username']) ||
        !isset($body['password'])
    ) {
        respond(400, [
            'error' => 'Username and password are required'
        ]);
    }


    $username = clean($body['username']);

        // Password should not be cleaned.
        // Cleaning could change special characters.
    $password = $body['password'];

    if (!$username || !$password) {
        respond(400, [
            'error' => 'Username and password are required'
        ]);
    }

    try {

        $stmt = $db->prepare( //see if the user exists and get the password hash
            'SELECT ID AS userId, Password AS passwordHash, FirstName AS firstName, LastName AS lastName, IsAdmin AS isAdmin, IsEnabled AS isEnabled
             FROM Users
             WHERE Username = :username
             LIMIT 1'
        );

        $stmt->execute([
            ':username' => $username
        ]);

        $user = $stmt->fetch();

        if (!$user) {
            respond(401, [
                'error' => 'Invalid username or password'
            ]);
        }

        if (!password_verify($password, $user['passwordHash'])) {
            respond(401, [
                'error' => 'Invalid username or password'
            ]);
        }

        if (!$user['isEnabled']) {
            respond(403, [
                'error' => 'User account is disabled'
            ]);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['userId'] = $user['userId'];
        $_SESSION['isAdmin'] = $user['isAdmin'];

        respond(200, [
            'message' => 'Login successful',
            'userId' => $user['userId'],
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'isAdmin' => $user['isAdmin']
        ]);

    } catch (PDOException $e) {
        respond(500, [
            'error' => 'Database error'
        ]);

    }

} else {
    respond(405, [
        'error' => 'Method not allowed'
    ]);
}