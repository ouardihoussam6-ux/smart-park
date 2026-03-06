<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Badge.php';
require_once __DIR__ . '/includes/layout.php';

$msg  = null;
$type = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = strtoupper(trim($_POST['uid'] ?? ''));
    $nom = trim($_POST['nom'] ?? '');

    if ($uid === '') {
        $msg  = 'L\'UID du badge est requis.';
        $type = 'err';
    } else {
        try {
            if (Badge::findByUid($uid)) {
                $msg  = 'Ce badge est déjà enregistré.';
                $type = 'info';
            } else {
                Badge::create($uid, $nom ?: 'Inconnu');
                $msg  = 'Badge enregistré avec succès.';
                $type = 'ok';
            }
        } catch (Throwable) {
            $msg  = 'Erreur serveur. Réessayez.';
            $type = 'err';
        }
    }
}

render_header('Inscrire un badge', 'inscription.php');
?>

<div style="max-width:440px;margin:0 auto">

    <h1>Inscrire un badge</h1>
    <p class="subtitle">Enregistrez votre badge RFID pour accéder au parking.</p>

    <?php if ($msg): ?>
        <div class="alert <?= $type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Zone de scan RFID (lecteur USB HID) -->
    <div class="scan-zone" id="scan-zone">
        <svg class="scan-icon" width="40" height="40" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
        </svg>
        <p class="scan-main" id="scan-main">Cliquez pour scanner un badge</p>
        <p class="scan-sub" id="scan-sub">Approchez le badge du lecteur après avoir cliqué</p>
    </div>

    <div class="surface" style="padding:20px">
        <form method="post" id="inscription-form" novalidate>
            <div class="field" style="margin-bottom:14px">
                <label for="uid">UID du badge</label>
                <input type="text" id="uid" name="uid"
                       placeholder="Ex. A1B2C3D4"
                       value="<?= htmlspecialchars($_POST['uid'] ?? '') ?>"
                       autocomplete="off" maxlength="32" required>
                <span class="hint">Scan automatique ou saisie manuelle.</span>
            </div>
            <div class="field" style="margin-bottom:18px">
                <label for="nom">Nom <span style="color:#aaa;font-weight:400">(optionnel)</span></label>
                <input type="text" id="nom" name="nom"
                       placeholder="Ex. Jean Dupont"
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                       maxlength="100">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                Inscrire le badge
            </button>
        </form>
    </div>

</div>

<?php render_footer(); ?>
