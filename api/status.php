<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/Place.php';
require_once __DIR__ . '/../models/Log.php';

header('Content-Type: application/json; charset=utf-8');

// Require authentication
if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

try {
    $response = [
        'places' => Place::all(),
        'stats'  => Place::stats(),
    ];

    // Only admins get the recent logs
    if (is_admin()) {
        $response['recent'] = Log::recent(10);
    }

    echo json_encode($response, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
