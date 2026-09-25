<?php

// utility functions for the API

function loadEnv($path = null)
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    if ($path === null) {
        $possiblePaths = [
            __DIR__ . '/../../.env',
            __DIR__ . '/../.env',
            __DIR__ . '/.env',
            (defined('ROOT_PATH') ? ROOT_PATH . '/.env' : null),
        ];

        foreach ($possiblePaths as $p) {
            if ($p && file_exists($p)) {
                $path = $p;
                break;
            }
        }
    }

    if ($path && file_exists($path)) {

        $lines = file(
            $path,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        foreach ($lines as $line) {

            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (strpos($line, '=') !== false) {

                list($name, $value) = explode('=', $line, 2);

                $name = trim($name);
                $value = trim($value);

                // Strip surrounding quotes
                if (
                    (str_starts_with($value, '"') &&
                     str_ends_with($value, '"'))
                    ||
                    (str_starts_with($value, "'") &&
                     str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                if (getenv($name) === false) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    $loaded = true;
}


// Automatically load environment variables
loadEnv();


/**
 * Sets standard CORS headers to allow cross-origin API requests.
 * Handles preflight OPTIONS requests.
 */
function setCORSHeaders()
{
    header("Access-Control-Allow-Origin: *");

    header(
        "Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"
    );

    header(
        "Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-Id"
    );

    if (
        isset($_SERVER['REQUEST_METHOD']) &&
        $_SERVER['REQUEST_METHOD'] === 'OPTIONS'
    ) {
        http_response_code(200);
        exit;
    }
}


/**
 * Sends JSON response and terminates execution.
 */
function respond($statusCode, $data)
{
    http_response_code($statusCode);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode($data);

    exit;
}


/**
 * Gets and decodes JSON request body.
 */
function getRequestBody()
{
    $rawInput = file_get_contents('php://input');

    if (!empty($rawInput)) {

        $decoded = json_decode($rawInput, true);

        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return $_POST ?? [];
}


/**
 * Cleans normal text input.
 */
function clean($data)
{
    if (is_string($data)) {
        return trim(strip_tags($data));
    }

    return $data;
}

/**
 * Converts a US phone number to its ten-digit storage format.
 */
function normalizePhoneNumber($phone)
{
    if (!is_string($phone)) {
        return null;
    }

    $phone = trim($phone);

    // Allow common phone punctuation, but reject letters and extensions.
    if ($phone === '' || !preg_match('/^\\+?[0-9\\s().-]+$/', $phone)) {
        return null;
    }

    $digits = preg_replace('/\\D/', '', $phone);

    // Accept an optional US country code, then store only ten digits.
    if (strlen($digits) === 11 && $digits[0] === '1') {
        $digits = substr($digits, 1);
    }

    return strlen($digits) === 10 ? $digits : null;
}


/**
 * Requires a logged-in PHP session and returns User ID.
 */
function requireAuth()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (
        !isset($_SESSION['userId']) ||
        !is_numeric($_SESSION['userId']) ||
        (int) $_SESSION['userId'] <= 0
    ) {
        respond(401, [
            'error' => 'Unauthorized'
        ]);
    }

    return (int) $_SESSION['userId'];
}

/**
 * Requires an authenticated account that has not been disabled since login.
 */
function requireActiveAuth()
{
    $userId = requireAuth();
    $db = getDB();

    $stmt = $db->prepare(
        'SELECT IsDisabled
         FROM Users
         WHERE ID = :userId
         LIMIT 1'
    );

    $stmt->execute([
        ':userId' => $userId
    ]);

    $user = $stmt->fetch();

    if (!$user || (int) $user['IsDisabled'] === 1) {
        $_SESSION = [];
        session_destroy();

        respond(403, [
            'error' => 'This account is no longer active'
        ]);
    }

    return $userId;
}

/**
 * Ensures only admins can perform certain actions. Returns User ID if admin.
 */
function requireAdmin()
{
    $userId = requireActiveAuth();

    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        respond(403, [
            'error' => 'Forbidden'
        ]);
    }

    return $userId;
}
