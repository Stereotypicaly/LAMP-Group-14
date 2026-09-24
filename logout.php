<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

if (!isset($_SERVER['REQUEST_METHOD'])) {
    respond(400, ['error' => 'Invalid request method']);
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = requireAuth();

if ($method !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

// Invalidate the user's session or token
// Implementation depends on your session management strategy

respond(200, ['message' => 'Logged out successfully']);