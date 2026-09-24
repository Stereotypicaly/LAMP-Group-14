<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

$method = $_SERVER['REQUEST_METHOD'];

$db = getDB();

if ($method === 'POST') {

    $body = getRequestBody();

    if (
        isset($body['username']) &&
        isset($body['password'])
    ) {

        $username = clean($body['username']);

        // Password should not be cleaned.
        // Cleaning could change special characters.
        $password = $body['password'];

        if (!$username || !$password) {

            respond(400, [
                'error' => 'Username and password are required'
            ]);
        }

        // Check whether username exists
        $stmt = $db->prepare(
            'SELECT ID, Username, Password, FirstName, LastName, IsAdmin, IsDisabled
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

        if (!password_verify($password, $user['Password'])) {

            respond(401, [
                'error' => 'Invalid username or password'
            ]);
        }

        if ((int) $user['IsDisabled'] === 1) {

            respond(403, [
                'error' => 'This account has been disabled'
            ]);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['userId'] = (int) $user['ID'];
        $_SESSION['isAdmin'] = (int) $user['IsAdmin'] === 1;

        respond(200, [
    	    'id' => (int) $user['ID'],
    	    'username' => $user['Username'],
    	    'firstName' => $user['FirstName'],
            'lastName' => $user['LastName'],
            'isAdmin' => (int) $user['IsAdmin'] === 1,
            'message' => 'Login successful'
        ]);

    } else {

        respond(400, [
            'error' => 'Username and password are required'
        ]);
    }

} else {

    respond(405, [
        'error' => 'Method not allowed'
    ]);
}
