<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Badge.php';
require_once __DIR__ . '/includes/layout.php';

$msg  = null;
$type = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        match ($action) {
            'toggle' => Badge::toggleAuth($id),
            'delete' => Badge::delete($id),
            'add' => (function () use (&$msg, &$type) {
                $uid = strtoupper(trim($_POST['uid'] ?? ''));
                $nom = trim($_POST['nom'] ?? '');
                if ($uid === '') { $msg = 'UID requis.'; $type = 'err'; return; }
                if (Badge::findByUid($uid)) { $msg = 'Badge déjà enregistré.'; $type = 'info'; return; }
                Badge::create($uid, $nom ?: 'Inconnu');
                $msg = 'Badge ajouté.';
            })(),
            default => null,
        };
        if ($action !== 'add') { header('Location: badges.php'); exit; }
    } catch (Throwable) {
        $msg = 'Erreur serveur.'; $type = 'err';
    }
}

try {
    $badges = Badge::all();
} catch (Throwable) {
    $badges = [];
    $msg = 'Impossible de charger les badges.'; $type = 'err';
}

render_header('Badges', 'badges.php');
?>

<h1>Badges</h1>
<p class="subtitle"><?= count($badges) ?> badge<?= count($badges) !== 1 ? 's' : '' ?> enregistré<?= count($badges) !== 1 ? 's' : '' ?>.</p>

<?php if ($msg): ?>
    <div class="alert <?= $type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="surface">
    <form method="post" class="add-row">
        <input type="hidden" name="action" value="add">
        <div class="field" style="margin:0;flex:1;min-width:130px">
            <label for="uid">UID</label>
            <input type="text" id="uid" name="uid" placeholder="A1B2C3D4" maxlength="32" required>
        </div>
        <div class="field" style="margin:0;flex:2;min-width:180px">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" placeholder="Nom du titulaire" maxlength="100">
        </div>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end">Ajouter</button>
    </form>

    <table>
        <thead>
            <tr><th>UID</th><th>Nom</th><th>Statut</th><th>Ajouté le</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (empty($badges)): ?>
                <tr><td colspan="5" class="empty">Aucun badge.</td></tr>
            <?php else: ?>
                <?php foreach ($badges as $b): ?>
                    <?php $dt = new DateTimeImmutable($b['created_at']); ?>
                    <tr>
                        <td data-label="UID"><span class="mono"><?= htmlspecialchars($b['tag_uid']) ?></span></td>
                        <td data-label="Nom"><?= htmlspecialchars($b['nom'] ?? '—') ?></td>
                        <td data-label="Statut">
                            <span class="auth <?= $b['autorise'] ? 'on' : 'off' ?>">
                                <?= $b['autorise'] ? 'Autorisé' : 'Bloqué' ?>
                            </span>
                        </td>
                        <td data-label="Ajouté" style="color:#888"><?= $dt->format('d/m/Y') ?></td>
                        <td style="display:flex;gap:6px;justify-content:flex-end">
                            <form method="post">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <button type="submit" class="btn btn-ghost btn-sm">
                                    <?= $b['autorise'] ? 'Bloquer' : 'Autoriser' ?>
                                </button>
                            </form>
                            <form method="post" onsubmit="return confirm('Supprimer ?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php render_footer(); ?>
