<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

// Require authentication to start badge scan
if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

file_put_contents(__DIR__ . '/../inscription_mode.txt', '1');
file_put_contents(__DIR__ . '/../inscription_uid.txt',  '');

echo json_encode(['ok' => true]);
//hello