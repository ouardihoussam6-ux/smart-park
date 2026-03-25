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

$user = current_user();
$userId = $user['id'];

$lockFile = __DIR__ . '/../inscription_mode.txt';
$lockData = explode('|', trim((string)@file_get_contents($lockFile)));

if (count($lockData) >= 2) {
    $lockUserId = (int)$lockData[0];
    $lockTime = (int)$lockData[1];
    
    // If locked recently by another user
    if ((time() - $lockTime) < 60 && $lockUserId !== $userId) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'Le lecteur est actuellement utilisé par un autre utilisateur.']);
        exit;
    }
}

file_put_contents($lockFile, $userId . '|' . time());
file_put_contents(__DIR__ . '/../inscription_uid.txt', '');

echo json_encode(['ok' => true]);