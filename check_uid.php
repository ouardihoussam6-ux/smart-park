<?php
declare(strict_types=1);

// Point d'entrée ESP32 : POST uid=XXXXXXXX → "OK", "REFUSE", "FERME" ou "REGISTERED"
require_once __DIR__ . '/models/Badge.php';
require_once __DIR__ . '/models/Setting.php';

header('Content-Type: text/plain; charset=utf-8');

$uid = strtoupper(trim($_POST['uid'] ?? ''));
if ($uid === '') { echo 'REFUSE'; exit; }

// Mode inscription actif → capturer l'UID pour la page web
$modeFile = __DIR__ . '/inscription_mode.txt';
$lockData = explode('|', trim((string)@file_get_contents($modeFile)));

if (count($lockData) >= 2) {
    $lockUserId = (int)$lockData[0];
    $lockTime = (int)$lockData[1];
    
    // Si le verrou est encore valide (< 60s)
    if ((time() - $lockTime) < 60) {
        // Libérer le verrou
        file_put_contents($modeFile, '0|0');
        // Écrire l'UID accompagné de l'ID utilisateur
        file_put_contents(__DIR__ . '/inscription_uid.txt', $uid . '|' . $lockUserId);
        echo 'REGISTERED';
        exit;
    }
}

// Mode normal → vérifier l'autorisation
try {
    $badge = Badge::findByUid($uid);
    if ($badge && (int)$badge['autorise'] === 1) {
        require_once __DIR__ . '/models/Place.php';
        Place::freeExpiredReservations();

        $nom = $badge['nom'];
        $userId = $badge['user_id'];

        // 1. Déjà garé ?
        $st = Database::get()->prepare("SELECT id_place FROM places WHERE uid_actuel = ? AND etat = 'occupee' LIMIT 1");
        $st->execute([$uid]);
        $parked = $st->fetch();
        if ($parked) {
            echo "OK|$nom|RELEASE|" . $parked['id_place'];
            exit;
        }

        // Vérifier la plage horaire d'accès UNIQUEMENT pour les entrées
        try {
            if (!Setting::isParkingOpen()) {
                echo 'FERME|' . Setting::openingHour() . '-' . Setting::closingHour();
                exit;
            }
        } catch (Throwable) {
            // En cas d'erreur de lecture des paramètres, on laisse passer
        }

        // 2. A une réservation ?
        if ($userId) {
            $st = Database::get()->prepare("SELECT id_place FROM places WHERE reserve_par = ? AND etat = 'reservee' LIMIT 1");
            $st->execute([$userId]);
            $reserved = $st->fetch();
            if ($reserved) {
                echo "OK|$nom|ENTER|" . $reserved['id_place'];
                exit;
            }
        }

        // 3. Sinon, trouver une place libre
        $st = Database::get()->prepare("SELECT id_place FROM places WHERE etat = 'libre' ORDER BY id_place ASC LIMIT 1");
        $st->execute();
        $libre = $st->fetch();
        if ($libre) {
            echo "OK|$nom|ENTER|" . $libre['id_place'];
            exit;
        }

        // 4. Complet
        echo "OK|$nom|FULL|0";
    } else {
        echo 'REFUSE';
    }
} catch (Throwable $e) {
    echo 'REFUSE';
}
