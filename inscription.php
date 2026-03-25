<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Badge.php';
require_once __DIR__ . '/includes/layout.php';

require_login();
$user = current_user();

$msg  = null;
$type = 'ok';

// Fetch the user's existing badge if any
$existingBadge = null;
$allUserBadges = Database::get()->prepare('SELECT * FROM badges WHERE user_id = ?');
$allUserBadges->execute([$user['id']]);
$userBadges = $allUserBadges->fetchAll();
if (count($userBadges) > 0) {
    $existingBadge = $userBadges[0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = strtoupper(trim($_POST['uid'] ?? ''));

    if ($existingBadge) {
        $msg  = 'Vous avez déjà un badge associé à votre compte.';
        $type = 'info';
    } elseif ($uid === '') {
        $msg  = 'L\'UID du badge est requis.';
        $type = 'err';
    } else {
        try {
            $badge = Badge::findByUid($uid);
            if ($badge) {
                if ($badge['user_id'] !== null) {
                    $msg  = 'Ce badge est déjà attribué à un autre compte.';
                    $type = 'err';
                } else {
                    Badge::updateUserId((int)$badge['id'], (int)$user['id']);
                    $msg  = 'Badge existant lié à votre compte avec succès.';
                    $type = 'ok';
                    $existingBadge = ['tag_uid' => $uid, 'autorise' => $badge['autorise']];
                }
            } else {
                $nomBadge = $user['prenom'] . ' ' . $user['nom'];
                Badge::create($uid, $nomBadge, (int)$user['id']);
                $msg  = 'Badge enregistré et associé à votre compte avec succès.';
                $type = 'ok';
                $existingBadge = ['tag_uid' => $uid, 'autorise' => 1];
            }
        } catch (Throwable) {
            $msg  = 'Erreur serveur. Réessayez.';
            $type = 'err';
        }
    }
}

render_header('Mon Badge', 'inscription.php');
?>

<div style="max-width:440px;margin:2rem auto">
    <h1>Mon Badge</h1>
    <p class="subtitle">Gérez le badge RFID associé à votre compte Smart Park.</p>

    <?php if ($msg): ?>
        <div class="alert <?= $type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if ($existingBadge): ?>
        <div class="surface" style="padding:20px;text-align:center">
            <h3>Badge Actuel</h3>
            <p>Le badge associé à votre compte est :</p>
            <div class="mono" style="font-size:1.5em;margin:15px 0;color:var(--primary-600)">
                <?= htmlspecialchars($existingBadge['tag_uid']) ?>
            </div>
            <p class="hint">Si vous avez perdu votre badge, contactez l'administrateur.</p>
        </div>
    <?php else: ?>
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
                <div class="field" style="margin-bottom:18px">
                    <label for="uid">UID du badge</label>
                    <input type="text" id="uid" name="uid"
                           placeholder="Ex. A1B2C3D4"
                           value="<?= htmlspecialchars($_POST['uid'] ?? '') ?>"
                           autocomplete="off" maxlength="32" required>
                    <span class="hint">Scan automatique ou saisie manuelle.</span>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                    Lier ce badge à mon compte
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php render_footer(); ?>
