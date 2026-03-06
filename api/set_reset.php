<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Place.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Remettre toutes les places à libre en BD
    Place::resetAll();

    // Envoyer l'ordre de reset à l'ESP32
    $ok = file_put_contents(__DIR__ . '/../reset_ordre.txt', '1') !== false;

    echo json_encode(['ok' => $ok]);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
