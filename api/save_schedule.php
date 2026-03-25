<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/Setting.php';

header('Content-Type: application/json; charset=utf-8');

// Only admin can change schedule
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$open     = trim($_POST['parking_open'] ?? '');
$close    = trim($_POST['parking_close'] ?? '');
$enabled  = isset($_POST['schedule_enabled']) ? $_POST['schedule_enabled'] : null;

try {
    if ($open !== '') {
        // Validate format HH:MM
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $open)) {
            echo json_encode(['ok' => false, 'error' => 'Format heure ouverture invalide (HH:MM).']);
            exit;
        }
        Setting::set('parking_open', $open);
    }

    if ($close !== '') {
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $close)) {
            echo json_encode(['ok' => false, 'error' => 'Format heure fermeture invalide (HH:MM).']);
            exit;
        }
        Setting::set('parking_close', $close);
    }

    if ($enabled !== null) {
        Setting::set('schedule_enabled', $enabled === '1' ? '1' : '0');
    }

    echo json_encode([
        'ok'               => true,
        'parking_open'     => Setting::openingHour(),
        'parking_close'    => Setting::closingHour(),
        'schedule_enabled' => Setting::isScheduleEnabled(),
        'is_open'          => Setting::isParkingOpen(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur.']);
}
