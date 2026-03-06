<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Place.php';
require_once __DIR__ . '/models/Log.php';
require_once __DIR__ . '/includes/layout.php';

$actionLabel = [
    'lecture'          => 'Lecture',
    'proposition_slot' => 'Proposition',
    'slot_valide'      => 'Validée',
    'slot_libere'      => 'Libérée',
    'slot_defaut'      => 'Défaut',
];

try {
    $places = Place::all();
    $stats  = Place::stats();
    $logs   = Log::recent(15);
    $error  = null;
} catch (Throwable) {
    $places = [];
    $stats  = ['libre' => 0, 'occupee' => 0, 'panne' => 0];
    $logs   = [];
    $error  = 'Impossible de se connecter à la base de données.';
}

render_header('Tableau de bord', 'index.php');
?>

<h1>Tableau de bord</h1>
<p class="subtitle">État du parking — actualisation toutes les 5 secondes.</p>

<?php if ($error): ?>
    <div class="alert err"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stats" id="stats-row">
    <span data-stat="libre">
        <span class="dot" style="background:#22c55e"></span>
        <strong class="count"><?= $stats['libre'] ?></strong> libre<?= $stats['libre'] !== 1 ? 's' : '' ?>
    </span>
    <span data-stat="occupee">
        <span class="dot" style="background:#ef4444"></span>
        <strong class="count"><?= $stats['occupee'] ?></strong> occupée<?= $stats['occupee'] !== 1 ? 's' : '' ?>
    </span>
    <span data-stat="panne">
        <span class="dot" style="background:#f59e0b"></span>
        <strong class="count"><?= $stats['panne'] ?></strong> en panne
    </span>
</div>

<div class="places" id="places-grid">
    <?php foreach ($places as $p): ?>
        <?php $etat = $p['etat'] ?? 'libre'; ?>
        <div class="place <?= htmlspecialchars($etat) ?>" data-place="<?= (int) $p['id_place'] ?>">
            <div class="place-name">Place <?= (int) $p['id_place'] ?></div>
            <span class="place-chip <?= htmlspecialchars($etat) ?>">
                <span class="dot" style="background:<?= $etat === 'libre' ? '#22c55e' : ($etat === 'occupee' ? '#ef4444' : '#f59e0b') ?>"></span>
                <span class="label"><?= ['libre' => 'Libre', 'occupee' => 'Occupée', 'panne' => 'En panne'][$etat] ?? $etat ?></span>
            </span>
            <div class="place-uid"><?= htmlspecialchars($p['uid_actuel'] ?? '') ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($places)): ?>
        <div class="empty" style="grid-column:1/-1">Aucune place. Lancez setup.php.</div>
    <?php endif; ?>
</div>

<p class="label-section">Activité récente</p>
<div class="surface">
    <table>
        <thead>
            <tr><th>Date</th><th>Badge</th><th>Action</th><th>Place</th></tr>
        </thead>
        <tbody id="logs-body">
            <?php if (empty($logs)): ?>
                <tr><td colspan="4" class="empty">Aucune activité.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <?php $dt = new DateTimeImmutable($log['date_heure']); ?>
                    <tr>
                        <td data-label="Date" style="color:#888"><?= $dt->format('d/m H:i') ?></td>
                        <td data-label="Badge"><span class="mono"><?= htmlspecialchars($log['tag_id']) ?></span></td>
                        <td data-label="Action">
                            <span class="chip <?= htmlspecialchars($log['action']) ?>">
                                <?= htmlspecialchars($actionLabel[$log['action']] ?? $log['action']) ?>
                            </span>
                        </td>
                        <td data-label="Place">
                            <span class="num <?= $log['slot'] == 0 ? 'empty' : '' ?>">
                                <?= $log['slot'] > 0 ? (int) $log['slot'] : '—' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="reset-bar">
    <div>
        <h3>Reset distant ESP32</h3>
        <p>Envoie un ordre de redémarrage pris en compte dans les 3 secondes.</p>
    </div>
    <button id="btn-reset" class="btn btn-warn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
        </svg>
        Redémarrer l'ESP32
    </button>
</div>

<?php render_footer(); ?>
