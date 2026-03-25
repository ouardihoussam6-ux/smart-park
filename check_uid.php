<?php
declare(strict_types=1);

// Point d'entrée ESP32 : POST uid=XXXXXXXX → "OK", "REFUSE" ou "REGISTERED"
require_once __DIR__ . '/models/Badge.php';

header('Content-Type: text/plain; charset=utf-8');

$uid = strtoupper(trim($_POST['uid'] ?? ''));
if ($uid === '') { echo 'REFUSE'; exit; }

// Mode inscription actif → capturer l'UID pour la page web
$modeFile = __DIR__ . '/inscription_mode.txt';
if (trim((string) (file_get_contents($modeFile) ?: '0')) === '1') {
    file_put_contents($modeFile,                         '0');
    file_put_contents(__DIR__ . '/inscription_uid.txt',  $uid);
    echo 'REGISTERED';
    exit;
}

// Mode normal → vérifier l'autorisation
try {
    $badge = Badge::findByUid($uid);
    if ($badge && (int)$badge['autorise'] === 1) {
        // Renvoyer OK suivi du nom pour que l'ESP32 l'affiche
        echo 'OK|' . $badge['nom'];
    } else {
        echo 'REFUSE';
    }
} catch (Throwable) {
    echo 'REFUSE';
}
