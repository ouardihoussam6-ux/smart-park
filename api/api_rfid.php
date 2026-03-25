<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Log.php';
require_once __DIR__ . '/../models/Place.php';

header('Content-Type: text/plain; charset=utf-8');

$tagId = strtoupper(trim($_POST['tag_id'] ?? ''));
$action = trim($_POST['source'] ?? '');
$slot   = (int) ($_POST['slot'] ?? 0);

if ($tagId === '' || $action === '') {
    http_response_code(400);
    echo 'ERR';
    exit;
}

try {
    Log::insert($tagId, $action, $slot);
    
    if ($action === 'boot') {
        Place::resetAll();
    } elseif ($slot >= 1 && $slot <= 3) {
        match ($action) {
            'slot_valide'  => Place::update($slot, 'occupee', $tagId),
            'slot_libere'  => Place::update($slot, 'libre', null),
            'slot_defaut'  => Place::update($slot, 'panne', null),
            default        => null,
        };
    }

    echo 'OK';
} catch (Throwable) {
    http_response_code(500);
    echo 'ERR';
}
