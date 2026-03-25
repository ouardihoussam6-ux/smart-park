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

$user = current_user();
$userId = $user['id'];

$file = __DIR__ . '/../inscription_uid.txt';
$data = explode('|', trim((string)@file_get_contents($file)));

// Check if the file contains the UID and the locking user matches
if (count($data) >= 2 && (int)$data[1] === $userId) {
    $uid = $data[0];
    file_put_contents($file, '');
    echo json_encode(['uid' => $uid]);
} else {
    echo json_encode(['uid' => null]);
}
