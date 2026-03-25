<?php
declare(strict_types=1);

/**
 * Smart Park — Vérification post-déploiement
 * La base est créée par deploy.sh (sudo mysql).
 * Cette page vérifie juste que tout est opérationnel.
 */

require_once __DIR__ . '/includes/session.php';
require_admin();


$DB_HOST = 'localhost';
$DB_NAME = 'smart_park';
$DB_USER = 'admin';
$DB_PASS = 'admin';

$steps = [];
$ok    = true;

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $steps[] = ['ok', 'Connexion à la base de données <b>smart_park</b> réussie.'];

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['badges', 'places', 'logs'] as $t) {
        if (in_array($t, $tables)) {
            $steps[] = ['ok', "Table <b>$t</b> présente."];
        } else {
            $steps[] = ['err', "Table <b>$t</b> manquante — relancez <code>bash deploy.sh</code>."];
            $ok = false;
        }
    }

    $places = (int) $pdo->query("SELECT COUNT(*) FROM places")->fetchColumn();
    if ($places === 3) {
        $steps[] = ['ok', '3 places initialisées.'];
    } else {
        $steps[] = ['err', "$places place(s) trouvée(s), 3 attendues."];
        $ok = false;
    }

    $reset = __DIR__ . '/reset_ordre.txt';
    if (file_exists($reset) && is_writable($reset)) {
        $steps[] = ['ok', '<b>reset_ordre.txt</b> présent et accessible en écriture.'];
    } else {
        $steps[] = ['err', '<b>reset_ordre.txt</b> absent ou non inscriptible par www-data.'];
        $ok = false;
    }

} catch (PDOException $e) {
    $ok = false;
    $msg = $e->getMessage();

    if (str_contains($msg, 'Access denied')) {
        $steps[] = ['err', 'Accès refusé — l\'utilisateur <b>admin</b> n\'existe pas encore.'];
        $steps[] = ['info', 'Lancez <code>bash deploy.sh</code> depuis le dossier du projet sur le Pi.'];
    } elseif (str_contains($msg, "Can't connect") || str_contains($msg, 'Connection refused')) {
        $steps[] = ['err', 'MariaDB n\'est pas démarré.'];
        $steps[] = ['info', 'Lancez : <code>sudo systemctl start mariadb</code>'];
    } elseif (str_contains($msg, 'Unknown database')) {
        $steps[] = ['err', 'La base <b>smart_park</b> n\'existe pas encore.'];
        $steps[] = ['info', 'Lancez <code>bash deploy.sh</code> depuis le dossier du projet sur le Pi.'];
    } else {
        $steps[] = ['err', htmlspecialchars($msg)];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification — Smart Park</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav>
    <div class="nav-inner">
        <span class="brand">Smart Park</span>
    </div>
</nav>
<main style="max-width:580px">
    <div class="page-header">
        <h1>Vérification du système</h1>
        <p>Contrôle de l'installation après déploiement.</p>
    </div>

    <div class="card">
        <?php foreach ($steps as [$type, $msg]): ?>
            <div class="setup-line setup-<?= $type ?>">
                <?= match($type) { 'ok' => '✓', 'err' => '✗', default => '→' } ?>
                <?= $msg ?>
            </div>
        <?php endforeach; ?>

        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #eee">
            <?php if ($ok): ?>
                <p style="font-size:13px;color:#555;margin-bottom:12px">
                    Tout est opérationnel.
                </p>
                <a href="/index.php" class="btn btn-primary">Accéder au tableau de bord</a>
            <?php else: ?>
                <p style="font-size:13px;color:#888">
                    Corrigez les erreurs ci-dessus et rechargez cette page.
                </p>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
