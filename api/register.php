<?php

// api/register.php - register new user

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

$method = $_SERVER['REQUEST_METHOD'];




if ($method === 'POST') {

    $db = getDB();

    $body = getRequestBody();


    if (
        isset($body['username']) &&
        isset($body['password']) &&
        isset($body['firstName']) &&
        isset($body['lastName'])
    ) {

        $username = clean($body['username']);

        // Password should not be cleaned.
        // Cleaning could change special characters.
        $password = $body['password'];

        $firstName = clean($body['firstName']);

        $lastName = clean($body['lastName']);

        if (
            !$username ||
            !$password ||
            !$firstName ||
            !$lastName
        ) {

            respond(400, [
                'error' => 'All fields are required'
            ]);
        }


        // Check whether login already exists
        $stmt = $db->prepare(
            'SELECT ID
             FROM Users
             WHERE Username = :username
             LIMIT 1'
        );

        $stmt->execute([
            ':username' => $username
        ]);


        if ($stmt->fetch()) {

            respond(409, [
                'error' => 'Username already exists'
            ]);
        }


        // Hash password after validation
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        try {

            // Insert new user
            $stmt = $db->prepare(
                'INSERT INTO Users
                (Username, Password, FirstName, LastName)
                VALUES
                (:username, :pass, :firstName, :lastName)'
            );


            $stmt->execute([

                ':username' => $username,

                ':pass' => $passwordHash,

                ':firstName' => $firstName,

                ':lastName' => $lastName
            ]);


            respond(201, [

                'message' =>
                    'User registered successfully',

                'id' =>
                    (int) $db->lastInsertId()
            ]);

        } catch (PDOException $e) {

            respond(500, [
                'error' => 'Database error'
            ]);
        }

    } else {

        respond(400, [
            'error' => 'Missing required fields'
        ]);
    }

} else {

    respond(405, [
        'error' => 'Method not allowed'
    ]);
}
