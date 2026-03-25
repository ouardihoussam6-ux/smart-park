<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';

header('Content-Type: text/plain; charset=utf-8');

// Only admin can clear reset orders
if (!is_admin()) {
    http_response_code(403);
    echo 'FORBIDDEN';
    exit;
}

$file = __DIR__ . '/../reset_ordre.txt';

if (file_put_contents($file, '0') !== false) {
    echo 'OK';
} else {
    http_response_code(500);
    echo 'ERR';
}
