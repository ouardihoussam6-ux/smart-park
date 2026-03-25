<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

// Require authentication to poll for badge UID
if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$file = __DIR__ . '/../inscription_uid.txt';
$uid  = trim((string) (file_get_contents($file) ?: ''));

if ($uid !== '') {
    file_put_contents($file, '');
    echo json_encode(['uid' => $uid]);
} else {
    echo json_encode(['uid' => null]);
}
