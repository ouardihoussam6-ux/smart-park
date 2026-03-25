<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/Place.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$user = current_user();
$action = $_POST['action'] ?? '';
$id_place = (int)($_POST['id_place'] ?? 0);

if ($id_place <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Place invalide.']);
    exit;
}

try {
    if ($action === 'reserve') {
        $success = Place::reserve($id_place, (int)$user['id']);
        if ($success) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Impossible de réserver cette place (déjà réservée ou vous avez déjà une réservation).']);
        }
    } elseif ($action === 'cancel') {
        Place::cancelReservation($id_place, (int)$user['id']);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Action inconnue.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur.']);
}
